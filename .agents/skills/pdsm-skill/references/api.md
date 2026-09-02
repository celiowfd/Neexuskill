# API Documentation: Pack de Sites Manager

Esta documentação descreve como se comunicar com o plugin WordPress PDSM.

## Endpoint Base
`https://<SEU_DOMINIO_PRINCIPAL>/wp-json/pdsm/v1/`
Exemplo: `https://seu-painel.com/wp-json/pdsm/v1/`

## Autenticação
Todos os endpoints exigem o envio de um Header HTTP para validação.
- **Header:** `X-API-Key`
- **Valor:** Chave de API de 24 caracteres gerada no painel de controle do plugin.

---

## Endpoints

### 1. Listar Sites
Retorna a lista de sites gerenciados e seus status.

- **Método:** `GET`
- **Caminho:** `/sites`
- **Exemplo de Resposta (200 OK):**
```json
{
  "cliente1.com": {
    "domain": "cliente1.com",
    "api_key": "abc123def456ghi789jkl012",
    "status": "pending"
  }
}
```

### 2. Disparar Atualização
Envia uma instrução de atualização de um plugin específico para a rede.

- **Método:** `POST`
- **Caminho:** `/update`
- **Corpo da Requisição (JSON):**
```json
{
  "plugin": "nome-do-plugin/nome-do-plugin.php",
  "sites": ["cliente1.com", "cliente2.com"]
}
```
*Se `sites` for uma lista vazia, o plugin deverá entender que é para atualizar em todos os domínios possíveis.*

- **Exemplo de Resposta (200 OK):**
```json
{
  "status": "success",
  "message": "Atualização disparada com sucesso",
  "plugin": "nome-do-plugin/nome-do-plugin.php",
  "sites": ["cliente1.com", "cliente2.com"]
}
```
