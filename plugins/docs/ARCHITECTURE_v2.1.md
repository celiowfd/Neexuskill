# Arquitetura Definitiva v2.1 (Hardened Build)

Este documento especifica a topologia corrigida após a auditoria do Dev Master, incorporando Policy Engine, RBAC Estrito, Segregação de Credenciais e Atomic Deployments.

## Diagrama de Orquestração e Execução (v2.1)

```mermaid
flowchart TD
    A[ANTIGRAVITY] -->|Intent Request| IE(Intent Engine)
    
    subgraph HUB [HUB: Governace & Control Plane]
        direction TB
        IE --> PE[Policy Engine]
        PE --> AUTH[Authorization Engine / RBAC]
        AUTH --> RE[Risk Engine]
        RE -->|If High Risk| CONFIRM[Explicit Confirmation]
        RE -->|Approved| JM[Job Manager]
        JM --> EE[Execution Engine]
        
        EE -.-> AL[Audit Logs]
    end

    subgraph RR [Release Registry / PKI]
        direction TB
        META[Release Metadata] --> SIGN[RSA/Ed25519 Sign]
        SIGN --> PUBKEY[Public Key Dist]
    end

    RR -->|Signed Package Info| HUB

    EE -->|HTTPS + HMAC v2 + Nonce| C1(CLIENT 01)
    
    subgraph CLIENT [Fluxo Local do Cliente (Atomic)]
        direction TB
        AUTH_HMAC[Validate HMAC v2 & Secret] --> VP[Verify Signature & SHA]
        VP --> ISOLATED[Extract to Isolated Staging]
        ISOLATED --> SANITIZE[Sanitize Zip Slip]
        SANITIZE --> SWAP{Atomic Swap}
        
        SWAP -->|Success| HC{Health Check}
        HC -->|OK| CM[Commit & Cleanup]
        HC -->|FAIL| RB[Atomic Rollback]
        SWAP -->|Fail| RB
    end
```

## Componentes Chave da v2.1

### 1. Hub: RBAC Engine e Autorização
O código abandonará a matriz booleana simples. O RBAC implementará escopos reais (`sites:read`, `plugins:update`, `jobs:create`).
O `Authorization Engine` garantirá que o Token da IA ou do Operador possui o escopo necessário para a ação solicitada antes da criação do Job.

### 2. Autenticação HMAC v2 e Separação de Credenciais
A chave de identificação não será usada como segredo.
- **Client Side:** O Site Cliente armazena um `Secret` gerado aleatoriamente e um `Key ID`.
- **Hub Side:** O Hub armazena o `Key ID` (público para o Hub) e um hash blindado do `Secret`.
- O payload assinado (HMAC) incluirá: `Metódo HTTP + URI Canônica + Hash do Body + Timestamp + Nonce`.

### 3. Supply Chain e PKI (Public Key Infrastructure)
O **Release Registry** assinará os pacotes com uma chave privada assimétrica.
O **Client** (e não só o Hub) possuirá a Chave Pública (*Root of Trust*).
O processo:
1. Hub envia Metadata + URL Assinada + Assinatura do Registry ao Client.
2. Client baixa de uma *Allowlist Restrita* (SSRF v2 protegido).
3. Client valida a assinatura digital do pacote contra a chave pública.
4. Só então procede com a instalação.

### 4. Deploy Atômico e Rollback Genuíno (Client)
A rotina de backup/rollback não utilizará `copy_dir` recursivo para sobrescrever arquivos.
- A pasta atual `plugin-x` é renomeada para `plugin-x.old` (Backup instantâneo e atômico).
- A nova versão, já sanitizada (Isolated Staging), é renomeada para `plugin-x` (Deploy Atômico).
- Ocorre o *Health Check*. Se falhar, o `plugin-x` novo é deletado, e o `plugin-x.old` é renomeado para `plugin-x` novamente. Sem resíduos e extremamente rápido.
