<?php
declare(strict_types=1);

namespace GameShelf\Services;

use GameShelf\Core\HttpException;
use GameShelf\Core\Logger;
use GameShelf\Core\Validator;
use GameShelf\Dominio;
use GameShelf\Repositories\UsuarioRepositoryInterface;

/** Administração de usuários (somente perfil admin chega aqui, via guarda do roteador). */
final class UsuarioService
{
    public function __construct(
        private readonly UsuarioRepositoryInterface $usuarios,
        private readonly Logger $log,
        private readonly ?string $adminPadraoEmail = null,
    ) {
    }

    /** O administrador padrão (ADMIN_EMAIL do .env) não pode ser excluído nem rebaixado. */
    private function protegido(array $usuario): bool
    {
        return $this->adminPadraoEmail !== null && strcasecmp((string) $usuario['email'], $this->adminPadraoEmail) === 0;
    }

    /**
     * @param array<string,mixed> $query
     * @return array{dados:list<array<string,mixed>>,paginacao:array<string,int>}
     */
    public function listar(array $query): array
    {
        $v = new Validator($query);
        $pagina = $v->inteiro('pagina', 'Página', 1, 100000) ?? 1;
        $porPagina = $v->inteiro('por_pagina', 'Itens por página', 1, 50) ?? 20;
        $v->lancar();
        $total = $this->usuarios->contar();
        return [
            'dados' => array_map(
                fn (array $u) => $u + ['protegido' => $this->protegido($u)],
                $this->usuarios->listar($porPagina, ($pagina - 1) * $porPagina)
            ),
            'paginacao' => ['pagina' => $pagina, 'por_pagina' => $porPagina, 'total' => $total, 'paginas' => max(1, (int) ceil($total / $porPagina))],
        ];
    }

    /**
     * @param array<string,mixed> $admin
     * @param array<string,mixed> $entrada
     */
    public function alterarPerfil(array $admin, int $id, array $entrada): void
    {
        $v = new Validator($entrada);
        $perfil = $v->enum('perfil', 'Perfil', Dominio::PERFIS);
        $v->lancar();

        $alvo = $this->usuarios->porId($id) ?? throw new HttpException(404, 'Usuário não encontrado.', [], 'nao_encontrado');
        if ($this->protegido($alvo) && $perfil !== 'admin') {
            throw new HttpException(409, 'O administrador padrão não pode ser rebaixado.', [], 'conflito');
        }
        if ($alvo['perfil'] === 'admin' && $perfil !== 'admin' && $this->usuarios->contarAdmins() <= 1) {
            throw new HttpException(409, 'Não é possível rebaixar o último administrador.', [], 'conflito');
        }
        $this->usuarios->atualizarPerfil($id, (string) $perfil);
        $this->log->info('usuario_perfil_alterado', ['admin_id' => (int) $admin['id'], 'usuario_id' => $id, 'perfil' => $perfil]);
    }

    /** @param array<string,mixed> $admin */
    public function excluir(array $admin, int $id): void
    {
        if ($id === (int) $admin['id']) {
            throw new HttpException(409, 'Você não pode excluir a própria conta por aqui.', [], 'conflito');
        }
        $alvo = $this->usuarios->porId($id) ?? throw new HttpException(404, 'Usuário não encontrado.', [], 'nao_encontrado');
        if ($this->protegido($alvo)) {
            throw new HttpException(409, 'O administrador padrão não pode ser excluído.', [], 'conflito');
        }
        $this->usuarios->excluir($id);
        $this->log->info('usuario_excluido', ['admin_id' => (int) $admin['id'], 'usuario_id' => $id]);
    }
}
