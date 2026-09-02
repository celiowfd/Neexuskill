<?php
/**
 * Plugin Name: Pack de Sites - Client
 * Description: Conecta este site ao Hub do Pack de Sites Manager para receber atualizações automatizadas.
 * Version: 1.0.0
 * Author: Equipe Pack de Sites
 */

if (!defined('ABSPATH')) {
    exit;
}

define('PDSM_CLIENT_VERSION', '1.0.0');
define('PDSM_CLIENT_DIR', plugin_dir_path(__FILE__));

require_once PDSM_CLIENT_DIR . 'includes/class-client-admin.php';
require_once PDSM_CLIENT_DIR . 'includes/class-client-api.php';
require_once PDSM_CLIENT_DIR . 'includes/class-updater.php';

function pdsm_client_init() {
    if (is_admin()) {
        new PDSM_Client_Admin();
    }
    new PDSM_Client_API();
}
add_action('plugins_loaded', 'pdsm_client_init');
