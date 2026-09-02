<?php

if (!defined('ABSPATH')) {
    exit;
}

class PDSM_Client_API {

    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes() {
        // Endpoint para receber atualização com rollback
        register_rest_route('pdsm/v2/client', '/update', [
            'methods' => 'POST',
            'callback' => [$this, 'process_update']
        ]);

        register_rest_route('pdsm/v2/client', '/diagnose', [
            'methods'             => 'POST',
            'callback'            => [$this, 'diagnose'],
            // 'permission_callback' => 'pdsm_client_auth_hmac' // TODO: RBAC
        ]);

        register_rest_route('pdsm/v2/client', '/action', [
            'methods'             => 'POST',
            'callback'            => [$this, 'execute_action'],
            // 'permission_callback' => 'pdsm_client_auth_hmac' // TODO: RBAC
        ]);
    }

    public function process_update($request) {
        $secret = get_option('pdsm_client_secret');
        if (!$secret) return new WP_Error('no_secret', 'Chave não configurada');

        $crypto = new PDSM_Crypto();
        if (!$crypto->verify_hmac($request, $secret)) {
            return new WP_Error('hmac_fail', 'Assinatura inválida');
        }

        $slug = sanitize_text_field($request->get_param('slug'));
        $package_url = esc_url_raw($request->get_param('package_url'));
        $sha = sanitize_text_field($request->get_param('sha'));

        // 1. Backup
        $backup_path = WP_CONTENT_DIR . "/pdsm-backups/{$slug}_" . time();
        if (!copy_dir(WP_PLUGIN_DIR . "/$slug", $backup_path)) {
            return ['error' => 'Falha no backup'];
        }

        // 2. Baixa e valida
        $tmp = download_url($package_url);
        if (!$crypto->validate_sha256($tmp, $sha)) {
            return ['error' => 'SHA inválido'];
        }

        // 3. Descompacta (com proteção Zip Slip)
        $zip = new ZipArchive();
        if ($zip->open($tmp) !== true) return ['error' => 'ZIP inválido'];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);
            $safe = $crypto->sanitize_zip_entry($entry, WP_PLUGIN_DIR);
            if (!$safe) {
                $zip->close();
                return ['error' => 'Zip Slip detectado'];
            }
        }
        $zip->extractTo(WP_PLUGIN_DIR);
        $zip->close();

        // 4. Health Check
        if (!$this->perform_health_check()) {
            // Rollback
            copy_dir($backup_path, WP_PLUGIN_DIR . "/$slug");
            return ['status' => 'rollback', 'reason' => 'Health Check falhou'];
        }

        return ['status' => 'success', 'version' => 'nova'];
    }

    private function perform_health_check() {
        $response = wp_remote_get(home_url(), ['timeout' => 15]);
        if (is_wp_error($response)) return false;
        $code = wp_remote_retrieve_response_code($response);
        return ($code >= 200 && $code < 300);
    }

    public function diagnose($request) {
        $data = [
            'wp_version'      => get_bloginfo('version'),
            'php_version'     => phpversion(),
            'active_plugins'  => [],
            'debug_log'       => '',
            'htaccess_exists' => file_exists(ABSPATH . '.htaccess'),
            'status'          => 'online'
        ];

        // Coleta plugins ativos
        foreach (get_plugins() as $path => $plugin) {
            if (is_plugin_active($path)) {
                $data['active_plugins'][] = [
                    'slug'  => dirname($path),
                    'name'  => $plugin['Name'],
                    'error' => false
                ];
            }
        }

        // Coleta últimas linhas do debug.log
        $log_file = WP_CONTENT_DIR . '/debug.log';
        if (file_exists($log_file)) {
            $data['debug_log'] = file_get_contents($log_file, false, null, -5000);
        }

        return rest_ensure_response($data);
    }

    public function execute_action($request) {
        $action = $request->get_param('action');
        $params = $request->get_param('params');

        switch ($action) {
            case 'deactivate_plugin':
                $slug = $params['slug'] ?? '';
                if (!$slug) return ['success' => false, 'error' => 'Slug não informado'];
                $plugin_path = $slug . '/' . $slug . '.php';
                if (!is_plugin_active($plugin_path)) {
                    return ['success' => false, 'error' => 'Plugin já inativo'];
                }
                deactivate_plugins($plugin_path);
                return ['success' => true, 'message' => 'Plugin desativado'];

            case 'restore_htaccess':
                $default = "# BEGIN WordPress\n<IfModule mod_rewrite.c>\nRewriteEngine On\nRewriteBase /\nRewriteRule ^index\.php$ - [L]\nRewriteCond %{REQUEST_FILENAME} !-f\nRewriteCond %{REQUEST_FILENAME} !-d\nRewriteRule . /index.php [L]\n</IfModule>\n# END WordPress\n";
                file_put_contents(ABSPATH . '.htaccess', $default);
                return ['success' => true, 'message' => '.htaccess restaurado'];

            case 'clear_cache':
                if (function_exists('rocket_clean_domain')) {
                    rocket_clean_domain();
                }
                if (function_exists('w3tc_flush_all')) {
                    w3tc_flush_all();
                }
                return ['success' => true, 'message' => 'Cache limpo'];

            default:
                return ['success' => false, 'error' => 'Ação desconhecida'];
        }
    }
}
