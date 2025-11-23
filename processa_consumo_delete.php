<?php
session_start();

if (!isset($_SESSION['usuario_logado']) || $_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.php");
    exit();
}

$id_consumo = filter_input(INPUT_POST, 'id_consumo', FILTER_VALIDATE_INT);
$id_cliente = $_SESSION['id_cliente'];

if (!$id_consumo) {
    header("Location: consumo.php?erro=id_invalido");
    exit();
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=mced;charset=utf8", "root", "p1Mc3d25*");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // SEGURANÇA: Delete com JOIN para garantir que o registro pertence ao usuário
    // O delete só acontece se o id_consumo estiver ligado a um eletrodomestico que pertence ao usuario logado
    $sql = "DELETE cons FROM consumo cons
            INNER JOIN eletrodomesticos e ON cons.id_eletro = e.id_eletro
            INNER JOIN comodos c ON e.id_comodo = c.id_comodo
            INNER JOIN imoveis i ON c.id_imovel = i.id_imovel
            WHERE cons.id_consumo = :id_consumo AND i.id_cliente = :id_cliente";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id_consumo' => $id_consumo, ':id_cliente' => $id_cliente]);

    if ($stmt->rowCount() > 0) {
        header("Location: consumo.php?sucesso=deletado");
    } else {
        header("Location: consumo.php?erro=" . urlencode("Registro não encontrado ou permissão negada."));
    }

} catch (PDOException $e) {
    error_log("Erro delete consumo: " . $e->getMessage());
    header("Location: consumo.php?erro=erro_banco");
}
?>