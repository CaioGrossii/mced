<?php
session_start();

if (!isset($_SESSION['usuario_logado']) || !isset($_SESSION['id_cliente'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Sanitização e Validação
    $id_eletro = filter_input(INPUT_POST, 'id_eletro', FILTER_VALIDATE_INT);
    $data_reg = $_POST['data_reg'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fim = $_POST['hora_fim'];
    $id_cliente = $_SESSION['id_cliente'];

    if (!$id_eletro || empty($data_reg) || empty($hora_inicio) || empty($hora_fim)) {
        header("Location: consumo.php?erro=" . urlencode("Todos os campos são obrigatórios."));
        exit();
    }

    // Validação Lógica: Hora fim deve ser maior que hora início
    if (strtotime($hora_fim) <= strtotime($hora_inicio)) {
        header("Location: consumo.php?erro=" . urlencode("A hora final deve ser maior que a hora inicial."));
        exit();
    }

    try {
        $pdo = new PDO("mysql:host=localhost;dbname=mced;charset=utf8", "root", "p1Mc3d25*");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 2. SEGURANÇA CRÍTICA: Verificar se o eletrodoméstico pertence ao usuário
        // Isso previne que um usuário mal intencionado insira dados para o eletro de outra pessoa
        $sql_check = "SELECT i.id_cliente 
                      FROM eletrodomesticos e 
                      JOIN comodos c ON e.id_comodo = c.id_comodo
                      JOIN imoveis i ON c.id_imovel = i.id_imovel
                      WHERE e.id_eletro = :id_eletro";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([':id_eletro' => $id_eletro]);
        $dono = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if (!$dono || $dono['id_cliente'] != $id_cliente) {
            die("Erro de segurança: Acesso não autorizado ao recurso.");
        }

        // 3. Inserção
        $sql = "INSERT INTO consumo (data_reg, hora_inicio, hora_fim, id_eletro) 
                VALUES (:data, :inicio, :fim, :id_eletro)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':data' => $data_reg,
            ':inicio' => $hora_inicio,
            ':fim' => $hora_fim,
            ':id_eletro' => $id_eletro
        ]);

        header("Location: consumo.php?sucesso=1");
        exit();

    } catch (PDOException $e) {
        error_log("Erro insert consumo: " . $e->getMessage());
        header("Location: consumo.php?erro=" . urlencode("Erro ao salvar dados."));
        exit();
    }
}
?>