#!/bin/bash

echo "Iniciando a criação da estrutura completa Neexus em .agents/..."

mkdir -p .agents/workflows
mkdir -p .agents/templates
mkdir -p .agents/skills/architecture
mkdir -p .agents/skills/ux-product
mkdir -p .agents/skills/backend
mkdir -p .agents/skills/frontend
mkdir -p .agents/skills/testing
mkdir -p .agents/skills/performance
mkdir -p .agents/skills/devsecops
mkdir -p .agents/skills/observability
mkdir -p .agents/skills/ai-engineering
mkdir -p .agents/skills/agents
mkdir -p .agents/skills/automation
mkdir -p .agents/skills/mcp-automation
mkdir -p .agents/skills/gemini-notebook-research
mkdir -p .agents/skills/neexus-core-brain
mkdir -p .agents/skills/google-ai-android
mkdir -p .agents/skills/growth
mkdir -p .agents/skills/database
mkdir -p .agents/skills/api
mkdir -p .agents/skills/seo
mkdir -p .agents/agents
mkdir -p .agents/checklists
mkdir -p .agents/security
mkdir -p .agents/rules

# ---------------------------------------------------------
# WORKFLOWS
# ---------------------------------------------------------

cat << 'EOF' > .agents/workflows/new-feature.md
# Workflow: Nova Funcionalidade (New Feature)

## Etapas:
1. **Ideia / Demanda**: Compreensão da solicitação do usuário.
2. **Descoberta**: Análise de impacto, requisitos e viabilidade.
3. **Planejamento**: Elaboração do PRD e ADR se necessário.
4. **Design & UX**: Validação de interfaces e usabilidade.
5. **Arquitetura**: Definição da stack e modelagem de dados.
6. **Desenvolvimento**: Implementação das camadas Frontend/Backend.
7. **Testes & QA**: Testes unitários, de integração e e2e.
8. **Deploy**: Lançamento em ambiente de homologação/produção.
EOF

cat << 'EOF' > .agents/workflows/production-deploy.md
# Workflow: Deploy em Produção

1. Verificar status da CI/CD.
2. Executar checklist de Release.
3. Validar Security Gates.
4. Aprovar artefatos.
5. Executar migrações de banco de dados (se houver).
6. Deploy blue/green ou canary.
7. Monitoramento pós-deploy (Observabilidade).
EOF

cat << 'EOF' > .agents/workflows/incident-response.md
# Workflow: Resposta a Incidentes

1. **Detecção**: Alerta via ferramentas de observabilidade.
2. **Triagem**: Classificação da severidade (P1, P2, P3).
3. **Mitigação**: Ações imediatas para estabilizar o sistema.
4. **Resolução**: Correção definitiva do root cause.
5. **Post-mortem**: Documentação do incidente e lições aprendidas.
EOF

cat << 'EOF' > .agents/workflows/refactoring.md
# Workflow: Refatoração

1. Identificar débito técnico.
2. Garantir cobertura de testes da área afetada.
3. Isolar a refatoração em branch específica.
4. Aplicar padrões de design.
5. Validar performance e regressão.
EOF

# ---------------------------------------------------------
# TEMPLATES
# ---------------------------------------------------------

cat << 'EOF' > .agents/templates/prd.md
# Product Requirements Document (PRD)

## Visão Geral
[Descreva o problema e a solução proposta]

## Objetivos
- [Objetivo 1]
- [Objetivo 2]

## Casos de Uso
1. [Caso 1]
2. [Caso 2]

## Requisitos Não-Funcionais
- Performance
- Segurança
EOF

cat << 'EOF' > .agents/templates/adr.md
# Architecture Decision Record (ADR)

## Contexto
[Descreva o contexto arquitetural]

## Decisão
[Qual foi a decisão técnica tomada?]

## Consequências
[Quais os impactos positivos e negativos?]
EOF

cat << 'EOF' > .agents/templates/technical-spec.md
# Technical Specification

## Arquitetura
[Diagramas ou descrição]

## Modelagem de Dados
[Esquemas]

## APIs
[Endpoints e payloads]
EOF

cat << 'EOF' > .agents/templates/definition-of-done.md
# Definition of Done (DoD)

- [ ] Código implementado
- [ ] Testes unitários passando (>80% cobertura)
- [ ] Code review aprovado
- [ ] Pipeline CI/CD verde
- [ ] Documentação atualizada
EOF

# ---------------------------------------------------------
# SKILLS
# ---------------------------------------------------------

cat << 'EOF' > .agents/skills/architecture/SKILL.md
---
name: architecture
description: Princípios de arquitetura de software Neexus
---
# Diretrizes de Arquitetura
Focar em escalabilidade, resiliência e baixo acoplamento.
EOF

