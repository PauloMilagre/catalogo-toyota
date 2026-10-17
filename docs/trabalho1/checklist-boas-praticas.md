# Checklist de boas práticas

Seção E do enunciado. Mínimo exigido: 12 das 20 práticas.
Atendidas: 20. Verificado em 21/09/2026.

| # | Prática | Atende | Evidência |
|---|---|---|---|
| 1 | DOCTYPE HTML5 correto | Sim | `<!DOCTYPE html>` na primeira linha das três páginas |
| 2 | Atributo lang | Sim | `<html lang="pt-BR">` nas três páginas |
| 3 | Meta viewport | Sim | `width=device-width, initial-scale=1.0` nas três páginas |
| 4 | HTML sem erros no W3C Validator | Sim | `docs/validacao/w3c-html-*.png` — "No errors or warnings to show" |
| 5 | CSS sem erros no W3C Validator | Sim | `docs/validacao/w3c-css.png` — "Não foram encontrados erros" |
| 6 | CSS externo separado do HTML | Sim | Folha única em `css/estilo.css`, sem `<style>` nas páginas |
| 7 | Uso predominante de unidades relativas | Sim | 38 ocorrências relativas (rem, %, em, ch, vw) contra 10 em px, estas restritas a bordas de 1px |
| 8 | Imagens com alt | Sim | Todas as imagens têm `alt` descritivo, incluindo contexto da cena |
| 9 | Imagens com loading="lazy" | Sim | Aplicado às imagens da Hilux e do Supra; omitido de propósito na primeira imagem visível, para não atrasar o LCP |
| 10 | Dimensões explícitas de imagens | Sim | `width` e `height` em todas as tags `<img>` |
| 11 | Uso de Flexbox ou Grid | Sim | Ambos: Grid com `auto-fit` no catálogo, Flexbox no cabeçalho e nos cards |
| 12 | Ausência de estilos inline | Sim | Nenhum atributo `style=` nas três páginas |
| 13 | Ausência de IDs duplicados | Sim | Nenhum id repetido dentro de uma mesma página |
| 14 | Ausência de links quebrados | Sim | Todos os href apontam para arquivos existentes no projeto |
| 15 | Foco visível | Sim | Regra `:focus-visible` com contorno de 3px, e variante clara dentro do cabeçalho escuro |
| 16 | Contraste WCAG adequado | Sim | Menor contraste de texto do projeto: 5,71:1, acima do mínimo AA de 4,5:1. Lighthouse sem violações |
| 17 | Labels associados a formulários | Sim | Todos os campos com `label for` correspondente ao `id`; textos de ajuda ligados por `aria-describedby` |
| 18 | Navegação por teclado funcional | Sim | Link de salto, ordem de tabulação coerente e foco visível; testado no Firefox 156 |
| 19 | Estrutura semântica completa | Sim | header, nav, main, section, article, aside, footer, figure e figcaption |
| 20 | Uso de variáveis CSS (:root) | Sim | Seis cores, escala de espaçamento, raio de borda e transição definidos em `:root` |