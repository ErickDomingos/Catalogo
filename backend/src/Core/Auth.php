<?php
declare(strict_types=1);

namespace GameShelf\Core;

use GameShelf\Repositories\UsuarioRepositoryInterface;

/** Autenticação por sessão. O perfil é sempre relido do banco (rebaixar/excluir vale na hora). */
final class Auth
{
    private ?array $cache = null;
    private bool $resolvido = false;

    public function __construct(
        private readonly UsuarioRepositoryInterface $usuarios,
        private readonly int $ociosoSegundos = 1800,
    ) {
    }

    /** @param array<string,mixed> $usuario */
    public function entrar(array $usuario): void
    {
        Session::iniciar();
        session_regenerate_id(true);   // evita fixação de sessão
        $_SESSION['uid'] = (int) $usuario['id'];
        $_SESSION['ultimo_acesso'] = time();
        unset($_SESSION['csrf']);      // novo token após mudança de privilégio
        $this->cache = null;
        $this->resolvido = false;
    }

    public function sair(): void
    {
        Session::destruir();
        $this->cache = null;
        $this->resolvido = true;
    }

    /** @return array<string,mixed>|null */
    public function usuario(): ?array
    {
        if ($this->resolvido) {
            return $this->cache;
        }
        $this->resolvido = true;
        if (!Session::existeCookie()) {
            return null;
        }
        Session::iniciar();
        $uid = $_SESSION['uid'] ?? null;
        if (!is_int($uid)) {
            return null;
        }
        if (time() - (int) ($_SESSION['ultimo_acesso'] ?? 0) > $this->ociosoSegundos) {
            Session::destruir();
            return null;
        }
        $usuario = $this->usuarios->porId($uid);
        if ($usuario === null) {
            Session::destruir();
            return null;
        }
        $_SESSION['ultimo_acesso'] = time();
        unset($usuario['senha_hash']);
        return $this->cache = $usuario;
    }
}
