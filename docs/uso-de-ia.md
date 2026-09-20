# Registro de uso de IA generativa

Documento mantido durante todo o desenvolvimento, conforme exige o enunciado:
para cada uso, registra-se a ferramenta, a finalidade, o trecho produzido, as
modificações realizadas e a avaliação crítica da sugestão.

**Ferramenta utilizada:** Claude (Anthropic), via interface web.

> Preencha as colunas "modificações realizadas" e "avaliação crítica" com as
> suas próprias palavras depois de revisar cada trecho. São elas que demonstram
> o uso crítico — e é sobre elas que a arguição costuma recair.

---

## Registro 1 — 16/09/2026 — README do repositório

**Finalidade:** produzir o arquivo de instruções de execução exigido pelo
critério F do enunciado.

**Trecho produzido:** estrutura do `README.md` com seções de execução,
estrutura de diretórios, tecnologias e autoria.

**Modificações realizadas:** _(preencher: nome de usuário, ajustes de texto,
o que foi reescrito)_

**Avaliação crítica:** _(preencher: o texto estava adequado? faltou alguma
informação? o que você mudaria?)_

---

## Registro 2 — 16/09/2026 — Artefatos de processo

**Finalidade:** estruturar a descrição do problema, o público-alvo, os
requisitos funcionais e não funcionais e a justificativa das decisões de
interface, exigidos na seção "Processo de desenvolvimento".

**Trecho produzido:** arquivo `docs/processo.md` completo.

**Modificações realizadas:** _(preencher)_

**Avaliação crítica:** _(preencher. Sugestão de ponto a comentar: os requisitos
não funcionais foram derivados diretamente da tabela de avaliação do enunciado,
o que garante rastreabilidade, mas significa que eles descrevem o critério de
nota e não uma necessidade levantada com usuários reais.)_

---

## Registro 3 — 16/09/2026 — Wireframe

**Finalidade:** esboçar o layout das três telas antes da implementação.

**Trecho produzido:** arquivo `docs/wireframe.svg`, esboço de baixa fidelidade
das telas de catálogo, modelo e agendamento.

**Modificações realizadas:** _(preencher)_

**Avaliação crítica:** _(preencher. Ponto honesto a registrar: um wireframe
gerado por IA parte de convenções genéricas de layout; a decisão sobre o que
cada tela precisa mostrar continua sendo do estudante.)_

---

## Registro 4 — 20/09/2026 — Revisão de acessibilidade e auditoria

**Finalidade:** revisar as três páginas e a folha de estilo à luz da WCAG 2.2 AA
antes da primeira auditoria Lighthouse.

**Trecho produzido:** nenhum código de página foi gerado pela IA. A ferramenta
leu os arquivos existentes, calculou a razão de contraste de cada combinação de
cor do projeto e apontou cinco correções: (1) aspas duplicadas no atributo `alt`
da imagem da Hilux, que invalidava o HTML; (2) borda dos campos de formulário em
1,43:1, abaixo dos 3:1 exigidos pelo critério 1.4.11, corrigida para
`var(--tinta-suave)`; (3) comentário do CSS que declarava contraste mínimo de
5,8:1 quando o real é 5,71:1; (4) ausência de favicon, que gerava 404 no console;
(5) `fetchpriority="high"` na imagem de maior renderização.

**Modificações realizadas:** _(preencher: você aplicou as cinco edições à mão no
VS Code. Registre se alterou alguma sugestão, e o que decidiu não fazer.)_

**Avaliação crítica:** _(preencher. Pontos que valem comentário: a falha de
contraste da borda não é detectada pelo Lighthouse — só apareceu porque o
critério 1.4.11 foi verificado manualmente; e a sugestão inicial da IA sobre a
extensão de um arquivo estava errada, o que reforça que sugestão automatizada
precisa de conferência.)_
