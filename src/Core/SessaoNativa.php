<?php

declare(strict_types=1);

namespace Toyota\Core;

/**
 * Sessão PHP com as opções de segurança ligadas:
 *  - HttpOnly: JavaScript da página não lê o cookie;
 *  - SameSite=Lax: o navegador não envia o cookie em POST vindo de outro site;
 *  - Secure: só trafega por HTTPS (ligado automaticamente quando há HTTPS);
 *  - strict_mode: o PHP recusa identificadores de sessão que ele não criou,
 *    então um cookie antigo, de uma sessão já encerrada, não volta a valer.
 */
final class SessaoNativa implements Sessao
{
    public static function iniciar(Config $config, string $base): self
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            $https = ($_SERVER['HTTPS'] ?? '') !== '' && ($_SERVER['HTTPS'] ?? '') !== 'off';
            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_only_cookies', '1');
            session_name('toyota_sessao');
            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => $base === '' ? '/' : $base . '/',
                'httponly' => true,
                'samesite' => 'Lax',
                'secure'   => $https || $config->bool('SESSION_SECURE'),
            ]);
            session_start();
        }
        return new self();
    }

    public function get(string $chave, mixed $padrao = null): mixed
    {
        return $_SESSION[$chave] ?? $padrao;
    }

    public function set(string $chave, mixed $valor): void
    {
        $_SESSION[$chave] = $valor;
    }

    public function remover(string $chave): void
    {
        unset($_SESSION[$chave]);
    }

    public function regenerar(): void
    {
        session_regenerate_id(true);
    }

    public function destruir(): void
    {
        $_SESSION = [];
        $parametros = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 3600,
            'path'     => $parametros['path'],
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => $parametros['secure'],
        ]);
        session_destroy();
    }
}
