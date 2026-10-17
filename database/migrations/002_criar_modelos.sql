-- Modelos do catálogo, antes escritos à mão em index.html, modelo.html,
-- hilux.html e supra.html. Agora as páginas são montadas a partir daqui.
CREATE TABLE modelos (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug           VARCHAR(60)       NOT NULL,
    nome           VARCHAR(100)      NOT NULL,
    categoria      VARCHAR(40)       NOT NULL,
    preco          DECIMAL(12, 2)    NOT NULL,
    motor          VARCHAR(100)      NOT NULL,
    potencia_cv    SMALLINT UNSIGNED NOT NULL,
    cambio         VARCHAR(60)       NOT NULL,
    consumo_km_l   DECIMAL(4, 1)     NOT NULL,
    porta_malas_l  SMALLINT UNSIGNED NULL,
    carga_kg       SMALLINT UNSIGNED NULL,
    descricao      TEXT              NULL,
    imagem         VARCHAR(100)      NULL,
    imagem_alt     VARCHAR(200)      NULL,
    imagem_largura SMALLINT UNSIGNED NULL,
    imagem_altura  SMALLINT UNSIGNED NULL,
    legenda        VARCHAR(150)      NULL,
    ativo          TINYINT(1)        NOT NULL DEFAULT 1,
    criado_em      DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em  DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_modelos_slug UNIQUE (slug),
    CONSTRAINT ck_modelos_preco CHECK (preco > 0),
    CONSTRAINT ck_modelos_potencia CHECK (potencia_cv > 0),
    CONSTRAINT ck_modelos_consumo CHECK (consumo_km_l > 0),
    CONSTRAINT ck_modelos_ativo CHECK (ativo IN (0, 1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- O catálogo público lista só os ativos, em ordem de nome.
CREATE INDEX idx_modelos_ativo_nome ON modelos (ativo, nome);
