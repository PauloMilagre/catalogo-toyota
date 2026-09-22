# Registro de uso de IA generativa

Documento mantido durante o desenvolvimento do Trabalho Individual I, conforme
exige o enunciado: para cada uso, registra-se a ferramenta, a finalidade, o
trecho produzido, as modificações realizadas e a avaliação crítica da sugestão.

**Ferramenta utilizada:** Claude (Anthropic), via interface de chat.

## Nota sobre a extensão do uso

A ferramenta produziu as versões iniciais de todos os arquivos de código e de
documentação deste projeto: `index.html`, `modelo.html`, `agendar.html`,
`css/estilo.css`, `README.md`, `docs/processo.md`, `docs/wireframe.svg` e o
modelo deste próprio documento. As mensagens de commit também foram sugeridas
por ela, com exceção do commit inicial gerado automaticamente pelo GitHub.

O trabalho do estudante concentrou-se na condução do projeto: definição de tema
e escopo, decisões de conteúdo, execução de todas as etapas de verificação
(auditorias, validações, testes em três navegadores, capturas), identificação e
correção de erros — inclusive erros cometidos pela própria ferramenta — e
revisão final.

Registra-se também que foi decidido não utilizar assistentes de código
integrados ao editor (GitHub Copilot ou equivalente) durante este trabalho,
para manter o uso de IA concentrado em uma única ferramenta e rastreável.

> Os campos "modificações realizadas" e "avaliação crítica" devem ser
> preenchidos pelo estudante, com suas próprias palavras.

---

## Registro 1 — 16/09/2026 — Análise do enunciado e planejamento

**Finalidade:** compreender os critérios de avaliação e definir escopo e
cronograma do trabalho.

**Trecho produzido:** leitura e sistematização dos critérios do enunciado;
alerta de que o critério de commits em dias distintos exigia criar o
repositório no mesmo dia; plano de trabalho de sete dias; definição de escopo
em três páginas; recomendação de usar fonte do sistema em vez de fonte externa;
orientação para executar o Lighthouse via servidor local, já que a ferramenta
não audita endereços `file://`.

**Modificações realizadas:** Nenhuma. O plano de trabalho e o escopo de três páginas foram seguidos como propostos.

**Avaliação crítica:** O plano e o escopo me pareceram adequados e eu segui como veio. Não encontrei nada que eu mudaria.

---

## Registro 2 — 16/09/2026 — Repositório e README

**Finalidade:** criar o repositório, produzir o arquivo de instruções de
execução exigido pelo critério de controle de versão e definir uma convenção de
mensagens de commit.

**Trecho produzido:** configuração do repositório; texto completo do
`README.md` com seções de execução, estrutura de diretórios, tecnologias e
autoria; convenção de prefixos `docs:`, `feat:`, `style:`, `fix:` e `chore:`.

**Modificações realizadas:** Corrigi o nome do autor, que veio errado no texto gerado.

**Avaliação crítica:** O README atendia ao que o enunciado pedia. Precisei conferir os dados de autoria, que vieram errados.

---

## Registro 3 — 16/09/2026 — Artefatos de processo

**Finalidade:** produzir os artefatos exigidos na seção "Processo de
desenvolvimento" do enunciado, antes da implementação.

**Trecho produzido:** `docs/processo.md` completo (descrição do problema,
público-alvo, requisitos funcionais RF1 a RF6, requisitos não funcionais RNF1 a
RNF7, descrição das três telas e justificativa de sete decisões de interface);
`docs/wireframe.svg`; e o modelo deste documento de registro.

**Modificações realizadas:** Nenhuma.

**Avaliação crítica:** Os artefatos cobriram o que o enunciado pedia na seção de processo de desenvolvimento. Achei tudo em ordem.

---

## Registro 4 — 17/09/2026 — index.html e tratamento das imagens

**Finalidade:** implementar a página de catálogo e preparar as imagens dos
modelos.

