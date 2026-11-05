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
('Cabo USB-C', 0, 29.90),
('Webcam HD', 10, 189.90),
('Monitor 24 polegadas', 8, 899.00),
('Pen drive 64 GB', 30, 49.90),
('Carregador turbo', 15, 89.90),
('Suporte para notebook', 7, 129.90),
('Hub USB 4 portas', 20, 69.90);
