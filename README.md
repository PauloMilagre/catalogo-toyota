# Catálogo Toyota

Catálogo de modelos Toyota com agendamento de test-drive, desenvolvido para a
disciplina SCOM.

- **Trabalho Individual I** (tag `v1.0`): interface em HTML5 e CSS3, sem
  backend. Os documentos dessa etapa estão em `docs/trabalho1/`.
- **Trabalho Individual II** (tag `v2.0`): backend em PHP com MySQL/MariaDB,
  contas de usuário, perfis de acesso, API JSON, testes automatizados e logs.

## O que o sistema faz

| Quem | O que pode fazer |
|---|---|
| Visitante | Ver o catálogo e a ficha de cada modelo; criar conta |
| Cliente | Agendar test-drive, ver e cancelar os próprios agendamentos |
| Administrador | Ver todos os agendamentos, confirmar, marcar como realizado, cancelar e excluir; cadastrar, alterar e excluir modelos pela API |

A matriz completa de permissões está em `docs/arquitetura.md`.

## Requisitos

- PHP 8.2 ou mais novo, com as extensões `pdo_mysql` e `mbstring`
- MySQL 8 ou MariaDB 10.4 ou mais novo
- O XAMPP 8.2 já traz tudo isso (Apache, PHP e MariaDB)

## Como executar com o XAMPP (Windows)

1. Clone o repositório e deixe a pasta acessível em `C:\xampp\htdocs\catalogo-toyota`
   (copiando a pasta ou criando um atalho de diretório):

   ```
   git clone https://github.com/PauloMilagre/catalogo-toyota.git
   mklink /J C:\xampp\htdocs\catalogo-toyota C:\caminho\para\catalogo-toyota
   ```

2. No XAMPP Control Panel, inicie **Apache** e **MySQL**.

3. Crie o arquivo de configuração a partir do modelo. No Git Bash, dentro da pasta do projeto:

   ```
   cp .env.example .env
   ```

   Os valores padrão já servem para o XAMPP (usuário `root`, sem senha).

4. Crie o banco e as tabelas:

   ```
   /c/xampp/php/php.exe bin/migrar.php
   ```

   Alternativa sem terminal: no phpMyAdmin, aba **Importar**, envie o arquivo
   `database/schema.sql`.

5. Crie a conta de administrador (a senha é digitada na hora, não fica no código):

   ```
   /c/xampp/php/php.exe bin/criar-admin.php
   ```

   Se o Git Bash não deixar digitar as respostas, passe os dados na mesma linha:
   `bin/criar-admin.php --email=admin@exemplo.com --nome="Administrador" --senha=SuaSenha2026`

6. Acesse **http://localhost/catalogo-toyota/**

### Alternativa: servidor embutido do PHP

Sem Apache, só com o PHP e o MySQL ligados:

```
php -S localhost:8000 -t public
```

e acesse **http://localhost:8000/**.

## Testes automatizados

São 82 testes (PHPUnit): 39 unitários, que não precisam de banco, e 43 de
integração, que usam um banco separado (`DB_NAME_TESTE` no `.env`), apagado e
recriado a cada execução. O banco principal não é tocado.

```
mkdir -p tools && curl -L -o tools/phpunit.phar https://phar.phpunit.de/phpunit-11.phar
/c/xampp/php/php.exe tools/phpunit.phar
```

Só os unitários: `/c/xampp/php/php.exe tools/phpunit.phar --testsuite unitarios`.
Com Composer instalado: `composer install` e depois `composer test`.

Testes de segurança contra o site rodando (SQL Injection, XSS, CSRF, acesso
sem login, perfil insuficiente e sessão reutilizada):

```
bash bin/testes-seguranca.sh http://localhost/catalogo-toyota
```

## Estrutura

```
catalogo-toyota/
├── public/              única pasta acessível pelo navegador
│   ├── index.php        ponto de entrada de todas as requisições
│   ├── css/estilo.css   folha de estilo (a mesma do Trabalho I, ampliada)
│   └── img/             imagens dos modelos
├── src/
│   ├── Core/            infraestrutura: rotas, requisição, resposta, sessão,
│   │                    CSRF, validação, erros, logs, banco
│   ├── Repositories/    acesso a dados (o único lugar com SQL)
│   ├── Services/        regras de negócio
│   ├── Controllers/
│   │   ├── Web/         páginas HTML
│   │   └── Api/         endpoints JSON
│   └── Views/           templates das páginas
├── config/
│   ├── rotas.php        URLs e quem pode acessar cada uma
│   └── servicos.php     montagem dos componentes
├── database/
│   ├── migrations/      script do banco, em etapas numeradas
│   └── schema.sql       script completo (gerado das migrações)
├── bin/                 migrar, criar administrador, testes de segurança
├── tests/
│   ├── Unitarios/
│   └── Integracao/
├── docs/                arquitetura, API, testes, evidências, uso de IA
├── logs/                app.log (fora do Git)
└── .env.example         modelo de configuração (o .env fica fora do Git)
```

## Documentação

| Arquivo | Conteúdo |
|---|---|
| `docs/arquitetura.md` | Camadas, fluxo de uma requisição, matriz de permissões, decisões |
| `docs/modelo-de-dados.md` | Tabelas, chaves, restrições e índices |
| `docs/api.md` | Todos os endpoints, com exemplos de requisição e resposta |
| `docs/testes.md` | Estratégia de testes, testes de segurança e desempenho |
| `docs/checklist-requisitos.md` | Requisitos do enunciado e onde cada um é atendido |
| `docs/uso-de-ia.md` | Registro do uso de IA generativa nos dois trabalhos |
| `docs/evidencias/` | Capturas, resultados dos testes, amostra de logs, desempenho |

## Tecnologias

PHP 8.2 sem framework, PDO com prepared statements, MySQL/MariaDB, PHPUnit 11,
HTML5 e CSS3. Nenhuma biblioteca é necessária para rodar o sistema.

## Autor

Paulo Francisco da Silva — SCOM, 2026.
