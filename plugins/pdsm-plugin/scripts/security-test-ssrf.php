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
