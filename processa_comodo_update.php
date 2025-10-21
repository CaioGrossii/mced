<?php
session_start();

// 1. Verificações iniciais de segurança
if (!isset($_SESSION['usuario_logado']) || !isset($_SESSION['id_cliente'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: comodos.php");
    exit();
}

// 2. Coleta e validação dos dados do formulário
$id_comodo = filter_input(INPUT_POST, 'id_comodo', FILTER_VALIDATE_INT);
$id_imovel = filter_input(INPUT_POST, 'id_imovel', FILTER_VALIDATE_INT);
$ds_comodo = trim(filter_input(INPUT_POST, 'ds_comodo', FILTER_SANITIZE_STRING));
$id_cliente_logado = $_SESSION['id_cliente'];

if (!$id_comodo || !$id_imovel || empty($ds_comodo)) {
    header("Location: comodos.php?erro=" . urlencode("Dados inválidos."));
    exit();
}

try {
    $servidor = "localhost";
    $usuario_db = "root";
    $senha_db = "p1Mc3d25*";
    $banco = "mced";
    $conexao = new PDO("mysql:host=$servidor;dbname=$banco;charset=utf8", $usuario_db, $senha_db);
    $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 3. DUPLA VERIFICAÇÃO DE PROPRIEDADE (ESSENCIAL PARA SEGURANÇA)
    // a) Verifica se o cômodo que está sendo editado pertence ao usuário.
    // b) Verifica se o imóvel de destino também pertence ao usuário.
    $sql_verificacao = "SELECT 
                           (SELECT COUNT(*) FROM comodos c JOIN imoveis i ON c.id_imovel = i.id_imovel WHERE c.id_comodo = :id_comodo AND i.id_cliente = :id_cliente_1) as dono_do_comodo,
                           (SELECT COUNT(*) FROM imoveis WHERE id_imovel = :id_imovel AND id_cliente = :id_cliente_2) as dono_do_imovel";
    
    $stmt_verificacao = $conexao->prepare($sql_verificacao);
    $stmt_verificacao->execute([
        ':id_comodo' => $id_comodo,
        ':id_cliente_1' => $id_cliente_logado,
        ':id_imovel' => $id_imovel,
        ':id_cliente_2' => $id_cliente_logado
    ]);
    $verificacao = $stmt_verificacao->fetch(PDO::FETCH_ASSOC);
    
    if ($verificacao['dono_do_comodo'] == 0 || $verificacao['dono_do_imovel'] == 0) {
        header("Location: comodos.php?erro=" . urlencode("Acesso negado. Tentativa de operação inválida."));
        exit();
    }

    // 4. Executa a atualização segura com Prepared Statements
    $sql_update = "UPDATE comodos SET ds_comodo = :ds_comodo, id_imovel = :id_imovel WHERE id_comodo = :id_comodo";
    $stmt_update = $conexao->prepare($sql_update);
    $stmt_update->execute([
        ':ds_comodo' => $ds_comodo,
        ':id_imovel' => $id_imovel,
        ':id_comodo' => $id_comodo
    ]);

    // 5. Redireciona com mensagem de sucesso
    header("Location: comodos.php?sucesso_edicao=1");
    exit();

} catch (PDOException $e) {
    error_log("Erro ao atualizar cômodo: " . $e->getMessage());
    header("Location: comodos.php?erro=" . urlencode("Ocorreu um erro de banco de dados."));
    exit();
} finally {
    $conexao = null;
}
?>