**Trecho produzido:** `index.html` completo — link de salto, cabeçalho com
`aria-current`, três cards em `article` com `figure`, `figcaption` e `dl`, e
rodapé. As três fotografias foram redimensionadas para 760 px de largura e o
logotipo teve o fundo removido.

**Correções feitas pela própria ferramenta, sem solicitação do estudante:** as
dimensões declaradas no HTML passaram de 980 para 760 px, para corresponder às
imagens otimizadas; e os três textos alternativos, escritos antes de a
ferramenta ver as fotografias, descreviam cor e ângulo errados nos três casos,
tendo sido corrigidos depois que as imagens foram exibidas.

**Modificações realizadas:** Nenhuma da minha parte. As correções de dimensões e dos textos alternativos partiram da própria ferramenta, depois que ela viu as fotografias.

**Avaliação crítica:** Na primeira leitura entendi o arquivo por cima, o suficiente para me situar no projeto. Os textos alternativos vieram errados porque foram escritos antes de a ferramenta ver as imagens.

---

## Registro 5 — 18/09/2026 — Folha de estilo

**Finalidade:** implementar a folha de estilo única do projeto.

**Trecho produzido:** `css/estilo.css` — seis cores em variáveis `:root`,
escala de espaçamento, regra de foco visível, grade com `auto-fit` e `minmax`,
estilos da ficha técnica e do formulário, e ajustes para telas estreitas.
Inclui a substituição do vermelho institucional `#EB0A1E` por `#C8102E`, por
este oferecer contraste adequado sobre fundo claro.

**Ajustes durante a implementação:** foi necessário acrescentar
`class="faixa"` ao elemento `<main>`, pois o CSS dependia dessa classe e ela
não havia sido incluída no HTML gerado antes. A altura fixa de `11rem` nas
imagens dos cards cortava os veículos e foi trocada por `height: auto` com
`aspect-ratio: 3 / 2`.

**Modificações realizadas:** Corrigi um ponto e vírgula duplicado que sobrou da minha edição na linha de altura das imagens.

**Avaliação crítica:** Li o CSS por cima na primeira vez. A organização em seções numeradas ajudou a me localizar. O problema da altura fixa cortando os carros só ficou visível depois de abrir a página no navegador.

---

## Registro 6 — 18/09/2026 — modelo.html e agendar.html

**Finalidade:** implementar a página de ficha detalhada e a página de
agendamento.

**Trecho produzido:** `modelo.html`, com a ficha do Corolla Altis Hybrid em
`aside` e lista de definições; e `agendar.html`, formulário de sete campos com
`label` associado, textos de ajuda ligados por `aria-describedby` e validação
nativa do HTML5, sem `action` e sem JavaScript. A escolha de qual modelo
receberia a ficha detalhada foi delegada à ferramenta. Posteriormente
acrescentou-se `max-width: 40rem` ao bloco da ficha técnica.

**Modificações realizadas:** Nenhuma.

**Avaliação crítica:** As duas páginas atenderam ao que eu esperava. Deleguei a escolha de qual modelo receberia a ficha detalhada.

---

## Registro 7 — 20/09/2026 — Revisão de acessibilidade e auditoria

**Finalidade:** revisar as três páginas e a folha de estilo à luz da WCAG 2.2
nível AA, antes da primeira auditoria Lighthouse.

**Trecho produzido:** nenhum código de página foi gerado neste uso. A
ferramenta leu os arquivos existentes, calculou a razão de contraste de cada
combinação de cor do projeto e apontou cinco correções: aspas duplicadas no
atributo `alt` da imagem da Hilux, que invalidavam o HTML; borda dos campos de
formulário em 1,43:1, abaixo dos 3:1 exigidos pelo critério 1.4.11; comentário
no CSS declarando contraste mínimo de 5,8:1 quando o valor real é 5,71:1;
ausência de favicon, que gerava erro 404 no console; e `fetchpriority="high"`
na imagem de maior renderização.

