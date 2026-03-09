CREATE DATABASE IF NOT EXISTS whitelist_panel;
USE whitelist_panel;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE whitelist_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    identifier VARCHAR(100) UNIQUE NOT NULL,
    type ENUM('minecraft_uuid', 'discord_id', 'steam_id', 'ip_address') NOT NULL,
    notes TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(255) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert default admin (password: admin123 - hashed with password_hash())
INSERT INTO users (username, password, email, role) VALUES 
('admin', '$2y$10$YourHashedPasswordHere', 'admin@localhost', 'admin');

-- Insert sample whitelist entries
INSERT INTO whitelist_entries (user_id, identifier, type, notes) VALUES 
(1, '12345678901234567890', 'discord_id', 'Server Moderator'),
(1, '192.168.1.100', 'ip_address', 'Office IP'),
(1, 'f47ac10b-58cc-4372-a567-0e02b2c3d479', 'minecraft_uuid', 'VIP Player');