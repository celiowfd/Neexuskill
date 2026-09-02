<?php
class PDSM_Crypto {

    /**
     * Verifica assinatura HMAC-SHA256
     */
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
