<?php
// Inicia a sessão para acesso às credenciais e ID do usuário
session_start();

// 1. GUARDIÃO DE SEGURANÇA: Verifica se o usuário está logado
if (!isset($_SESSION['usuario_logado']) || !isset($_SESSION['id_cliente'])) {
    header("Location: login.php");
    exit();
}

// 2. VALIDAÇÃO DO INPUT
$id_comodo = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$id_cliente_logado = $_SESSION['id_cliente'];

if (!$id_comodo) {
    header("Location: comodos.php?erro=" . urlencode("ID do cômodo inválido."));
    exit();
}

try {
    // 3. CONEXÃO COM O BANCO DE DADOS (Configurações do projeto)
    $servidor = "localhost";
    $usuario_db = "root";
    $senha_db = "p1Mc3d25*";
    $banco = "mced";

    $conexao = new PDO("mysql:host=$servidor;dbname=$banco;charset=utf8", $usuario_db, $senha_db);
    $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 4. VERIFICAÇÃO DE PROPRIEDADE E EXCLUSÃO
    // A query de exclusão é feita em duas etapas para garantir que o cômodo
    // pertence ao imóvel do cliente logado antes de tentar deletá-lo.
    
    // A. Busca o id_imovel e id_cliente do cômodo
    $sql_check = "SELECT i.id_cliente 
                  FROM comodos c 
                  JOIN imoveis i ON c.id_imovel = i.id_imovel 
                  WHERE c.id_comodo = :id_comodo";
    $stmt_check = $conexao->prepare($sql_check);
    $stmt_check->bindParam(':id_comodo', $id_comodo);
    $stmt_check->execute();
    $comodo_owner = $stmt_check->fetch(PDO::FETCH_ASSOC);

    // B. Garante que o cômodo existe E que ele pertence ao cliente logado
    if (!$comodo_owner || $comodo_owner['id_cliente'] != $id_cliente_logado) {
        header("Location: comodos.php?erro=" . urlencode("Acesso negado ou cômodo não encontrado."));
        exit();
    }

    // C. Executa a exclusão
    $sql_delete = "DELETE FROM comodos WHERE id_comodo = :id_comodo";
    $stmt_delete = $conexao->prepare($sql_delete);
    $stmt_delete->bindParam(':id_comodo', $id_comodo);
    $stmt_delete->execute();

    // 5. FEEDBACK E REDIRECIONAMENTO DE SUCESSO
    header("Location: comodos.php?sucesso_edicao=2"); // Usei 2 para diferenciar da edição
    exit();

} catch (PDOException $e) {
    // 6. TRATAMENTO DE ERRO (Foreign Key Constraint)
    // Código '23000' em PDO geralmente indica falha em Foreign Key
    if ($e->getCode() == '23000') {
        $msg = "Não é possível excluir: Existem eletrodomésticos vinculados a este cômodo.";
    } else {
        $msg = "Ocorreu um erro ao excluir o cômodo. Tente novamente.";
        error_log("Erro exclusão cômodo: " . $e->getMessage());
    }
    header("Location: comodos.php?erro=" . urlencode($msg));
    exit();
} finally {
    $conexao = null;
}
?>