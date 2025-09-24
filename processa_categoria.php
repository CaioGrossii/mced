<?php
session_start();

// Guardião: Verifica se o usuário está logado
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header("Location: login.php");
    exit();
}

// Verifica se o formulário foi enviado e o campo não está vazio
if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty(trim($_POST['ds_categoria']))) {
    
    $dsCategoria = trim(htmlspecialchars($_POST['ds_categoria']));
    $idCliente = $_SESSION['id_cliente']; // Pega o ID do cliente da sessão

    // Conexão com o banco
    $servidor = "localhost";
    $usuario_db = "root";
    $senha_db = "p1Mc3d25*";
    $banco = "mced";
    $conexao = null;

    try {
        $conexao = new PDO("mysql:host=$servidor;dbname=$banco;charset=utf8", $usuario_db, $senha_db);
        $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // SQL para inserir, agora com o id_cliente
        $sql = "INSERT INTO categorias (ds_categoria, id_cliente) VALUES (:ds_categoria, :id_cliente)";
        $stmt = $conexao->prepare($sql);

        // Binds
        $stmt->bindParam(':ds_categoria', $dsCategoria);
        $stmt->bindParam(':id_cliente', $idCliente); // Associa ao cliente logado

        $stmt->execute();

        // Redireciona de volta para a página de categorias com sucesso
        header("Location: categorias.php?sucesso=1");
        exit();

    } catch (PDOException $e) {
        error_log("Erro ao cadastrar categoria: " . $e->getMessage());
        die("Ocorreu um erro ao salvar a categoria. Tente novamente.");
    }

} else {
    // Redireciona se o acesso for indevido ou o campo estiver vazio
    header("Location: categorias.php");
    exit();
}
?>