cat << 'EOF' > .agents/skills/ux-product/SKILL.md
---
name: ux-product
description: Boas práticas de UX e Produto
---
# UX & Produto
Garantir a melhor experiência para o usuário (Cliente em primeiro lugar).
EOF

cat << 'EOF' > .agents/skills/backend/SKILL.md
---
name: backend
description: Desenvolvimento Backend
---
# Backend
Padrões de API, microsserviços, tratamento de erros e logs.
EOF

cat << 'EOF' > .agents/skills/frontend/SKILL.md
---
name: frontend
description: Desenvolvimento Frontend
---
# Frontend
Acessibilidade, responsividade, state management e performance (Core Web Vitals).
EOF

cat << 'EOF' > .agents/skills/testing/SKILL.md
---
name: testing
description: Qualidade e testes automatizados
---
# Testes
Pirâmide de testes: unitários (base), integração (meio), e2e (topo).
EOF

cat << 'EOF' > .agents/skills/mcp-automation/SKILL.md
---
name: mcp-automation
description: Configuração de MCP, integração Antigravity Stitch e Google IA Android.
---
# Automação MCP e Integrações
Você atua como um agente autônomo (computer use) capaz de acessar e navegar no computador como se fosse um humano.
Use essa skill para:
- Configurar servidores MCP.
- Integrar com o Antigravity Stitch (ferramentas de design).
- Configurar a integração com o Google IA para Android.
Essa skill visa automatizar e agilizar tarefas de setup locais para os projetos.
EOF

cat << 'EOF' > .agents/skills/gemini-notebook-research/SKILL.md
---
name: gemini-notebook-research
description: Transforma o agente em um analista de pesquisa avançado, capaz de processar centenas de horas de vídeos, PDFs, sites e áudios usando o Gemini Notebook (antigo NotebookLM).
---

# Gemini Notebook Research Skill – Análise e Planejamento Estratégico

Você é um especialista em pesquisa e análise com IA, capaz de transformar grandes volumes de conteúdo em conhecimento estruturado e acionável usando o Gemini Notebook.

## Quando usar esta skill
- Analisar horas de vídeos do YouTube sobre um tema específico.
- Criar planos de negócio, marketing ou produto a partir de pesquisas de mercado.
- Consolidar conhecimento de múltiplas fontes (PDFs, sites, áudios, vídeos).
- Gerar apresentações, dashboards ou relatórios a partir de pesquisas.
- Responder perguntas complexas baseadas em um conjunto específico de fontes.

## Como usar esta skill

### 1. Configuração do Gemini Notebook
**Pré-requisitos:**
- Acesso ao Gemini Notebook (notebooklm.google.com).
- Conta Google Workspace ou Gemini.

### 2. Fluxo de Pesquisa e Análise
1. **Definição do Objetivo**: Tema, escopo, perguntas.
2. **Coleta de Fontes**: YouTube, PDFs, links.
3. **Upload**: Enviar para o Gemini Notebook.
4. **Análise**: Resumos, conexões, tendências.
5. **Geração**: Planos de negócios, apresentações, relatórios.
6. **Iteração**: Adicionar mais fontes, refinar análise.

### 3. Automação
Comandos de exemplo:
`Gere um plano de negócio a partir das fontes`
`Identifique tendências nas fontes de vídeo`
`Resuma as ideias principais`

*Utilize esta skill em conjunto com outras skills de agentes para maior eficiência de pesquisa e tomada de decisão estratégica.*
EOF

cat << 'EOF' > .agents/skills/neexus-core-brain/SKILL.md
---
name: neexus-core-brain
description: Centro de conhecimento e aprendizagem da Neexus. Coleta, armazena e disponibiliza dados de todos os projetos, serviços e skills em tempo real, alimentando agentes e decisões com inteligência acumulada.
---

# Neexus Core Brain – Central de Conhecimento e Aprendizagem

Você é o **Neexus Core Brain Agent**, responsável por gerenciar a base de conhecimento central da Neexus, integrando dados de todos os projetos, serviços, skills e agentes em tempo real.

## Missão

**Coletar → Armazenar → Aprender → Disponibilizar → Evoluir**

## Quando usar esta skill

- Para consultar conhecimento acumulado da Neexus (decisões, padrões, erros, métricas).
- Para registrar novos aprendizados, lições, incidentes e boas práticas.
- Para alimentar agentes com contexto histórico e inteligência coletiva.
- Para monitorar a saúde e evolução do ecossistema Neexus.
- Para gerar relatórios de evolução e recomendações automáticas.

## Mecanismos de Aprendizagem
- **Padrões recorrentes:** Identifique erros comuns e crie regras preventivas.
- **Lições aprendidas:** Após incidentes, extraia ações corretivas e registre.
- **Otimização contínua:** Baseado em métricas, sugira melhorias de código, infra, custos.
- **Evolução de skills:** Skills podem ser refinadas com base em feedback de uso.
- **Geração automática de recomendações:** Para novos projetos, use dados históricos para sugerir stack, arquitetura, riscos.

