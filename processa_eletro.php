<?php
session_start();

if (!isset($_SESSION['usuario_logado']) || !isset($_SESSION['id_cliente'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (empty($_POST['id_comodo']) || empty(trim($_POST['nm_eletro'])) || empty($_POST['watts'])) {
        die("Erro: Todos os campos são obrigatórios.");
    }

    $id_comodo = $_POST['id_comodo'];
    $nm_eletro = trim(htmlspecialchars($_POST['nm_eletro']));
    $watts = filter_var($_POST['watts'], FILTER_VALIDATE_INT);
    $id_cliente_sessao = $_SESSION['id_cliente'];

    if ($watts === false || $watts <= 0) {
        die("Erro: A potência (watts) deve ser um número inteiro positivo.");
    }

    try {
        $servidor = "localhost";
        $usuario_db = "root";
        $senha_db = "p1Mc3d25*";
        $banco = "mced";
        
        $conexao = new PDO("mysql:host=$servidor;dbname=$banco;charset=utf8", $usuario_db, $senha_db);
        $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // VERIFICAÇÃO DE SEGURANÇA: Confirma que o cômodo pertence a um imóvel do usuário logado.
        $sql_check = "SELECT i.id_cliente FROM comodos c JOIN imoveis i ON c.id_imovel = i.id_imovel WHERE c.id_comodo = :id_comodo";
        $stmt_check = $conexao->prepare($sql_check);
        $stmt_check->bindParam(':id_comodo', $id_comodo);
        $stmt_check->execute();
        $comodo_owner = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if (!$comodo_owner || $comodo_owner['id_cliente'] != $id_cliente_sessao) {
            die("Erro de permissão: Você não pode adicionar eletrodomésticos a este cômodo.");
        }

        // Se a verificação passar, insere o eletrodoméstico
        $sql_insert = "INSERT INTO eletrodomesticos (nm_eletro, watts, id_comodo, id_categoria) VALUES (:nm_eletro, :watts, :id_comodo, 1)";
        $stmt_insert = $conexao->prepare($sql_insert);
        $stmt_insert->bindParam(':nm_eletro', $nm_eletro);
        $stmt_insert->bindParam(':watts', $watts);
        $stmt_insert->bindParam(':id_comodo', $id_comodo);
        $stmt_insert->execute();
        
        header("Location: eletro.php");
        exit();

    } catch (PDOException $e) {
        error_log("Erro ao salvar eletrodoméstico: " . $e->getMessage());
        die("Ocorreu um erro ao salvar os dados. Tente novamente.");
    } finally {
        $conexao = null;
    }

} else {
    header("Location: eletrodomesticos.php");
    exit();
}
?>