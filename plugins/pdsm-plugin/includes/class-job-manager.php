<?php
class PDSM_Job_Manager {

    public function __construct() {
        add_action('pdsm_process_job', [$this, 'process_job']);
    }

    public function create_job($action, $payload, $user_id = 0) {
        global $wpdb;
        $table = $wpdb->prefix . 'pdsm_jobs';

        $data = [
            'action' => $action,
            'payload' => maybe_serialize($payload),
            'status' => 'pending',
            'user_id' => $user_id,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];
        $wpdb->insert($table, $data);
        $job_id = $wpdb->insert_id;

        // Agenda execução em 5 segundos
        wp_schedule_single_event(time() + 5, 'pdsm_process_job', [$job_id]);
        return $job_id;
    }

    public function process_job($job_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'pdsm_jobs';
        $job = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $job_id));

        if (!$job || $job->status !== 'pending') return;

        $wpdb->update($table, ['status' => 'processing', 'updated_at' => current_time('mysql')], ['id' => $job_id]);

        // Dispara ação específica baseada no `action`
        try {
            $result = null;
            switch ($job->action) {
                case 'update_plugin':
                    $result = $this->handle_update($job);
                    break;
                case 'install_plugin':
                    $result = $this->handle_install($job);
                    break;
                default:
                    throw new Exception('Ação desconhecida');
            }

            $wpdb->update($table, [
                'status' => 'completed',
                'result' => maybe_serialize($result),
                'updated_at' => current_time('mysql')
            ], ['id' => $job_id]);

        } catch (Exception $e) {
            $wpdb->update($table, [
                'status' => 'failed',
                'result' => maybe_serialize(['error' => $e->getMessage()]),
                'updated_at' => current_time('mysql')
            ], ['id' => $job_id]);
        }
    }

    private function handle_update($job) {
        $payload = maybe_unserialize($job->payload);
        $updater = new PDSM_Updater(new PDSM_Site_Manager(), new PDSM_Crypto(), $this);
        return $updater->apply_update_batch($payload['plugin_slug'], $payload['domains']);
    }

    private function handle_install($job) {
        $payload = maybe_unserialize($job->payload);
        $installer = new PDSM_Installer(new PDSM_Site_Manager(), new PDSM_Crypto(), $this);
        return $installer->install_batch($payload['plugin_slug'], $payload['domains']);
    }

    public function get_status($job_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'pdsm_jobs';
        return $wpdb->get_row($wpdb->prepare("SELECT id, status, result, created_at, updated_at FROM $table WHERE id = %d", $job_id));
    }

    // Criação da tabela no activate
    public static function create_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'pdsm_jobs';
        $charset = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            action varchar(50) NOT NULL,
            payload longtext NOT NULL,
            status varchar(20) DEFAULT 'pending',
            result longtext,
            user_id bigint(20) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY status (status)
        ) $charset;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
