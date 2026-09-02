<?php
/**
 * Plugin Name: Pack de Sites Manager
 * Description: Gerencia múltiplos sites WordPress com instalação/atualização em lote.
 * Version: 1.0.0
 * Author: Seu Nome
 */

// Previne acesso direto
if (!defined('ABSPATH')) {
    exit;
}

// Define constantes
define('PDSM_VERSION', '1.0.0');
define('PDSM_PLUGIN_DIR', plugin_dir_path(__FILE__));

// Arquivos principais
require_once PDSM_PLUGIN_DIR . 'includes/class-main.php';
require_once PDSM_PLUGIN_DIR . 'includes/class-api.php';
require_once PDSM_PLUGIN_DIR . 'includes/class-site-manager.php';
require_once PDSM_PLUGIN_DIR . 'includes/class-admin.php';

// Inicializa o plugin
function pdsm_init() {
    $plugin = new PDSM_Main();
    $plugin->run();
}
add_action('plugins_loaded', 'pdsm_init');
