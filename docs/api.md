# Documentação da API

API JSON do Catálogo Toyota. Os exemplos abaixo foram gerados com o sistema
rodando (respostas reais, só encurtadas onde indicado com `…`).

**URL base:** `http://localhost/catalogo-toyota` (XAMPP) ou
`http://localhost:8000` (servidor embutido do PHP).

## Convenções

### Autenticação

A API usa **sessão por cookie** (`toyota_sessao`, `HttpOnly`, `SameSite=Lax`),
a mesma do site. O login devolve um `csrf_token`.

### Token CSRF

Todo `POST`, `PUT`, `PATCH` e `DELETE` de quem está logado precisa do
cabeçalho:

```
X-CSRF-Token: <csrf_token recebido no login ou em GET /api/auth/eu>
```

Sem ele, a resposta é `403`. Só `POST /api/auth/registro` e
`POST /api/auth/login` dispensam o token (ainda não há sessão).

### Corpo das requisições

`POST`, `PUT` e `PATCH` recebem JSON, com o cabeçalho
`Content-Type: application/json`. Outro formato: `415`. JSON quebrado: `400`.

### Formato das respostas

Sucesso com um item:

```json
{ "dados": { … } }
```

Sucesso com lista (sempre paginada):

```json
{ "dados": [ … ], "meta": { "pagina": 1, "por_pagina": 10, "total": 23, "total_paginas": 3 } }
```

Erro (todas as rotas, mesmo formato):

```json
{ "erro": { "status": 422, "mensagem": "Os dados enviados são inválidos.", "campos": { "data": "…" } } }
```

`campos` só aparece em erros de validação. Em erros `500`, o campo
`detalhes` (tipo da exceção, arquivo, linha, pilha) só aparece com
`APP_ENV=desenvolvimento` e `APP_DEBUG=true`.

### Paginação

Listagens aceitam `?pagina=N` (padrão 1) e `?por_pagina=N` (padrão 10,
máximo 50). Valor inválido: `422`.

### Códigos de status usados

| Código | Quando |
|---|---|
| 200 OK | Leitura ou alteração bem-sucedida |
| 201 Created | Item criado (com cabeçalho `Location`) |
| 204 No Content | Exclusão ou logout bem-sucedidos |
| 400 Bad Request | Corpo não é JSON válido |
| 401 Unauthorized | Sem login, sessão expirada, ou e-mail/senha errados no login |
| 403 Forbidden | Perfil sem permissão ou token CSRF ausente/inválido |
| 404 Not Found | Recurso inexistente (ou agendamento de outra pessoa) |
| 405 Method Not Allowed | URL existe, mas não com esse método (cabeçalho `Allow`) |
| 409 Conflict | Conflito com o estado atual: e-mail/slug repetido, horário lotado, mudança de status proibida |
| 415 Unsupported Media Type | Corpo enviado sem `Content-Type: application/json` |
| 422 Unprocessable Entity | Dados inválidos (com `campos`) |
| 429 Too Many Requests | 5 logins errados do mesmo IP em 15 minutos (cabeçalho `Retry-After`) |
| 500 Internal Server Error | Erro inesperado (registrado no log) |

---

## Autenticação

### `POST /api/auth/registro`

Cria uma conta de **cliente**. Público.

| Campo | Tipo | Regras |
|---|---|---|
| `nome` | texto | obrigatório, 3 a 100 caracteres |
| `email` | texto | obrigatório, e-mail válido, até 150 caracteres, único |
| `senha` | texto | obrigatório, 8 a 72 caracteres, com letras e números |

Qualquer outro campo (por exemplo `perfil`) é ignorado.

```
POST /api/auth/registro
Content-Type: application/json

{"nome": "João Silva", "email": "joao@exemplo.com", "senha": "joao2026"}
```

```
HTTP/1.1 201 Created

{"dados":{"id":70,"nome":"João Silva","email":"joao@exemplo.com","perfil":"cliente"}}
```

Erros: `409` e-mail já cadastrado · `422` dados inválidos.

