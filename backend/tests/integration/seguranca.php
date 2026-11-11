<?php
declare(strict_types=1);

/**
 * Testes de integração e de segurança contra o servidor em execução (HTTP real + banco real).
 *
 *   1) php -S localhost:8000 -t ../frontend router.php        (em outro terminal, dentro de backend/)
 *   2) php tests/integration/seguranca.php [http://localhost:8000] | tee ../docs/evidencias/seguranca.txt
 *
 * Cada teste imprime a requisição, o status e um trecho da resposta: use a saída como evidência no relatório.
 * Os testes criam usuários "teste+<hex>@gameshelf.local" (limpeza em docs/TESTES.md).
 */

require __DIR__ . '/../../src/autoload.php';

use GameShelf\Core\Env;

Env::carregar(__DIR__ . '/../../.env');
date_default_timezone_set(Env::get('APP_TZ', 'America/Sao_Paulo') ?? 'America/Sao_Paulo');
$base = rtrim($argv[1] ?? 'http://localhost:8000', '/');

final class Cliente
{
    /** @var array<string,string> */
    public array $cookies = [];
    private ?string $csrf = null;

    public function __construct(private readonly string $base)
    {
    }

    /** @return array{status:int,cabecalhos:array<string,string>,texto:string,json:mixed} */
    public function req(string $metodo, string $caminho, ?array $corpo = null, bool $comCsrf = true, ?string $csrfForcado = null): array
    {
        // O token precisa ser buscado ANTES de montar o cabeçalho Cookie: é essa busca que cria a sessão.
        $tokenCsrf = null;
        if ($metodo !== 'GET') {
            if ($csrfForcado !== null) {
                $tokenCsrf = $csrfForcado;
            } elseif ($comCsrf) {
                $tokenCsrf = $this->csrf ??= $this->buscarCsrf();
            }
        }
        $cab = ['Accept: application/json'];
        if ($this->cookies !== []) {
            $cab[] = 'Cookie: ' . implode('; ', array_map(fn ($k, $v) => "{$k}={$v}", array_keys($this->cookies), $this->cookies));
        }
        if ($tokenCsrf !== null) {
            $cab[] = 'X-CSRF-Token: ' . $tokenCsrf;
        }
        $conteudo = '';
        if ($corpo !== null) {
            $conteudo = json_encode($corpo, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            $cab[] = 'Content-Type: application/json';
        }
        $ctx = stream_context_create(['http' => [
            'method' => $metodo, 'header' => implode("\r\n", $cab), 'content' => $conteudo,
            'ignore_errors' => true, 'follow_location' => 0, 'timeout' => 15,
        ]]);
        $texto = @file_get_contents($this->base . $caminho, false, $ctx);
        // PHP 8.5 depreciou $http_response_header em favor de http_get_last_response_headers()
        $linhasCab = function_exists('http_get_last_response_headers') ? http_get_last_response_headers() : ($http_response_header ?? null);
        if (empty($linhasCab)) {
            throw new RuntimeException("Servidor inacessível em {$this->base}. Ele está rodando?");
        }
        $texto = (string) $texto;
        preg_match('#HTTP/\S+\s+(\d{3})#', $linhasCab[0], $m);
        $cabecalhos = [];
        foreach (array_slice($linhasCab, 1) as $linha) {
            if (str_contains($linha, ':')) {
                [$n, $v] = explode(':', $linha, 2);
                $cabecalhos[strtolower(trim($n))] = trim($v);
                if (strtolower(trim($n)) === 'set-cookie') {
                    $par = explode(';', trim($v))[0];
                    [$nome, $valor] = array_pad(explode('=', $par, 2), 2, '');
                    if ($valor === '' || $valor === 'deleted') {
                        unset($this->cookies[$nome]);
                    } else {
                        $this->cookies[$nome] = $valor;
                    }
                }
            }
        }
        if ($metodo === 'POST' && str_starts_with($caminho, '/api/auth/')) {
            $this->csrf = null; // o token é renovado no login/logout/registro
        }
        return ['status' => (int) ($m[1] ?? 0), 'cabecalhos' => $cabecalhos, 'texto' => $texto, 'json' => json_decode($texto, true)];
    }

    private function buscarCsrf(): string
    {
        return (string) ($this->req('GET', '/api/csrf')['json']['csrf_token'] ?? '');
    }
}

$falhas = 0;
$total = 0;
function relatar(string $nome, bool $passou, string $evidencia): void
{
    global $falhas, $total;
    $total++;
    if (!$passou) {
        $falhas++;
    }
    echo ($passou ? '[PASSOU]  ' : '[FALHOU]  ') . $nome . "\n          " . str_replace("\n", "\n          ", $evidencia) . "\n\n";
}
function resumo(string $metodo, string $caminho, array $r): string
{
    $trecho = mb_substr(preg_replace('/\s+/', ' ', $r['texto']) ?? '', 0, 150);
    return "{$metodo} {$caminho}\n=> HTTP {$r['status']} {$trecho}";
}
function novoUsuario(string $base, array $extra = []): array
{
    $c = new Cliente($base);
    $email = 'teste+' . bin2hex(random_bytes(4)) . '@gameshelf.local';
    $r = $c->req('POST', '/api/auth/registro', ['nome' => 'Teste Automatico', 'email' => $email, 'senha' => 'Teste12345'] + $extra);
    return [$c, $email, $r];
}

echo "GameShelf - testes de segurança e integração\nAlvo: {$base}\nData: " . date('c') . "\n" . str_repeat('=', 70) . "\n\n";

try {
    // Pré-condição: catálogo carregado (seed).
    $visitante = new Cliente($base);
    $todos = $visitante->req('GET', '/api/jogos?por_pagina=1');
    $totalCatalogo = (int) ($todos['json']['paginacao']['total'] ?? 0);
    relatar('Pré-condição: visitante lê o catálogo oficial (seed executado)', $todos['status'] === 200 && $totalCatalogo >= 128, resumo('GET', '/api/jogos?por_pagina=1', $todos) . "\ntotal no catálogo = {$totalCatalogo}");

    // 1) SQL Injection ----------------------------------------------------------------------
    $payload = rawurlencode("' OR '1'='1");
    $r = $visitante->req('GET', "/api/jogos?busca={$payload}");
    relatar("SQL Injection na busca (\"' OR '1'='1\") não devolve a tabela inteira", $r['status'] === 200 && ($r['json']['paginacao']['total'] ?? -1) === 0, resumo('GET', "/api/jogos?busca={$payload}", $r));

    $drop = rawurlencode("x'; DROP TABLE jogos; --");
    $r1 = $visitante->req('GET', "/api/jogos?busca={$drop}");
    $r2 = $visitante->req('GET', '/api/jogos?por_pagina=1');
    relatar('SQL Injection com DROP TABLE não afeta o banco (tabela jogos intacta)', $r1['status'] === 200 && ($r2['json']['paginacao']['total'] ?? 0) === $totalCatalogo, resumo('GET', "/api/jogos?busca={$drop}", $r1) . "\nDepois: total no catálogo = " . ($r2['json']['paginacao']['total'] ?? '?'));

    $c = new Cliente($base);
    $a = $c->req('POST', '/api/auth/login', ['email' => "a'or'1'='1@x.com", 'senha' => "' OR '1'='1"]);
    $b = $c->req('POST', '/api/auth/login', ['email' => "' OR 1=1 -- ", 'senha' => 'qualquer123']);
    relatar('SQL Injection no login (e-mail sintaticamente válido e inválido) não autentica', $a['status'] === 401 && $b['status'] === 422, resumo('POST', '/api/auth/login (email válido com aspas)', $a) . "\n" . resumo('POST', '/api/auth/login (email malformado)', $b));

    $r = $visitante->req('GET', '/api/jogos/1%20OR%201=1');
    relatar('SQL Injection no parâmetro de rota (/jogos/1 OR 1=1) vira 404', $r['status'] === 404, resumo('GET', '/api/jogos/1%20OR%201=1', $r));

    // 2) XSS --------------------------------------------------------------------------------
    [$u, $email, $reg] = novoUsuario($base);
    $titulo = '<script>alert(1)</script>Jogo XSS ' . bin2hex(random_bytes(3));
    $cri = $u->req('POST', '/api/jogos', ['titulo' => $titulo, 'genero' => 'Ação', 'plataformas' => ['pc'], 'resenha' => '<img src=x onerror=alert(2)>Boa']);
    $lista = $u->req('GET', '/api/jogos?escopo=meus');
    relatar('XSS armazenado: tags são removidas na entrada e o JSON nunca contém "<"', $cri['status'] === 201 && !str_contains((string) ($cri['json']['jogo']['titulo'] ?? '<'), '<') && !str_contains($lista['texto'], '<') && !str_contains($lista['texto'], 'onerror=alert(2)>'), 'Enviado: ' . $titulo . "\n" . resumo('POST', '/api/jogos', $cri));

    $r = $visitante->req('GET', '/api/jogos?genero=' . rawurlencode('<script>alert(1)</script>'));
    relatar('XSS refletido: parâmetro malicioso não é refletido; resposta é JSON com nosniff', $r['status'] === 422 && !str_contains($r['texto'], '<script') && str_contains($r['cabecalhos']['content-type'] ?? '', 'application/json') && ($r['cabecalhos']['x-content-type-options'] ?? '') === 'nosniff', resumo('GET', '/api/jogos?genero=<script>...', $r) . "\nContent-Type: " . ($r['cabecalhos']['content-type'] ?? '?') . ' | X-Content-Type-Options: ' . ($r['cabecalhos']['x-content-type-options'] ?? '?'));

    // 3) Acesso sem login ---------------------------------------------------------------------
    $anon = new Cliente($base);
    $r1 = $anon->req('POST', '/api/jogos', ['titulo' => 'Invasor', 'genero' => 'Ação', 'plataformas' => ['pc']]);
    $r2 = $anon->req('GET', '/api/auth/me');
    $r3 = $anon->req('GET', '/api/listas');
    $r4 = $anon->req('GET', '/api/usuarios');
    relatar('Acesso sem login a rotas protegidas retorna 401', [$r1['status'], $r2['status'], $r3['status'], $r4['status']] === [401, 401, 401, 401], resumo('POST', '/api/jogos', $r1) . "\n" . resumo('GET', '/api/auth/me', $r2) . "\n" . resumo('GET', '/api/listas', $r3) . "\n" . resumo('GET', '/api/usuarios', $r4));

    // 4) Perfil insuficiente --------------------------------------------------------------------
    $oficial = $visitante->req('GET', '/api/jogos?por_pagina=1');
    $idOficial = (int) ($oficial['json']['dados'][0]['id'] ?? 0);
    $r1 = $u->req('GET', '/api/usuarios');
    $r2 = $u->req('PATCH', '/api/usuarios/1', ['perfil' => 'admin']);
    $r3 = $u->req('DELETE', "/api/jogos/{$idOficial}");
    $r4 = $u->req('PUT', "/api/jogos/{$idOficial}", ['titulo' => 'Hack', 'genero' => 'Ação', 'plataformas' => ['pc']]);
    relatar('Usuário comum não acessa rotas de admin nem altera jogo oficial (403)', [$r1['status'], $r2['status'], $r3['status'], $r4['status']] === [403, 403, 403, 403], resumo('GET', '/api/usuarios', $r1) . "\n" . resumo('PATCH', '/api/usuarios/1', $r2) . "\n" . resumo('DELETE', "/api/jogos/{$idOficial}", $r3) . "\n" . resumo('PUT', "/api/jogos/{$idOficial}", $r4));

    // 5) CSRF -------------------------------------------------------------------------------------
    $c = new Cliente($base);
    $r1 = $c->req('POST', '/api/auth/login', ['email' => $email, 'senha' => 'Teste12345'], false);
    $r2 = $c->req('POST', '/api/auth/login', ['email' => $email, 'senha' => 'Teste12345'], true, 'token-falso');
    $r3 = $u->req('POST', '/api/jogos', ['titulo' => 'Sem token', 'genero' => 'Ação', 'plataformas' => ['pc']], false);
    relatar('CSRF: escrita sem token ou com token falso é recusada (403)', [$r1['status'], $r2['status'], $r3['status']] === [403, 403, 403], resumo('POST', '/api/auth/login (sem token)', $r1) . "\n" . resumo('POST', '/api/auth/login (token falso)', $r2) . "\n" . resumo('POST', '/api/jogos (logado, sem token)', $r3));

    // 6) Sessão inválida / logout ---------------------------------------------------------------------
    $login = $c->req('POST', '/api/auth/login', ['email' => $email, 'senha' => 'Teste12345']);
    $cookieAntigo = $c->cookies;
    $eu = $c->req('GET', '/api/auth/me');
    $out = $c->req('POST', '/api/auth/logout');
    $reuso = new Cliente($base);
    $reuso->cookies = $cookieAntigo;
    $r1 = $reuso->req('GET', '/api/auth/me');
    $forjado = new Cliente($base);
    $forjado->cookies = ['gameshelf_sid' => 'sessaoinventada1234567890abcdef'];
    $r2 = $forjado->req('GET', '/api/auth/me');
    relatar('Login/logout funcionais; sessão antiga e sessão forjada não autenticam (401)', $login['status'] === 200 && $eu['status'] === 200 && $out['status'] === 204 && $r1['status'] === 401 && $r2['status'] === 401, resumo('POST', '/api/auth/login', $login) . "\n" . resumo('GET', '/api/auth/me (logado)', $eu) . "\nPOST /api/auth/logout => HTTP {$out['status']}\n" . resumo('GET', '/api/auth/me (cookie antigo reutilizado)', $r1) . "\n" . resumo('GET', '/api/auth/me (cookie forjado)', $r2));

    // 7) Mass assignment ----------------------------------------------------------------------------------
    [$m, , $regM] = novoUsuario($base, ['perfil' => 'admin']);
    $meu = $m->req('GET', '/api/auth/me');
    relatar('Cadastro público com "perfil":"admin" é ignorado (nasce como usuario)', $regM['status'] === 201 && ($meu['json']['usuario']['perfil'] ?? '') === 'usuario', resumo('POST', '/api/auth/registro {perfil:admin}', $regM) . "\n" . resumo('GET', '/api/auth/me', $meu));

    // 8) Limite de tentativas de login ------------------------------------------------------------------------
    $alvo = 'teste+' . bin2hex(random_bytes(4)) . '@gameshelf.local';
    $codigos = [];
    $cb = new Cliente($base);
    for ($i = 0; $i < 6; $i++) {
        $codigos[] = $cb->req('POST', '/api/auth/login', ['email' => $alvo, 'senha' => 'Errada12345'])['status'];
    }
    relatar('Rate limiting: 5 falhas seguidas bloqueiam a 6ª tentativa (429)', array_slice($codigos, 0, 5) === [401, 401, 401, 401, 401] && $codigos[5] === 429, 'Status das 6 tentativas: ' . implode(', ', $codigos));

    // 9) Fluxo CRUD completo + lista pessoal (integração) ---------------------------------------------------------
    [$f, ,] = novoUsuario($base);
    $novo = $f->req('POST', '/api/jogos', ['titulo' => 'Jogo CRUD ' . bin2hex(random_bytes(3)), 'genero' => 'RPG', 'console' => 'ps5', 'ano' => 2024, 'duracao' => 25, 'metacritic' => 88, 'comunidade' => 9.1]);
    $id = (int) ($novo['json']['jogo']['id'] ?? 0);
    $upd = $f->req('PUT', "/api/jogos/{$id}", ['titulo' => 'Jogo CRUD editado ' . bin2hex(random_bytes(2)), 'genero' => 'Aventura', 'plataformas' => ['pc', 'playstation'], 'ano' => 2025]);
    $lst = $f->req('PUT', "/api/listas/{$id}", ['estado' => 'jogando']);
    $ver = $f->req('GET', "/api/jogos/{$id}");
    $del = $f->req('DELETE', "/api/jogos/{$id}");
    $apos = $f->req('GET', "/api/jogos/{$id}");
    relatar('CRUD completo de jogo + lista pessoal (criar, editar, marcar "jogando", ler, excluir)', $novo['status'] === 201 && $upd['status'] === 200 && $lst['status'] === 204 && ($ver['json']['jogo']['minha_lista'] ?? '') === 'jogando' && $del['status'] === 204 && $apos['status'] === 404, 'POST=' . $novo['status'] . ' PUT=' . $upd['status'] . ' PUT lista=' . $lst['status'] . ' GET.minha_lista=' . ($ver['json']['jogo']['minha_lista'] ?? '?') . ' DELETE=' . $del['status'] . ' GET apos excluir=' . $apos['status']);

    // 9b) Consistência entre telas: o que a página Catálogo grava aparece em Minha conta (mesmo banco) ---------------
    [$g, ,] = novoUsuario($base);
    $titulo = 'Jogo sincronia ' . bin2hex(random_bytes(3));
    $criado = $g->req('POST', '/api/jogos', ['titulo' => $titulo, 'genero' => 'Aventura', 'plataformas' => ['pc']]);
    $gid = (int) ($criado['json']['jogo']['id'] ?? 0);
    $g->req('PUT', "/api/listas/{$gid}", ['estado' => 'quero']);
    $meus = $g->req('GET', '/api/jogos?escopo=meus&por_pagina=50');
    $achado = null;
    foreach ($meus['json']['dados'] ?? [] as $item) {
        if (($item['id'] ?? 0) === $gid) {
            $achado = $item;
        }
    }
    $listas = $g->req('GET', '/api/listas');
    $naLista = false;
    foreach ($listas['json']['itens'] ?? [] as $item) {
        if (($item['jogo_id'] ?? 0) === $gid && ($item['estado'] ?? '') === 'quero') {
            $naLista = true;
        }
    }
    $oficiais = $g->req('GET', '/api/jogos?escopo=oficiais&por_pagina=1');
    $slugOficial = $oficiais['json']['dados'][0]['slug'] ?? '';
    relatar(
        'Jogo e lista criados em uma tela aparecem na outra (escopo=meus, /api/listas) e a API expõe o slug dos oficiais',
        $criado['status'] === 201 && $achado !== null && ($achado['minha_lista'] ?? '') === 'quero' && $naLista && $slugOficial !== '',
        'POST=' . $criado['status'] . ' meus.minha_lista=' . ($achado['minha_lista'] ?? 'ausente') . ' /api/listas contém=' . ($naLista ? 'sim' : 'não') . ' slug oficial=' . $slugOficial
    );
    $g->req('DELETE', "/api/jogos/{$gid}");

    // 10) Admin (opcional: precisa de ADMIN_EMAIL/ADMIN_SENHA no .env e de criar_admin.php executado) -------------------
    $admEmail = Env::get('ADMIN_EMAIL');
    $admSenha = Env::get('ADMIN_SENHA');
    if ($admEmail && $admSenha) {
        $adm = new Cliente($base);
        $lg = $adm->req('POST', '/api/auth/login', ['email' => $admEmail, 'senha' => $admSenha]);
        $us = $adm->req('GET', '/api/usuarios');
        $of = $adm->req('POST', '/api/jogos', ['titulo' => 'Oficial teste ' . bin2hex(random_bytes(3)), 'genero' => 'Ação', 'plataformas' => ['pc']]);
        $idOf = (int) ($of['json']['jogo']['id'] ?? 0);
        $dl = $adm->req('DELETE', "/api/jogos/{$idOf}");
        relatar('Admin lista usuários e cria/exclui jogo do catálogo oficial', $lg['status'] === 200 && $us['status'] === 200 && $of['status'] === 201 && ($of['json']['jogo']['oficial'] ?? false) === true && $dl['status'] === 204, 'login=' . $lg['status'] . ' GET /api/usuarios=' . $us['status'] . ' POST /api/jogos=' . $of['status'] . ' (oficial) DELETE=' . $dl['status']);
    } else {
        echo "[PULADO]  Teste de admin: defina ADMIN_EMAIL/ADMIN_SENHA no .env e rode database/criar_admin.php\n\n";
    }
} catch (Throwable $e) {
    echo '[ERRO]    ' . $e->getMessage() . "\n";
    exit(2);
}

echo str_repeat('=', 70) . "\nResultado: " . ($total - $falhas) . "/{$total} verificações passaram.\n";
exit($falhas === 0 ? 0 : 1);
