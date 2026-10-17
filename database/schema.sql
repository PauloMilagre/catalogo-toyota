CREATE DATABASE IF NOT EXISTS catalogo_toyota CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE catalogo_toyota;

-- Script completo do banco do Catálogo Toyota (Trabalho Individual II, SCOM).
-- Gerado por bin/gerar-schema.php a partir de database/migrations/. Não edite à mão.

CREATE TABLE IF NOT EXISTS migracoes (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome        VARCHAR(150) NOT NULL,
    aplicada_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_migracoes_nome UNIQUE (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- 001_criar_usuarios.sql
-- ---------------------------------------------------------------
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

-- ---------------------------------------------------------------
-- 002_criar_modelos.sql
-- ---------------------------------------------------------------
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

-- ---------------------------------------------------------------
-- 003_criar_agendamentos.sql
-- ---------------------------------------------------------------
-- Entidade principal: cada test-drive agendado por um cliente.
CREATE TABLE agendamentos (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id    INT UNSIGNED NOT NULL,
    modelo_id     INT UNSIGNED NOT NULL,
    data          DATE         NOT NULL,
    periodo       ENUM('manha', 'tarde') NOT NULL,
    telefone      VARCHAR(20)  NULL,
    observacoes   VARCHAR(400) NULL,
    status        ENUM('pendente', 'confirmado', 'cancelado', 'realizado') NOT NULL DEFAULT 'pendente',
    criado_em     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- Apagar uma conta apaga os agendamentos dela.
    CONSTRAINT fk_agendamentos_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios (id) ON DELETE CASCADE ON UPDATE CASCADE,
    -- Um modelo com agendamentos não pode ser apagado (deve ser desativado).
    CONSTRAINT fk_agendamentos_modelo FOREIGN KEY (modelo_id)
        REFERENCES modelos (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- "Meus agendamentos": filtra por usuário e ordena por data.
CREATE INDEX idx_agendamentos_usuario_data ON agendamentos (usuario_id, data);
-- Conferência de vagas: quantos agendamentos ativos há no mesmo modelo, dia e período.
CREATE INDEX idx_agendamentos_horario ON agendamentos (modelo_id, data, periodo, status);
-- Painel do administrador: filtro por status.
CREATE INDEX idx_agendamentos_status_data ON agendamentos (status, data);

-- ---------------------------------------------------------------
-- 004_criar_tentativas_login.sql
-- ---------------------------------------------------------------
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

-- ---------------------------------------------------------------
-- 005_inserir_modelos.sql
-- ---------------------------------------------------------------
-- Os três modelos do Trabalho I. Os valores são ilustrativos (não vêm de
-- fonte oficial). A motorização do Corolla foi corrigida de 2.0 para 1.8,
-- que é o motor do Corolla Altis Hybrid vendido no Brasil.
INSERT INTO modelos
    (slug, nome, categoria, preco, motor, potencia_cv, cambio, consumo_km_l,
     porta_malas_l, carga_kg, descricao, imagem, imagem_alt, imagem_largura, imagem_altura, legenda)
VALUES
    ('corolla-altis-hybrid', 'Corolla Altis Hybrid', 'Sedã híbrido', 195000.00,
     '1.8 híbrido, 16 válvulas', 122, 'Automático e-CVT', 21.5, 470, NULL,
     'O Corolla híbrido combina um motor 1.8 a combustão com um motor elétrico que assume sozinho o trabalho em baixas velocidades. A bateria se recarrega durante as frenagens, sem precisar de tomada, o que explica o consumo de 21,5 km/l no ciclo urbano, superior ao da versão a gasolina.\n\nNa prática, é um sedã de uso diário que troca a resposta imediata de um motor turbo pela economia em trânsito parado. Quem roda majoritariamente em estrada tende a notar menos diferença, e quem enfrenta congestionamento todos os dias sente no fim do mês.',
     'corolla.jpg', 'Toyota Corolla branco, visto de frente, estacionado em área gramada', 760, 497,
     'Corolla Altis Hybrid na versão 2025, em branco pérola'),
    ('hilux-srx-4x4', 'Hilux SRX 4x4', 'Picape 4x4', 330000.00,
     '2.8 turbodiesel, 16 válvulas', 204, 'Automático de 6 marchas', 9.8, NULL, 1000,
     'A Hilux SRX combina um motor 2.8 turbodiesel com tração 4x4 e caixa reduzida, projetada para piso irregular sem abrir mão de conforto na estrada. O que sustenta o reboque e a subida com carga não é a potência de pico, e sim o torque disponível em baixas rotações.\n\nNa prática, é uma picape que atende dois usos que raramente convivem: trabalho durante a semana e viagem no fim de semana. O consumo de 9,8 km/l no ciclo urbano é o preço do peso e da tração integral.',
     'hilux.jpg', 'Toyota Hilux SRX cinza, vista de frente, em terreno de vegetação seca', 760, 428,
     'Hilux SRX 4x4 na versão 2025, em pintura cinza'),
    ('gr-supra', 'GR Supra', 'Esportivo', 620000.00,
     '3.0 seis cilindros em linha, turbo', 382, 'Automático de 8 marchas', 8.6, 290, NULL,
     'O GR Supra usa um motor 3.0 de seis cilindros em linha, turbo, montado longitudinalmente e com tração traseira. A distribuição de peso próxima de 50/50 entre os eixos define o comportamento em curva mais do que o número de cavalos.\n\nNa prática, é um cupê de dois lugares feito para dirigir, não para transportar. O porta-malas de 290 litros e o consumo de 8,6 km/l no ciclo urbano deixam claro que a prioridade do projeto está em outro lugar.',
     'supra.jpg', 'Toyota GR Supra branco, visto de três quartos pela frente, com montanhas ao fundo', 760, 475,
     'GR Supra na versão 2025, em branco');

-- Registra as migrações como aplicadas, para o bin/migrar.php não repeti-las.
INSERT INTO migracoes (nome) VALUES ('001_criar_usuarios.sql'), ('002_criar_modelos.sql'), ('003_criar_agendamentos.sql'), ('004_criar_tentativas_login.sql'), ('005_inserir_modelos.sql');
