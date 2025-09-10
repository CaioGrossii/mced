<?php
// Inicia a sessão para criar as variáveis de sessão
session_start();

// Verifica se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Conexão com o banco de dados
    $servidor = "localhost";
    $usuario_db = "root";
    $senha_db = "p1Mc3d25*";
    $banco = "mced";
    $conexao = null;

    try {
        $conexao = new PDO("mysql:host=$servidor;dbname=$banco;charset=utf8", $usuario_db, $senha_db);
        $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $email = trim(htmlspecialchars($_POST['email']));
        $senha_digitada = $_POST['senha'];

        // --- MUDANÇA 1: Selecionar também o id_cliente e o nm_cliente ---
        $sql = "SELECT id_cliente, nm_cliente, email_cliente, senha FROM clientes WHERE email_cliente = :email";
        $stmt = $conexao->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario && password_verify($senha_digitada, $usuario['senha'])) {
            // SUCESSO NO LOGIN!
            $_SESSION['usuario_logado'] = true;
            $_SESSION['email_usuario'] = $usuario['email_cliente'];
            $_SESSION['nome_usuario'] = $usuario['nm_cliente'];
            
            // --- MUDANÇA 2: Guardar o ID do cliente na sessão ---
            $_SESSION['id_cliente'] = $usuario['id_cliente'];
            
            header("Location: /mced/dash.php");
            exit();
        } else {
            // FALHA NO LOGIN!
            header("Location: login.php?erro=1");
            exit();
        }

    } catch (PDOException $e) {
        error_log("Erro de login no banco de dados: " . $e->getMessage());
        die("Ocorreu um erro no servidor. Tente novamente mais tarde.");
    } finally {
        $conexao = null;
    }
} else {
    header("Location: login.php");
    exit();
}
?>