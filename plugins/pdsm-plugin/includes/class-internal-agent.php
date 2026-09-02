<?php
/**
 * Classe PDSM_Internal_Agent
 * Agente autônomo especialista em WordPress, com aprendizado contínuo.
 */
class PDSM_Internal_Agent {

    private $site_manager;
    private $crypto;
    private $job_manager;
    private $table_knowledge;

    public function __construct($site_manager, $crypto, $job_manager) {
        $this->site_manager   = $site_manager;
        $this->crypto         = $crypto;
        $this->job_manager    = $job_manager;
        global $wpdb;
        $this->table_knowledge = $wpdb->prefix . 'pdsm_agent_knowledge';
    }

    /**
     * Inicializa hooks: cron diário para auto-diagnóstico e endpoints REST.
     */
    public function init() {
        add_action('pdsm_agent_daily_health_check', [$this, 'auto_diagnose_all_sites']);
        add_action('rest_api_init', [$this, 'register_agent_routes']);
    }

    // -------------------------------------------------------------------------
    // 1. DIAGNÓSTICO
    // -------------------------------------------------------------------------

    /**
     * Diagnostica um site remoto (Client) via REST.
     * Retorna array com sintomas encontrados.
     */
    public function diagnose_site($domain) {
        $site = $this->site_manager->get_site($domain);
        if (!$site) {
            return ['error' => 'Site não encontrado'];
        }

        // Coleta logs, status do WordPress, plugins ativos, etc.
        $remote_data = $this->fetch_remote_diagnosis($site);
        if (is_wp_error($remote_data)) {
            return ['error' => $remote_data->get_error_message()];
        }

        $symptoms = $this->analyze_symptoms($remote_data);
        return [
            'domain'   => $domain,
            'status'   => $remote_data['status'] ?? 'unknown',
            'symptoms' => $symptoms
        ];
    }

    /**
     * Faz requisição para o endpoint de diagnóstico no Client.
     */
    private function fetch_remote_diagnosis($site) {
        $domain = $site['domain'];
        $secret = $site['api_key'];
        $timestamp = time();
        $nonce = bin2hex(random_bytes(16));
        $uri = '/wp-json/pdsm/v2/client/diagnose';
        $body = json_encode(['action' => 'diagnose']);
        $signature = $this->crypto->generate_signature($uri, $body, $timestamp, $nonce, $secret);

        $response = wp_remote_post("https://{$domain}{$uri}", [
            'headers' => [
                'X-PDSM-Timestamp' => $timestamp,
                'X-PDSM-Nonce'     => $nonce,
                'X-PDSM-Signature' => $signature,
                'Content-Type'     => 'application/json'
            ],
            'body'    => $body,
            'timeout' => 30
        ]);

        if (is_wp_error($response)) {
            return $response;
        }
        return json_decode(wp_remote_retrieve_body($response), true);
    }

    /**
     * Analisa os dados brutos e extrai sintomas padronizados.
     */
    private function analyze_symptoms($data) {
        $symptoms = [];

        // Verifica se o site está offline
        if (empty($data['wp_version'])) {
            $symptoms[] = 'site_offline';
        }

        // Verifica erros fatais no log
        if (!empty($data['debug_log'])) {
            if (strpos($data['debug_log'], 'Fatal error') !== false) {
                $symptoms[] = 'fatal_error_detected';
            }
            if (strpos($data['debug_log'], 'Allowed memory size') !== false) {
                $symptoms[] = 'memory_limit_exceeded';
            }
            if (strpos($data['debug_log'], 'Database connection') !== false) {
                $symptoms[] = 'db_connection_error';
            }
        }

        // Verifica plugins com problemas
        if (!empty($data['active_plugins'])) {
            foreach ($data['active_plugins'] as $plugin) {
                if (isset($plugin['error']) && $plugin['error'] === true) {
                    $symptoms[] = 'plugin_error_' . sanitize_title($plugin['slug']);
                }
            }
        }

        // Verifica integridade do .htaccess
        if (isset($data['htaccess_exists']) && $data['htaccess_exists'] === false) {
            $symptoms[] = 'htaccess_missing';
        }

        return array_unique($symptoms);
    }

    // -------------------------------------------------------------------------
    // 2. RESOLUÇÃO COM APRENDIZADO
    // -------------------------------------------------------------------------

