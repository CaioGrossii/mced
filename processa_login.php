<?php
// Inicia a sessão para criar as variáveis de sessão
session_start();

// Verifica se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Conexão com o banco de dados (use suas credenciais)
    $servidor = "localhost";
    $usuario_db = "root";
    $senha_db = "p1Mc3d25*";
    $banco = "mced";
    $conexao = null;

    try {
        $conexao = new PDO("mysql:host=$servidor;dbname=$banco;charset=utf8", $usuario_db, $senha_db);
        $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Pega os dados do formulário
        $email = trim(htmlspecialchars($_POST['email']));
        $senha_digitada = $_POST['senha'];

        // Prepara a consulta para buscar o usuário pelo e-mail
        // MUDANÇA: Buscar também id_cliente e nm_cliente
        $sql = "SELECT id_cliente, nm_cliente, email_cliente, senha FROM clientes WHERE email_cliente = :email";
        $stmt = $conexao->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verifica se encontrou um usuário e se a senha está correta
        if ($usuario && password_verify($senha_digitada, $usuario['senha'])) {
            // SUCESSO NO LOGIN!
            // Armazena os dados na sessão
            $_SESSION['usuario_logado'] = true;
            $_SESSION['id_cliente'] = $usuario['id_cliente']; // NOVO: Armazena o ID
            $_SESSION['nome_usuario'] = $usuario['nm_cliente']; // NOVO: Armazena o Nome
            $_SESSION['email_usuario'] = $usuario['email_cliente'];
            
            // Redireciona para a dashboard
            header("Location: /mced/dash.php");
            exit();
        } else {
            // FALHA NO LOGIN!
            // Redireciona de volta para a página de login com um indicador de erro
            header("Location: login.php?erro=1");
            exit();
        }

    } catch (PDOException $e) {
        // Em caso de erro de banco, grava no log e mostra mensagem genérica
        error_log("Erro de login no banco de dados: " . $e->getMessage());
        die("Ocorreu um erro no servidor. Tente novamente mais tarde.");
    } finally {
        $conexao = null;
    }
} else {
    // Se alguém tentar acessar este arquivo diretamente, redireciona para o login
    header("Location: login.php");
    exit();
}
?>