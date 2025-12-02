CREATE DATABASE IF NOT EXISTS mced;
USE mced;

-- Tabela Clientes
CREATE TABLE clientes (
    id_cliente INT AUTO_INCREMENT PRIMARY KEY,
    cpf_cliente VARCHAR(14) NOT NULL UNIQUE,
    nm_cliente VARCHAR(100) NOT NULL,
    tel_cliente VARCHAR(20),
    email_cliente VARCHAR(100) NOT NULL UNIQUE,
    tarifa DECIMAL(10, 4) NOT NULL DEFAULT 0.00,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    senha VARCHAR(255) NOT NULL,
    tema VARCHAR(10) DEFAULT 'dark'
);

-- Tabela Imoveis (Depende de Clientes)
CREATE TABLE imoveis (
    id_imovel INT AUTO_INCREMENT PRIMARY KEY,
    fantasia VARCHAR(50) NOT NULL,
    rua VARCHAR(100) NOT NULL,
    numero VARCHAR(10) NOT NULL, 
    bairro VARCHAR(50) NOT NULL,
    cidade VARCHAR(50) NOT NULL, 
    estado VARCHAR(2) NOT NULL,
    cep VARCHAR(9) NOT NULL,
    id_cliente INT NOT NULL,
    FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente)
);

-- Tabela Categorias (Independente)
CREATE TABLE categorias (
     id_categoria INT AUTO_INCREMENT PRIMARY KEY,
     ds_categoria VARCHAR(100) NOT NULL,
     id_cliente INT NOT NULL,
     FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente)   
 );

-- Tabela Comodos (Depende de Imoveis)
CREATE TABLE comodos (
    id_comodo INT AUTO_INCREMENT PRIMARY KEY,
    ds_comodo VARCHAR(100) NOT NULL,
    id_imovel INT NOT NULL,
    FOREIGN KEY (id_imovel) REFERENCES imoveis(id_imovel)
);

-- Tabela Eletrodomesticos (Depende de Categorias e Comodos)
CREATE TABLE eletrodomesticos (
    id_eletro INT AUTO_INCREMENT PRIMARY KEY,
    nm_eletro VARCHAR(100) NOT NULL,
    watts INT NOT NULL,
    id_categoria INT NOT NULL,
    id_comodo INT NOT NULL,
    FOREIGN KEY (id_categoria) REFERENCES categorias(id_categoria),
    FOREIGN KEY (id_comodo) REFERENCES comodos(id_comodo)
);

-- Tabela Consumo (Depende de Eletrodomesticos)
CREATE TABLE consumo (
    id_consumo INT AUTO_INCREMENT PRIMARY KEY,
    data_reg DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fim TIME NOT NULL, 
    id_eletro INT NOT NULL,
    consumokwh VARCHAR(5) NOT NULL,
    FOREIGN KEY (id_eletro) REFERENCES eletrodomesticos(id_eletro)
);