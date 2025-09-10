<?php
session_start();

if (!isset($_SESSION['usuario_logado']) || !isset($_SESSION['id_cliente'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validação básica dos dados recebidos
    if (empty($_POST['id_imovel']) || empty(trim($_POST['ds_comodo']))) {
        die("Erro: Todos os campos são obrigatórios.");
    }
    
    $id_imovel = $_POST['id_imovel'];
    $ds_comodo = trim(htmlspecialchars($_POST['ds_comodo']));
    $id_cliente_sessao = $_SESSION['id_cliente'];

    try {
        $servidor = "localhost";
        $usuario_db = "root";
        $senha_db = "p1Mc3d25*";
        $banco = "mced";
        
        $conexao = new PDO("mysql:host=$servidor;dbname=$banco;charset=utf8", $usuario_db, $senha_db);
        $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // VERIFICAÇÃO DE SEGURANÇA: Confirma que o imóvel pertence ao usuário logado
        $sql_check = "SELECT id_cliente FROM imoveis WHERE id_imovel = :id_imovel";
        $stmt_check = $conexao->prepare($sql_check);
        $stmt_check->bindParam(':id_imovel', $id_imovel);
        $stmt_check->execute();
        $imovel_owner = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if (!$imovel_owner || $imovel_owner['id_cliente'] != $id_cliente_sessao) {
            die("Erro de permissão: Você não pode adicionar cômodos a este imóvel.");
        }

        // Se a verificação passar, insere o cômodo
        $sql_insert = "INSERT INTO comodos (ds_comodo, id_imovel) VALUES (:ds_comodo, :id_imovel)";
        $stmt_insert = $conexao->prepare($sql_insert);
        $stmt_insert->bindParam(':ds_comodo', $ds_comodo);
        $stmt_insert->bindParam(':id_imovel', $id_imovel);
        $stmt_insert->execute();
        
        // Redireciona de volta para a página de cômodos
        header("Location: comodos.php");
        exit();

    } catch (PDOException $e) {
        error_log("Erro ao salvar cômodo: " . $e->getMessage());
        die("Ocorreu um erro ao salvar os dados. Tente novamente.");
    } finally {
        $conexao = null;
    }

} else {
    header("Location: comodos.php");
    exit();
}
?>