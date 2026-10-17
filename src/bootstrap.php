<?php

declare(strict_types=1);

/*
 * Ponto de partida comum da aplicação, dos scripts de bin/ e dos testes.
 * Registra o carregamento automático das classes do namespace Toyota\,
 * que fica em src/ (padrão PSR-4: Toyota\Core\Router -> src/Core/Router.php).
 */

spl_autoload_register(static function (string $classe): void {
    $prefixo = 'Toyota\\';
    if (!str_starts_with($classe, $prefixo)) {
        return;
    }
    $relativo = str_replace('\\', '/', substr($classe, strlen($prefixo)));
    $arquivo = __DIR__ . '/' . $relativo . '.php';
    if (is_file($arquivo)) {
        require $arquivo;
    }
});

// Erros do PHP (warnings, notices) viram exceções e passam pelo tratamento
// centralizado em ErrorHandler, em vez de aparecerem soltos na página.
set_error_handler(static function (int $nivel, string $mensagem, string $arquivo, int $linha): bool {
    if (!(error_reporting() & $nivel)) {
        return false;
    }
    throw new ErrorException($mensagem, 0, $nivel, $arquivo, $linha);
});

ini_set('display_errors', '0');
