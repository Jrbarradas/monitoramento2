-- Database schema for Spacecom Monitoring System
-- Execute this script to create the required tables

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS monitoramento CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE monitoramento;

-- Users table
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nome_completo VARCHAR(100) NOT NULL,
    nivel_acesso ENUM('admin', 'operador') DEFAULT 'operador',
    estado_permitido VARCHAR(3),
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ultimo_login TIMESTAMP NULL,
    ativo BOOLEAN DEFAULT TRUE,
    INDEX idx_username (username),
    INDEX idx_nivel_acesso (nivel_acesso),
    INDEX idx_estado (estado_permitido)
) ENGINE=InnoDB;

-- Links table
CREATE TABLE IF NOT EXISTS links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    endereco TEXT,
    cidade VARCHAR(100),
    uf VARCHAR(3) NOT NULL,
    contato VARCHAR(100),
    lat DECIMAL(10, 8) DEFAULT -15.780100,
    lon DECIMAL(11, 8) DEFAULT -47.929200,
    status ENUM('online', 'offline', 'unknown') DEFAULT 'unknown',
    last_check TIMESTAMP NULL,
    last_seen_online TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ativo BOOLEAN DEFAULT TRUE,
    INDEX idx_ip (ip),
    INDEX idx_uf (uf),
    INDEX idx_status (status),
    INDEX idx_last_check (last_check),
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB;

-- Status history table
CREATE TABLE IF NOT EXISTS historico_status (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    link_id INT NOT NULL,
    status ENUM('online', 'offline') NOT NULL,
    latency DECIMAL(8, 2) DEFAULT NULL,
    packet_loss TINYINT DEFAULT NULL,
    checked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (link_id) REFERENCES links(id) ON DELETE CASCADE,
    INDEX idx_link_id (link_id),
    INDEX idx_status (status),
    INDEX idx_checked_at (checked_at),
    INDEX idx_link_date (link_id, checked_at)
) ENGINE=InnoDB;

-- Activity log table
CREATE TABLE IF NOT EXISTS log_atividades (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    acao VARCHAR(100) NOT NULL,
    detalhes TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_acao (acao),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- System settings table
CREATE TABLE IF NOT EXISTS configuracoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chave VARCHAR(100) UNIQUE NOT NULL,
    valor TEXT,
    descricao TEXT,
    tipo ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_chave (chave)
) ENGINE=InnoDB;

-- Insert default admin user (password: admin123)
INSERT IGNORE INTO usuarios (username, password, nome_completo, nivel_acesso, estado_permitido) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador do Sistema', 'admin', NULL);

-- Insert default system configurations
INSERT IGNORE INTO configuracoes (chave, valor, descricao, tipo) VALUES
('ping_timeout', '2', 'Timeout para ping em segundos', 'number'),
('ping_count', '2', 'Número de pings por verificação', 'number'),
('update_interval', '30', 'Intervalo de atualização em segundos', 'number'),
('offline_threshold', '180', 'Tempo em segundos para considerar offline', 'number'),
('max_history_days', '90', 'Dias de histórico a manter', 'number'),
('enable_notifications', 'true', 'Habilitar notificações', 'boolean'),
('webhook_url', '', 'URL do webhook para alertas', 'string');

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_historico_performance ON historico_status (link_id, checked_at DESC, status);
CREATE INDEX IF NOT EXISTS idx_links_monitoring ON links (uf, status, last_check);

-- Create view for dashboard statistics
CREATE OR REPLACE VIEW vw_dashboard_stats AS
SELECT 
    uf,
    COUNT(*) as total_links,
    SUM(CASE WHEN status = 'online' THEN 1 ELSE 0 END) as online_count,
    SUM(CASE WHEN status = 'offline' THEN 1 ELSE 0 END) as offline_count,
    AVG(CASE WHEN status = 'online' THEN 1 ELSE 0 END) * 100 as availability_percent
FROM links 
WHERE ativo = TRUE
GROUP BY uf
ORDER BY uf;

-- Cleanup old history records (keep only last 90 days)
CREATE EVENT IF NOT EXISTS cleanup_old_history
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO
DELETE FROM historico_status 
WHERE checked_at < DATE_SUB(NOW(), INTERVAL 90 DAY);