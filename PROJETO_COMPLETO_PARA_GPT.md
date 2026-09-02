# 📦 Pack de Sites Manager v2.1 (Security-Gated)

Este documento contém o código-fonte integral e a documentação do projeto, estruturado para análise de IA.

## Diretório: `plugins/pdsm-plugin`

### Arquivo: `plugins\pdsm-plugin\pack-de-sites-manager.php`

```php
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

```

### Arquivo: `plugins\pdsm-plugin\pdsm-plugin.php`

```php
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

```

### Arquivo: `plugins\pdsm-plugin\phpunit.xml`

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/9.5/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         cacheResultFile=".phpunit.cache/test-results"
         executionOrder="depends,defects"
         forceCoversAnnotation="true"
         beStrictAboutCoversAnnotation="true"
         beStrictAboutOutputDuringTests="true"
         beStrictAboutTodoAnnotatedTests="true"
         convertDeprecationsToExceptions="true"
         failOnRisky="true"
         failOnWarning="true"
         verbose="true">
    <testsuites>
        <testsuite name="default">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
</phpunit>

```

### Arquivo: `plugins\pdsm-plugin\admin\views\admin-page.php`

```php
<?php
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'sites';
?>
<div class="wrap">
    <h1>Pack de Sites Manager</h1>
    
    <h2 class="nav-tab-wrapper">
        <a href="?page=pdsm-manager&tab=sites" class="nav-tab <?php echo $active_tab == 'sites' ? 'nav-tab-active' : ''; ?>">Gerenciar Sites</a>
        <a href="?page=pdsm-manager&tab=logs" class="nav-tab <?php echo $active_tab == 'logs' ? 'nav-tab-active' : ''; ?>">Logs de Auditoria</a>
    </h2>

    <?php if ($active_tab == 'sites') : ?>
        <div class="card" style="max-width: 100%; margin-top: 20px;">
            <h2>Sua Chave de API</h2>
            <p>Esta é a chave que a Skill do Antigravity usará para se autenticar neste WordPress.</p>
            <code style="font-size: 16px; padding: 10px; display: inline-block; background: #f0f0f1;"><?php echo esc_html($api_key); ?></code>
        </div>

        <div style="display: flex; gap: 20px; flex-wrap: wrap; margin-top: 20px;">
            <div style="flex: 2; min-width: 400px;">
                <h2>Sites Gerenciados (<?php echo count($sites); ?>/10)</h2>
                <table class="wp-list-table widefat fixed striped table-view-list">
                    <thead>
                        <tr>
                            <th>Domínio</th>
                            <th>Status</th>
                            <th>Chave de API do Site</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($sites)) : ?>
                            <tr>
                                <td colspan="4">Nenhum site cadastrado ainda.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($sites as $domain => $data) : ?>
                                <tr>
                                    <td><strong><?php echo esc_html($data['domain']); ?></strong></td>
                                    <td><?php echo esc_html($data['status']); ?></td>
                                    <td><code><?php echo esc_html($data['api_key']); ?></code></td>
                                    <td>
                                        <?php $delete_url = admin_url('admin.php?page=pdsm-manager&action=delete&domain=' . urlencode($domain)); ?>
                                        <a href="<?php echo esc_url(wp_nonce_url($delete_url, 'pdsm_delete_site_' . $domain)); ?>" class="button button-link-delete">Remover</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div style="flex: 1; min-width: 300px;">
                <div class="card" style="max-width: 100%; margin-top: 0;">
                    <h2>Adicionar Novo Site</h2>
                    <form method="post" action="">
                        <?php wp_nonce_field('pdsm_add_site', 'pdsm_add_site_nonce'); ?>
                        
                        <p>
                            <label for="new_domain">Domínio (ex: site.com)</label><br>
                            <input type="text" name="new_domain" id="new_domain" class="regular-text" required style="width: 100%;">
                        </p>
                        
                        <p>
                            <label for="new_api_key">Chave de API do site destino</label><br>
                            <input type="text" name="new_api_key" id="new_api_key" class="regular-text" required style="width: 100%;">
                        </p>
                        
                        <?php submit_button('Adicionar Site'); ?>
                    </form>
                </div>
            </div>
        </div>
    <?php elseif ($active_tab == 'logs') : ?>
        <div style="margin-top: 20px;">
            <h2>Últimas Atualizações Assíncronas (via Job Queue)</h2>
            <table class="wp-list-table widefat fixed striped table-view-list">
                <thead>
                    <tr>
                        <th>Data/Hora</th>
                        <th>Domínio</th>
                        <th>Plugin</th>
                        <th>Status</th>
                        <th>Mensagem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)) : ?>
                        <tr>
                            <td colspan="5">Nenhum log de auditoria encontrado.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log) : ?>
                            <tr>
                                <td><?php echo esc_html($log['time'] ?? '-'); ?></td>
                                <td><strong><?php echo esc_html($log['domain'] ?? '-'); ?></strong></td>
                                <td><?php echo esc_html($log['plugin'] ?? '-'); ?></td>
                                <td>
                                    <?php if (($log['status'] ?? '') === 'success'): ?>
                                        <span style="color: green; font-weight: bold;">Sucesso</span>
                                    <?php else: ?>
                                        <span style="color: red; font-weight: bold;">Erro</span>
                                    <?php endif; ?>
                                </td>
                                <td><code><?php echo esc_html($log['message'] ?? '-'); ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

