<?php
session_start();

// 1. Guardião de Sessão: Garante que o usuário está logado.
if (!isset($_SESSION['usuario_logado']) || !isset($_SESSION['id_cliente'])) {
    header("Location: login.php");
    exit();
}

// 2. Guardião de Método: Ações destrutivas devem usar POST.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: eletro.php");
    exit();
}

// 3. Coleta e Validação Segura do ID
$id_eletro_a_excluir = filter_input(INPUT_POST, 'id_eletro', FILTER_VALIDATE_INT);
$id_cliente_logado = $_SESSION['id_cliente'];

if (!$id_eletro_a_excluir) {
    header("Location: eletro.php?erro=" . urlencode("ID inválido ou não fornecido."));
    exit();
}

// 4. Conexão com o Banco de Dados
$servidor = "localhost";
$usuario_db = "root";
$senha_db = "p1Mc3d25*";
$banco = "mced";
$conexao = null;

try {
    $conexao = new PDO("mysql:host=$servidor;dbname=$banco;charset=utf8", $usuario_db, $senha_db);
    $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 5. VERIFICAÇÃO DE PROPRIEDADE (A Etapa de Segurança Mais Crítica)
    // Antes de excluir, garantimos que o eletrodoméstico pertence ao cliente logado.
    $sql_verificacao = "SELECT e.id_eletro 
                        FROM eletrodomesticos e
                        JOIN comodos c ON e.id_comodo = c.id_comodo
                        JOIN imoveis i ON c.id_imovel = i.id_imovel
                        WHERE e.id_eletro = :id_eletro AND i.id_cliente = :id_cliente";
    
    $stmt_verificacao = $conexao->prepare($sql_verificacao);
    $stmt_verificacao->execute([
        ':id_eletro' => $id_eletro_a_excluir,
        ':id_cliente' => $id_cliente_logado
    ]);

    if ($stmt_verificacao->rowCount() === 0) {
        // Se 0, o item não existe ou o usuário não é dono.
        header("Location: eletro.php?erro=" . urlencode("Acesso negado. Você não pode excluir este item."));
        exit();
    }

    // 6. EXECUÇÃO DA EXCLUSÃO DENTRO DE UMA TRANSAÇÃO
    // Isso garante que ambas as exclusões (consumo e eletro) ocorram, ou nenhuma ocorra.
    $conexao->beginTransaction();

    // a) Exclui os registros de consumo (filhos)
    $sql_delete_consumo = "DELETE FROM consumo WHERE id_eletro = :id_eletro";
    $stmt_consumo = $conexao->prepare($sql_delete_consumo);
    $stmt_consumo->execute([':id_eletro' => $id_eletro_a_excluir]);

    // b) Exclui o eletrodoméstico (pai)
    $sql_delete_eletro = "DELETE FROM eletrodomesticos WHERE id_eletro = :id_eletro";
    $stmt_eletro = $conexao->prepare($sql_delete_eletro);
    $stmt_eletro->execute([':id_eletro' => $id_eletro_a_excluir]);

    // c) Confirma a transação
    $conexao->commit();

    // 7. Redireciona com sucesso
    header("Location: eletro.php?sucesso_exclusao=1");
    exit();

} catch (PDOException $e) {
    // 8. Tratamento de Erro (Rollback)
    // Se qualquer coisa der errado, desfaz a transação
    if ($conexao && $conexao->inTransaction()) {
        $conexao->rollBack();
    }
    
    error_log("Erro ao excluir eletrodoméstico: " . $e->getMessage());
    header("Location: eletro.php?erro=" . urlencode("Erro de banco de dados ao tentar excluir."));
    exit();
} finally {
    $conexao = null;
}
?>