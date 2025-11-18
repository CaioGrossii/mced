<?php
session_start();

if (!isset($_SESSION['usuario_logado']) || !isset($_SESSION['id_cliente'])) {
    header("Location: login.php");
    exit();
}

$id_categoria = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$id_cliente_logado = $_SESSION['id_cliente'];

if (!$id_categoria) {
    header("Location: categorias.php?erro=" . urlencode("ID inválido."));
    exit();
}

try {
    $servidor = "localhost";
    $usuario_db = "root";
    $senha_db = "p1Mc3d25*";
    $banco = "mced";

    $conexao = new PDO("mysql:host=$servidor;dbname=$banco;charset=utf8", $usuario_db, $senha_db);
    $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Exclusão Segura: Garante que só exclui se pertencer ao usuário
    $sql = "DELETE FROM categorias WHERE id_categoria = :id AND id_cliente = :id_cliente";
    $stmt = $conexao->prepare($sql);
    
    $stmt->execute([
        ':id' => $id_categoria,
        ':id_cliente' => $id_cliente_logado
    ]);

    if ($stmt->rowCount() > 0) {
        header("Location: categorias.php?sucesso=1");
    } else {
        header("Location: categorias.php?erro=" . urlencode("Categoria não encontrada ou você não tem permissão."));
    }
    exit();

} catch (PDOException $e) {
    // Código 23000 geralmente indica violação de integridade (Foreign Key)
    if ($e->getCode() == '23000') {
        $msg = "Não é possível excluir: Esta categoria está vinculada a um ou mais eletrodomésticos.";
    } else {
        $msg = "Erro ao excluir categoria. Tente novamente.";
        error_log("Erro exclusão categoria: " . $e->getMessage());
    }
    header("Location: categorias.php?erro=" . urlencode($msg));
    exit();
} finally {
    $conexao = null;
}
?>