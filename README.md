# Catálogo Toyota

Interface web desenvolvida para o Trabalho Individual I da disciplina SCOM.
Catálogo de modelos com página de detalhe e agendamento de test-drive,
construído em HTML5 e CSS3 sem frameworks nem etapa de build.

## Como executar

Baixe ou clone o repositório e abra o arquivo `index.html` em qualquer
navegador moderno. Não há dependências, servidor ou instalação.

```
git clone https://github.com/PauloMilagre/catalogo-toyota.git
```

Para auditoria com Lighthouse é preciso servir por HTTP, já que o Lighthouse
não audita endereços `file://`. Com o XAMPP: copie a pasta para
`C:\xampp\htdocs\` e acesse `http://localhost/catalogo-toyota/`.

## Estrutura

```
catalogo-toyota/
├── index.html          catálogo de modelos
├── modelo.html         ficha detalhada do modelo
├── agendar.html        formulário de test-drive
├── css/estilo.css      folha de estilo única
├── img/                imagens dos modelos
└── docs/               artefatos de processo e registro de uso de IA
```

## Tecnologias

HTML5 semântico, CSS3 com variáveis em `:root`, Grid e Flexbox.
Sem bibliotecas externas.

## Autor

Paulo Francisco da Silva — SCOM, 2026.
