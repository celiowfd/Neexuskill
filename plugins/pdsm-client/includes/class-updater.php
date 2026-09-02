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
