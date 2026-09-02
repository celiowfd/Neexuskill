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
