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
