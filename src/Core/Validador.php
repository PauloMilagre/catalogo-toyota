<?php

declare(strict_types=1);

namespace Toyota\Core;

use DateTimeImmutable;

/**
 * Validação e sanitização de toda entrada externa (formulários, JSON, URL).
 *
 * Uso:
 *   $dados = Validador::validar($entrada, [
 *       'nome'    => ['obrigatorio', 'texto', 'min:3', 'max:100'],
 *       'periodo' => ['obrigatorio', 'em:manha,tarde'],
 *       'idade'   => ['opcional', 'inteiro', 'min:18'],
 *   ]);
 *
 * Devolve só os campos declarados, já limpos e convertidos ao tipo certo.
 * Campos extras são descartados: quem enviar "perfil=admin" no cadastro
 * não consegue se promover. Se algo falhar, lança ValidacaoException (422)
 * com uma mensagem por campo.
 *
 * Regras de tipo: texto, inteiro, decimal, booleano, email, data.
 * Regras de tamanho: min:N e max:N (caracteres no texto, valor no número).
 * Outras: em:a,b,c | regex:/padrao/ | senha_forte.
 */
final class Validador
{
    /**
     * @param array<string, mixed>        $entrada
     * @param array<string, list<string>> $regras
     * @return array<string, mixed>
     * @throws ValidacaoException
     */
    public static function validar(array $entrada, array $regras): array
    {
        $erros = [];
        $saida = [];

        foreach ($regras as $campo => $lista) {
            $valor = $entrada[$campo] ?? null;
            if (is_string($valor)) {
                $valor = self::sanitizar($valor);
            }

            if ($valor === null || $valor === '') {
                if (in_array('opcional', $lista, true)) {
                    $saida[$campo] = null;
                } else {
                    $erros[$campo] = 'Campo obrigatório.';
                }
                continue;
            }

            $erro = null;
            foreach ($lista as $regra) {
                [$valor, $erro] = self::aplicar($regra, $valor);
                if ($erro !== null) {
                    break;
                }
            }

            if ($erro !== null) {
                $erros[$campo] = $erro;
            } else {
                $saida[$campo] = $valor;
            }
        }

        if ($erros !== []) {
            throw new ValidacaoException($erros);
        }
        return $saida;
    }

    /**
     * Limpeza aplicada a todo texto recebido: tira espaços das pontas,
     * padroniza quebras de linha e remove caracteres de controle invisíveis.
     * HTML não é removido aqui: ele é neutralizado na SAÍDA (View::e), que
     * é o lugar certo para impedir XSS sem estragar o texto original.
     */
    public static function sanitizar(string $texto): string
    {
        $texto = str_replace(["\r\n", "\r"], "\n", trim($texto));
        $limpo = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $texto);
        return $limpo ?? $texto; // null = UTF-8 inválido; a regra "texto" recusa depois
    }

    /** @return array{0: mixed, 1: ?string} valor convertido e mensagem de erro */
    private static function aplicar(string $regra, mixed $valor): array
    {
        [$nome, $argumento] = array_pad(explode(':', $regra, 2), 2, null);

        return match ($nome) {
            'obrigatorio', 'opcional' => [$valor, null],
            'texto'       => self::texto($valor),
            'inteiro'     => self::inteiro($valor),
            'decimal'     => self::decimal($valor),
            'booleano'    => self::booleano($valor),
            'email'       => self::email($valor),
            'data'        => self::data($valor),
            'em'          => self::em($valor, explode(',', (string) $argumento)),
            'min'         => self::limite($valor, (float) $argumento, true),
            'max'         => self::limite($valor, (float) $argumento, false),
            'regex'       => [$valor, preg_match((string) $argumento, (string) $valor) === 1 ? null : 'Formato inválido.'],
            'senha_forte' => [$valor, preg_match('/[A-Za-z]/', $valor) && preg_match('/\d/', $valor)
                ? null : 'A senha precisa ter letras e números.'],
            default       => throw new \LogicException("Regra de validação desconhecida: {$regra}"),
        };
    }

    private static function texto(mixed $valor): array
    {
        if (!is_string($valor)) {
            return [$valor, 'Deve ser um texto.'];
        }
        if (!mb_check_encoding($valor, 'UTF-8')) {
            return [$valor, 'Texto com codificação inválida.'];
        }
        return [$valor, null];
    }

    private static function inteiro(mixed $valor): array
    {
        if (is_int($valor)) {
            return [$valor, null];
        }
        if (is_string($valor) && preg_match('/^-?\d{1,10}$/', $valor)) {
            return [(int) $valor, null];
        }
        return [$valor, 'Deve ser um número inteiro.'];
    }

    private static function decimal(mixed $valor): array
    {
        if (is_int($valor) || is_float($valor)) {
            return [(float) $valor, null];
        }
        if (is_string($valor) && preg_match('/^-?\d{1,10}([.,]\d{1,2})?$/', $valor)) {
            return [(float) str_replace(',', '.', $valor), null];
        }
        return [$valor, 'Deve ser um número (use ponto ou vírgula para decimais, até 2 casas).'];
    }

    private static function booleano(mixed $valor): array
    {
        if (is_bool($valor)) {
            return [$valor, null];
        }
        $mapa = ['1' => true, '0' => false, 'true' => true, 'false' => false];
        $chave = is_int($valor) ? (string) $valor : (is_string($valor) ? strtolower($valor) : null);
        if ($chave !== null && array_key_exists($chave, $mapa)) {
            return [$mapa[$chave], null];
        }
        return [$valor, 'Deve ser verdadeiro ou falso.'];
    }

    private static function email(mixed $valor): array
    {
        if (!is_string($valor) || filter_var($valor, FILTER_VALIDATE_EMAIL) === false) {
            return [$valor, 'E-mail inválido.'];
        }
        return [mb_strtolower($valor), null];
    }

    private static function data(mixed $valor): array
    {
        if (is_string($valor)) {
            $data = DateTimeImmutable::createFromFormat('!Y-m-d', $valor);
            if ($data !== false && $data->format('Y-m-d') === $valor) {
                return [$valor, null];
            }
        }
        return [$valor, 'Data inválida. Use o formato AAAA-MM-DD.'];
    }

    /** @param list<string> $opcoes */
    private static function em(mixed $valor, array $opcoes): array
    {
        if (is_string($valor) && in_array($valor, $opcoes, true)) {
            return [$valor, null];
        }
        return [$valor, 'Valor não permitido. Use: ' . implode(', ', $opcoes) . '.'];
    }

    private static function limite(mixed $valor, float $limite, bool $minimo): array
    {
        $n = (int) $limite;
        if (is_string($valor)) {
            $tamanho = mb_strlen($valor);
            if ($minimo && $tamanho < $limite) {
                return [$valor, "Deve ter pelo menos {$n} caracteres."];
            }
            if (!$minimo && $tamanho > $limite) {
                return [$valor, "Deve ter no máximo {$n} caracteres."];
            }
            return [$valor, null];
        }
        if (is_int($valor) || is_float($valor)) {
            if ($minimo && $valor < $limite) {
                return [$valor, "Deve ser no mínimo {$n}."];
            }
            if (!$minimo && $valor > $limite) {
                return [$valor, "Deve ser no máximo {$n}."];
            }
        }
        return [$valor, null];
    }
}