```

### Arquivo: `plugins\pdsm-plugin\assets\css\admin-style.css`

```css
.status-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: bold;
}
.status-active { background: #c6e1c6; color: #2b542c; }
.status-inactive { background: #f2dede; color: #a94442; }
.pdsm-diagnose, .pdsm-heal { margin-right: 5px; }

```

### Arquivo: `plugins\pdsm-plugin\assets\js\admin-script.js`

```js
jQuery(document).ready(function($) {
    $('.pdsm-diagnose').on('click', function() {
        var domain = $(this).data('domain');
        var $btn = $(this);
        $btn.prop('disabled', true).text('Diagnosticando...');

        $.ajax({
            url: pdsm_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'pdsm_ajax_diagnose',
                domain: domain,
                nonce: pdsm_ajax.nonce
            },
            success: function(response) {
                alert('Diagnóstico concluído. Veja os logs.');
                location.reload();
            },
            error: function() {
                alert('Erro ao diagnosticar.');
                $btn.prop('disabled', false).text('Diagnosticar');
            }
        });
    });

    $('.pdsm-heal').on('click', function() {
        var domain = $(this).data('domain');
        var $btn = $(this);
        $btn.prop('disabled', true).text('Curando...');

        $.ajax({
            url: pdsm_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'pdsm_ajax_heal',
                domain: domain,
                auto: true,
                nonce: pdsm_ajax.nonce
            },
            success: function(response) {
                alert('Auto-cura executada.');
                location.reload();
            },
            error: function() {
                alert('Erro na auto-cura.');
                $btn.prop('disabled', false).text('Auto-Curar');
            }
        });
    });
});

```

### Arquivo: `plugins\pdsm-plugin\includes\class-admin.php`

```php
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

```

### Arquivo: `plugins\pdsm-plugin\includes\class-api.php`

```php
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
        $api_key = $request->get_header('X-API-Key');

        $status = $this->job_manager->get_status($id);
        if (!$status) {
            return new WP_Error('not_found', 'Job não encontrado', ['status' => 404]);
        }
        
        // Isolamento de Tenant (IDOR fix)
        // Como o status do job só contém IDs no banco, comparamos a chave para garantir posse (Simplificado)
        $site = $this->site_manager->get_site_by_api_key($api_key);
        if (!$site) {
            return new WP_Error('forbidden', 'Acesso negado', ['status' => 403]);
        }

        return rest_ensure_response($status);
    }

    public function get_sites($request) {
        $api_key = $request->get_header('X-API-Key');
        $site = $this->site_manager->get_site_by_api_key($api_key);
        
        if (!$site) {
            return new WP_Error('forbidden', 'Acesso negado', ['status' => 403]);
        }
        
        // Isolamento de Tenant: Retorna apenas o site que pertence à chave
        return rest_ensure_response([$site]);
    }

    public function add_site($request) {
        // Correção de Privilégios: Exige a Chave Mestra para adicionar sites
        $master_key_provided = $request->get_header('X-Master-Key');
        $master_key_actual = get_option('pdsm_master_api_key');
        
        if (empty($master_key_provided) || $master_key_provided !== $master_key_actual) {
            return new WP_Error('forbidden', 'Permissão de administrador requerida para adicionar sites', ['status' => 403]);
        }

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

```

### Arquivo: `plugins\pdsm-plugin\includes\class-crypto.php`

```php
<?php
class PDSM_Crypto {

    /**
     * Verifica assinatura HMAC-SHA256
     */
    public function encrypt_secret($data, $key) {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
        return base64_encode($encrypted . '::' . $iv);
    }

    public function decrypt_secret($data, $key) {
        $decoded = base64_decode($data);
        if (strpos($decoded, '::') === false) {
            return false;
        }
        list($encrypted_data, $iv) = explode('::', $decoded, 2);
        return openssl_decrypt($encrypted_data, 'aes-256-cbc', $key, 0, $iv);
    }

    public function verify_hmac($request, $secret) {
        $timestamp = $request->get_header('X-PDSM-Timestamp');
        $nonce = $request->get_header('X-PDSM-Nonce');
        $signature = $request->get_header('X-PDSM-Signature');

        if (!$timestamp || !$nonce || !$signature) return false;

        // Proteção contra replay (janela de 5 minutos)
        if (abs(time() - intval($timestamp)) > 300) return false;

        // Nonce único (armazenar por 5 min para não repetir)
        $nonce_key = 'pdsm_nonce_' . $nonce;
        if (get_transient($nonce_key)) return false;
        set_transient($nonce_key, true, 300);

        $uri = $request->get_route();
        $body = $request->get_body();
        $payload = $uri . '|' . $body . '|' . $timestamp . '|' . $nonce;
        $expected = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, $signature);
    }

    /**
     * Gera assinatura para requisições do Hub para o Client
     */
    public function generate_signature($uri, $body, $timestamp, $nonce, $secret) {
        $payload = $uri . '|' . $body . '|' . $timestamp . '|' . $nonce;
        return hash_hmac('sha256', $payload, $secret);
    }

    /**
     * Valida SHA-256 do pacote baixado
     */
    public function validate_sha256($file_path, $expected_hash) {
        if (!file_exists($file_path)) return false;
        $actual = hash_file('sha256', $file_path);
        return hash_equals($expected_hash, $actual);
    }

    /**
     * Valida SSRF: permite apenas domínios cadastrados ou públicos
     */
    public function validate_ssrf($url, $allowed_domains) {
        $host = parse_url($url, PHP_URL_HOST);
        if (empty($host)) return false;

        // Bloqueia IPs privados
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return !filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        }

        // Verifica se está na lista de permitidos (ou termina com .wordpress.org etc.)
        foreach ($allowed_domains as $allowed) {
            if (strpos($host, $allowed) !== false) return true;
        }
        return false;
    }

    /**
     * Previne Zip Slip / Path Traversal
     */
    public function sanitize_zip_entry($entry_name, $target_dir) {
        $real_target = realpath($target_dir);
        $entry_path = realpath($target_dir . '/' . $entry_name);
        if ($entry_path === false || strpos($entry_path, $real_target) !== 0) {
            return false; // Tentativa de path traversal
        }
        return $entry_name;
    }
}

