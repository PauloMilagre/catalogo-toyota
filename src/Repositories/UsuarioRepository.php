<?php

declare(strict_types=1);

namespace Toyota\Repositories;

final class UsuarioRepository extends Repositorio
{
    private const COLUNAS = 'id, nome, email, perfil, criado_em';

    public function buscarPorId(int $id): ?array
    {
        $linha = $this->linha('SELECT ' . self::COLUNAS . ' FROM usuarios WHERE id = :id', ['id' => $id]);
        return $linha === null ? null : $this->hidratar($linha);
    }

    /** Única consulta que devolve o hash da senha: usada só no login. */
    public function buscarParaLogin(string $email): ?array
    {
        $linha = $this->linha(
            'SELECT ' . self::COLUNAS . ', senha_hash FROM usuarios WHERE email = :email',
            ['email' => $email]
        );
        return $linha === null ? null : $this->hidratar($linha);
    }

    public function emailEmUso(string $email): bool
    {
        return (bool) $this->valor('SELECT 1 FROM usuarios WHERE email = :email', ['email' => $email]);
    }

    public function criar(string $nome, string $email, string $senhaHash, string $perfil = 'cliente'): int
    {
        $this->executar(
            'INSERT INTO usuarios (nome, email, senha_hash, perfil) VALUES (:nome, :email, :hash, :perfil)',
            ['nome' => $nome, 'email' => $email, 'hash' => $senhaHash, 'perfil' => $perfil]
        );
        return $this->ultimoId();
    }

    public function atualizarSenha(int $id, string $senhaHash): void
    {
        $this->executar('UPDATE usuarios SET senha_hash = :hash WHERE id = :id', ['hash' => $senhaHash, 'id' => $id]);
    }

    public function definirPerfil(int $id, string $perfil): void
    {
        $this->executar('UPDATE usuarios SET perfil = :perfil WHERE id = :id', ['perfil' => $perfil, 'id' => $id]);
    }

    private function hidratar(array $linha): array
    {
        $linha['id'] = (int) $linha['id'];
        return $linha;
    }
}
