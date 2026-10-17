# Arquitetura

## Padrão adotado: MVC em camadas

O sistema é uma aplicação cliente-servidor em PHP sem framework. O navegador
(ou qualquer cliente da API) faz requisições HTTP; o servidor monta as páginas
HTML ou responde em JSON. O código é dividido em quatro camadas, e cada uma
só conversa com a de baixo:

```
 Navegador / cliente da API
            │  HTTP
            ▼
 ┌──────────────────────────────────────────────────────────────┐
 │ public/index.php   ponto de entrada único (front controller) │
 └──────────────────────────────────────────────────────────────┘
            │
            ▼
 ┌──────────────────────────────────────────────────────────────┐
 │ Core\App + Core\Router                                       │
 │   rota existe? exige login? exige perfil? JSON válido? CSRF? │
 └──────────────────────────────────────────────────────────────┘
            │
            ▼
 ┌─────────────────────────────┐    ┌───────────────────────────┐
 │ INTERFACE                   │    │                           │
 │ Controllers\Web  + Views    │    │  Core\ErrorHandler        │
 │ Controllers\Api  (JSON)     │───▶│  toda exceção vira uma    │
 └─────────────────────────────┘    │  resposta padronizada     │
            │                       │  e erros 500 vão ao log   │
            ▼                       └───────────────────────────┘
 ┌──────────────────────────────────────────────────────────────┐
 │ LÓGICA DE APLICAÇÃO                                          │
 │ Services\  AuthService, AgendamentoService, ModeloService,   │
 │            RegrasAgendamento, SessaoUsuario                  │
 └──────────────────────────────────────────────────────────────┘
            │
            ▼
 ┌──────────────────────────────────────────────────────────────┐
 │ ACESSO A DADOS                                               │
 │ Repositories\  (o único lugar com SQL, sempre parametrizado) │
 └──────────────────────────────────────────────────────────────┘
            │  PDO
            ▼
       MySQL / MariaDB
```

| Camada | Pasta | Responsabilidade | Não faz |
|---|---|---|---|
| Interface | `src/Controllers/Web`, `src/Views` | Ler a requisição, chamar o serviço, montar a página | Regra de negócio, SQL |
| Interface (API) | `src/Controllers/Api` | O mesmo, respondendo em JSON | Regra de negócio, SQL |
| Lógica de aplicação | `src/Services` | Validar, aplicar regras (datas, vagas, transições de status), registrar logs | HTML, SQL |
| Acesso a dados | `src/Repositories` | Consultas SQL com prepared statements | Regras, HTML |
| Infraestrutura | `src/Core` | Rotas, sessão, CSRF, validação, erros, logs, conexão | Regras do domínio |

As páginas HTML e a API usam os **mesmos serviços**. Agendar pelo formulário
do site ou por `POST /api/agendamentos` passa exatamente pelas mesmas regras.

## Caminho de uma requisição

Exemplo: cliente envia o formulário de agendamento.

1. O Apache recebe `POST /catalogo-toyota/agendar`. O `.htaccess` da raiz
   repassa para `public/`, e o de `public/` entrega ao `index.php`.
2. `Request::capturar()` lê método, caminho, campos e cabeçalhos.
3. `Fabrica::criarApp()` lê o `.env`, monta os componentes
   (`config/servicos.php`) e as rotas (`config/rotas.php`).
4. `App::handle()` encontra a rota `POST /agendar` com `auth => true`,
   confirma que há usuário logado e que o token `_csrf` confere.
5. `AgendamentoController::criar()` chama `AgendamentoService::criar()`.
6. O serviço valida os campos (`Validador`), confere a data
   (`RegrasAgendamento`) e, dentro de uma transação com a linha do modelo
   travada, verifica conflito e vagas e grava pelo `AgendamentoRepository`.
7. O log recebe `agendamento.criado`, e o controller responde `303` para
   `/meus-agendamentos`.
8. Se algo falhar no meio (data inválida, horário lotado), o serviço lança
   uma exceção com o código HTTP certo e o controller mostra o formulário de
   novo com as mensagens. Erros inesperados chegam ao `ErrorHandler`.

## Matriz de permissões

Perfis: **visitante** (sem login), **cliente** (padrão de toda conta nova) e
**administrador** (criado por `bin/criar-admin.php`). Ninguém consegue se
cadastrar como administrador: o campo `perfil` enviado no cadastro é
descartado pela validação.

### Páginas

| Rota | Visitante | Cliente | Admin |
|---|:---:|:---:|:---:|
| `GET /` catálogo | ✅ | ✅ | ✅ |
| `GET /modelos/{slug}` ficha | ✅ | ✅ | ✅ |
| `GET/POST /entrar`, `/cadastro` | ✅ | ✅ | ✅ |
| `POST /sair` | — | ✅ | ✅ |
| `GET/POST /agendar` | ↪ login | ✅ | ✅ |
| `GET /meus-agendamentos` | ↪ login | ✅ (só os seus) | ✅ (só os seus) |
| `POST /agendamentos/{id}/cancelar` | ↪ login | ✅ (só os seus, até a véspera) | ✅ |
| `GET /admin/agendamentos` | ↪ login | ❌ 403 | ✅ |
| `POST /admin/agendamentos/{id}/status` | ↪ login | ❌ 403 | ✅ |
| `POST /admin/agendamentos/{id}/excluir` | ↪ login | ❌ 403 | ✅ |

