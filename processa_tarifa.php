<?php
/**
 * processa_tarifa.php
 * Responsabilidade: Atualizar a tarifa global de energia do cliente logado.
 */

session_start();

// 1. GUARDIÃO (Segurança)
// Impede acesso direto sem login
if (!isset($_SESSION['usuario_logado']) || !isset($_SESSION['id_cliente'])) {
    header("Location: login.php");
    exit();
}

// 2. VALIDAÇÃO DE MÉTODO
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: dash.php");
    exit();
}

// 3. SANITIZAÇÃO E VALIDAÇÃO DE DADOS
// Aceita tanto formato 0.85 quanto 0,85 (substitui vírgula por ponto)
$tarifa_raw = $_POST['tarifa'] ?? '';
$tarifa_clean = str_replace(',', '.', $tarifa_raw);

// Valida se é um número float válido
if (!is_numeric($tarifa_clean) || $tarifa_clean < 0) {
    // Redireciona com erro (ajuste o caminho 'consumo.php' conforme o nome da sua página na imagem)
    header("Location: consumo.php?erro=" . urlencode("Valor de tarifa inválido."));
    exit();
}

$tarifa_final = (float) $tarifa_clean;
$id_cliente = $_SESSION['id_cliente'];

// 4. PERSISTÊNCIA (Atualização no Banco)
try {
    $servidor = "localhost";
    $usuario_db = "root";
    $senha_db = "p1Mc3d25*";
    $banco = "mced";

    $conexao = new PDO("mysql:host=$servidor;dbname=$banco;charset=utf8", $usuario_db, $senha_db);
    $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Query segura focada apenas no cliente logado (evita IDOR)
    $sql = "UPDATE clientes SET tarifa = :tarifa WHERE id_cliente = :id_cliente";
    
    $stmt = $conexao->prepare($sql);
    $stmt->bindParam(':tarifa', $tarifa_final);
    $stmt->bindParam(':id_cliente', $id_cliente);
    $stmt->execute();

    // Atualiza também na sessão para uso imediato sem precisar reconsultar o banco
    $_SESSION['tarifa_usuario'] = $tarifa_final;

    // Redireciona com sucesso
    header("Location: consumo.php?sucesso_tarifa=1");
    exit();

} catch (PDOException $e) {
    error_log("Erro ao atualizar tarifa: " . $e->getMessage());
    header("Location: consumo.php?erro=" . urlencode("Erro no sistema. Tente novamente."));
    exit();
} finally {
    $conexao = null;
}
?>