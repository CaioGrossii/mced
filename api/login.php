<?php
// Arquivo: api/login.php

// 1. CONFIGURAÇÃO DO BANCO DE DADOS
$dbHost = 'localhost';
$dbName = 'auth_db'; // O mesmo banco de dados de antes
$dbUser = 'root';
$dbPass = 'sua_senha_aqui'; // Altere para a sua senha do MySQL

// 2. DEFININDO O CABEÇALHO COMO JSON
// Isso informa ao navegador que a resposta será em formato JSON.
header('Content-Type: application/json');

// 3. CONEXÃO SEGURA COM O BANCO USANDO PDO
// PDO é a forma moderna e segura de se conectar a bancos de dados em PHP.
try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
    // Configura o PDO para lançar exceções em caso de erro.
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Se a conexão falhar, retorna um erro e encerra o script.
    http_response_code(500); // Erro interno do servidor
    echo json_encode(['success' => false, 'message' => 'Erro ao conectar com o banco de dados.']);
    exit();
}

// 4. RECEBENDO OS DADOS ENVIADOS PELO JAVASCRIPT
// file_get_contents('php://input') pega o corpo da requisição (que está em JSON).
// json_decode() converte a string JSON para um objeto/array PHP.
$data = json_decode(file_get_contents('php://input'), true);

// Validação simples dos dados recebidos
if (!$data || !isset($data['email']) || !isset($data['password'])) {
    http_response_code(400); // Requisição inválida
    echo json_encode(['success' => false, 'message' => 'Dados de login inválidos.']);
    exit();
}

$email = $data['email'];
$password = $data['password'];

// 5. BUSCANDO O USUÁRIO NO BANCO DE DADOS
try {
    // Usamos "Prepared Statements" (com ?) para PREVENIR SQL INJECTION. É a forma mais segura!
    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // 6. VERIFICANDO A SENHA
    // Verifica se o usuário existe E se a senha enviada corresponde ao hash salvo no banco.
    // A função password_verify é a forma correta de checar senhas com hash bcrypt.
    if ($user && password_verify($password, $user['senha'])) {
        // Sucesso!
        echo json_encode(['success' => true, 'message' => 'Login bem-sucedido!']);
    } else {
        // Falha!
        http_response_code(401); // Não autorizado
        echo json_encode(['success' => false, 'message' => 'E-mail ou senha inválidos.']);
    }

} catch (PDOException $e) {
    // Se ocorrer um erro na consulta
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno no servidor.']);
}