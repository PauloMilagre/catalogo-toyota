# Testes, segurança e desempenho

## Estratégia de testes

Três níveis, do mais rápido ao mais completo:

| Nível | Onde | Precisa de | O que cobre |
|---|---|---|---|
| Unitários (39) | `tests/Unitarios/` | só o PHP | Peças isoladas: validação, regras de agendamento, rotas, CSRF, paginação, tratamento de erros |
| Integração (43) | `tests/Integracao/` | PHP + MySQL | A aplicação inteira (rotas, controllers, serviços, banco), sem servidor web. Inclui 14 testes de segurança |
| Segurança via HTTP (20 verificações) | `bin/testes-seguranca.sh` | site rodando | Os mesmos ataques feitos com `curl`, com cookies de verdade, contra o Apache |

### Como os testes de integração funcionam

- Usam o banco `DB_NAME_TESTE` (padrão `catalogo_toyota_teste`), que é
  **apagado e recriado pelas próprias migrações** no início da execução.
  Isso também testa as migrações. Se `DB_NAME_TESTE` for igual a `DB_NAME`,
  os testes se recusam a rodar, para não apagar o banco principal.
- Cada teste começa com o banco no estado inicial (só os três modelos).
- A requisição é montada em memória (`new Request(...)`) e entregue ao
  `App`, exatamente o mesmo objeto que atende o navegador. A sessão é
  `SessaoMemoria`, que se comporta como a do PHP sem precisar de cookie.
- Os logs dos testes vão para `logs/teste.log`, e alguns testes conferem
  que o evento certo foi registrado.

### Casos válidos e inválidos

Todos os grupos testam os dois lados. Exemplos:

| Funcionalidade | Caso válido | Casos inválidos |
|---|---|---|
| Cadastro | cria cliente, senha vira hash bcrypt | e-mail repetido (409), dados curtos (422), tentativa de virar admin (ignorada) |
| Login | devolve token, troca id da sessão | senha errada (401), e-mail inexistente (mesma mensagem), 6ª tentativa (429) |
| Agendamento | cria (201), remarca, cancela, admin confirma | data passada, domingo, mais de 60 dias, modelo inexistente (422), horário repetido, lotado (409), agendamento alheio (404) |
| Modelos | admin cria, altera, desativa, exclui | slug repetido (409), preço/potência inválidos (422), excluir com agendamento (409), cliente tentando (403) |
| Validação | converte tipos, limpa espaços e caracteres de controle | tipo errado, tamanho fora do limite, data inexistente (30/02), campo extra descartado |

### Como rodar

```
mkdir -p tools && curl -L -o tools/phpunit.phar https://phar.phpunit.de/phpunit-11.phar
/c/xampp/php/php.exe tools/phpunit.phar                         # todos
/c/xampp/php/php.exe tools/phpunit.phar --testsuite unitarios   # sem banco
bash bin/testes-seguranca.sh http://localhost/catalogo-toyota    # contra o site rodando
```

Resultado: `docs/evidencias/resultado-testes-automatizados.txt` (82 testes,
302 verificações, todos aprovados) e
`docs/evidencias/resultado-testes-seguranca-http.txt`.

## Testes de segurança

Cada ataque foi feito de três formas: teste automatizado
(`tests/Integracao/SegurancaTest.php`), script HTTP
(`bin/testes-seguranca.sh`) e no navegador (capturas em `docs/evidencias/`).

### 1. SQL Injection

| Tentativa | Resultado | Por que não funciona |
|---|---|---|
| E-mail `' OR '1'='1' -- ` no login | 422, "E-mail inválido." | A validação recusa o formato antes de qualquer consulta |
| Senha `' OR '1'='1` no login | 401, "E-mail ou senha inválidos." | A senha nunca entra no SQL: é comparada com o hash por `password_verify` |
| URL `/api/agendamentos/1 OR 1=1` | 404 | A rota só aceita dígitos no `{id}` |
| `?status=pendente' OR '1'='1` | 422 | Status só aceita os quatro valores conhecidos |
| Observação `'); DROP TABLE agendamentos; --` | 201, texto gravado igual ao digitado, tabela intacta | Prepared statement: o valor vai separado do SQL |

Proteção principal: todas as consultas estão em `src/Repositories` e usam
`prepare()` + `bindValue()` com `ATTR_EMULATE_PREPARES` desligado (prepared
statements nativos do MySQL). Nomes de coluna dinâmicos (`ModeloRepository`)
saem de uma lista fixa, nunca da requisição.

Capturas: `07-sqli-login-email.png`, `08-sqli-login-senha.png`.

### 2. XSS (refletido e armazenado)

| Tentativa | Resultado |
|---|---|
| Cadastro com nome `<script>alert("xss")</script>` e e-mail inválido: o nome volta para a tela | Aparece como texto: `&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;` |
| `/entrar?voltar="><script>alert(1)</script>` | O parâmetro é descartado (destino inválido) e nada é refletido |
| Observação `<img src=x onerror=alert(1)>` vista pelo administrador | Aparece como texto no painel |

