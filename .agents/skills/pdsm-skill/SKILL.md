# Skill: Pack de Sites Manager v2.0 (Security-Gated)

## Instruções para o Agente
Esta skill permite orquestrar atualizações/instalações, respeitando rigorosamente as políticas de segurança do Hub.

### Configuração Obrigatória
- **HUB_URL**: URL do site central.
- **CLIENT_SECRET**: Chave secreta para gerar HMAC.

### Geração de HMAC
Para cada requisição REST, o agente DEVE incluir os headers:
- `X-PDSM-Timestamp`: Timestamp UNIX atual.
- `X-PDSM-Nonce`: String aleatória de 16 bytes (hex).
- `X-PDSM-Signature`: `hash_hmac('sha256', $uri . '|' . $body . '|' . $timestamp . '|' . $nonce, $CLIENT_SECRET)`

### Fluxo de Execução
1. **Intenção**: Identifique a ação (update, install, list).
2. **Criação de Job**: Envie `POST /pdsm/v2/jobs` com `action` e `payload`.
   - Exemplo: `{ "action": "update_plugin", "payload": { "plugin_slug": "woocommerce", "domains": [] } }`
3. **Polling**: Use `GET /pdsm/v2/jobs/{id}` para monitorar o progresso.
4. **Resposta**: Informe ao usuário o resultado consolidado.

### Restrições (RBAC)
- Se o Hub retornar `403`, informe ao usuário: "Permissão negada pelo administrador."
- Nunca tente burlar políticas de execução.

### Exemplo de Comando
**Usuário:** "Atualize o WooCommerce em todos os sites."  
**Ação Interna:**
```javascript
const nonce = randomHex(16);
const timestamp = Math.floor(Date.now()/1000);
const body = JSON.stringify({ action: 'update_plugin', payload: { plugin_slug: 'woocommerce', domains: [] } });
const signature = hmac_sha256('/pdsm/v2/jobs|' + body + '|' + timestamp + '|' + nonce, SECRET);
// Envia POST com headers...
// Aguarda job_id e faz polling a cada 5s até status != 'processing'.
// Exibe resultado.
```

### Comandos do Agente Especialista

- **"Diagnostique o site meusite.com"** → Chama `POST /pdsm/v2/agent/diagnose` e exibe os sintomas.
- **"Resolva os problemas do site meusite.com"** → Chama `POST /pdsm/v2/agent/heal` com `auto=true`.
- **"Quais soluções o agente aprendeu?"** → Chama `GET /pdsm/v2/agent/knowledge` e lista o histórico de aprendizado.