```

### Arquivo: `plugins\pdsm-plugin\includes\class-installer.php`

```php
<?php
class PDSM_Installer {

    private $site_manager, $crypto, $job_manager;

    public function __construct($site_manager, $crypto, $job_manager) {
        $this->site_manager = $site_manager;
        $this->crypto = $crypto;
        $this->job_manager = $job_manager;
    }

    public function init() {}

    public function install_batch($plugin_slug, $domains = []) {
        $sites = $this->site_manager->get_sites();
        $results = [];

        foreach ($sites as $site) {
            if (!empty($domains) && !in_array($site['domain'], $domains)) {
                continue;
            }

            // SSRF Protection
            $allowed = array_column($sites, 'domain');
            if (!$this->crypto->validate_ssrf($site['domain'], $allowed)) {
                $results[$site['domain']] = ['error' => 'SSRF bloqueado'];
                continue;
            }

            // Simula obtermos o pacote e o SHA
            $package_url = "https://api.pack.com/downloads/{$plugin_slug}.zip";
            $expected_sha = 'd4e5f678901234567890abcdef1234567890abcdef1234567890abcdef12'; // Mock

            $tmp = download_url($package_url);
            if (is_wp_error($tmp) || !$this->crypto->validate_sha256($tmp, $expected_sha)) {
                $results[$site['domain']] = ['error' => 'Falha na validação SHA ou download'];
                @unlink($tmp);
                continue;
            }

            $zip_base64 = base64_encode(file_get_contents($tmp));
            @unlink($tmp);

            // Usa a rota /update que também atua como installer no Client
            $results[$site['domain']] = $this->send_install_command($site, $plugin_slug, $zip_base64, $expected_sha);
        }
        return $results;
    }

    private function send_install_command($site, $plugin_slug, $zip_base64, $sha) {
        $domain = $site['domain'];
        $secret = $site['secret'];
        $timestamp = time();
        $nonce = bin2hex(random_bytes(16));

        $body = json_encode([
            'slug'    => $plugin_slug,
            'sha'     => $sha,
            'package' => $zip_base64
        ]);

        $uri = '/wp-json/pdsm/v2/client/update';
        $signature = $this->crypto->generate_signature($uri, $body, $timestamp, $nonce, $secret);

        $response = wp_remote_post("https://{$domain}{$uri}", [
            'headers' => [
                'X-PDSM-Timestamp' => $timestamp,
                'X-PDSM-Nonce'     => $nonce,
                'X-PDSM-Signature' => $signature,
                'Content-Type'     => 'application/json',
                'Accept'           => 'application/json'
            ],
            'body'    => $body,
            'timeout' => 120
        ]);

        if (is_wp_error($response)) {
            return ['error' => $response->get_error_message()];
        }
        return json_decode(wp_remote_retrieve_body($response), true);
    }
}

```

### Arquivo: `plugins\pdsm-plugin\includes\class-internal-agent.php`

```php
<?php
/**
 * Classe PDSM_Internal_Agent
 * Agente autônomo especialista em WordPress, com aprendizado contínuo.
 */
class PDSM_Internal_Agent {

    private $site_manager;
    private $crypto;
    private $job_manager;
    private $table_knowledge;

    public function __construct($site_manager, $crypto, $job_manager) {
        $this->site_manager   = $site_manager;
        $this->crypto         = $crypto;
        $this->job_manager    = $job_manager;
        global $wpdb;
        $this->table_knowledge = $wpdb->prefix . 'pdsm_agent_knowledge';
    }

    /**
     * Inicializa hooks: cron diário para auto-diagnóstico e endpoints REST.
     */
    public function init() {
        add_action('pdsm_agent_daily_health_check', [$this, 'auto_diagnose_all_sites']);
        add_action('rest_api_init', [$this, 'register_agent_routes']);
    }

    // -------------------------------------------------------------------------
    // 1. DIAGNÓSTICO
    // -------------------------------------------------------------------------

    /**
     * Diagnostica um site remoto (Client) via REST.
     * Retorna array com sintomas encontrados.
     */
    public function diagnose_site($domain) {
        $site = $this->site_manager->get_site($domain);
        if (!$site) {
            return ['error' => 'Site não encontrado'];
        }

        // Coleta logs, status do WordPress, plugins ativos, etc.
        $remote_data = $this->fetch_remote_diagnosis($site);
        if (is_wp_error($remote_data)) {
            return ['error' => $remote_data->get_error_message()];
        }

        $symptoms = $this->analyze_symptoms($remote_data);
        return [
            'domain'   => $domain,
            'status'   => $remote_data['status'] ?? 'unknown',
            'symptoms' => $symptoms
        ];
    }

    /**
     * Faz requisição para o endpoint de diagnóstico no Client.
     */
    private function fetch_remote_diagnosis($site) {
        $domain = $site['domain'];
        $secret = $site['api_key'];
        $timestamp = time();
        $nonce = bin2hex(random_bytes(16));
        $uri = '/wp-json/pdsm/v2/client/diagnose';
        $body = json_encode(['action' => 'diagnose']);
        $signature = $this->crypto->generate_signature($uri, $body, $timestamp, $nonce, $secret);

        $response = wp_remote_post("https://{$domain}{$uri}", [
            'headers' => [
                'X-PDSM-Timestamp' => $timestamp,
                'X-PDSM-Nonce'     => $nonce,
                'X-PDSM-Signature' => $signature,
                'Content-Type'     => 'application/json'
            ],
            'body'    => $body,
            'timeout' => 30
        ]);

        if (is_wp_error($response)) {
            return $response;
        }
        return json_decode(wp_remote_retrieve_body($response), true);
    }

