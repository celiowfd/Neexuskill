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
