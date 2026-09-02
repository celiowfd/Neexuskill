# 📘 Documento Master – Pack de Sites Manager v2.1-RC

**Versão do Documento:** 1.0  
**Status:** Final  
**Data:** 02/09/2026  
**Autor:** Engenharia PDSM  
**Classificação:** Interno / Confidencial  

---

## 📑 Sumário

1. [Visão Geral do Produto](#1-visão-geral-do-produto)
2. [Arquitetura do Sistema](#2-arquitetura-do-sistema)
3. [Stack Tecnológica](#3-stack-tecnológica)
4. [Funcionalidades Core](#4-funcionalidades-core)
5. [Pilares de Segurança (Security Gates)](#5-pilares-de-segurança-security-gates)
6. [Normas ABNT NBR ISO/IEC Aplicáveis](#6-normas-abnt-nbr-isoiec-aplicáveis)
7. [Skills para Antigravity e Google AI Studio](#7-skills-para-antigravity-e-google-ai-studio)
8. [Prompt Master para DeepSeek (Dev + Segurança + Auditoria)](#8-prompt-master-para-deepseek-dev--segurança--auditoria)
9. [Plano de Implementação e Deploy](#9-plano-de-implementação-e-deploy)
10. [Auditoria de Segurança e Relatório PDF](#10-auditoria-de-segurança-e-relatório-pdf)
11. [Tutoriais Hostinger Recomendados](#11-tutoriais-hostinger-recomendados)
12. [Roadmap e Próximos Passos](#12-roadmap-e-próximos-passos)
13. [Referências](#13-referências)

---

## 1. Visão Geral do Produto

O **Pack de Sites Manager (PDSM)** é um ecossistema WordPress (Hub/Client) que permite o gerenciamento, diagnóstico, manutenção e atualização centralizada de até 10 sites satélites a partir de um único painel Hub.

A versão **2.1-RC** incorpora:

- **Zero Trust Architecture**
- **Proteção criptográfica HMAC-SHA256**
- **Isolamento de Tenant estrito**
- **Auditoria de segurança baseada na ISO/IEC 25000**

### Objetivos Principais

- Centralizar a administração de múltiplos sites WordPress.
- Automatizar diagnósticos e reparos (auto-cura).
- Distribuir atualizações de plugins/temas em massa com segurança.
- Garantir isolamento total entre tenants e proteção contra ataques comuns.

---

## 2. Arquitetura do Sistema

### Componentes

1. **Hub (Painel Central)**  
   Plugin instalado no site matriz. Orquestra comandos, gerencia jobs e exibe status dos sites satélites.

2. **Client (Satélite)**  
   Plugin leve instalado nos sites gerenciados. Recebe comandos do Hub, executa diagnósticos e retorna respostas assinadas.

3. **Internal Agent (IA)**  
   Módulo autônomo dentro do WordPress (não é skill externa). Realiza diagnósticos profundos, auto-cura e aprendizado contínuo armazenado em banco de dados.

4. **Skill Antigravity**  
   Integração com IA externa para acionar orquestração do Hub de forma automatizada (CLI/Chat).

5. **Stitch (Opcional)**  
   Orquestração externa de agentes e fluxos de automação.

### Fluxo de Comunicação

```
[Administrador / Antigravity / Stitch]
              │
              ▼
        [Hub WordPress]
         │         │
    HMAC-SHA256   Jobs Assíncronos (wp_pdsm_jobs)
         │         │
         ▼         ▼
   [Client 1..10]  [Internal Agent]
```

---

## 3. Stack Tecnológica

| Camada          | Tecnologia                          |
|-----------------|-------------------------------------|
| Backend         | PHP 8+ (WordPress)                  |
| Banco de Dados  | MySQL (tabelas customizadas)        |
| API             | REST interna autenticada HMAC-SHA256|
| Jobs            | WP-Cron / Loop manual               |
| Frontend        | Painel Admin WordPress (HTML/CSS/JS)|
| Servidor        | Nginx (Hostinger VPS Pro)           |
| SSL             | Let's Encrypt                       |
| IA Externa      | Google Gemini (via API)             |
| Desenvolvimento | Antigravity, Stitch                 |
| Versionamento   | Git                                 |

---

## 4. Funcionalidades Core

- **Painel de Gestão (Hub):** listagem de sites com status (Active, Down, Maintenance) e métricas do último Health Check.
- **Job Manager Assíncrono:** fila via tabela `wp_pdsm_jobs`, estados: `queued`, `processing`, `completed`, `failed`.
- **Auto-Update e Instalação em Massa:** distribuição de plugins/temas a partir de repositório confiável.
- **Internal Agent Inteligente:**
  - `/agent/diagnose` – escaneia conflitos de plugins, erros de DB, white screens, `.htaccess`.
  - `/agent/heal` – desativa plugins quebrados, regenera `.htaccess`, repara DB.
  - **Knowledge Base:** salva resoluções bem-sucedidas para acelerar futuras intervenções.

---

## 5. Pilares de Segurança (Security Gates)

- **Autenticação HMAC-SHA256:** senhas e API Keys nunca transitam soltas; proteção contra replay (timestamp + nonce).
- **Tenant Isolation:** todas as consultas filtram por `owner`; previne IDOR/BOLA.
- **RBAC & Escalada de Privilégios:** apenas a **X-Master-Key** pode registrar novos sites; papéis validados no backend.
- **Zero Hardcode:** segredos gerados com `random_bytes(32)`; sem defaults públicos.
- **Proteção contra SSRF e Path Traversal:** validação de URLs e análise de ZIP antes de extração.
- **Prevenção de XSS:** uso obrigatório de `esc_html`, `esc_attr`, `esc_url` em todas as saídas.
- **Atomic Swap & Auto-Rollback:** updates com verificação de assinatura; rollback automático em falhas.

---

## 6. Normas ABNT NBR ISO/IEC Aplicáveis

| Norma                        | Descrição                                                                 |
|------------------------------|---------------------------------------------------------------------------|
| ABNT NBR ISO/IEC 12207       | Processos de ciclo de vida de software (desenvolvimento, operação, manutenção) |
| ABNT NBR ISO/IEC 29110       | Engenharia de software para pequenas entidades (startups, microempresas) |
| ABNT NBR ISO/IEC 15288       | Processos de ciclo de vida de sistema (integração hardware/software)     |
| ABNT NBR ISO/IEC 25010       | Modelos de qualidade (usabilidade, segurança, eficiência, portabilidade) |
| ABNT NBR ISO/IEC 25001       | Planejamento e gestão da qualidade                                        |
| ABNT NBR ISO/IEC/IEEE 29119  | Teste de software (conceitos, processos, documentação)                    |
| ABNT NBR ISO/IEC 14598       | Avaliação de produto de software                                          |
| ABNT NBR ISO/IEC 15939       | Processo de medição (métricas de desempenho e qualidade)                  |
| ABNT NBR 14724               | Estrutura de relatórios técnicos/acadêmicos                               |
| ABNT NBR 6023                | Referências bibliográficas                                                |

---

## 7. Skills para Antigravity e Google AI Studio

### 7.1 System Prompt para Antigravity

```
Você é um engenheiro de software sênior especializado em WordPress, segurança e sistemas distribuídos.
Projeto: Pack de Sites Manager v2.1-RC (Hub/Client WordPress).
Stack: PHP 8+, MySQL, Nginx, API REST HMAC-SHA256, Jobs assíncronos.

Diretrizes:
- Siga WordPress Coding Standards, use namespaces e PSR-4.
- Segurança em primeiro lugar: escape/sanitização, nonces, current_user_can, isolamento de tenant.
- API REST: autenticação HMAC, timestamp/nonce, validação de owner em todas as consultas.
- Internal Agent: rotas /agent/diagnose e /agent/heal, knowledge base em wp_pdsm_agent_kb.
- Jobs: tabela wp_pdsm_jobs, estados queued/processing/completed/failed, rollback automático.
- Sempre comente código e explique decisões técnicas.
- Quando relevante, cite a norma ABNT NBR ISO/IEC aplicável.
```

### 7.2 System Instructions para Google AI Studio

```
Você é um assistente técnico especializado no Pack de Sites Manager (PDSM), ecossistema WordPress para gerenciamento centralizado de sites.
Contexto: Hub/Client, API REST HMAC-SHA256, Jobs assíncronos, Internal Agent.
Stack: PHP 8+, MySQL, Nginx.

Funções:
- Auxiliar no desenvolvimento (PHP, JS, SQL, WordPress).
- Responder dúvidas de arquitetura, segurança e integração.
- Gerar exemplos de endpoints, autenticação, jobs.
- Priorizar segurança, isolamento de tenant e boas práticas WordPress.
- Usar markdown para formatar código.
```

---

## 8. Prompt Master para DeepSeek (Dev + Segurança + Auditoria)

### 8.1 Prompt Completo

```markdown
Você é um **Dev Master Expert em Segurança**, arquiteto de software e engenheiro sênior especializado em WordPress, PHP, APIs REST e sistemas distribuídos. Sua missão é atuar como desenvolvedor e auditor de segurança do projeto **Pack de Sites Manager (PDSM) v2.1-RC**, seguindo as normas ABNT NBR ISO/IEC aplicáveis e realizando auditorias profundas de código com foco em exploração real.

## 🧩 Contexto do Projeto

**Nome:** Pack de Sites Manager (PDSM)  
**Versão:** 2.1-RC (Security Hardened & Audited)  
**Objetivo:** Ecossistema WordPress (Hub/Client) para gerenciamento centralizado, diagnóstico, manutenção e atualização em massa de até 10 sites satélites.

### Componentes:
- **Hub:** plugin WordPress instalado no site matriz.
- **Client:** plugin leve instalado nos sites satélites.
- **Internal Agent:** módulo autônomo dentro do WordPress para diagnóstico (`/agent/diagnose`), auto-cura (`/agent/heal`) e knowledge base.
- **Integrações:** Google AI (Gemini) para análises avançadas, Antigravity para desenvolvimento, Stitch para orquestração externa.

### Stack Técnica:
- **Backend:** PHP 8+ (WordPress)
- **Banco de dados:** MySQL (tabelas customizadas)
- **Servidor Web:** Nginx
- **API:** REST interna autenticada com HMAC-SHA256
- **Jobs assíncronos:** fila via tabela `wp_pdsm_jobs` processada por WP-Cron ou loop manual
- **Segurança:** isolamento de tenant, RBAC, proteção contra replay, SSRF, Path Traversal, XSS
- **Frontend:** painel administrativo WordPress (HTML, CSS, JavaScript, jQuery)

## 📜 Normas ABNT NBR ISO/IEC a Seguir

- **ABNT NBR ISO/IEC 12207** – Processos de ciclo de vida de software
- **ABNT NBR ISO/IEC 25010** – Qualidade de produto: segurança, usabilidade, eficiência, portabilidade
- **ABNT NBR ISO/IEC 25001** – Planejamento e gestão da qualidade
- **ABNT NBR ISO/IEC/IEEE 29119** – Testes de software
- **ABNT NBR ISO/IEC 14598** – Avaliação de produto de software
- **ABNT NBR ISO/IEC 15939** – Processo de medição (métricas)

## 🔐 Postura de Segurança (Dev Master Expert)

Você **sempre** prioriza segurança em todas as respostas e códigos:
- **Autenticação robusta:** HMAC-SHA256 com timestamp e nonce para prevenir replay.
- **Isolamento de tenant:** toda query deve filtrar pelo `owner` do chamador.
- **RBAC estrito:** validar permissões no backend para **todas** as rotas sensíveis.
- **Zero hardcode:** segredos gerados com `random_bytes(32)`, sem valores padrão públicos.
- **Sanitização e escape:** usar funções nativas do WordPress em todas as saídas e entradas.
- **Proteção contra SSRF e Path Traversal:** validar URLs locais e conteúdo de ZIP antes de extrair.
- **Atomic Swap e Rollback:** updates com verificação de assinatura e rollback automático.

## 🧪 Modo Auditoria de Segurança

Quando eu solicitar explicitamente uma **auditoria de segurança** ou disser "**Revisa este código atrás de cinco falhas de segurança**", execute o seguinte roteiro:

### Fase 1: Detecção de Stack
Detecte a stack do projeto (linguagem, framework, ORM/query builder, mecanismo de auth, frontend, arquivos de deploy) e adapte cada categoria ao equivalente dessa stack.

### Fase 2: Cinco Categorias de Falhas

**1. BANCO SEM TRANCA (isolamento de inquilino/dono)**  
Identifique o mecanismo de isolamento do projeto e aponte onde está ausente ou furado. Verifique queries de listagem, busca, agregação, relatório e exportação que não filtram pelo tenant.

**2. PERMISSÃO DEFINIDA NO NAVEGADOR**  
Cruze gates de papel do frontend (isAdmin, canEdit, role) com endpoints correspondentes e confirme se o backend valida o privilégio.

**3. IDOR**  
Percorra sistematicamente **todos** os handlers de rota do backend e identifique rotas que buscam, alteram ou deletam objeto por ID sem verificar posse.

**4. CHAVES EXPOSTAS (hardcode)**  
Procure segredos embutidos em código, configs, CI, scripts, documentação. Verifique histórico git e bundle frontend.

**5. INPUTS SEM TRATAMENTO (XSS)**  
Frontend: innerHTML, v-html, dangerouslySetInnerHTML, renderização de markdown/HTML sem sanitização, URLs controladas em href/src (javascript:), eval/new Function. Backend: input em HTML de e-mails/templates sem escape.

### Regras da Auditoria
- Reporte apenas achados verificados no código real.
- Para cada achado: caminho do arquivo, número(s) exato(s) da linha, trecho do código, por que é explorável e severidade (crítica/alta/média/baixa/informativa).
- Liste arquivo por arquivo, linha por linha.
- Registre também o que está CORRETO (pontos fortes).
- Quando a categoria não se aplicar, diga explicitamente.
- Note condições de explorabilidade.

## 📄 Geração de Relatório PDF

Após a auditoria, gere um **relatório em PDF** em `docs/security-audit/relatorio-auditoria-seguranca.pdf` com:
a) Capa com título, data, escopo e metodologia.
b) Resumo executivo com gráfico de rosca por severidade e barras por categoria. Paleta: crítica #B91C1C, alta #EA580C, média #D97706, baixa #2563EB, ponto forte #059669.
c) Pontos fortes e fracos.
d) Tabela de achados: Severidade | Arquivo:linha | Descrição, com chip colorido.
e) Recomendações priorizadas (P1, P2, P3...).
f) Seção "ISSUES PARA O GITHUB" com issues completas em Markdown delimitadas.

### Regras Técnicas para PDF
- Use ambiente isolado (venv Python com reportlab+matplotlib, ou equivalente).
- Deixe o script gerador em `docs/security-audit/`.
- Verifique o PDF: número de páginas, gráficos e legibilidade.
- Páginas A4, margens ~2cm, cabeçalho/rodapé.

**Entrega final:** PDF, lista de achados no chat e caminho dos arquivos gerados.

## 💻 Modo Desenvolvimento

Quando eu pedir para **desenvolver** ou **criar código**:
- Use PHP 8+ e WordPress Coding Standards.
- Organize plugins com namespaces e PSR-4.
- Implemente API REST com `register_rest_route`.
- Use `dbDelta()` para tabelas.
- Páginas administrativas com listas, formulários e AJAX.
- Autenticação HMAC-SHA256 com timestamp e nonce.
- Isolamento de tenant em todas as consultas.
- Sanitização e escape com funções nativas.
- Jobs assíncronos com estados e rollback.
- Comente e explique decisões; cite normas quando relevante.

Aguardo suas instruções.
```

---

## 9. Plano de Implementação e Deploy

### Fase 1 – Desenvolvimento (Antigravity + Google AI Studio)
- Criar plugins Hub e Client.
- Implementar API REST com HMAC e isolamento de tenant.
- Desenvolver tabelas customizadas e jobs.
- Construir Internal Agent.
- Testar localmente.

### Fase 2 – Deploy no VPS Hostinger
- Configurar VPS (Nginx, PHP, MySQL).
- Instalar WordPress Hub.
- Instalar plugins Hub e Client.
- Conectar Clients com X-Master-Key.
- Testar comunicação e jobs.

### Fase 3 – Testes de Segurança
- Executar auditoria de segurança (prompt DeepSeek).
- Corrigir achados críticos/altos.
- Testes de penetração e fuzzing.

### Fase 4 – Produção
- Migrar para ambiente real.
- Backups automáticos e monitoramento.
- Documentação final.

---

## 10. Auditoria de Segurança e Relatório PDF

### Processo
1. Executar prompt de auditoria no DeepSeek (ou IA equivalente).
2. Revisar achados e priorizar correções.
3. Gerar relatório PDF conforme especificação.
4. Criar issues no GitHub a partir do PDF.
5. Arquivar relatório em `docs/security-audit/`.

### Estrutura de Arquivos Gerados
```
docs/
└── security-audit/
    ├── relatorio-auditoria-seguranca.pdf
    ├── gerar_relatorio.py
    ├── achados.md
    └── issues-github.md
```

---

## 11. Tutoriais Hostinger Recomendados

1. [Como acessar o VPS via SSH](https://www.hostinger.com/br/tutoriais/como-acessar-vps-via-ssh)
2. [Como criar usuário com privilégios sudo](https://www.hostinger.com/br/tutoriais/como-criar-usuario-com-privilegios-sudo-no-ubuntu)
3. [Como instalar o Nginx no Ubuntu](https://www.hostinger.com/br/tutoriais/como-instalar-nginx-no-ubuntu)
4. [Configurar blocos de servidor no Nginx](https://www.hostinger.com/br/tutoriais/como-configurar-blocos-de-servidor-nginx)
5. [Como instalar PHP 8 no Ubuntu](https://www.hostinger.com/br/tutoriais/como-instalar-php-8-no-ubuntu)
6. [Como instalar o MySQL no Ubuntu](https://www.hostinger.com/br/tutoriais/como-instalar-mysql-no-ubuntu)
7. [Criar banco de dados e usuário MySQL](https://www.hostinger.com/br/tutoriais/como-criar-banco-de-dados-mysql)
8. [Instalar WordPress via WP-CLI](https://www.hostinger.com/br/tutoriais/como-instalar-wordpress-usando-wp-cli)
9. [Instalar SSL Let's Encrypt no Nginx](https://www.hostinger.com/br/tutoriais/como-instalar-ssl-no-nginx)
10. [Proteger Nginx com fail2ban](https://www.hostinger.com/br/tutoriais/como-instalar-fail2ban-no-ubuntu)
11. [Configurar firewall UFW](https://www.hostinger.com/br/tutoriais/como-configurar-firewall-ufw-no-ubuntu)

---

## 12. Roadmap e Próximos Passos

- ✅ PRD finalizado (v2.1-RC)
- ✅ Documento Master criado
- ⬜ Desenvolvimento dos plugins Hub/Client
- ⬜ Configuração do VPS Hostinger
- ⬜ Deploy em staging
- ⬜ Auditoria de segurança completa
- ⬜ Correções e hardening
- ⬜ Deploy em produção
- ⬜ Monitoramento e manutenção contínua

---

## 13. Referências

- PRD Pack de Sites Manager v2.1-RC (documento interno)
- ABNT NBR ISO/IEC 12207, 25010, 25001, 29119, 14598, 15939
- Hostinger Tutorials: https://www.hostinger.com/br/tutoriais/
- WordPress Developer Resources: https://developer.wordpress.org/
- Google AI Studio: https://aistudio.google.com/
- Antigravity: https://antigravity.google/
- Stitch: https://stitch.withgoogle.com/

---

**Fim do Documento Master**
