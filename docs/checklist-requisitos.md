# Checklist de requisitos — Trabalho Individual II

Cada item do enunciado e onde ele é atendido.

## Requisitos obrigatórios

| # | Requisito | Atendido | Onde |
|---|---|:---:|---|
| 1 | Separação entre interface, lógica de aplicação e acesso a dados | Sim | `src/Controllers` + `src/Views` / `src/Services` / `src/Repositories` |
| 1 | Arquitetura documentada | Sim | `docs/arquitetura.md` (MVC em camadas) |
| 2 | Entidade principal persistente | Sim | `agendamentos` (`database/migrations/003_criar_agendamentos.sql`) |
| 2 | Criar, ler, atualizar e excluir | Sim | `POST`, `GET`, `PATCH`, `DELETE /api/agendamentos`; páginas `/agendar`, `/meus-agendamentos`, `/admin/agendamentos` |
| 2 | Consultas parametrizadas | Sim | `src/Repositories/Repositorio.php` (prepared statements nativos) |
| 2 | Constraints PK, FK, UNIQUE, NOT NULL | Sim | Migrações 001 a 004; `docs/modelo-de-dados.md` |
| 2 | Script de criação do banco | Sim | `database/schema.sql` e `database/migrations/` |
| 3 | Autenticação por sessão segura | Sim | `src/Core/SessaoNativa.php`, `src/Services/SessaoUsuario.php` |
| 3 | Senha com hash seguro | Sim | `password_hash` bcrypt custo 12 (`src/Services/AuthService.php`) |
| 3 | Logout funcional | Sim | `POST /sair`, `POST /api/auth/logout` |
| 3 | Proteção de rotas autenticadas | Sim | Opções `auth`/`perfil` em `config/rotas.php`, conferidas em `src/Core/App.php` |
| 4 | Dois perfis coerentes com o domínio | Sim | cliente e administrador |
| 4 | Matriz de permissões no relatório | Sim | `docs/arquitetura.md`, seção "Matriz de permissões" |
| 5 | Validação de tipos | Sim | `src/Core/Validador.php` (texto, inteiro, decimal, booleano, e-mail, data) |
| 5 | Validação de tamanho | Sim | Regras `min`/`max` em todos os campos |
| 5 | Sanitização | Sim | `Validador::sanitizar` (espaços, caracteres de controle) e escape na saída (`View::e`) |
| 5 | Mensagens de erro estruturadas | Sim | `{"erro":{"status","mensagem","campos"}}`; mensagem por campo nos formulários |
| 6 | Proteção contra SQL Injection | Sim | Prepared statements; testes em `SegurancaTest` |
| 6 | Proteção contra XSS refletido | Sim | `View::e()` e CSP `script-src 'none'`; testes em `SegurancaTest` |
| 6 | Proteção contra CSRF | Sim | `src/Core/Csrf.php`, cookie `SameSite=Lax`; testes em `SegurancaTest` |
| 6 | Proteção contra acesso não autorizado | Sim | 401/403/404 conforme a matriz; testes em `SegurancaTest` |
| 6 | Evidências dos testes | Sim | `docs/testes.md`, `docs/evidencias/` |
| 7 | Código HTTP, mensagem legível, detalhes só em desenvolvimento | Sim | `src/Core/ErrorHandler.php`; `docs/evidencias/erro-interno-dev-vs-producao.txt` |
| 8 | Documentação de todos os endpoints | Sim | `docs/api.md` (método, URL, parâmetros, exemplos, códigos) |
| 9 | Pelo menos 5 testes automatizados | Sim | 82 testes (`tests/`) |
| 9 | Casos válidos e inválidos | Sim | `docs/testes.md`, tabela "Casos válidos e inválidos" |
| 10 | Log de login bem-sucedido | Sim | Evento `login.sucesso` |
| 10 | Log de falha de autenticação | Sim | Eventos `login.falha` e `login.bloqueado` |
| 10 | Log de criação de entidade | Sim | `agendamento.criado`, `modelo.criado`, `usuario.criado` |
| 10 | Log de erro interno | Sim | `erro.interno` |
| 10 | Logs com timestamp | Sim | `[2026-10-16T15:50:06-03:00]` em cada linha (`src/Core/Logger.php`) |

## Métricas de avaliação

### A. Arquitetura e organização

| Item | Atendido | Onde |
|---|:---:|---|
| Separação frontend/backend | Sim | `public/` + `src/Views` / `src/` |
| Diretórios organizados | Sim | Estrutura no `README.md` |
| Camada de acesso a dados isolada | Sim | `src/Repositories` (único lugar com SQL) |
| Configuração separada do código | Sim | `.env` + `config/` |
| Variáveis sensíveis fora do código-fonte | Sim | `.env` no `.gitignore`; admin criado por `bin/criar-admin.php` |
| Script SQL fornecido | Sim | `database/schema.sql` |
| README de execução | Sim | `README.md` |
| Documentação da arquitetura | Sim | `docs/arquitetura.md` |
| Nomenclatura consistente | Sim | Português em todo o domínio; classes em PascalCase, métodos em camelCase, tabelas e colunas em snake_case |
| Ausência de arquivos temporários | Sim | `.gitignore` cobre `logs/*.log`, `vendor/`, `tools/`, caches |
| Ausência de código morto evidente | Sim | Páginas `.html` antigas removidas (substituídas pelos templates) |
| Uso consistente de namespaces | Sim | `Toyota\Core`, `Toyota\Repositories`, `Toyota\Services`, `Toyota\Controllers\Web`, `Toyota\Controllers\Api` (PSR-4) |
| Tratamento centralizado de erros | Sim | `src/Core/ErrorHandler.php` |
| Reutilização de componentes | Sim | Layout, ficha única, parciais, `Validador`, `Repositorio` (ver `docs/arquitetura.md`) |
| Estrutura compatível com o padrão descrito | Sim | Camadas do diagrama = pastas de `src/` |

