# Modelo de dados

Banco MySQL/MariaDB `catalogo_toyota`, conjunto de caracteres `utf8mb4`,
tabelas InnoDB (necessário para chaves estrangeiras e transações).

Script de criação: `database/migrations/` (em etapas) ou
`database/schema.sql` (tudo junto, para importar no phpMyAdmin).

## Diagrama

```
 ┌──────────────────────┐            ┌──────────────────────────┐
 │ usuarios             │            │ modelos                  │
 ├──────────────────────┤            ├──────────────────────────┤
 │ PK id                │            │ PK id                    │
 │ UQ email             │            │ UQ slug                  │
 │    nome              │            │    nome, categoria       │
 │    senha_hash        │            │    preco, motor, cambio  │
 │    perfil            │            │    potencia_cv           │
 │    criado_em         │            │    consumo_km_l          │
 └──────────┬───────────┘            │    porta_malas_l, carga  │
            │ 1                      │    descricao, imagem...  │
            │                        │    ativo                 │
            │                        └────────────┬─────────────┘
            │ N                                 1 │
 ┌──────────▼──────────────────────────────────── ▼ ┐
 │ agendamentos  (entidade principal)              N │
 ├───────────────────────────────────────────────────┤
 │ PK id                                             │
 │ FK usuario_id → usuarios.id   ON DELETE CASCADE   │
 │ FK modelo_id  → modelos.id    ON DELETE RESTRICT  │
 │    data, periodo, telefone, observacoes, status   │
 │    criado_em, atualizado_em                       │
 └───────────────────────────────────────────────────┘

 ┌──────────────────────┐            ┌──────────────────────────┐
 │ tentativas_login     │            │ migracoes                │
 ├──────────────────────┤            ├──────────────────────────┤
 │ PK id                │            │ PK id                    │
 │    ip, email         │            │ UQ nome                  │
 │    sucesso           │            │    aplicada_em           │
 │    criado_em         │            └──────────────────────────┘
 └──────────────────────┘
```

## Tabelas

### usuarios

| Coluna | Tipo | Restrições |
|---|---|---|
| `id` | INT UNSIGNED | PK, AUTO_INCREMENT |
| `nome` | VARCHAR(100) | NOT NULL, CHECK (pelo menos 3 caracteres) |
| `email` | VARCHAR(150) | NOT NULL, UNIQUE |
| `senha_hash` | VARCHAR(255) | NOT NULL (bcrypt; a senha nunca é gravada) |
| `perfil` | ENUM('cliente','admin') | NOT NULL, padrão `cliente` |
| `criado_em` | DATETIME | NOT NULL, padrão agora |

### modelos

| Coluna | Tipo | Restrições |
|---|---|---|
| `id` | INT UNSIGNED | PK |
| `slug` | VARCHAR(60) | NOT NULL, UNIQUE (usado na URL `/modelos/{slug}`) |
| `nome`, `categoria`, `motor`, `cambio` | VARCHAR | NOT NULL |
| `preco` | DECIMAL(12,2) | NOT NULL, CHECK > 0 (DECIMAL evita erro de arredondamento em dinheiro) |
| `potencia_cv` | SMALLINT UNSIGNED | NOT NULL, CHECK > 0 |
| `consumo_km_l` | DECIMAL(4,1) | NOT NULL, CHECK > 0 |
| `porta_malas_l`, `carga_kg` | SMALLINT UNSIGNED | NULL (cada modelo usa um ou outro) |
| `descricao` | TEXT | NULL |
| `imagem`, `imagem_alt`, `legenda` | VARCHAR | NULL |
| `imagem_largura`, `imagem_altura` | SMALLINT UNSIGNED | NULL (evitam deslocamento do layout ao carregar a foto) |
| `ativo` | TINYINT(1) | NOT NULL, padrão 1, CHECK IN (0,1) |
| `criado_em`, `atualizado_em` | DATETIME | NOT NULL |

### agendamentos

| Coluna | Tipo | Restrições |
|---|---|---|
| `id` | INT UNSIGNED | PK |
| `usuario_id` | INT UNSIGNED | NOT NULL, FK → `usuarios.id`, apagar a conta apaga os agendamentos |
| `modelo_id` | INT UNSIGNED | NOT NULL, FK → `modelos.id`, modelo com agendamento não pode ser apagado |
| `data` | DATE | NOT NULL |
| `periodo` | ENUM('manha','tarde') | NOT NULL |
| `telefone` | VARCHAR(20) | NULL |
| `observacoes` | VARCHAR(400) | NULL |
| `status` | ENUM('pendente','confirmado','cancelado','realizado') | NOT NULL, padrão `pendente` |
| `criado_em`, `atualizado_em` | DATETIME | NOT NULL |

### tentativas_login

Histórico usado para limitar tentativas de login (5 falhas por IP em 15
minutos). Sem chave estrangeira de propósito: o e-mail tentado pode nem
existir.

### migracoes

Controle das migrações já aplicadas, preenchida por `bin/migrar.php`.

## Índices

| Índice | Colunas | Consulta atendida |
|---|---|---|
| `uq_usuarios_email` | `usuarios(email)` | Login e verificação de e-mail repetido |
| `uq_modelos_slug` | `modelos(slug)` | Página da ficha |
| `idx_modelos_ativo_nome` | `modelos(ativo, nome)` | Catálogo (só ativos, em ordem de nome) |
| `idx_agendamentos_usuario_data` | `agendamentos(usuario_id, data)` | "Meus agendamentos" |
| `idx_agendamentos_horario` | `agendamentos(modelo_id, data, periodo, status)` | Conferência de vagas: a consulta é respondida só pelo índice |
| `idx_agendamentos_status_data` | `agendamentos(status, data)` | Filtro por status no painel do administrador |
| `idx_tentativas_ip` | `tentativas_login(ip, sucesso, criado_em)` | Contagem de falhas recentes por IP |

O `EXPLAIN` de cada consulta, com 3.003 agendamentos no banco, está em
`docs/evidencias/desempenho.txt`.

## Regras garantidas pelo banco e pela aplicação

| Regra | Banco | Aplicação |
|---|---|---|
| E-mail único | UNIQUE | `AuthService` responde 409 antes |
| Agendamento sempre de um usuário e modelo existentes | FK | `AgendamentoService` responde 422 se o modelo não existe |
| Status e período só com valores conhecidos | ENUM | `Validador` (`em:...`) responde 422 |
| Preço, potência e consumo positivos | CHECK | `Validador` (`min:1`) responde 422 |
| Data no futuro, até 60 dias, sem domingo | — | `RegrasAgendamento` |
| No máximo 3 test-drives por modelo, dia e período | — | `AgendamentoService`, em transação com trava |