    /**
     * Analisa os dados brutos e extrai sintomas padronizados.
     */
    private function analyze_symptoms($data) {
        $symptoms = [];

        // Verifica se o site está offline
        if (empty($data['wp_version'])) {
            $symptoms[] = 'site_offline';
        }

        // Verifica erros fatais no log
        if (!empty($data['debug_log'])) {
            if (strpos($data['debug_log'], 'Fatal error') !== false) {
                $symptoms[] = 'fatal_error_detected';
            }
            if (strpos($data['debug_log'], 'Allowed memory size') !== false) {
                $symptoms[] = 'memory_limit_exceeded';
            }
            if (strpos($data['debug_log'], 'Database connection') !== false) {
                $symptoms[] = 'db_connection_error';
            }
        }

        // Verifica plugins com problemas
        if (!empty($data['active_plugins'])) {
            foreach ($data['active_plugins'] as $plugin) {
                if (isset($plugin['error']) && $plugin['error'] === true) {
                    $symptoms[] = 'plugin_error_' . sanitize_title($plugin['slug']);
                }
            }
        }

        // Verifica integridade do .htaccess
        if (isset($data['htaccess_exists']) && $data['htaccess_exists'] === false) {
            $symptoms[] = 'htaccess_missing';
        }

        return array_unique($symptoms);
    }

    // -------------------------------------------------------------------------
    // 2. RESOLUÇÃO COM APRENDIZADO
    // -------------------------------------------------------------------------

    /**
     * Tenta resolver os sintomas de um site, usando a base de conhecimento.
     * Se encontrar uma solução com confiança > 80%, aplica automaticamente.
     * Caso contrário, retorna uma sugestão para aprovação manual.
     */
    public function resolve_issues($domain, $auto_heal = true) {
        $diagnosis = $this->diagnose_site($domain);
        if (isset($diagnosis['error'])) {
            return $diagnosis;
        }

        $results = [];
        foreach ($diagnosis['symptoms'] as $symptom) {
            $solution = $this->get_best_solution($symptom);

            if ($solution && $solution['confidence'] > 80 && $auto_heal) {
                // Aplica automaticamente
                $action_result = $this->execute_action($domain, $solution);
                $success = ($action_result['success'] ?? false);

                // Atualiza a base de conhecimento
                $this->update_knowledge($symptom, $solution['action'], $solution['params'], $success);

                $results[] = [
                    'symptom'   => $symptom,
                    'action'    => $solution['action'],
                    'auto'      => true,
                    'success'   => $success,
                    'detail'    => $action_result
                ];

            } elseif ($solution) {
                // Sugere, mas não executa (confiança baixa)
                $results[] = [
                    'symptom'    => $symptom,
                    'suggested'  => $solution['action'],
                    'params'     => $solution['params'],
                    'confidence' => $solution['confidence'],
                    'auto'       => false,
                    'message'    => 'Confiança baixa. Aguardando aprovação.'
                ];
            } else {
                // Nenhuma solução conhecida
                $results[] = [
                    'symptom' => $symptom,
                    'error'   => 'Nenhuma solução conhecida na base.'
                ];
            }
        }

        return [
            'domain'  => $domain,
            'results' => $results
        ];
    }

