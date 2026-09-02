# SECURITY TEST REPORT

- **RBAC Real:** PASS. Capability `manage_options` ou Master Key agora exigido para ações críticas.
- **BOLA/IDOR:** PASS. Refatorado `get_sites()` e `get_job_status()` para filtrar pelo `X-API-Key`.
- **HMAC:** PASS. (class-crypto.php implementa hash_hmac)
- **SSRF:** BLOCKED. Endpoint de download usa IP local rejection `strpos`, mas necessita de testes ZAP/Burp em staging para prova cabal de bypass.
- **OWASP ZAP / Burp:** BLOCKED (Requer Staging).
