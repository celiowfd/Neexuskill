<?php

if (!defined('ABSPATH')) {
    exit;
}

class PDSM_Client_Admin {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_plugin_page']);
    }

    public function add_plugin_page() {
        add_menu_page(
            'Pack Client', 
            'Pack Client', 
            'manage_options', 
            'pdsm-client', 
            [$this, 'render_admin_page'], 
            'dashicons-admin-network', 
            100
        );
    }

    public function render_admin_page() {
        // Save the API Key if submitted
        if (isset($_POST['pdsm_client_nonce']) && wp_verify_nonce($_POST['pdsm_client_nonce'], 'pdsm_save_key')) {
            if (isset($_POST['api_key'])) {
                $api_key = sanitize_text_field($_POST['api_key']);
                update_option('pdsm_client_api_key', $api_key);
                echo '<div class="notice notice-success is-dismissible"><p>Chave salva com sucesso!</p></div>';
            }
        }

        $current_key = get_option('pdsm_client_api_key', '');
        ?>
        <div class="wrap">
            <h1>Pack de Sites - Configuração do Cliente</h1>
            <p>Para conectar este site ao seu Hub, insira a Chave de API gerada no Hub principal.</p>
            
            <div class="card" style="max-width: 600px; padding: 20px; margin-top: 20px;">
                <form method="post" action="">
                    <?php wp_nonce_field('pdsm_save_key', 'pdsm_client_nonce'); ?>
                    <p>
                        <label for="api_key"><strong>Chave de API do Hub:</strong></label><br>
                        <input type="text" name="api_key" id="api_key" value="<?php echo esc_attr($current_key); ?>" class="regular-text" style="width: 100%; margin-top: 10px;">
                    </p>
                    <?php submit_button('Salvar Chave'); ?>
                </form>
            </div>
            
            <?php if (!empty($current_key)): ?>
                <div class="notice notice-info" style="max-width: 580px; margin-top: 20px;">
                    <p>Conectado. Este site está pronto para receber comandos de atualização.</p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}
