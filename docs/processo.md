# Processo de projeto — Catálogo Toyota

Artefatos produzidos antes da implementação, conforme exigido no enunciado do
Trabalho Individual I (seção "Processo de desenvolvimento").

---

## 1. Descrição do problema

Quem está pesquisando a compra de um carro precisa de poucas informações
objetivas para montar uma lista curta de candidatos: qual é o modelo, a que
categoria pertence, quanto custa, quanta potência tem e quanto consome. Depois
disso, precisa de um caminho direto para marcar um test-drive.

Os sites das montadoras costumam enterrar esses dados sob vídeos em reprodução
automática, carrosséis e animações de rolagem. O custo recai sobre quem acessa
pelo celular em conexão móvel: a página demora a pintar, o conteúdo se desloca
enquanto carrega, e a informação que interessa só aparece depois de várias
rolagens.

Este projeto entrega o mesmo conteúdo essencial em uma interface leve, sem
dependências externas, navegável inteiramente por teclado e legível em qualquer
largura de tela.

## 2. Público-alvo

**Perfil principal:** pessoa adulta entre 25 e 60 anos, pesquisando a compra de
um veículo. Acessa majoritariamente pelo celular, em conexão móvel e com plano
de dados limitado. Tem familiaridade média com a web — não é um público
técnico, então a interface não pode exigir aprendizado.

**Perfis incluídos por decisão de projeto:**

- pessoas que navegam apenas por teclado, por deficiência motora ou preferência;
- usuários de leitor de tela, que dependem da hierarquia de títulos e dos textos
  alternativos para entender a página;
- pessoas com baixa visão, que precisam de contraste adequado e de um layout que
  não quebre com o zoom do navegador em 200%.

**Cenário de uso típico:** o usuário chega pelo celular, percorre os cards do
catálogo, abre a ficha de um modelo que chamou atenção e agenda um test-drive —
tudo em uma sessão curta, possivelmente em movimento.

## 3. Requisitos do sistema

### Requisitos funcionais

| ID | Requisito |
|----|-----------|
| RF1 | Listar os modelos do catálogo com foto, nome, categoria, preço e dados objetivos (potência e consumo) |
| RF2 | Permitir abrir a ficha completa de um modelo a partir do catálogo |
| RF3 | Oferecer formulário de agendamento de test-drive com validação nativa do HTML |
| RF4 | Permitir navegar entre as três páginas a partir de qualquer uma delas |
| RF5 | Indicar visualmente qual é a página atual na navegação |
| RF6 | Oferecer retorno à página inicial a partir de qualquer ponto do site |

### Requisitos não funcionais

| ID | Requisito | Verificação |
|----|-----------|-------------|
| RNF1 | HTML5 semântico, com um único `h1` por página e hierarquia de títulos consistente | W3C HTML Validator |
| RNF2 | Layout funcional em três breakpoints: móvel, intermediário e desktop | Capturas de tela |
| RNF3 | Conformidade com WCAG 2.2 nível AA: navegação por teclado, foco visível, contraste mínimo de 4.5:1, labels associados | Lighthouse + WebAIM |
| RNF4 | Lighthouse em modo móvel: Acessibilidade ≥ 90, Performance ≥ 85, Boas Práticas ≥ 90, SEO ≥ 90 | Relatório Lighthouse |
| RNF5 | Execução local sem servidor, build ou dependências externas | Abrir `index.html` no navegador |
| RNF6 | CSS externo único, com variáveis em `:root` e unidades relativas | W3C CSS Validator |
| RNF7 | Compatibilidade verificada em Chrome, Firefox e Edge | Tabela de compatibilidade |

## 4. Wireframe

O esboço das três telas está em [`wireframe.svg`](wireframe.svg), nesta mesma
pasta.

**Tela 1 — Catálogo (`index.html`).** Cabeçalho fixo com marca e navegação de
três itens, bloco de abertura compacto e grade de cards dos modelos. Cada card é
um `article` contendo `figure` com a foto e `figcaption` com a legenda.

**Tela 2 — Modelo (`modelo.html`).** Foto em destaque, título do modelo,
parágrafo descritivo e um `aside` com a ficha técnica em lista de definições.
Botão de agendamento ao final do conteúdo principal.

**Tela 3 — Agendar (`agendar.html`).** Formulário em coluna única, com `label`
visível acima de cada campo, mensagens de ajuda associadas por `aria-describedby`
e um único botão de envio.

## 5. Justificativa das decisões de interface

**Navegação de três itens sempre visível, sem menu hambúrguer.** O menu
recolhido economiza espaço, mas esconde a navegação atrás de um clique e exige
JavaScript para abrir. Com apenas três destinos, eles cabem lado a lado mesmo em
360 px de largura. Isso mantém o menu principal visível sem rolagem e preserva a
navegação por teclado sem nenhum script.

**Tarefa principal em dois cliques.** Do catálogo, o usuário chega ao
agendamento direto pelo menu (um clique) ou passando pela ficha do modelo (dois
cliques). Nenhum caminho até a tarefa principal exige três ou mais passos.

**Fonte de sistema em vez de fonte externa.** Uma webfont carregada de CDN
bloqueia a renderização e adiciona uma requisição externa, atrasando o LCP na
auditoria móvel. A pilha de fontes do sistema operacional aparece
instantaneamente e não custa nenhuma requisição.

**Paleta reduzida, com contraste verificado antes de implementar.** Seis cores
principais definidas como variáveis em `:root`. As combinações de texto sobre
fundo foram checadas no WebAIM antes de entrar no CSS, em vez de corrigidas
depois que o Lighthouse reclamar.

**Bloco de abertura compacto.** Em desktop, o conteúdo principal — os primeiros
cards do catálogo — precisa estar visível sem rolagem. Um bloco de abertura
ocupando a tela inteira empurraria o conteúdo para baixo da dobra.

**Grade com `grid-template-columns: repeat(auto-fit, minmax(...))`.** A grade se
reorganiza sozinha conforme a largura disponível, o que reduz a quantidade de
breakpoints necessários e evita regras duplicadas no CSS.

**Dimensões explícitas nas imagens.** Declarar `width` e `height` no HTML permite
ao navegador reservar o espaço antes de a imagem chegar, evitando o deslocamento
de layout que penaliza a métrica CLS.
