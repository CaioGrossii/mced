<?php
session_start();

// 1. Verificações de segurança
if (!isset($_SESSION['usuario_logado']) || !isset($_SESSION['id_cliente'])) {
    header("Location: login.php");
    exit();
}

// 2. Validação do ID vindo da URL (via GET)
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    header("Location: view_imoveis.php?erro=id_invalido");
    exit();
}

$id_imovel_a_excluir = $_GET['id'];
$id_cliente_logado = $_SESSION['id_cliente'];

$servidor = "localhost";
$usuario_db = "root";
$senha_db = "p1Mc3d25*";
$banco = "mced";
$conexao = null;

try {
    $conexao = new PDO("mysql:host=$servidor;dbname=$banco;charset=utf8", $usuario_db, $senha_db);
    $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 3. VERIFICAÇÃO DE DEPENDÊNCIA (FOREIGN KEY)
    // Não podemos excluir um imóvel se ele tiver cômodos associados (tabela 'comodos')
    $sql_check = "SELECT COUNT(*) FROM comodos WHERE id_imovel = :id_imovel";
    $stmt_check = $conexao->prepare($sql_check);
    $stmt_check->bindParam(':id_imovel', $id_imovel_a_excluir);
    $stmt_check->execute();
    $contador_comodos = $stmt_check->fetchColumn();

    if ($contador_comodos > 0) {
        // Se houver dependências, redireciona com erro
        header("Location: view_imoveis.php?erro=dependencia");
        exit();
    }

    // 4. Se não há dependências, executa a exclusão SEGURA
    // A cláusula "WHERE id_cliente = :id_cliente" garante que um usuário
    // só possa excluir seus próprios imóveis.
    $sql_delete = "DELETE FROM imoveis 
                   WHERE id_imovel = :id_imovel AND id_cliente = :id_cliente";
    
    $stmt_delete = $conexao->prepare($sql_delete);
    $stmt_delete->bindParam(':id_imovel', $id_imovel_a_excluir);
    $stmt_delete->bindParam(':id_cliente', $id_cliente_logado);
    $stmt_delete->execute();

    // 5. Verifica se a exclusão realmente aconteceu
    if ($stmt_delete->rowCount() > 0) {
        header("Location: view_imoveis.php?sucesso=delete");
    } else {
        // Isso pode acontecer se o ID for de outro usuário (rowCount será 0)
        header("Location: view_imoveis.php?erro=permissao");
    }
    exit();

} catch(PDOException $e) {
    error_log("Erro ao excluir imóvel: " . $e->getMessage());
    die("Ocorreu um erro inesperado ao excluir os dados. Tente novamente mais tarde.");
} finally {
    $conexao = null;
}
?>