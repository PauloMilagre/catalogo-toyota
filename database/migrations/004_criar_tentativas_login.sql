-- Histórico de tentativas de login, usado para limitar tentativas por IP
-- (rate limiting) e impedir que alguém fique testando senhas sem parar.
-- Sem chave estrangeira: o e-mail tentado pode nem existir em usuarios.
CREATE TABLE tentativas_login (
    id        BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip        VARCHAR(45)  NOT NULL,
    email     VARCHAR(150) NULL,
    sucesso   TINYINT(1)   NOT NULL,
    criado_em DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT ck_tentativas_sucesso CHECK (sucesso IN (0, 1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_tentativas_ip ON tentativas_login (ip, sucesso, criado_em);
