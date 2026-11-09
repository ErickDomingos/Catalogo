<?php
declare(strict_types=1);

namespace GameShelf\Services;

use GameShelf\Core\HttpException;
use GameShelf\Core\Logger;
use GameShelf\Core\Validator;
use GameShelf\Repositories\UsuarioRepositoryInterface;

/** Regras de cadastro e login: hash seguro, mensagens genéricas, limite de tentativas e logs. */
final class AuthService
{
    public const MAX_FALHAS = 5;
    public const JANELA_MINUTOS = 15;

    private ?string $hashFalso = null;

    public function __construct(
        private readonly UsuarioRepositoryInterface $usuarios,
        private readonly Logger $log,
    ) {
    }

    public static function algoritmo(): string
    {
        return defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
    }

    /**
     * O perfil NUNCA vem da entrada: todo cadastro público nasce como "usuario" (evita mass assignment).
     *
     * @param array<string,mixed> $entrada
     * @return array<string,mixed> usuário público (sem hash)
     */
    public function registrar(array $entrada): array
    {
        $v = new Validator($entrada);
        $nome = $v->texto('nome', 'Nome', 2, 80);
        $email = $v->email('email', 'E-mail');
        $senha = $v->senha('senha', 'Senha');
        $v->lancar();

        if ($this->usuarios->porEmail((string) $email) !== null) {
            throw new HttpException(409, 'Este e-mail já está cadastrado.', ['email' => 'E-mail já cadastrado.'], 'conflito');
        }
        $id = $this->usuarios->criar((string) $nome, (string) $email, password_hash((string) $senha, self::algoritmo()), 'usuario');
        $this->log->info('usuario_criado', ['usuario_id' => $id]);

        return ['id' => $id, 'nome' => $nome, 'email' => $email, 'perfil' => 'usuario'];
    }

    /**
     * @param array<string,mixed> $entrada
     * @return array<string,mixed> usuário público (sem hash)
     */
    public function login(array $entrada, string $ip): array
    {
        $v = new Validator($entrada);
        $email = $v->email('email', 'E-mail');
        $senha = $entrada['senha'] ?? null;
        if (!is_string($senha) || $senha === '' || strlen($senha) > 200) {
            $v->adicionarErro('senha', 'Senha é obrigatória.');
        }
        $v->lancar();
        $email = (string) $email;

        if ($this->usuarios->contarFalhas($email, $ip, self::JANELA_MINUTOS) >= self::MAX_FALHAS) {
            $this->log->warning('login_bloqueado', ['email' => $email, 'ip' => $ip]);
            throw new HttpException(
                429,
                'Muitas tentativas de login. Aguarde alguns minutos e tente de novo.',
                [],
                'muitas_tentativas',
                ['Retry-After' => (string) (self::JANELA_MINUTOS * 60)]
            );
        }

        $usuario = $this->usuarios->porEmail($email);
        // Mesmo sem usuário, verificamos contra um hash falso: o tempo de resposta não revela se o e-mail existe.
        $confere = password_verify((string) $senha, $usuario['senha_hash'] ?? $this->hashFalso());
        if ($usuario === null || !$confere) {
            $this->usuarios->registrarFalha($email, $ip);
            $this->log->warning('login_falha', ['email' => $email, 'ip' => $ip]);
            throw new HttpException(401, 'E-mail ou senha incorretos.', [], 'credenciais_invalidas');
        }

        if (password_needs_rehash($usuario['senha_hash'], self::algoritmo())) {
            $this->usuarios->atualizarHash((int) $usuario['id'], password_hash((string) $senha, self::algoritmo()));
        }
        $this->usuarios->limparFalhas($email, $ip);
        $this->log->info('login_ok', ['usuario_id' => (int) $usuario['id'], 'ip' => $ip]);

        unset($usuario['senha_hash']);
        return $usuario;
    }

    private function hashFalso(): string
    {
        return $this->hashFalso ??= password_hash('senha-falsa-para-igualar-tempo', self::algoritmo());
    }
}
