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
