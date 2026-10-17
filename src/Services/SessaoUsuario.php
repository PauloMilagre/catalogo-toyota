<?php

declare(strict_types=1);

namespace Toyota\Services;

use Toyota\Core\Csrf;
use Toyota\Core\Sessao;
use Toyota\Core\UsuarioAtual;
use Toyota\Repositories\UsuarioRepository;

/**
 * Liga a sessão ao usuário logado.
 *
 * A sessão guarda só o id. Os dados (inclusive o perfil) são lidos do banco
 * a cada requisição: se um administrador perder o perfil, a mudança vale
 * na hora, sem esperar a pessoa sair e entrar de novo.
 */
final class SessaoUsuario implements UsuarioAtual
{
    /** Sessão parada por mais de 30 minutos expira. */
    public const TEMPO_OCIOSO_SEGUNDOS = 30 * 60;

    private bool $carregado = false;
    private ?array $usuario = null;

    public function __construct(
        private Sessao $sessao,
        private UsuarioRepository $usuarios,
        private Csrf $csrf
    ) {
    }

    public function usuario(): ?array
    {
        if ($this->carregado) {
            return $this->usuario;
        }
        $this->carregado = true;

        $id = $this->sessao->get('usuario_id');
        if (!is_int($id)) {
            return null;
        }

        $ultimoAcesso = (int) $this->sessao->get('ultimo_acesso', 0);
        if (time() - $ultimoAcesso > self::TEMPO_OCIOSO_SEGUNDOS) {
            $this->sair();
            return null;
        }

        $this->usuario = $this->usuarios->buscarPorId($id);
        if ($this->usuario === null) {
            $this->sair();
            return null;
        }
        $this->sessao->set('ultimo_acesso', time());
        return $this->usuario;
    }

    /** Novo id de sessão e novo token CSRF a cada login (evita fixação de sessão). */
    public function entrar(array $usuario): string
    {
        $this->sessao->regenerar();
        $this->sessao->set('usuario_id', $usuario['id']);
        $this->sessao->set('ultimo_acesso', time());
        $this->usuario = $usuario;
        $this->carregado = true;
        return $this->csrf->renovar();
    }

    public function sair(): void
    {
        $this->sessao->destruir();
        $this->usuario = null;
        $this->carregado = true;
    }
}
