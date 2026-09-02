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
