<?php
session_start();

if (!isset($_SESSION['usuario_logado']) || !isset($_SESSION['id_cliente'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Filtros e sanitização
    $id_categoria = filter_input(INPUT_POST, 'id_categoria', FILTER_VALIDATE_INT);
    $ds_categoria = trim(htmlspecialchars($_POST['ds_categoria'] ?? ''));
    $id_cliente_logado = $_SESSION['id_cliente'];

    if (!$id_categoria || empty($ds_categoria)) {
        header("Location: categorias.php?erro=" . urlencode("Dados inválidos."));
        exit();
    }

    try {
        $servidor = "localhost";
        $usuario_db = "root";
        $senha_db = "p1Mc3d25*";
        $banco = "mced";

        $conexao = new PDO("mysql:host=$servidor;dbname=$banco;charset=utf8", $usuario_db, $senha_db);
        $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Atualização Segura: WHERE inclui id_cliente para garantir propriedade
        $sql = "UPDATE categorias SET ds_categoria = :ds_categoria 
                WHERE id_categoria = :id AND id_cliente = :id_cliente";
        
        $stmt = $conexao->prepare($sql);
        $stmt->execute([
            ':ds_categoria' => $ds_categoria,
            ':id' => $id_categoria,
            ':id_cliente' => $id_cliente_logado
        ]);

        if ($stmt->rowCount() > 0) {
            header("Location: categorias.php?sucesso=1");
        } else {
            // Se rowCount for 0, ou não houve mudança no texto ou o ID não pertence ao usuário
            header("Location: categorias.php?sucesso=1"); // Consideramos sucesso mesmo se não mudou texto, para UX
        }
        exit();

    } catch (PDOException $e) {
        error_log("Erro update categoria: " . $e->getMessage());
        header("Location: categorias.php?erro=" . urlencode("Erro ao atualizar."));
        exit();
    } finally {
        $conexao = null;
    }
} else {
    header("Location: categorias.php");
    exit();
}
?>