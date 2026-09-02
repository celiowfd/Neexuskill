<?php
/**
 * Classe Principal - Gerencia a execução do plugin
 */
class PDSM_Main {

    private $site_manager, $job_manager, $updater, $installer, $api, $admin, $crypto, $internal_agent;

    public function __construct($sm, $jm, $up, $ins, $api, $admin, $crypto, $agent) {
        $this->site_manager = $sm;
        $this->job_manager = $jm;
        $this->updater = $up;
        $this->installer = $ins;
        $this->api = $api;
        $this->admin = $admin;
        $this->crypto = $crypto;
        $this->internal_agent = $agent;
    }

    public function run() {
        // Agendamento de tarefas (Cron)
        add_action('wp', [$this, 'schedule_events']);
        add_action('pdsm_daily_update_check', [$this, 'run_scheduled_updates']);
        add_action('pdsm_agent_daily_health_check', [$this->internal_agent, 'auto_diagnose_all_sites']);

        // Inicializa os módulos
        $this->site_manager->init();
        $this->job_manager->init();
        $this->updater->init();
        $this->installer->init();
        $this->api->init();
        $this->admin->init();
        $this->internal_agent->init();
    }

    public function schedule_events() {
        if (!wp_next_scheduled('pdsm_daily_update_check')) {
            wp_schedule_event(time(), 'daily', 'pdsm_daily_update_check');
        }
        if (!wp_next_scheduled('pdsm_agent_daily_health_check')) {
            wp_schedule_event(time(), 'daily', 'pdsm_agent_daily_health_check');
        }
    }

    public function run_scheduled_updates() {
        $this->updater->check_all_sites_for_updates();
    }

    public static function activate() {
        // Cria opções padrão
        if (!get_option('pdsm_sites')) {
            update_option('pdsm_sites', []);
        }
        if (!get_option('pdsm_update_log')) {
            update_option('pdsm_update_log', []);
        }
        if (!get_option('pdsm_master_api_key')) {
            update_option('pdsm_master_api_key', bin2hex(random_bytes(32)));
        }

        // Cria tabelas
        PDSM_Job_Manager::create_table();
        PDSM_Internal_Agent::create_table();

        // Agenda CRON
        if (!wp_next_scheduled('pdsm_daily_update_check')) {
            wp_schedule_event(time(), 'daily', 'pdsm_daily_update_check');
        }
        if (!wp_next_scheduled('pdsm_agent_daily_health_check')) {
            wp_schedule_event(time(), 'daily', 'pdsm_agent_daily_health_check');
        }
    }

    public static function deactivate() {
        $timestamps = [
            wp_next_scheduled('pdsm_daily_update_check'),
            wp_next_scheduled('pdsm_agent_daily_health_check')
        ];
        foreach ($timestamps as $ts) {
            if ($ts) {
                wp_unschedule_event($ts, 'pdsm_daily_update_check');
                wp_unschedule_event($ts, 'pdsm_agent_daily_health_check');
            }
        }
    }
}