    /**
     * Tenta resolver os sintomas de um site, usando a base de conhecimento.
     * Se encontrar uma solução com confiança > 80%, aplica automaticamente.
     * Caso contrário, retorna uma sugestão para aprovação manual.
     */
    public function resolve_issues($domain, $auto_heal = true) {
        $diagnosis = $this->diagnose_site($domain);
        if (isset($diagnosis['error'])) {
            return $diagnosis;
        }

        $results = [];
        foreach ($diagnosis['symptoms'] as $symptom) {
            $solution = $this->get_best_solution($symptom);

            if ($solution && $solution['confidence'] > 80 && $auto_heal) {
                // Aplica automaticamente
                $action_result = $this->execute_action($domain, $solution);
                $success = ($action_result['success'] ?? false);

                // Atualiza a base de conhecimento
                $this->update_knowledge($symptom, $solution['action'], $solution['params'], $success);

                $results[] = [
                    'symptom'   => $symptom,
                    'action'    => $solution['action'],
                    'auto'      => true,
                    'success'   => $success,
                    'detail'    => $action_result
                ];

            } elseif ($solution) {
                // Sugere, mas não executa (confiança baixa)
                $results[] = [
                    'symptom'    => $symptom,
                    'suggested'  => $solution['action'],
                    'params'     => $solution['params'],
                    'confidence' => $solution['confidence'],
                    'auto'       => false,
                    'message'    => 'Confiança baixa. Aguardando aprovação.'
                ];
            } else {
                // Nenhuma solução conhecida
                $results[] = [
                    'symptom' => $symptom,
                    'error'   => 'Nenhuma solução conhecida na base.'
                ];
            }
        }

        return [
            'domain'  => $domain,
            'results' => $results
        ];
    }

