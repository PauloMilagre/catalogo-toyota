<?php

declare(strict_types=1);

namespace Toyota\Services;

use Toyota\Core\HttpException;
use Toyota\Core\Logger;
use Toyota\Core\Validador;
use Toyota\Repositories\TentativaLoginRepository;
use Toyota\Repositories\UsuarioRepository;

/**
 * Cadastro e conferência de credenciais.
 *
 * Senhas são guardadas com password_hash (bcrypt, custo 12): o banco tem só
 * o hash, nunca a senha. password_verify compara em tempo constante.
 */
final class AuthService
{
    /** Falhas de login permitidas por IP dentro da janela. */
    public const LIMITE_FALHAS = 5;
    public const JANELA_SEGUNDOS = 15 * 60;

    private const OPCOES_HASH = ['cost' => 12];

    /**
     * Hash de uma senha qualquer, usado quando o e-mail não existe: o login
     * leva o mesmo tempo nos dois casos, então medir o tempo de resposta
     * não revela quais e-mails estão cadastrados.
     */
    private const HASH_FICTICIO = '$2y$12$GGYoykcDvl4DMm/lNqpW7eHMLRsgFGq4DJIoMFRfK5MNc3QtUNgv6';

    public const REGRAS_CADASTRO = [
        'nome'  => ['obrigatorio', 'texto', 'min:3', 'max:100'],
        'email' => ['obrigatorio', 'texto', 'max:150', 'email'],
        'senha' => ['obrigatorio', 'texto', 'min:8', 'max:72', 'senha_forte'],
    ];

    private const REGRAS_LOGIN = [
        'email' => ['obrigatorio', 'texto', 'max:150', 'email'],
        'senha' => ['obrigatorio', 'texto', 'max:72'],
    ];

    public function __construct(
        private UsuarioRepository $usuarios,
        private TentativaLoginRepository $tentativas,
        private Logger $logger
    ) {
    }

    /**
     * @param array<string, mixed> $entrada nome, email, senha
     * @return array<string, mixed> usuário criado (sem a senha)
     */
    public function registrar(array $entrada, string $perfil = 'cliente'): array
    {
        $dados = Validador::validar($entrada, self::REGRAS_CADASTRO);

        if ($this->usuarios->emailEmUso($dados['email'])) {
            throw new HttpException(409, 'Já existe uma conta com este e-mail.', ['email' => 'E-mail já cadastrado.']);
        }

        $id = $this->usuarios->criar(
            $dados['nome'],
            $dados['email'],
            password_hash($dados['senha'], PASSWORD_DEFAULT, self::OPCOES_HASH),
            $perfil
        );
        $this->logger->info('usuario.criado', ['usuario_id' => $id, 'perfil' => $perfil]);

        return $this->usuarios->buscarPorId($id) ?? throw new \LogicException('Usuário recém-criado não encontrado.');
    }

    /**
     * @param array<string, mixed> $entrada email, senha
     * @return array<string, mixed> usuário autenticado (sem a senha)
     * @throws HttpException 429 se o IP passou do limite, 401 se e-mail ou senha não conferem
     */
    public function autenticar(array $entrada, string $ip): array
    {
        $this->verificarLimite($ip);
        $dados = Validador::validar($entrada, self::REGRAS_LOGIN);

        $usuario = $this->usuarios->buscarParaLogin($dados['email']);
        $senhaConfere = password_verify($dados['senha'], $usuario['senha_hash'] ?? self::HASH_FICTICIO);

        if ($usuario === null || !$senhaConfere) {
            $this->tentativas->registrar($ip, $dados['email'], false);
            $this->logger->aviso('login.falha', ['email' => $dados['email'], 'ip' => $ip]);
            throw new HttpException(401, 'E-mail ou senha inválidos.');
        }

        $this->tentativas->limparFalhas($ip);
        $this->tentativas->registrar($ip, $dados['email'], true);
        $this->logger->info('login.sucesso', ['usuario_id' => $usuario['id'], 'ip' => $ip]);

        if (password_needs_rehash($usuario['senha_hash'], PASSWORD_DEFAULT, self::OPCOES_HASH)) {
            $this->usuarios->atualizarSenha(
                $usuario['id'],
                password_hash($dados['senha'], PASSWORD_DEFAULT, self::OPCOES_HASH)
            );
        }

        unset($usuario['senha_hash']);
        return $usuario;
    }

    private function verificarLimite(string $ip): void
    {
        $situacao = $this->tentativas->situacao($ip, self::JANELA_SEGUNDOS);
        if ($situacao['falhas'] >= self::LIMITE_FALHAS) {
            $this->logger->aviso('login.bloqueado', ['ip' => $ip, 'falhas' => $situacao['falhas']]);
            throw new HttpException(
                429,
                'Muitas tentativas de login. Aguarde alguns minutos e tente de novo.',
                [],
                ['Retry-After' => (string) max(1, $situacao['espera'])]
            );
        }
    }
}