## Comandos para agentes
`/core-brain consulta "Quais decisões de arquitetura foram tomadas para projeto X?"`
`/core-brain registra "Lições do incidente INC-123: adicionar validação de input"`
`/core-brain métricas "Performance do serviço Y nos últimos 7 dias"`
`/core-brain recomenda "Sugestões para novo projeto de e-commerce"`
EOF

cat << 'EOF' > .agents/skills/google-ai-android/SKILL.md
---
name: google-ai-android
description: Especialista em desenvolvimento Android com IA e Gemini. Integra o SDK do Google AI para Android, gerencia modelos, prompts, e otimiza apps com inteligência artificial.
---

# Google AI Android – Desenvolvimento Mobile com IA

Você é um especialista em desenvolvimento Android com foco em integração de IA (Gemini, ML Kit, etc.), capaz de projetar, implementar e otimizar apps com recursos de inteligência artificial.

## Quando usar esta skill

- Desenvolvimento de apps Android com IA (Gemini, ML Kit, TensorFlow Lite).
- Integração de modelos de linguagem, visão, áudio no Android.
- Otimização de prompts e latência para dispositivos móveis.
- Implementação de recursos de IA off-line/on-device.
- Segurança e privacidade em apps Android com IA.

## Como usar esta skill

- **Tamanho do modelo:** Use quantização (INT8, FP16) para reduzir tamanho.
- **Latência:** Execute modelos em background thread (Coroutines/WorkManager).
- **Bateria:** Monitore consumo e evite execuções excessivas.
- **Off-line:** Baixe modelos sob demanda, cache local.
- **Segurança:** Valide inputs para evitar prompt injection, valide permissões (câmera, microfone).

## Exemplo de Fluxo Completo

**Cenário:** App de tradução de textos com Gemini + OCR.
1. Usuário tira foto de um texto.
2. ML Kit OCR extrai o texto.
3. Gemini traduz para o idioma desejado.
4. Resultado é exibido na tela.
5. Histórico é salvo localmente (Room).
6. Uso de IA é monitorado (Firebase Performance).
EOF

# ---------------------------------------------------------
# AGENTS
# ---------------------------------------------------------

cat << 'EOF' > .agents/agents/fullstack.md
# Agente Fullstack Neexus
Responsável por implementar soluções end-to-end, respeitando as camadas de backend, frontend e banco de dados.
EOF

cat << 'EOF' > .agents/agents/qa.md
# Agente QA Neexus
Responsável por criar, executar e validar testes automatizados e manuais, garantindo a qualidade das entregas.
EOF

# ---------------------------------------------------------
# CHECKLISTS
# ---------------------------------------------------------

cat << 'EOF' > .agents/checklists/api.md
# Checklist de API
- [ ] Autenticação/Autorização validada
- [ ] Rate limiting configurado
- [ ] Paginação em endpoints de listagem
- [ ] Payload validado
EOF

cat << 'EOF' > .agents/checklists/database.md
# Checklist de Banco de Dados
- [ ] Índices criados
- [ ] Migrations testadas (up/down)
- [ ] Queries otimizadas
EOF

cat << 'EOF' > .agents/checklists/release.md
# Checklist de Release
- [ ] Changelog atualizado
- [ ] Variáveis de ambiente configuradas
- [ ] Scripts de rollback preparados
EOF

cat << 'EOF' > .agents/checklists/production.md
# Checklist de Produção
- [ ] Logs centralizados
- [ ] Alertas configurados
- [ ] Backups ativos
EOF

# ---------------------------------------------------------
# SECURITY & RULES
# ---------------------------------------------------------

cat << 'EOF' > .agents/security/ai-security.md
# AI Security
Proteção contra prompt injection, vazamento de dados via LLM, etc.
EOF

cat << 'EOF' > .agents/security/lgpd.md
# Adequação LGPD
Tratamento de dados sensíveis, consentimento, direito ao esquecimento.
EOF

cat << 'EOF' > .agents/security/multi-tenancy.md
# Segurança Multi-tenancy
Isolamento lógico/físico de dados entre clientes.
EOF

cat << 'EOF' > .agents/rules/security-gates.md
# Security Gates
Nenhum código vai para produção sem passar pelo Sonar, verificação de dependências e SAST/DAST.
EOF

cat << 'EOF' > .agents/rules/anti-vibecoding.md
# Anti-Vibecoding
Não codar no escuro. Seguir o ciclo: planejamento, testes e execução sistemática.
EOF

echo "Estrutura Neexus criada com sucesso!"
chmod +x .agents/setup_neexus_full.sh 2>/dev/null || true
EOF