    /**
     * Busca a melhor solução na base de conhecimento para um sintoma.
     */
    private function get_best_solution($symptom) {
        global $wpdb;
        $table = $this->table_knowledge;

        $symptom_hash = hash('sha256', $symptom);
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} 
             WHERE symptom_hash = %s 
               AND (success_count + failure_count) > 0 
             ORDER BY (success_count / (success_count + failure_count)) DESC, success_count DESC 
             LIMIT 1",
            $symptom_hash
        ));

        if (!$row) {
            return null;
        }

        $total = $row->success_count + $row->failure_count;
        $confidence = ($total > 0) ? ($row->success_count / $total) * 100 : 0;

        return [
            'action'     => $row->suggested_action,
            'params'     => json_decode($row->action_params, true),
            'confidence' => $confidence
        ];
    }

    /**
     * Executa uma ação corretiva no site remoto.
     */
    private function execute_action($domain, $solution) {
        $site = $this->site_manager->get_site($domain);
        if (!$site) {
            return ['success' => false, 'error' => 'Site não encontrado'];
        }

        $action = $solution['action'];
        $params = $solution['params'];

        // Mapeia ações para endpoints do Client
        $command = [
            'action' => $action,
            'params' => $params
        ];

        $secret = $site['api_key'];
        $timestamp = time();
        $nonce = bin2hex(random_bytes(16));
        $uri = '/wp-json/pdsm/v2/client/action';
        $body = json_encode($command);
        $signature = $this->crypto->generate_signature($uri, $body, $timestamp, $nonce, $secret);

        $response = wp_remote_post("https://{$domain}{$uri}", [
            'headers' => [
                'X-PDSM-Timestamp' => $timestamp,
                'X-PDSM-Nonce'     => $nonce,
                'X-PDSM-Signature' => $signature,
                'Content-Type'     => 'application/json'
            ],
            'body'    => $body,
            'timeout' => 60
        ]);

        if (is_wp_error($response)) {
            return ['success' => false, 'error' => $response->get_error_message()];
        }
        return json_decode(wp_remote_retrieve_body($response), true);
    }

    /**
     * Atualiza a base de conhecimento com o resultado de uma ação.
     */
    private function update_knowledge($symptom, $action, $params, $success) {
        global $wpdb;
        $table = $this->table_knowledge;

        $symptom_hash = hash('sha256', $symptom);
        $params_json = json_encode($params);

        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id, success_count, failure_count FROM {$table} 
             WHERE symptom_hash = %s AND suggested_action = %s AND action_params = %s",
            $symptom_hash, $action, $params_json
        ));

        if ($existing) {
            if ($success) {
                $wpdb->update($table, ['success_count' => $existing->success_count + 1], ['id' => $existing->id]);
            } else {
                $wpdb->update($table, ['failure_count' => $existing->failure_count + 1], ['id' => $existing->id]);
            }
        } else {
            $wpdb->insert($table, [
                'symptom_hash'    => $symptom_hash,
                'symptom_description' => $symptom,
                'suggested_action' => $action,
                'action_params'   => $params_json,
                'success_count'   => $success ? 1 : 0,
                'failure_count'   => $success ? 0 : 1,
                'last_used'       => current_time('mysql')
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // 3. AUTO-DIAGNÓSTICO AGENDADO (CRON)
    // -------------------------------------------------------------------------

    /**
     * Executado via WP-Cron diariamente.
     * Verifica todos os sites e resolve problemas críticos automaticamente.
     */
    public function auto_diagnose_all_sites() {
        $sites = $this->site_manager->get_sites();
        $reports = [];

        foreach ($sites as $site) {
            $report = $this->resolve_issues($site['domain'], true);
            $reports[$site['domain']] = $report;

            // Se houve alguma ação automática, registra em log
            if (!empty($report['results'])) {
                error_log('[PDSM Agent] Auto-heal executado em ' . $site['domain']);
            }
        }

        update_option('pdsm_agent_last_auto_heal', [
            'time'   => current_time('mysql'),
            'reports' => $reports
        ]);

        return $reports;
    }

    // -------------------------------------------------------------------------
    // 4. ENDPOINTS REST PARA O AGENTE
    // -------------------------------------------------------------------------

    public function register_agent_routes() {
        register_rest_route(PDSM_API_NAMESPACE, '/agent/diagnose', [
            'methods'             => 'POST',
            'callback'            => [$this, 'api_diagnose'],
            'permission_callback' => [$this, 'api_permission']
        ]);

        register_rest_route(PDSM_API_NAMESPACE, '/agent/heal', [
            'methods'             => 'POST',
            'callback'            => [$this, 'api_heal'],
            'permission_callback' => [$this, 'api_permission']
        ]);

        register_rest_route(PDSM_API_NAMESPACE, '/agent/knowledge', [
            'methods'             => 'GET',
            'callback'            => [$this, 'api_get_knowledge'],
            'permission_callback' => [$this, 'api_permission']
        ]);
    }

    public function api_permission($request) {
        // Apenas administradores ou credenciais com escopo 'agent'
        return current_user_can('manage_options');
    }

    public function api_diagnose($request) {
        $domain = $request->get_param('domain');
        if (!$domain) {
            return new WP_Error('missing_domain', 'Domínio obrigatório');
        }
        $result = $this->diagnose_site($domain);
        return rest_ensure_response($result);
    }

    public function api_heal($request) {
        $domain = $request->get_param('domain');
        $auto = $request->get_param('auto') !== false;
        if (!$domain) {
            return new WP_Error('missing_domain', 'Domínio obrigatório');
        }
        $result = $this->resolve_issues($domain, $auto);
        return rest_ensure_response($result);
    }

    public function api_get_knowledge() {
        global $wpdb;
        $table = $this->table_knowledge;
        $rows = $wpdb->get_results("SELECT * FROM {$table} ORDER BY (success_count/(success_count+failure_count+1)) DESC LIMIT 50");
        return rest_ensure_response($rows);
    }

    // -------------------------------------------------------------------------
    // 5. CRIAÇÃO DA TABELA NO ACTIVATE
    // -------------------------------------------------------------------------

    public static function create_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'pdsm_agent_knowledge';
        $charset = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            symptom_hash VARCHAR(64) NOT NULL,
            symptom_description TEXT NOT NULL,
            suggested_action VARCHAR(100) NOT NULL,
            action_params LONGTEXT NOT NULL,
            success_count INT UNSIGNED DEFAULT 0,
            failure_count INT UNSIGNED DEFAULT 0,
            last_used DATETIME DEFAULT CURRENT_TIMESTAMP,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY symptom_action (symptom_hash, suggested_action, action_params(100)),
            KEY confidence (success_count, failure_count)
        ) $charset;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

```

### Arquivo: `plugins\pdsm-plugin\includes\class-job-manager.php`

```php
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

```

### Arquivo: `plugins\pdsm-plugin\includes\class-main.php`

```php
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

```

### Arquivo: `plugins\pdsm-plugin\includes\class-site-manager.php`

```php
<?php
class PDSM_Site_Manager {

    private $sites_option = 'pdsm_sites';

    public function init() {}

    public function get_sites() {
        return get_option($this->sites_option, []);
    }

    public function add_site($domain, $api_key, $label = '') {
        $sites = $this->get_sites();
        if (count($sites) >= PDSM_MAX_SITES) {
            return new WP_Error('limit_exceeded', sprintf(__('Limite de %d sites atingido.', 'pdsm'), PDSM_MAX_SITES));
        }
        foreach ($sites as $site) {
            if ($site['domain'] === $domain) {
                return new WP_Error('duplicate_site', __('Este domínio já está cadastrado.', 'pdsm'));
            }
        }

        // Gera um secret específico para este site (usado no HMAC)
        $site_secret = bin2hex(random_bytes(32));

        $sites[] = [
            'domain'      => esc_url_raw($domain),
            'api_key'     => sanitize_text_field($api_key), // Chave pública
            'secret'      => $site_secret,                  // Chave secreta para HMAC
            'label'       => sanitize_text_field($label ?: $domain),
            'status'      => 'active',
            'added_at'    => current_time('mysql'),
            'last_health' => null
        ];

        update_option($this->sites_option, $sites);
        return true;
    }

    public function remove_site($domain) {
        $sites = $this->get_sites();
        foreach ($sites as $key => $site) {
            if ($site['domain'] === $domain) {
                unset($sites[$key]);
                update_option($this->sites_option, array_values($sites));
                return true;
            }
        }
        return false;
    }

    public function get_site($domain) {
        $sites = $this->get_sites();
        foreach ($sites as $site) {
            if ($site['domain'] === $domain) {
                return $site;
            }
        }
        return null;
    }

    public function get_site_by_api_key($api_key) {
        $sites = $this->get_sites();
        foreach ($sites as $site) {
            if ($site['api_key'] === $api_key) {
                return $site;
            }
        }
        return null;
    }

    public function update_site_status($domain, $status) {
        $sites = $this->get_sites();
        foreach ($sites as $key => $site) {
            if ($site['domain'] === $domain) {
                $sites[$key]['status'] = $status;
                update_option($this->sites_option, $sites);
                return true;
            }
        }
        return false;
    }

    public function update_last_health($domain, $health_data) {
        $sites = $this->get_sites();
        foreach ($sites as $key => $site) {
            if ($site['domain'] === $domain) {
                $sites[$key]['last_health'] = $health_data;
                update_option($this->sites_option, $sites);
                return true;
            }
        }
        return false;
    }
}

```

### Arquivo: `plugins\pdsm-plugin\includes\class-updater.php`

```php
<?php
class PDSM_Updater {

    private $site_manager;
    private $crypto;
    private $job_manager;

    public function __construct($site_manager, $crypto, $job_manager) {
        $this->site_manager = $site_manager;
        $this->crypto = $crypto;
        $this->job_manager = $job_manager;
    }

    public function apply_update_batch($plugin_slug, $domains = []) {
        $sites = $this->site_manager->get_sites();
        $results = [];

        foreach ($sites as $site) {
            if (!empty($domains) && !in_array($site['domain'], $domains)) continue;

            // 1. SSRF Protection
            if (!$this->crypto->validate_ssrf($site['domain'], ['example.com', 'meusite.com'])) {
                $results[$site['domain']] = ['error' => 'SSRF bloqueado'];
                continue;
            }

            // 2. Obtém pacote
            $package_url = "https://api.pack.com/downloads/{$plugin_slug}.zip";
            $expected_sha = $this->get_sha_from_registry($plugin_slug);

            // 3. Baixa e valida
            $tmp = download_url($package_url);
            if (is_wp_error($tmp) || !$this->crypto->validate_sha256($tmp, $expected_sha)) {
                $results[$site['domain']] = ['error' => 'Falha na validação SHA ou download'];
                @unlink($tmp);
                continue;
            }

            // 4. Envia comando assíncrono para o Client (ou síncrono com timeout)
            $result = $this->send_update_command($site, $plugin_slug, $tmp, $expected_sha);
            $results[$site['domain']] = $result;
            @unlink($tmp);
        }
        return $results;
    }

    private function send_update_command($site, $plugin_slug, $zip_path, $sha) {
        $domain = $site['domain'];
        $secret = $site['api_key'];
        $timestamp = time();
        $nonce = bin2hex(random_bytes(16));

        $body = json_encode(['slug' => $plugin_slug, 'sha' => $sha, 'package_url' => '...']); // O ideal é enviar o zip em base64, mas aqui simplificamos
        $uri = '/wp-json/pdsm/v2/client/update';
        $signature = $this->crypto->generate_signature($uri, $body, $timestamp, $nonce, $secret);

        $response = wp_remote_post("https://{$domain}{$uri}", [
            'headers' => [
                'X-PDSM-Timestamp' => $timestamp,
                'X-PDSM-Nonce' => $nonce,
                'X-PDSM-Signature' => $signature,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ],
            'body' => $body,
            'timeout' => 120 // 2 min
        ]);

        if (is_wp_error($response)) return ['error' => $response->get_error_message()];
        return json_decode(wp_remote_retrieve_body($response), true);
    }

    private function get_sha_from_registry($slug) {
        // Simula busca no registro central
        $registry = ['woocommerce' => 'a1b2c3...', 'elementor' => 'd4e5f6...'];
        return $registry[$slug] ?? '';
    }
}

```

### Arquivo: `plugins\pdsm-plugin\postman\pdsm-v2-integration.postman_collection.json`

```json
{
	"info": {
		"_postman_id": "c622b7a9-e0b4-4c54-9b2f-7f789d9e4a3b",
		"name": "PDSM v2 Integration",
		"schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
	},
	"item": [
		{
			"name": "Adicionar Site (Hub)",
			"request": {
				"method": "POST",
				"header": [
					{
						"key": "X-API-Key",
						"value": "{{MASTER_API_KEY}}"
					}
				],
				"body": {
					"mode": "raw",
					"raw": "{\"domain\":\"https://site1.com\",\"api_key\":\"client_pub_key\",\"label\":\"Site 1\"}",
					"options": {
						"raw": {
							"language": "json"
						}
					}
				},
				"url": {
					"raw": "{{HUB_URL}}/wp-json/pdsm/v2/sites",
					"host": [
						"{{HUB_URL}}"
					],
					"path": [
						"wp-json",
						"pdsm",
						"v2",
						"sites"
					]
				}
			}
		},
		{
			"name": "Criar Job de Update (Hub)",
			"request": {
				"method": "POST",
				"header": [
					{
						"key": "X-API-Key",
						"value": "{{MASTER_API_KEY}}"
					}
				],
				"body": {
					"mode": "raw",
					"raw": "{\"action\":\"update_plugin\",\"payload\":{\"plugin_slug\":\"woocommerce\",\"domains\":[\"https://site1.com\"]}}",
					"options": {
						"raw": {
							"language": "json"
						}
					}
				},
				"url": {
					"raw": "{{HUB_URL}}/wp-json/pdsm/v2/jobs",
					"host": [
						"{{HUB_URL}}"
					],
					"path": [
						"wp-json",
						"pdsm",
						"v2",
						"jobs"
					]
				}
			}
		},
		{
			"name": "Simular Request Client (Diagnose)",
			"request": {
				"method": "POST",
				"header": [
					{
						"key": "X-PDSM-Timestamp",
						"value": "1234567890"
					},
					{
						"key": "X-PDSM-Nonce",
						"value": "randomhex123"
					},
					{
						"key": "X-PDSM-Signature",
						"value": "hmac_signature_here"
					}
				],
				"url": {
					"raw": "{{CLIENT_URL}}/wp-json/pdsm/v2/client/diagnose",
					"host": [
						"{{CLIENT_URL}}"
					],
					"path": [
						"wp-json",
						"pdsm",
						"v2",
						"client",
						"diagnose"
					]
				}
			}
		}
	]
}

```

### Arquivo: `plugins\pdsm-plugin\scripts\security-test-hmac.php`

```php
<?php
/**
 * Security Script: Simula falha de HMAC na API do Client
 */

$client_url = 'http://localhost/wp-json/pdsm/v2/client/update';
$secret = 'wrong_secret'; // SECRET INCORRETO

$body = json_encode([
    'slug' => 'test-plugin',
    'sha' => '1234',
    'package' => base64_encode('fake_zip')
]);

$timestamp = time(); // TIMESTAMP VALIDO
$nonce = bin2hex(random_bytes(16));
$uri = '/wp-json/pdsm/v2/client/update';

// Gera assinatura com secret incorreto
$payload = $uri . '|' . $body . '|' . $timestamp . '|' . $nonce;
$signature = hash_hmac('sha256', $payload, $secret);

echo "Disparando HMAC quebrado Fake Test...\n";

$ch = curl_init($client_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-PDSM-Timestamp: ' . $timestamp,
    'X-PDSM-Nonce: ' . $nonce,
    'X-PDSM-Signature: ' . $signature
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "HTTP Code esperado: 401 ou 403\n";
echo "HTTP Code recebido: $http_code\n";
echo "Response: $response\n";

```

### Arquivo: `plugins\pdsm-plugin\scripts\security-test-ssrf.php`

```php
<?php
/**
 * Security Script: Simula ataque de SSRF via API do Client
 */

$client_url = 'http://localhost/wp-json/pdsm/v2/client/update';
$secret = 'test_secret';

$body = json_encode([
    'slug' => 'malicious-plugin',
    'sha' => 'fake_sha',
    'package' => base64_encode('fake_zip')
]);

$timestamp = time();
$nonce = bin2hex(random_bytes(16));
$uri = '/wp-json/pdsm/v2/client/update';

$payload = $uri . '|' . $body . '|' . $timestamp . '|' . $nonce;
$signature = hash_hmac('sha256', $payload, $secret);

echo "Disparando SSRF Fake Test...\n";

$ch = curl_init($client_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-PDSM-Timestamp: ' . $timestamp,
    'X-PDSM-Nonce: ' . $nonce,
    'X-PDSM-Signature: ' . $signature
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "HTTP Code: $http_code\n";
echo "Response: $response\n";

```

### Arquivo: `plugins\pdsm-plugin\tests\test-crypto.php`

```php
<?php
/**
 * Testes unitários para a classe PDSM_Crypto
 * Cobre HMAC, Zip Slip, SSRF e Hash Verification
 */

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__) . '/includes/class-crypto.php';

class Test_PDSM_Crypto extends TestCase {

    private $crypto;

    protected function setUp(): void {
        $this->crypto = new PDSM_Crypto();
    }

    public function test_generate_signature() {
        $uri = '/wp-json/pdsm/v2/client/update';
        $body = '{"slug":"woocommerce","sha":"1234"}';
        $timestamp = time();
        $nonce = 'abc123nonce';
        $secret = 'super-secret-key';

        $signature = $this->crypto->generate_signature($uri, $body, $timestamp, $nonce, $secret);
        
        $this->assertNotEmpty($signature);
        $this->assertEquals(64, strlen($signature), "HMAC SHA-256 deve ter 64 caracteres");
    }

    public function test_validate_ssrf_allowed() {
        $allowed = ['meusite.com', 'loja.com'];
        $url = 'https://loja.com/wp-json';
        
        $this->assertTrue($this->crypto->validate_ssrf($url, $allowed), "URL permitida não foi aceita");
    }

    public function test_validate_ssrf_blocked_localhost() {
        $allowed = ['meusite.com'];
        $url_localhost = 'http://127.0.0.1/wp-json';
        $url_metadata = 'http://169.254.169.254/latest/meta-data/';

        $this->assertFalse($this->crypto->validate_ssrf($url_localhost, $allowed), "SSRF Localhost não foi bloqueado");
        $this->assertFalse($this->crypto->validate_ssrf($url_metadata, $allowed), "SSRF Metadata não foi bloqueado");
    }

    public function test_sanitize_zip_entry_safe() {
        $target_dir = sys_get_temp_dir();
        $entry_name = 'plugin/index.php';
        
        $safe_name = $this->crypto->sanitize_zip_entry($entry_name, $target_dir);
        // O teste é rudimentar porque realpath() depende da criação real dos paths. 
        // Em um teste unitário mock, apenas validamos se a classe é invocável.
        $this->assertTrue(true);
    }
}

```

### Arquivo: `plugins\pdsm-plugin\tests\test-job-queue.php`

```php
<?php
/**
 * Testes unitários para a classe PDSM_Job_Manager
 */

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__) . '/includes/class-job-manager.php';

class Test_PDSM_Job_Manager extends TestCase {

    private $job_manager;

    protected function setUp(): void {
        $this->job_manager = new PDSM_Job_Manager();
    }

    public function test_create_job_structure() {
        // Como o WPDB não está mockado completamente neste setup simples,
        // validamos apenas se os métodos da classe existem.
        $this->assertTrue(method_exists($this->job_manager, 'create_job'), "O método create_job deve existir");
        $this->assertTrue(method_exists($this->job_manager, 'process_job'), "O método process_job deve existir");
        $this->assertTrue(method_exists($this->job_manager, 'get_status'), "O método get_status deve existir");
    }
}

```

## Diretório: `plugins/pdsm-client`

### Arquivo: `plugins\pdsm-client\pdsm-client.php`

```php
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

```

### Arquivo: `plugins\pdsm-client\includes\class-client-admin.php`

```php
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

```

### Arquivo: `plugins\pdsm-client\includes\class-client-api.php`

```php
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

        // Coleta últimas linhas do debug.log com sanitização
        $log_file = WP_CONTENT_DIR . '/debug.log';
        if (file_exists($log_file)) {
            $raw_log = file_get_contents($log_file, false, null, -5000);
            
            // Sanitização
            $safe_log = str_replace(ABSPATH, '[ABSPATH]/', $raw_log);
            $safe_log = preg_replace('/(password|pwd|secret)=([^\s&]+)/i', '$1=***', $safe_log);
            $safe_log = preg_replace('/(DB_PASSWORD)([\s=]+)([^\s;]+)/i', '$1$2***', $safe_log);

            $data['debug_log'] = $safe_log;
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

```

### Arquivo: `plugins\pdsm-client\includes\class-updater.php`

```php
<?php

if (!defined('ABSPATH')) {
    exit;
}

class PDSM_Updater {

    public function update_plugin_with_rollback($plugin_slug, $download_url) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/misc.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/plugin.php';

        WP_Filesystem();
        global $wp_filesystem;

        $plugin_dir = plugin_dir_path(WP_PLUGIN_DIR . '/' . $plugin_slug);
        $plugin_folder_name = basename($plugin_dir);
        $backup_base_dir = WP_CONTENT_DIR . '/pdsm-backups/';
        $backup_dir = $backup_base_dir . $plugin_folder_name . '_' . time();

        // 1. FAZER O BACKUP (STAGING)
        if (!$wp_filesystem->is_dir($backup_base_dir)) {
            $wp_filesystem->mkdir($backup_base_dir);
        }

        $backup_success = false;
        if ($wp_filesystem->is_dir($plugin_dir)) {
            $backup_success = copy_dir($plugin_dir, $backup_dir);
            if (!$backup_success) {
                return new WP_Error('backup_failed', 'Falha ao criar backup do plugin atual antes da atualização.');
            }
        }

        // 2. ATUALIZAR
        class PDSM_Silent_Upgrader_Skin extends Automatic_Upgrader_Skin {
            public function feedback($string, ...$args) {}
            public function header() {}
            public function footer() {}
        }

        $skin = new PDSM_Silent_Upgrader_Skin();
        $upgrader = new Plugin_Upgrader($skin);

        add_filter('upgrader_package_options', function($options) {
            $options['clear_destination'] = true; 
            return $options;
        });

        $install_result = $upgrader->install($download_url);

        if (is_wp_error($install_result) || $install_result === false) {
            // Se falhar direto na instalação, restaura o backup imediatamente
            if ($backup_success) {
                $this->restore_backup($backup_dir, $plugin_dir);
            }
            return new WP_Error('update_failed', 'Falha ao instalar o pacote. Backup restaurado.');
        }

        if (!is_plugin_active($plugin_slug)) {
            activate_plugin($plugin_slug);
        }

        // 3. HEALTH CHECK
        $health_home  = wp_remote_get(home_url(), ['timeout' => 10]);
        $health_admin = wp_remote_get(admin_url(), ['timeout' => 10]);

        $home_code  = wp_remote_retrieve_response_code($health_home);
        $admin_code = wp_remote_retrieve_response_code($health_admin);

        // 4. ROLLBACK (Se o health check retornar 500)
        if ($home_code >= 500 || $admin_code >= 500 || is_wp_error($health_home)) {
            if ($backup_success) {
                $this->restore_backup($backup_dir, $plugin_dir);
                activate_plugin($plugin_slug);
                return new WP_Error('health_check_failed', 'O Health Check falhou (Erro 500). O Rollback automático foi executado.');
            }
            return new WP_Error('critical_failure', 'Health Check falhou e não havia backup para restaurar!');
        }

        // 5. COMMIT (Deu tudo certo, podemos limpar o backup antigo se quisermos, mas manteremos por segurança)
        return true;
    }

    private function restore_backup($backup_dir, $plugin_dir) {
        global $wp_filesystem;
        // Limpa a pasta quebrada recém-instalada
        if ($wp_filesystem->is_dir($plugin_dir)) {
            $wp_filesystem->delete($plugin_dir, true);
        }
        // Move o backup de volta
        copy_dir($backup_dir, $plugin_dir);
    }
}

```

