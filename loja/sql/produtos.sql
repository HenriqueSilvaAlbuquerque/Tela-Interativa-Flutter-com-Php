SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    quantidade INT NOT NULL DEFAULT 0,
    valor DECIMAL(10,2) NOT NULL
);

INSERT INTO produtos (nome, quantidade, valor) VALUES
('Fone Bluetooth', 12, 149.90),
('Mouse sem fio', 25, 79.90),
('Teclado mecânico', 5, 299.00),
('Cabo USB-C', 0, 29.90);