### `POST /api/auth/login`

Público. Cria a sessão e devolve o token CSRF.

| Campo | Tipo | Regras |
|---|---|---|
| `email` | texto | obrigatório, e-mail válido |
| `senha` | texto | obrigatório, até 72 caracteres |

```
POST /api/auth/login
Content-Type: application/json

{"email": "joao@exemplo.com", "senha": "joao2026"}
```

```
HTTP/1.1 200 OK
Set-Cookie: toyota_sessao=…; path=/catalogo-toyota/; HttpOnly; SameSite=Lax

{"dados":{"usuario":{"id":70,"nome":"João Silva","email":"joao@exemplo.com","perfil":"cliente"},
          "csrf_token":"66387797128ba8073e709069317f31da066effb3724e7c6e432bf3a5acba5e98"}}
```

Erros: `401` e-mail ou senha inválidos (mesma mensagem nos dois casos) ·
`422` formato inválido · `429` muitas tentativas.

### `POST /api/auth/logout`

Requer login e `X-CSRF-Token`. Encerra a sessão; o cookie antigo deixa de valer.

```
HTTP/1.1 204 No Content
```

### `GET /api/auth/eu`

Requer login. Dados de quem está logado e o token CSRF atual.

```
HTTP/1.1 200 OK

{"dados":{"usuario":{"id":70,"nome":"João Silva","email":"joao@exemplo.com","perfil":"cliente"},
          "csrf_token":"66387797…"}}
```

Erro: `401` sem sessão.

---

## Modelos

### `GET /api/modelos`

Público. Lista os modelos ativos em ordem de nome. Administrador pode
passar `?todos=1` para incluir os inativos.

```
GET /api/modelos?pagina=1&por_pagina=2
```

```
HTTP/1.1 200 OK

{"dados":[
  {"id":1,"slug":"corolla-altis-hybrid","nome":"Corolla Altis Hybrid","categoria":"Sedã híbrido",
   "preco":195000,"motor":"1.8 híbrido, 16 válvulas","potencia_cv":122,"cambio":"Automático e-CVT",
   "consumo_km_l":21.5,"porta_malas_l":470,"carga_kg":null,"descricao":"…","imagem":"corolla.jpg",
   "imagem_alt":"Toyota Corolla branco, visto de frente, estacionado em área gramada","ativo":true},
  {"id":3,"slug":"gr-supra", … }
 ],
 "meta":{"pagina":1,"por_pagina":2,"total":3,"total_paginas":2}}
```

Erro: `422` paginação inválida.

### `GET /api/modelos/{id}`

Público. Modelo inativo só aparece para administrador.

```
HTTP/1.1 200 OK

{"dados":{"id":3,"slug":"gr-supra","nome":"GR Supra","categoria":"Esportivo","preco":620000, … }}
```

Erro: `404` não existe ou inativo.

### `POST /api/modelos`

Requer **administrador** e `X-CSRF-Token`.

| Campo | Tipo | Regras |
|---|---|---|
| `slug` | texto | obrigatório, 2 a 60, só `a-z`, `0-9` e hífens (`gr-supra`), único |
| `nome` | texto | obrigatório, 2 a 100 |
| `categoria` | texto | obrigatório, 2 a 40 |
| `preco` | número | obrigatório, maior que 0 |
| `motor` | texto | obrigatório, 2 a 100 |
| `potencia_cv` | inteiro | obrigatório, 1 a 2000 |
| `cambio` | texto | obrigatório, 2 a 60 |
| `consumo_km_l` | número | obrigatório, 1 a 99 |
| `porta_malas_l` | inteiro | opcional, 0 a 5000 |
| `carga_kg` | inteiro | opcional, 0 a 10000 |
| `descricao` | texto | opcional, até 2000 (parágrafos separados por linha em branco) |
| `imagem` | texto | opcional, nome de arquivo em `public/img/` (`foto.jpg`, `.png`, `.webp`) |
| `imagem_alt` | texto | opcional, até 200 |
| `imagem_largura`, `imagem_altura` | inteiro | opcional, 1 a 4000 |
| `legenda` | texto | opcional, até 150 |
| `ativo` | booleano | opcional, padrão `true` |

