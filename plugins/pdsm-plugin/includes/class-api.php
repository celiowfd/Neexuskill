<?php
class PDSM_API {

    private $site_manager, $job_manager, $updater, $installer, $crypto, $internal_agent;

    public function __construct($sm, $jm, $up, $ins, $cry, $agent) {
        $this->site_manager = $sm;
        $this->job_manager = $jm;
        $this->updater = $up;
        $this->installer = $ins;
        $this->crypto = $cry;
        $this->internal_agent = $agent;
    }

    public function init() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes() {
        // Jobs
        register_rest_route(PDSM_API_NAMESPACE, '/jobs', [
            'methods'             => 'POST',
            'callback'            => [$this, 'create_job_endpoint'],
            'permission_callback' => [$this, 'auth_hmac_rbac']
        ]);

        register_rest_route(PDSM_API_NAMESPACE, '/jobs/(?P<id>\d+)', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_job_status'],
            'permission_callback' => [$this, 'auth_hmac_rbac']
        ]);

        // Sites
        register_rest_route(PDSM_API_NAMESPACE, '/sites', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_sites'],
            'permission_callback' => [$this, 'auth_hmac_rbac']
        ]);

        register_rest_route(PDSM_API_NAMESPACE, '/sites', [
            'methods'             => 'POST',
            'callback'            => [$this, 'add_site'],
            'permission_callback' => [$this, 'auth_hmac_rbac']
        ]);

        // Agente
        register_rest_route(PDSM_API_NAMESPACE, '/agent/diagnose', [
            'methods'             => 'POST',
            'callback'            => [$this, 'api_diagnose'],
            'permission_callback' => [$this, 'auth_hmac_rbac']
        ]);

        register_rest_route(PDSM_API_NAMESPACE, '/agent/heal', [
            'methods'             => 'POST',
            'callback'            => [$this, 'api_heal'],
            'permission_callback' => [$this, 'auth_hmac_rbac']
        ]);

        register_rest_route(PDSM_API_NAMESPACE, '/agent/knowledge', [
            'methods'             => 'GET',
            'callback'            => [$this, 'api_get_knowledge'],
            'permission_callback' => [$this, 'auth_hmac_rbac']
        ]);
    }

    public function auth_hmac_rbac($request) {
        $api_key = $request->get_header('X-API-Key');
        if (!$api_key) {
            return false;
        }

        $site = $this->site_manager->get_site_by_api_key($api_key);
        if (!$site) {
            return false;
        }

        // HMAC
        if (!$this->crypto->verify_hmac($request, $site['secret'])) {
            return false;
        }

        // Rate Limiting
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $rate_key = 'pdsm_rate_' . md5($ip . $api_key);
        $count = get_transient($rate_key) ?: 0;
        if ($count > 10) {
            return false;
        }
        set_transient($rate_key, $count + 1, 60);

        // RBAC Efetivo
        $route = $request->get_route();
        $method = $request->get_method();
        
        // Verifica as permissões reais na array do site (Mock de capabilities)
        $capabilities = $site['capabilities'] ?? ['read', 'write', 'diagnose', 'heal'];
        
        if (strpos($route, '/agent/diagnose') !== false && !in_array('diagnose', $capabilities)) {
            return false; // Proibido diagnosticar
        }
        if (strpos($route, '/agent/heal') !== false && !in_array('heal', $capabilities)) {
            return false; // Proibido curar
        }
        if (strpos($route, '/jobs') !== false && $method === 'POST' && !in_array('write', $capabilities)) {
            return false; // Proibido criar jobs
        }

        return true;
    }

    public function create_job_endpoint($request) {
        $action = $request->get_param('action');
        $payload = $request->get_param('payload');

        $job_id = $this->job_manager->create_job($action, $payload, get_current_user_id());
        return rest_ensure_response(['job_id' => $job_id, 'status' => 'pending']);
    }

    public function get_job_status($request) {
        $id = $request->get_param('id');
        $status = $this->job_manager->get_status($id);
        if (!$status) {
            return new WP_Error('not_found', 'Job não encontrado', ['status' => 404]);
        }
        return rest_ensure_response($status);
    }

    public function get_sites() {
        return rest_ensure_response($this->site_manager->get_sites());
    }

    public function add_site($request) {
        $domain = $request->get_param('domain');
        $api_key = $request->get_param('api_key');
        $label = $request->get_param('label');

        $result = $this->site_manager->add_site($domain, $api_key, $label);
        if (is_wp_error($result)) {
            return $result;
        }
        return rest_ensure_response(['message' => 'Site adicionado com sucesso']);
    }

    public function api_diagnose($request) {
        $domain = $request->get_param('domain');
        if (!$domain) {
            return new WP_Error('missing_domain', 'Domínio obrigatório');
        }
        $result = $this->internal_agent->diagnose_site($domain);
        return rest_ensure_response($result);
    }

    public function api_heal($request) {
        $domain = $request->get_param('domain');
        $auto = $request->get_param('auto') !== false;
        if (!$domain) {
            return new WP_Error('missing_domain', 'Domínio obrigatório');
        }
        $result = $this->internal_agent->resolve_issues($domain, $auto);
        return rest_ensure_response($result);
    }

    public function api_get_knowledge() {
        global $wpdb;
        $table = $wpdb->prefix . 'pdsm_agent_knowledge';
        $rows = $wpdb->get_results("SELECT * FROM {$table} ORDER BY (success_count/(success_count+failure_count+1)) DESC LIMIT 50");
        return rest_ensure_response($rows);
    }
}
