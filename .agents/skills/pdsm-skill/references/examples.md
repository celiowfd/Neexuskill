# Exemplos de Uso da Skill

Aqui estão exemplos de como o agente Antigravity deverá processar pedidos do usuário utilizando a API do plugin.

## Cenário 1: Listar Sites

**Usuário:** "Quais sites eu estou gerenciando agora?"

**Ação do Agente:**
O agente fará um request `GET` para `/wp-json/pdsm/v1/sites` passando o `X-API-Key`.

**Resposta do Agente (Simulada):**
"Você tem 2 sites cadastrados atualmente:
- cliente1.com (Status: pending)
- cliente2.com (Status: active)"

---

## Cenário 2: Atualizar um plugin em todos os sites

**Usuário:** "Atualize o plugin WooCommerce em todos os sites da minha rede."

**Ação do Agente:**
O agente fará um request `POST` para `/wp-json/pdsm/v1/update` com o corpo:
```json
{
  "plugin": "woocommerce/woocommerce.php",
  "sites": []
}
```

**Resposta do Agente (Simulada):**
"O comando de atualização para o WooCommerce foi disparado com sucesso para todos os sites. O status retornado foi: success."

---

## Cenário 3: Instalar/Atualizar num site específico

**Usuário:** "Atualize o Elementor apenas no site cliente1.com."

**Ação do Agente:**
O agente fará um request `POST` para `/wp-json/pdsm/v1/update` com o corpo:
```json
{
  "plugin": "elementor/elementor.php",
  "sites": ["cliente1.com"]
}
```

**Resposta do Agente (Simulada):**
"Comando de atualização disparado para o Elementor no site cliente1.com."
