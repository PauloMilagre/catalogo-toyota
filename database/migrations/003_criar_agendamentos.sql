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
