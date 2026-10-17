-- Contas de acesso. O perfil define o que cada um pode fazer:
-- cliente agenda e acompanha os próprios test-drives; admin gerencia tudo.
CREATE TABLE usuarios (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome       VARCHAR(100) NOT NULL,
    email      VARCHAR(150) NOT NULL,
    senha_hash VARCHAR(255) NOT NULL,
    perfil     ENUM('cliente', 'admin') NOT NULL DEFAULT 'cliente',
    criado_em  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_usuarios_email UNIQUE (email),
    CONSTRAINT ck_usuarios_nome CHECK (CHAR_LENGTH(nome) >= 3)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
