<?php
session_start();

// 1. Verificações iniciais de segurança e sessão
if (!isset($_SESSION['usuario_logado']) || !isset($_SESSION['id_cliente'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: eletro.php");
    exit();
}

// 2. Coleta e validação dos dados do formulário
$id_eletro = filter_input(INPUT_POST, 'id_eletro', FILTER_VALIDATE_INT);
$id_comodo = filter_input(INPUT_POST, 'id_comodo', FILTER_VALIDATE_INT);
$id_categoria = filter_input(INPUT_POST, 'id_categoria', FILTER_VALIDATE_INT);
$nm_eletro = trim(filter_input(INPUT_POST, 'nm_eletro', FILTER_SANITIZE_STRING));
$watts = filter_input(INPUT_POST, 'watts', FILTER_VALIDATE_INT);
$id_cliente_logado = $_SESSION['id_cliente'];

if (!$id_eletro || !$id_comodo || !$id_categoria || empty($nm_eletro) || $watts === false) {
    header("Location: eletro.php?erro=" . urlencode("Dados inválidos. Verifique todos os campos."));
    exit();
}

try {
    $servidor = "localhost";
    $usuario_db = "root";
    $senha_db = "p1Mc3d25*";
    $banco = "mced";

    $conexao = new PDO("mysql:host=$servidor;dbname=$banco;charset=utf8", $usuario_db, $senha_db);
    $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 3. VERIFICAÇÃO DE PROPRIEDADE (DUPLA CHECAGEM)
    // Antes de atualizar, garantimos que o eletrodoméstico e o cômodo de destino pertencem ao usuário.
    $sql_verificacao = "SELECT e.id_eletro 
                        FROM eletrodomesticos e
                        JOIN comodos c_origem ON e.id_comodo = c_origem.id_comodo
                        JOIN imoveis i_origem ON c_origem.id_imovel = i_origem.id_imovel
                        WHERE e.id_eletro = :id_eletro AND i_origem.id_cliente = :id_cliente_origem";
    
    $stmt_verificacao = $conexao->prepare($sql_verificacao);
    $stmt_verificacao->bindParam(':id_eletro', $id_eletro);
    $stmt_verificacao->bindParam(':id_cliente_origem', $id_cliente_logado);
    $stmt_verificacao->execute();

    if ($stmt_verificacao->rowCount() === 0) {
        // Se rowCount é 0, o eletrodoméstico não existe ou não pertence ao usuário.
        header("Location: eletro.php?erro=" . urlencode("Acesso negado ao tentar atualizar o eletrodoméstico."));
        exit();
    }
    
    // 4. Executa a atualização segura com Prepared Statements
    $sql_update = "UPDATE eletrodomesticos 
                   SET nm_eletro = :nm_eletro, watts = :watts, id_comodo = :id_comodo, id_categoria = :id_categoria
                   WHERE id_eletro = :id_eletro";
                   
    $stmt_update = $conexao->prepare($sql_update);
    $stmt_update->bindParam(':nm_eletro', $nm_eletro);
    $stmt_update->bindParam(':watts', $watts);
    $stmt_update->bindParam(':id_comodo', $id_comodo);
    $stmt_update->bindParam(':id_categoria', $id_categoria);
    $stmt_update->bindParam(':id_eletro', $id_eletro);
    
    $stmt_update->execute();

    // 5. Redireciona para a página de listagem com uma mensagem de sucesso
    header("Location: eletro.php?sucesso_edicao=1");
    exit();

} catch (PDOException $e) {
    error_log("Erro ao atualizar eletrodoméstico: " . $e->getMessage());
    header("Location: eletro.php?erro=" . urlencode("Ocorreu um erro de banco de dados ao salvar as alterações."));
    exit();
} finally {
    $conexao = null;
}