```
POST /api/modelos
Content-Type: application/json
X-CSRF-Token: 24b048d7…

{"slug":"yaris-cross","nome":"Yaris Cross","categoria":"SUV compacto","preco":159990,
 "motor":"1.5 flex","potencia_cv":122,"cambio":"Automático CVT","consumo_km_l":12.4,"porta_malas_l":440}
```

```
HTTP/1.1 201 Created
Location: /catalogo-toyota/api/modelos/5

{"dados":{"id":5,"slug":"yaris-cross","nome":"Yaris Cross", … ,"ativo":true}}
```

Erros: `401` · `403` não é administrador ou sem token CSRF · `409` slug
repetido · `422` dados inválidos.

### `PUT /api/modelos/{id}`

Requer **administrador** e `X-CSRF-Token`. Substitui o modelo inteiro:
mesmos campos e regras do `POST`. Para tirar um modelo do catálogo sem
apagar, envie `"ativo": false`.

```
HTTP/1.1 200 OK

{"dados":{"id":5,"slug":"yaris-cross", … ,"preco":164990, … ,"ativo":false}}
```

Erros: `401` · `403` · `404` · `409` · `422`.

### `DELETE /api/modelos/{id}`

Requer **administrador** e `X-CSRF-Token`.

```
HTTP/1.1 204 No Content
```

Erros: `401` · `403` · `404` · `409` modelo tem agendamentos:

```json
{"erro":{"status":409,"mensagem":"Este modelo tem agendamentos e não pode ser excluído. Desative-o (ativo: false) pelo PUT."}}
```

---

## Agendamentos

Objeto devolvido:

```json
{
  "id": 4100,
  "data": "2026-10-23",
  "periodo": "tarde",
  "status": "pendente",
  "telefone": "(15) 99999-0000",
  "observacoes": "Quero testar na estrada.",
  "modelo": {"id": 3, "slug": "gr-supra", "nome": "GR Supra"},
  "usuario": {"id": 70, "nome": "João Silva"},
  "criado_em": "2026-10-16 15:52:18"
}
```

`periodo`: `manha` (8h às 12h) ou `tarde` (13h às 18h).
`status`: `pendente`, `confirmado`, `cancelado` ou `realizado`.

### `GET /api/agendamentos`

Requer login. Cliente vê só os próprios; administrador vê todos.
Filtro opcional `?status=pendente`.

```
GET /api/agendamentos?status=pendente
```

```
HTTP/1.1 200 OK

{"dados":[{"id":4100,"data":"2026-10-23","periodo":"tarde","status":"pendente", … }],
 "meta":{"pagina":1,"por_pagina":10,"total":1,"total_paginas":1}}
```

Erros: `401` · `422` status ou paginação inválidos.

### `GET /api/agendamentos/{id}`

Requer login. Cliente só acessa os próprios.

Erros: `401` · `404` não existe ou é de outra pessoa.

### `POST /api/agendamentos`

Requer login e `X-CSRF-Token`. O agendamento fica no nome de quem está logado.

| Campo | Tipo | Regras |
|---|---|---|
| `modelo_id` | inteiro | obrigatório, modelo ativo |
| `data` | texto `AAAA-MM-DD` | obrigatório, de amanhã até 60 dias à frente, não pode ser domingo |
| `periodo` | texto | obrigatório, `manha` ou `tarde` |
| `telefone` | texto | opcional, 8 a 20 caracteres (dígitos, espaço, `( ) + -`) |
| `observacoes` | texto | opcional, até 400 |

Regras de negócio: o mesmo cliente não pode ter dois agendamentos ativos no
mesmo dia e período; cada modelo aceita 3 test-drives por período.

```
POST /api/agendamentos
Content-Type: application/json
X-CSRF-Token: 66387797…

{"modelo_id":3,"data":"2026-10-23","periodo":"tarde","telefone":"(15) 99999-0000",
 "observacoes":"Quero testar na estrada."}
```