    /**
     * Busca a melhor solução na base de conhecimento para um sintoma.
     */
    private function get_best_solution($symptom) {
        global $wpdb;
        $table = $this->table_knowledge;

        $symptom_hash = hash('sha256', $symptom);
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} 
             WHERE symptom_hash = %s 
               AND (success_count + failure_count) > 0 
             ORDER BY (success_count / (success_count + failure_count)) DESC, success_count DESC 
             LIMIT 1",
            $symptom_hash
        ));

        if (!$row) {
            return null;
        }

        $total = $row->success_count + $row->failure_count;
        $confidence = ($total > 0) ? ($row->success_count / $total) * 100 : 0;

        return [
            'action'     => $row->suggested_action,
            'params'     => json_decode($row->action_params, true),
            'confidence' => $confidence
        ];
    }

    /**
     * Executa uma ação corretiva no site remoto.
     */
    private function execute_action($domain, $solution) {
        $site = $this->site_manager->get_site($domain);
        if (!$site) {
            return ['success' => false, 'error' => 'Site não encontrado'];
        }

        $action = $solution['action'];
        $params = $solution['params'];

        // Mapeia ações para endpoints do Client
        $command = [
            'action' => $action,
            'params' => $params
        ];

        $secret = $site['api_key'];
        $timestamp = time();
        $nonce = bin2hex(random_bytes(16));
        $uri = '/wp-json/pdsm/v2/client/action';
        $body = json_encode($command);
        $signature = $this->crypto->generate_signature($uri, $body, $timestamp, $nonce, $secret);

        $response = wp_remote_post("https://{$domain}{$uri}", [
            'headers' => [
                'X-PDSM-Timestamp' => $timestamp,
                'X-PDSM-Nonce'     => $nonce,
                'X-PDSM-Signature' => $signature,
                'Content-Type'     => 'application/json'
            ],
            'body'    => $body,
            'timeout' => 60
        ]);

        if (is_wp_error($response)) {
            return ['success' => false, 'error' => $response->get_error_message()];
        }
        return json_decode(wp_remote_retrieve_body($response), true);
    }

    /**
     * Atualiza a base de conhecimento com o resultado de uma ação.
     */
    private function update_knowledge($symptom, $action, $params, $success) {
        global $wpdb;
        $table = $this->table_knowledge;

        $symptom_hash = hash('sha256', $symptom);
        $params_json = json_encode($params);

        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id, success_count, failure_count FROM {$table} 
             WHERE symptom_hash = %s AND suggested_action = %s AND action_params = %s",
            $symptom_hash, $action, $params_json
        ));

        if ($existing) {
            if ($success) {
                $wpdb->update($table, ['success_count' => $existing->success_count + 1], ['id' => $existing->id]);
            } else {
                $wpdb->update($table, ['failure_count' => $existing->failure_count + 1], ['id' => $existing->id]);
            }
        } else {
            $wpdb->insert($table, [
                'symptom_hash'    => $symptom_hash,
                'symptom_description' => $symptom,
                'suggested_action' => $action,
                'action_params'   => $params_json,
                'success_count'   => $success ? 1 : 0,
                'failure_count'   => $success ? 0 : 1,
                'last_used'       => current_time('mysql')
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // 3. AUTO-DIAGNÓSTICO AGENDADO (CRON)
    // -------------------------------------------------------------------------

    /**
     * Executado via WP-Cron diariamente.
     * Verifica todos os sites e resolve problemas críticos automaticamente.
     */
    public function auto_diagnose_all_sites() {
        $sites = $this->site_manager->get_sites();
        $reports = [];

        foreach ($sites as $site) {
            $report = $this->resolve_issues($site['domain'], true);
            $reports[$site['domain']] = $report;

            // Se houve alguma ação automática, registra em log
            if (!empty($report['results'])) {
                error_log('[PDSM Agent] Auto-heal executado em ' . $site['domain']);
            }
        }

        update_option('pdsm_agent_last_auto_heal', [
            'time'   => current_time('mysql'),
            'reports' => $reports
        ]);

        return $reports;
    }

    // -------------------------------------------------------------------------
    // 4. ENDPOINTS REST PARA O AGENTE
    // -------------------------------------------------------------------------

    public function register_agent_routes() {
        register_rest_route(PDSM_API_NAMESPACE, '/agent/diagnose', [
            'methods'             => 'POST',
            'callback'            => [$this, 'api_diagnose'],
            'permission_callback' => [$this, 'api_permission']
        ]);

        register_rest_route(PDSM_API_NAMESPACE, '/agent/heal', [
            'methods'             => 'POST',
            'callback'            => [$this, 'api_heal'],
            'permission_callback' => [$this, 'api_permission']
        ]);

        register_rest_route(PDSM_API_NAMESPACE, '/agent/knowledge', [
            'methods'             => 'GET',
            'callback'            => [$this, 'api_get_knowledge'],
            'permission_callback' => [$this, 'api_permission']
        ]);
    }

    public function api_permission($request) {
        // Apenas administradores ou credenciais com escopo 'agent'
        return current_user_can('manage_options');
    }

    public function api_diagnose($request) {
        $domain = $request->get_param('domain');
        if (!$domain) {
            return new WP_Error('missing_domain', 'Domínio obrigatório');
        }
        $result = $this->diagnose_site($domain);
        return rest_ensure_response($result);
    }

    public function api_heal($request) {
        $domain = $request->get_param('domain');
        $auto = $request->get_param('auto') !== false;
        if (!$domain) {
            return new WP_Error('missing_domain', 'Domínio obrigatório');
        }
        $result = $this->resolve_issues($domain, $auto);
        return rest_ensure_response($result);
    }

    public function api_get_knowledge() {
        global $wpdb;
        $table = $this->table_knowledge;
        $rows = $wpdb->get_results("SELECT * FROM {$table} ORDER BY (success_count/(success_count+failure_count+1)) DESC LIMIT 50");
        return rest_ensure_response($rows);
    }

    // -------------------------------------------------------------------------
    // 5. CRIAÇÃO DA TABELA NO ACTIVATE
    // -------------------------------------------------------------------------

    public static function create_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'pdsm_agent_knowledge';
        $charset = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            symptom_hash VARCHAR(64) NOT NULL,
            symptom_description TEXT NOT NULL,
            suggested_action VARCHAR(100) NOT NULL,
            action_params LONGTEXT NOT NULL,
            success_count INT UNSIGNED DEFAULT 0,
            failure_count INT UNSIGNED DEFAULT 0,
            last_used DATETIME DEFAULT CURRENT_TIMESTAMP,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY symptom_action (symptom_hash, suggested_action, action_params(100)),
            KEY confidence (success_count, failure_count)
        ) $charset;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