Proteções: todo valor impresso nas páginas passa por `View::e()`
(`htmlspecialchars` com `ENT_QUOTES`); e o cabeçalho
`Content-Security-Policy: script-src 'none'` faz o navegador recusar
qualquer script, mesmo que algum escapasse.

Capturas: `06-xss-refletido-cadastro.png`, `14-admin-painel.png`.

### 3. CSRF

| Tentativa | Resultado |
|---|---|
| `POST /agendar` sem o campo `_csrf` (logado) | 403 |
| `POST /agendar` com token de outra sessão | 403 |
| `POST /api/agendamentos` sem `X-CSRF-Token` (logado) | 403 |

Proteções: token aleatório de 64 caracteres por sessão, comparado com
`hash_equals`, renovado a cada login; cookie `SameSite=Lax`. Até o "Sair" é
um formulário POST com token, para um link malicioso não deslogar ninguém.

Captura: `12-csrf-sem-token-403.png`.

### 4. Acesso sem login e com perfil insuficiente

| Tentativa | Resultado |
|---|---|
| `GET /api/agendamentos` sem sessão | 401 |
| `GET /meus-agendamentos` sem sessão | 303 para `/entrar?voltar=/meus-agendamentos` |
| Cliente em `GET /admin/agendamentos` | 403, registrado no log como `acesso.negado` |
| Cliente em `POST /api/modelos` e `DELETE /api/modelos/1` | 403 |
| Cliente abrindo agendamento de outro cliente | 404 |
| Admin que perde o perfil no banco | 403 na requisição seguinte |

Capturas: `04-api-sem-login-401.png`, `05-login-exigido.png`,
`11-cliente-no-admin-403.png`.

### 5. Sessão e token reutilizados

| Tentativa | Resultado |
|---|---|
| Mesmo cookie depois do logout | 401 |
| Cookie e token CSRF antigos juntos | 401 |
| Sessão parada por mais de 30 minutos | 401 |

Proteções: `session_destroy()` no logout, `session.use_strict_mode` (o PHP
recusa ids que ele não emitiu), `session_regenerate_id()` no login e
expiração por inatividade.

### 6. Força bruta no login

5 senhas erradas do mesmo IP em 15 minutos bloqueiam o login daquele IP
(429, com `Retry-After`), mesmo com a senha certa. Outro IP não é afetado.
Teste: `AutenticacaoTest::testSextaTentativaErradaSeguidaEhBloqueadaCom429`.

## Desempenho

Medido com Apache Bench (`ab`, que também vem no XAMPP em
`C:\xampp\apache\bin\ab.exe`), com 3.003 agendamentos e 53 usuários no banco.
Resultado completo: `docs/evidencias/desempenho.txt`.

| Requisição (500 pedidos, 10 simultâneos) | Pedidos/s | p50 | p95 |
|---|---:|---:|---:|
| `GET /` catálogo | 1.869 | 5 ms | 8 ms |
| `GET /modelos/gr-supra` ficha | 2.005 | 5 ms | 8 ms |
| `GET /api/modelos` | 1.493 | 6 ms | 10 ms |
| `GET /api/agendamentos` (admin, 50 por página) | 122 | 78 ms | 96 ms |
| `GET /css/estilo.css` (estático) | 16.403 | 1 ms | 1 ms |
| `POST /api/auth/login` (1 por vez) | 4 | 248 ms | — |

Leitura dos números:

- A listagem de agendamentos leva **8,7 ms** com um pedido por vez. Os 78 ms
  com 10 simultâneos acontecem porque o teste usou **a mesma sessão** nas 10
  conexões, e o PHP processa em fila os pedidos de uma mesma sessão (trava do
  arquivo de sessão). Usuários diferentes não disputam essa trava.
- O login leva ~250 ms **de propósito**: é o custo do bcrypt com fator 12.
  Isso torna inviável testar milhões de senhas caso o banco vaze, e o limite
  de 5 tentativas por IP impede abuso pelo site.
- O `EXPLAIN` mostra que as consultas de "meus agendamentos", vagas no
  horário, filtro por status e login usam os índices criados. A de vagas é
  respondida só pelo índice (`Using index`).

## Limitações conhecidas

- **E-mail de confirmação**: a página promete confirmação por e-mail, mas o
  envio não foi implementado; o cliente acompanha o status em "Meus
  agendamentos".
- **Cadastro de modelos só pela API**: o painel do administrador cobre os
  agendamentos; modelos são gerenciados por `POST/PUT/DELETE /api/modelos`.
- **Sem recuperação de senha** nesta versão.
- **Limite de login por IP**: várias pessoas atrás do mesmo IP (rede de
  faculdade, por exemplo) dividem as 5 tentativas.
- **Sessões em arquivo**: suficiente para um servidor; com vários servidores
  seria preciso guardar as sessões no banco ou em Redis.
- **Ordenação da listagem do administrador** sem índice próprio para
  `ORDER BY data DESC` em todos os registros (filesort); irrelevante com
  milhares de linhas, mereceria índice com centenas de milhares.
- **HTTPS** não é configurado pelo projeto; em produção, ligar
  `SESSION_SECURE=true` para o cookie só trafegar cifrado.
- **Dados dos modelos** são ilustrativos, não de fonte oficial.
