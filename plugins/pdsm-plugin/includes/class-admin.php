<?php
class PDSM_Admin {

    private $site_manager, $updater, $job_manager, $internal_agent;

    public function __construct($sm, $up, $jm, $agent) {
        $this->site_manager = $sm;
        $this->updater = $up;
        $this->job_manager = $jm;
        $this->internal_agent = $agent;
    }

    public function init() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    public function add_admin_menu() {
        add_menu_page(
            'Pack de Sites',
            'Pack de Sites',
            'manage_options',
            'pack-de-sites',
            [$this, 'render_page'],
            'dashicons-networking',
            30
        );
    }

    public function enqueue_scripts($hook) {
        if ($hook !== 'toplevel_page_pack-de-sites') {
            return;
        }
        wp_enqueue_style('pdsm-admin-style', PDSM_PLUGIN_URL . 'assets/css/admin-style.css', [], PDSM_VERSION);
        wp_enqueue_script('pdsm-admin-script', PDSM_PLUGIN_URL . 'assets/js/admin-script.js', ['jquery'], PDSM_VERSION, true);
        wp_localize_script('pdsm-admin-script', 'pdsm_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('pdsm_admin_nonce')
        ]);
    }

    public function render_page() {
        $sites = $this->site_manager->get_sites();
        $master_key = get_option('pdsm_master_api_key');
        ?>
        <div class="wrap">
            <h1>Pack de Sites Manager v2.0</h1>
            <p><strong>Chave Mestra:</strong> <code><?php echo esc_html($master_key); ?></code></p>
            <hr>

            <h2>Adicionar Novo Site</h2>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="pdsm_add_site">
                <?php wp_nonce_field('pdsm_add_site'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="domain">Domínio</label></th>
                        <td><input type="url" name="domain" id="domain" required placeholder="https://meusite.com"></td>
                    </tr>
                    <tr>
                        <th><label for="api_key">Chave Pública do Site</label></th>
                        <td><input type="text" name="api_key" id="api_key" required></td>
                    </tr>
                    <tr>
                        <th><label for="label">Apelido</label></th>
                        <td><input type="text" name="label" id="label"></td>
                    </tr>
                </table>
                <?php submit_button('Adicionar Site', 'primary', 'submit', false); ?>
            </form>

            <hr>

            <h2>Sites Gerenciados (<?php echo count($sites); ?>/<?php echo PDSM_MAX_SITES; ?>)</h2>
            <?php if (count($sites) > 0): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Apelido</th>
                            <th>Domínio</th>
                            <th>Status</th>
                            <th>Último Health Check</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sites as $site): ?>
                            <tr>
                                <td><?php echo esc_html($site['label']); ?></td>
                                <td><?php echo esc_url($site['domain']); ?></td>
                                <td><span class="status-badge status-<?php echo esc_attr($site['status']); ?>"><?php echo esc_html($site['status']); ?></span></td>
                                <td><?php echo esc_html($site['last_health'] ?? 'Nunca'); ?></td>
                                <td>
                                    <button class="button button-small pdsm-diagnose" data-domain="<?php echo esc_attr($site['domain']); ?>">Diagnosticar</button>
                                    <button class="button button-small pdsm-heal" data-domain="<?php echo esc_attr($site['domain']); ?>">Auto-Curar</button>
                                    <button class="button button-small pdsm-remove" data-domain="<?php echo esc_attr($site['domain']); ?>">Remover</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Nenhum site cadastrado ainda.</p>
            <?php endif; ?>
        </div>
        <?php
    }
}
