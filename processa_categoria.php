<?php
session_start();

if (!isset($_SESSION['usuario_logado'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty(trim($_POST['ds_categoria']))) {
        die("Erro: O nome da categoria é obrigatório.");
    }
    $ds_categoria = trim(htmlspecialchars($_POST['ds_categoria']));

    try {
        $servidor = "localhost";
        $usuario_db = "root";
        $senha_db = "p1Mc3d25*";
        $banco = "mced";
        $conexao = new PDO("mysql:host=$servidor;dbname=$banco;charset=utf8", $usuario_db, $senha_db);
        $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = "INSERT INTO categorias (ds_categoria) VALUES (:ds_categoria)";
        $stmt = $conexao->prepare($sql);
        $stmt->bindParam(':ds_categoria', $ds_categoria);
        $stmt->execute();
        
        header("Location: categorias.php?sucesso=1");
        exit();

    } catch (PDOException $e) {
        error_log("Erro ao salvar categoria: " . $e->getMessage());
        if ($e->getCode() == 23000) { die("Erro: Esta categoria já existe."); }
        die("Ocorreu um erro ao salvar os dados.");
    } finally {
        $conexao = null;
    }
} else {
    header("Location: categorias.php");
    exit();
}
?>