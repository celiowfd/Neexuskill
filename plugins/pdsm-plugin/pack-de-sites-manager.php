<?php
/**
 * Plugin Name: Pack de Sites Manager v2.0
 * Description: Orquestração segura para até 10 sites WordPress com HMAC, RBAC, Rollback e IA.
 * Version: 2.0.0
 * Author: Equipe Neexus
 * Text Domain: pdsm
 */

if (!defined('ABSPATH')) exit;

define('PDSM_VERSION', '2.0.0');
define('PDSM_MAX_SITES', 10);
define('PDSM_API_NAMESPACE', 'pdsm/v2');
define('PDSM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PDSM_PLUGIN_URL', plugin_dir_url(__FILE__));

// Classes Core
require_once PDSM_PLUGIN_DIR . 'includes/class-main.php';
require_once PDSM_PLUGIN_DIR . 'includes/class-api.php';
require_once PDSM_PLUGIN_DIR . 'includes/class-site-manager.php';
require_once PDSM_PLUGIN_DIR . 'includes/class-updater.php';
require_once PDSM_PLUGIN_DIR . 'includes/class-job-manager.php'; // NOVO
require_once PDSM_PLUGIN_DIR . 'includes/class-crypto.php';      // NOVO
require_once PDSM_PLUGIN_DIR . 'includes/class-installer.php';
require_once PDSM_PLUGIN_DIR . 'includes/class-admin.php';

function pdsm_init() {
    load_plugin_textdomain('pdsm', false, dirname(plugin_basename(__FILE__)) . '/languages');

    $site_manager = new PDSM_Site_Manager();
    $job_manager = new PDSM_Job_Manager();
    $crypto = new PDSM_Crypto();
    $updater = new PDSM_Updater($site_manager, $crypto, $job_manager);
    $installer = new PDSM_Installer($site_manager, $crypto, $job_manager);
    $api = new PDSM_API($site_manager, $job_manager, $updater, $installer, $crypto);
    $admin = new PDSM_Admin($site_manager, $updater, $job_manager);

    register_activation_hook(__FILE__, ['PDSM_Main', 'activate']);
    register_deactivation_hook(__FILE__, ['PDSM_Main', 'deactivate']);

    $main = new PDSM_Main($site_manager, $job_manager, $updater, $installer, $api, $admin, $crypto);
    $main->run();
}
add_action('plugins_loaded', 'pdsm_init');
