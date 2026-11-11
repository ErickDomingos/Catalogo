-- GameShelf - Trabalho Individual II (SCOM)
-- Script de criação do banco. Compatível com MySQL 8 / MariaDB 10.4+.
-- Executar:  mysql -u <usuario> -p < database/schema.sql

CREATE DATABASE IF NOT EXISTS gameshelf
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE gameshelf;

-- Contas. O perfil define as permissões (matriz em docs/SEGURANCA.md).
CREATE TABLE IF NOT EXISTS usuarios (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome        VARCHAR(80)  NOT NULL,
    email       VARCHAR(120) NOT NULL,
    senha_hash  VARCHAR(255) NOT NULL,
    perfil      ENUM('usuario', 'admin') NOT NULL DEFAULT 'usuario',
    criado_em   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuarios_email (email)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS generos (
    id    SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome  VARCHAR(30) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_generos_nome (nome)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS plataformas (
    id     TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    chave  VARCHAR(20) NOT NULL,
    nome   VARCHAR(30) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_plataformas_chave (chave)
) ENGINE = InnoDB;

-- Entidade principal. dono_id NULL = jogo do catálogo oficial; senão, jogo privado do usuário.
CREATE TABLE IF NOT EXISTS jogos (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug           VARCHAR(80)  NOT NULL,
    titulo         VARCHAR(80)  NOT NULL,
    estudio        VARCHAR(100) NULL,
    ano            SMALLINT UNSIGNED NULL,
    genero_id      SMALLINT UNSIGNED NOT NULL,
    console        VARCHAR(20)  NULL,
    duracao_horas  SMALLINT UNSIGNED NULL,
    metacritic     TINYINT UNSIGNED NULL,
    comunidade     DECIMAL(3,1) NULL,
    resenha        VARCHAR(600) NULL,
    capa           VARCHAR(255) NULL,
    dono_id        INT UNSIGNED NULL,
    criado_em      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_jogos_slug (slug),
    KEY idx_jogos_titulo (titulo),
    KEY idx_jogos_genero (genero_id),
    KEY idx_jogos_dono (dono_id),
    CONSTRAINT fk_jogos_genero FOREIGN KEY (genero_id) REFERENCES generos (id) ON DELETE RESTRICT,
    CONSTRAINT fk_jogos_dono   FOREIGN KEY (dono_id)   REFERENCES usuarios (id) ON DELETE CASCADE,
    CONSTRAINT ck_jogos_ano        CHECK (ano IS NULL OR ano BETWEEN 1950 AND 2100),
    CONSTRAINT ck_jogos_duracao    CHECK (duracao_horas IS NULL OR duracao_horas BETWEEN 0 AND 500),
    CONSTRAINT ck_jogos_metacritic CHECK (metacritic IS NULL OR metacritic BETWEEN 0 AND 100),
    CONSTRAINT ck_jogos_comunidade CHECK (comunidade IS NULL OR comunidade BETWEEN 0 AND 10)
) ENGINE = InnoDB;

-- N:N entre jogos e famílias de plataforma.
CREATE TABLE IF NOT EXISTS jogo_plataforma (
    jogo_id        INT UNSIGNED NOT NULL,
    plataforma_id  TINYINT UNSIGNED NOT NULL,
    PRIMARY KEY (jogo_id, plataforma_id),
    CONSTRAINT fk_jp_jogo       FOREIGN KEY (jogo_id)       REFERENCES jogos (id)       ON DELETE CASCADE,
    CONSTRAINT fk_jp_plataforma FOREIGN KEY (plataforma_id) REFERENCES plataformas (id) ON DELETE CASCADE
) ENGINE = InnoDB;

-- Lista pessoal: um estado por jogo e por usuário.
CREATE TABLE IF NOT EXISTS lista_usuario_jogo (
    usuario_id     INT UNSIGNED NOT NULL,
    jogo_id        INT UNSIGNED NOT NULL,
    estado         ENUM('quero', 'jogando', 'joguei', 'zerado') NOT NULL,
    atualizado_em  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (usuario_id, jogo_id),
    KEY idx_lista_usuario_estado (usuario_id, estado),
    CONSTRAINT fk_lista_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE,
    CONSTRAINT fk_lista_jogo    FOREIGN KEY (jogo_id)    REFERENCES jogos (id)    ON DELETE CASCADE
) ENGINE = InnoDB;

-- Tentativas de login malsucedidas (limite simples contra força bruta).
CREATE TABLE IF NOT EXISTS login_falhas (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    email      VARCHAR(120) NOT NULL,
    ip         VARCHAR(45)  NOT NULL,
    criado_em  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_login_falhas_busca (email, ip, criado_em)
) ENGINE = InnoDB;

-- Dados de referência.
INSERT IGNORE INTO generos (nome) VALUES
    ('Aventura'), ('Ação'), ('Estratégia'), ('Plataforma'), ('Puzzle'), ('RPG'), ('Roguelike'),
    ('Simulação'), ('Sobrevivência'), ('Corrida'), ('Luta'), ('Tiro'), ('Esporte'), ('Outro');

INSERT IGNORE INTO plataformas (chave, nome) VALUES
    ('pc', 'PC'), ('playstation', 'PlayStation'), ('xbox', 'Xbox'), ('switch', 'Nintendo Switch');