### API

| Endpoint | Visitante | Cliente | Admin |
|---|:---:|:---:|:---:|
| `POST /api/auth/registro` | ✅ | ✅ | ✅ |
| `POST /api/auth/login` | ✅ | ✅ | ✅ |
| `POST /api/auth/logout`, `GET /api/auth/eu` | 401 | ✅ | ✅ |
| `GET /api/modelos`, `GET /api/modelos/{id}` | ✅ (ativos) | ✅ (ativos) | ✅ (inclui inativos) |
| `POST /api/modelos` | 401 | ❌ 403 | ✅ |
| `PUT /api/modelos/{id}` | 401 | ❌ 403 | ✅ |
| `DELETE /api/modelos/{id}` | 401 | ❌ 403 | ✅ |
| `GET /api/agendamentos` | 401 | ✅ só os seus | ✅ todos |
| `GET /api/agendamentos/{id}` | 401 | ✅ só os seus (outros: 404) | ✅ todos |
| `POST /api/agendamentos` | 401 | ✅ | ✅ |
| `PATCH /api/agendamentos/{id}` (remarcar) | 401 | ✅ só os seus, se pendente | ✅ se pendente |
| `PATCH /api/agendamentos/{id}/status` | 401 | ✅ só para `cancelado` | ✅ conforme o fluxo abaixo |
| `DELETE /api/agendamentos/{id}` | 401 | ❌ 403 | ✅ |

### Mudanças de status do agendamento

```
            ┌──────────── cancelado ◀───────────┐
            │ (cliente ou admin)                │ (cliente ou admin)
 pendente ──┴──▶ confirmado ────────────────────┴──▶ realizado
           (admin)            (admin)
```

`cancelado` e `realizado` são finais. O cliente só cancela até a véspera.

### Como a permissão é garantida

- **Na rota**, não no controller: `config/rotas.php` declara `auth` ou
  `perfil` em cada URL, e o `App` confere antes de chamar qualquer código.
  Não há como esquecer a verificação num controller novo.
- **O perfil é lido do banco a cada requisição** (`SessaoUsuario`). Se um
  administrador perder o perfil, a mudança vale na hora.
- **Esconder o link não é proteção**: o menu "Administração" só aparece para
  admin, mas o que barra o cliente é o 403 da rota, testado em
  `tests/Integracao/SegurancaTest.php`.
- **Agendamento de outra pessoa responde 404**, não 403, para não confirmar
  que aquele número existe.

## Decisões técnicas

| Decisão | Motivo |
|---|---|
| PHP sem framework | O enunciado sugere PHP; sem framework, cada mecanismo (rotas, CSRF, sessão) fica visível no código e pode ser explicado |
| Sessão (cookie) em vez de JWT | O site e a API são do mesmo domínio; sessão permite logout imediato e o PHP já oferece cookie `HttpOnly` e `SameSite`. JWT exigiria lista de revogação para ter logout de verdade |
| Token CSRF por sessão | Cookie de sessão é enviado automaticamente pelo navegador; o token prova que o pedido saiu de uma página do próprio site |
| `SameSite=Lax` além do token | Segunda barreira: o navegador nem envia o cookie em POST vindo de outro site |
| Validação no servidor, sempre | A validação HTML5 do Trabalho I ajuda o usuário, mas pode ser contornada; os formulários agora usam `novalidate` e quem decide é o `Validador` |
| Escapar na saída, não na entrada | O texto é guardado como foi digitado e neutralizado na hora de exibir (`View::e`). Assim o mesmo dado serve para HTML e JSON sem ser corrompido |
| Transação com `SELECT ... FOR UPDATE` | Dois clientes agendando a última vaga ao mesmo tempo: o segundo espera o primeiro terminar e vê a contagem certa |
| Migrações numeradas | O banco evolui por arquivos novos, nunca editando os antigos; `bin/migrar.php` aplica só o que falta |
| `.env` fora do Git | Senha do banco nunca vai para o repositório; o `.env.example` mostra o que configurar |
| Só `public/` exposta | Código, `.env`, logs e scripts ficam fora do alcance do navegador (`.htaccess` da raiz) |

## Componentes reutilizados

- `View` e `layout.php`: cabeçalho, menu e rodapé escritos uma vez (no
  Trabalho I eram copiados em cada `.html`).
- `ficha.php`: um template para todos os modelos (antes, `modelo.html`,
  `hilux.html` e `supra.html` repetiam a mesma estrutura).
- `partials/paginacao.php` e `partials/campo-erro.php`: usados em várias páginas.
- `Validador`, `Paginacao` e `HttpException`: usados pelos três serviços e pela API.
- `Repositorio`: base de todos os repositórios, com a execução parametrizada.