### B. Banco de dados

| Item | Atendido | Onde |
|---|:---:|---|
| PK em todas as tabelas | Sim | As 5 tabelas e `migracoes` |
| FKs corretas | Sim | `agendamentos.usuario_id` (CASCADE) e `agendamentos.modelo_id` (RESTRICT) |
| Constraints adequadas | Sim | UNIQUE (e-mail, slug, migração), NOT NULL, CHECK (preço, potência, consumo, nome), ENUM |
| Tipos apropriados | Sim | DECIMAL para preço, DATE para data, ENUM para status/período, UNSIGNED para quantidades |
| Índices relevantes | Sim | 5 índices além das PKs/UNIQUE, conferidos com `EXPLAIN` |
| Script executável sem erros | Sim | Rodado do zero a cada execução dos testes de integração |

### C. Segurança

| Item | Atendido | Onde |
|---|:---:|---|
| Prepared statements | Sim | `Repositorio::executar` |
| Escaping de saída HTML | Sim | `View::e()` em todos os templates |
| Proteção CSRF | Sim | Formulários e API |
| Controle de acesso efetivo | Sim | Rotas + 404 em recurso alheio + perfil lido do banco |
| Testes de segurança documentados | Sim | 6 categorias em `docs/testes.md`: SQL Injection, XSS, CSRF, sem login, perfil insuficiente, sessão reutilizada |

### D. Autenticação e autorização

| Item | Atendido | Onde |
|---|:---:|---|
| Hash seguro de senhas | Sim | bcrypt custo 12, com rehash automático |
| Login funcional | Sim | Página e API |
| Logout funcional | Sim | Página e API, sessão destruída |
| Proteção de rotas | Sim | `config/rotas.php` |
| Diferenciação de perfis | Sim | Menu, rotas e regras de status diferentes por perfil |

### E. Qualidade da API

| Item | Atendido | Onde |
|---|:---:|---|
| Métodos HTTP corretos | Sim | GET lê, POST cria, PUT substitui, PATCH altera parte, DELETE exclui |
| Códigos de status adequados | Sim | 200, 201, 204, 400, 401, 403, 404, 405, 409, 415, 422, 429, 500 |
| Respostas consistentes em JSON | Sim | Envelope `dados`/`meta` e `erro` em todas as rotas |
| Documentação completa | Sim | `docs/api.md` |

### F. Testes automatizados

| Item | Atendido | Onde |
|---|:---:|---|
| 5 ou mais testes executáveis | Sim | 82 |
| Casos inválidos | Sim | Maioria dos testes |
| Evidência de execução | Sim | `docs/evidencias/resultado-testes-automatizados.txt` |

### G. Git e processo

| Item | Atendido | Onde |
|---|:---:|---|
| Commits significativos e descritivos | Sim | `git log` |
| Mensagens padronizadas | Sim | Prefixos `feat:`, `fix:`, `docs:`, `test:`, `refactor:`, `chore:` |
| Evolução incremental | Sim | Uma branch por etapa |
| Branches ou tags para marcos | Sim | Branches `feature/*`; tags `v1.0`, `v2.0-beta`, `v2.0` |

## Boas práticas opcionais

| Prática | Feita | Onde |
|---|:---:|---|
| `.env` para configuração | Sim | `.env.example`, `src/Core/Config.php` |
| Migrações de banco | Sim | `database/migrations/`, `bin/migrar.php` |
| Paginação de resultados | Sim | `?pagina=` e `?por_pagina=` nas listagens; páginas HTML com "Anterior/Próxima" |
| Rate limiting simples | Sim | 5 falhas de login por IP em 15 minutos (429) |
| Testes de integração automatizados | Sim | `tests/Integracao/` (43 testes) |

## Evidências obrigatórias

| Evidência | Arquivo |
|---|---|
| Script SQL | `database/schema.sql` |
| Documentação da API | `docs/api.md` |
| Resultado dos testes automatizados | `docs/evidencias/resultado-testes-automatizados.txt` |
| Capturas dos testes de segurança | `docs/evidencias/*.png`, `docs/evidencias/resultado-testes-seguranca-http.txt` |
| Amostra dos logs | `docs/evidencias/amostra-logs.txt` |
| Histórico Git | `docs/evidencias/historico-git.txt` |
| Checklist preenchido | este arquivo |