**Erro da ferramenta neste uso:** ao interpretar uma captura de tela do
Explorador de Arquivos, a ferramenta concluiu que um arquivo estava sem
extensão e orientou acrescentá-la, quando na verdade o Windows apenas ocultava
as extensões. A orientação gerou arquivos com extensão duplicada, corrigidos em
seguida.

**Modificações realizadas:** Apliquei as cinco correções à mão no VS Code. Corrigi também a extensão duplicada que a orientação errada da ferramenta gerou nos arquivos de captura.

**Avaliação crítica:** Aqui o papel inverteu: a ferramenta apontava e eu executava e conferia. Achei interessante ficar executando. Foi nessa parte que comecei a perguntar quando alguma coisa não batia com o que eu estava vendo na tela.

---

## Registro 8 — 21/09/2026 — Validação e testes de compatibilidade

**Finalidade:** apoiar a validação W3C, os testes nos três navegadores e a
montagem do checklist de boas práticas.

**Trecho produzido:** verificação prévia do HTML das três páginas, que
identificou um erro no `select` de período — a primeira opção de um campo
obrigatório precisa ter valor vazio; roteiro dos testes de compatibilidade;
tabela de compatibilidade a partir das versões levantadas pelo estudante; e o
arquivo `docs/checklist-boas-praticas.md`.

**Erro da ferramenta neste uso:** previu que a tarja de validação nativa do
formulário teria aparência distinta entre Firefox e navegadores Chromium. A
verificação direta mostrou que a aparência é a mesma nos dois, e a tabela foi
corrigida.

**Modificações realizadas:** Apliquei a correção do select no agendar.html. Levantei as versões dos três navegadores e executei os testes de renderização, teclado e formulário em cada um.

**Avaliação crítica:** A verificação prévia do HTML evitou que eu descobrisse o erro do select só no validador. A previsão errada sobre o comportamento no Edge me fez conferir antes de escrever a tabela do relatório.

---

## Registro 9 — 22/09/2026 — Fichas da Hilux e do Supra

**Finalidade:** replicar o gabarito da ficha detalhada para os outros dois
modelos do catálogo, eliminando a limitação de os três cards apontarem para a
mesma página.

**Trecho produzido:** `hilux.html` e `supra.html`, a partir da estrutura de
`modelo.html`, com textos descritivos e fichas técnicas próprias; ajuste do
seletor de CSS de `a[aria-current="page"]` para `a[aria-current]`, para
acomodar o valor `true` usado nas páginas que não correspondem a um item do
menu principal.

**Observação sobre os dados:** os valores das fichas técnicas são ilustrativos,
conforme declarado na seção 12.3 do relatório. Não foram obtidos em fonte
oficial da fabricante.

**Modificações realizadas:**
Coloquei os dois arquivos na raiz do projeto, troquei os links dos cards da Hilux e do Supra no index.html, e ajustei o seletor do CSS que ainda estava com ="page". Validei as duas páginas no W3C e revalidei o CSS.

**Avaliação crítica:** [Notei o problema clicando na ficha da Hilux e vendo aparecer o Corolla. Vi na hora que estava completamente errado e que tinha que resolver o quanto antes.]

---

## Registro 10 — 22/09/2026 — Relatório técnico

**Finalidade:** produzir o relatório técnico em PDF exigido pelo enunciado.

**Trecho produzido:** documento completo com as quatorze seções previstas, a
partir dos dados verificados ao longo do trabalho — resultados das auditorias
Lighthouse, razões de contraste calculadas, resultados das validações W3C,
tabela de compatibilidade e decisões técnicas registradas durante o
desenvolvimento. Os campos de avaliação crítica, trabalhos futuros e conclusões
foram deixados em branco para preenchimento pelo estudante.

**Modificações realizadas:** 
Escrevi os três blocos que estavam em branco: a avaliação crítica do uso de IA, o acréscimo em trabalhos futuros e o parágrafo final das conclusões. Ajustei a redação de alguns trechos

**Avaliação crítica:** [Nos campos deixados em branco eu apresentei as minhas contribuições e a minha análise sobre o desenvolvimento do projeto.]
