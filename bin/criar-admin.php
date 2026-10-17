<?php

declare(strict_types=1);

/*
 * Cria uma conta de administrador (ou promove uma conta que já existe).
 * A senha é digitada na hora: nenhuma credencial fica gravada no código.
 *
 *   php bin/criar-admin.php
 *
 * Se o terminal não aceitar digitação (alguns casos do Git Bash no Windows),
 * passe os dados direto:
 *
 *   php bin/criar-admin.php --email=admin@exemplo.com --nome="Administrador" --senha=Senha2026
 */

use Toyota\Core\Config;
use Toyota\Core\Database;
use Toyota\Core\HttpException;
use Toyota\Core\Logger;
use Toyota\Repositories\TentativaLoginRepository;
use Toyota\Repositories\UsuarioRepository;
use Toyota\Services\AuthService;

require dirname(__DIR__) . '/src/bootstrap.php';

$config = Config::carregar(dirname(__DIR__) . '/.env');
$pdo = Database::conectar($config);
$usuarios = new UsuarioRepository($pdo);
$logger = new Logger(dirname(__DIR__) . '/' . $config->get('LOG_ARQUIVO', 'logs/app.log'));

$opcoes = getopt('', ['email:', 'nome:', 'senha:']);

$perguntar = static function (string $campo, string $pergunta) use ($opcoes): string {
    if (isset($opcoes[$campo]) && is_string($opcoes[$campo])) {
        return trim($opcoes[$campo]);
    }
    echo $pergunta;
    return trim((string) fgets(STDIN));
};

$email = mb_strtolower($perguntar('email', 'E-mail do administrador: '));
$existente = $usuarios->buscarParaLogin($email);

if ($existente !== null) {
    $usuarios->definirPerfil($existente['id'], 'admin');
    $logger->info('usuario.promovido', ['usuario_id' => $existente['id']]);
    echo "Conta {$email} agora é administradora.\n";
    exit(0);
}

$nome = $perguntar('nome', 'Nome: ');
$senha = $perguntar('senha', 'Senha (8 a 72 caracteres, com letras e números): ');

try {
    $auth = new AuthService($usuarios, new TentativaLoginRepository($pdo), $logger);
    $admin = $auth->registrar(['nome' => $nome, 'email' => $email, 'senha' => $senha], 'admin');
} catch (HttpException $erro) {
    fwrite(STDERR, $erro->getMessage() . "\n");
    foreach ($erro->campos as $campo => $mensagem) {
        fwrite(STDERR, "  {$campo}: {$mensagem}\n");
    }
    exit(1);
}

echo "Administrador criado: {$admin['email']} (id {$admin['id']}).\n";