```
HTTP/1.1 201 Created
Location: /catalogo-toyota/api/agendamentos/4100

{"dados":{"id":4100,"data":"2026-10-23","periodo":"tarde","status":"pendente", … }}
```

Erro de validação (vários campos de uma vez):

```
HTTP/1.1 422 Unprocessable Entity

{"erro":{"status":422,"mensagem":"Os dados enviados são inválidos.",
         "campos":{"periodo":"Valor não permitido. Use: manha, tarde.",
                   "data":"Escolha uma data a partir de amanhã."}}}
```

Erros: `401` · `403` sem token CSRF · `409` já tem agendamento no horário ou
horário lotado · `422`.

### `PATCH /api/agendamentos/{id}`

Requer login e `X-CSRF-Token`. Remarca: envie só os campos que mudam
(`modelo_id`, `data`, `periodo`, `telefone`, `observacoes`), com as mesmas
regras do `POST`. Só agendamentos `pendente`.

```
PATCH /api/agendamentos/4100
Content-Type: application/json
X-CSRF-Token: 66387797…

{"periodo": "manha"}
```

```
HTTP/1.1 200 OK

{"dados":{"id":4100,"data":"2026-10-23","periodo":"manha","status":"pendente", … }}
```

Erros: `401` · `403` · `404` · `409` não está pendente, ou novo horário
indisponível · `422`.

### `PATCH /api/agendamentos/{id}/status`

Requer login e `X-CSRF-Token`.

| Campo | Tipo | Regras |
|---|---|---|
| `status` | texto | obrigatório, um dos quatro status |

Mudanças permitidas:

| Perfil | De | Para |
|---|---|---|
| cliente (só os próprios, até a véspera) | `pendente` ou `confirmado` | `cancelado` |
| administrador | `pendente` | `confirmado` ou `cancelado` |
| administrador | `confirmado` | `realizado` ou `cancelado` |

```
PATCH /api/agendamentos/4100/status
Content-Type: application/json
X-CSRF-Token: 66387797…

{"status": "cancelado"}
```

```
HTTP/1.1 200 OK

{"dados":{"id":4100, … ,"status":"cancelado", … }}
```

Mudança não permitida:

```
HTTP/1.1 409 Conflict

{"erro":{"status":409,"mensagem":"Não é possível mudar o status de \"pendente\" para \"confirmado\"."}}
```

Erros: `401` · `403` · `404` · `409` · `422` status desconhecido.

### `DELETE /api/agendamentos/{id}`

Requer **administrador** e `X-CSRF-Token`.

```
HTTP/1.1 204 No Content
```

Erros: `401` · `403` (cliente):

```json
{"erro":{"status":403,"mensagem":"Seu perfil não tem permissão para acessar este recurso."}}
```

· `404`.

---

## Exemplo completo com curl (Git Bash)

```bash
B=http://localhost/catalogo-toyota
J='Content-Type: application/json'

# login (guarda o cookie em sessao.txt) e pega o token
T=$(curl -s -c sessao.txt -H "$J" -d '{"email":"joao@exemplo.com","senha":"joao2026"}' \
     $B/api/auth/login | sed -n 's/.*"csrf_token":"\([0-9a-f]*\)".*/\1/p')

# agenda
curl -s -b sessao.txt -H "$J" -H "X-CSRF-Token: $T" \
     -d '{"modelo_id":3,"data":"2026-10-23","periodo":"tarde"}' $B/api/agendamentos

# lista
curl -s -b sessao.txt $B/api/agendamentos

# sai
curl -s -b sessao.txt -H "$J" -H "X-CSRF-Token: $T" -X POST $B/api/auth/logout
```

## Páginas HTML

Além da API, o site tem as rotas de página listadas na matriz de permissões
em `docs/arquitetura.md`. Elas usam os mesmos serviços e respondem com HTML;
formulários enviam o token no campo oculto `_csrf`.
