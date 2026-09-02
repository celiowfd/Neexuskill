<?php
class PDSM_API {

    private $site_manager, $job_manager, $updater, $installer, $crypto;

    public function __construct($sm, $jm, $up, $ins, $cry) {
        $this->site_manager = $sm;
        $this->job_manager = $jm;
        $this->updater = $up;
        $this->installer = $ins;
        $this->crypto = $cry;
    }

    public function init() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes() {
        register_rest_route(PDSM_API_NAMESPACE, '/jobs', [
            'methods' => 'POST',
            'callback' => [$this, 'create_job_endpoint'],
            'permission_callback' => [$this, 'auth_hmac_rbac']
        ]);
        register_rest_route(PDSM_API_NAMESPACE, '/jobs/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_job_status'],
            'permission_callback' => [$this, 'auth_hmac_rbac']
        ]);
        // ... outros endpoints (sites, etc) com mesma proteção
    }

    public function auth_hmac_rbac($request) {
        $api_key = $request->get_header('X-API-Key');
        if (!$api_key) return false;

        // Busca credencial do site
        $sites = $this->site_manager->get_sites();
        $site = null;
        foreach ($sites as $s) {
            if ($s['api_key'] === $api_key) {
                $site = $s;
                break;
            }
        }
        if (!$site) return false;

        // HMAC
        if (!$this->crypto->verify_hmac($request, $site['api_key'])) {
            return false;
        }

        // Rate Limiting (10 req/min)
        $ip = $_SERVER['REMOTE_ADDR'];
        $rate_key = 'pdsm_rate_' . md5($ip . $api_key);
        $count = get_transient($rate_key) ?: 0;
        if ($count > 10) return false;
        set_transient($rate_key, $count + 1, 60);

        // RBAC (simplificado: permite tudo se for admin, mas lê scopes do perfil)
        // Aqui poderíamos verificar uma meta capability.
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
        return rest_ensure_response($status);
    }
}
