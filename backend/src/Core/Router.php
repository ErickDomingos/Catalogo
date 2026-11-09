<?php
declare(strict_types=1);

namespace GameShelf\Core;

/**
 * Roteador com guardas de acesso: 'publica' | 'usuario' | 'admin'.
 * Ordem: rota existe -> autenticação (401) -> permissão (403) -> CSRF (403) -> handler.
 */
final class Router
{
    private const METODOS_ESCRITA = ['POST', 'PUT', 'PATCH', 'DELETE'];

    /** @var list<array{metodo:string,regex:string,handler:callable,acesso:string}> */
    private array $rotas = [];

    /**
     * @param \Closure():(array<string,mixed>|null) $usuarioAtual
     * @param \Closure(Request):bool                $csrfValido
     */
    public function __construct(
        private readonly \Closure $usuarioAtual,
        private readonly \Closure $csrfValido,
    ) {
    }

    public function adicionar(string $metodo, string $padrao, callable $handler, string $acesso = 'publica'): void
    {
        $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $padrao) . '$#';
        $this->rotas[] = ['metodo' => $metodo, 'regex' => $regex, 'handler' => $handler, 'acesso' => $acesso];
    }

    public function despachar(Request $req): Response
    {
        $permitidos = [];
        foreach ($this->rotas as $rota) {
            if (!preg_match($rota['regex'], $req->caminho, $m)) {
                continue;
            }
            if ($rota['metodo'] !== $req->metodo) {
                $permitidos[] = $rota['metodo'];
                continue;
            }
            $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);

            if ($rota['acesso'] !== 'publica') {
                $usuario = ($this->usuarioAtual)();
                if ($usuario === null) {
                    throw new HttpException(401, 'Autenticação necessária.', [], 'nao_autenticado');
                }
                if ($rota['acesso'] === 'admin' && ($usuario['perfil'] ?? '') !== 'admin') {
                    throw new HttpException(403, 'Permissão insuficiente para esta ação.', [], 'sem_permissao');
                }
            }
            if (in_array($req->metodo, self::METODOS_ESCRITA, true) && !($this->csrfValido)($req)) {
                throw new HttpException(403, 'Token CSRF ausente ou inválido.', [], 'csrf_invalido');
            }
            return ($rota['handler'])($req, $params);
        }
        if ($permitidos !== []) {
            throw new HttpException(405, 'Método não permitido.', [], 'metodo_nao_permitido', ['Allow' => implode(', ', array_unique($permitidos))]);
        }
        throw new HttpException(404, 'Rota não encontrada.', [], 'nao_encontrado');
    }
}
