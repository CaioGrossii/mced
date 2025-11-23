<?php
session_start();
if (!isset($_SESSION['usuario_logado'])) exit();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_consumo = filter_input(INPUT_POST, 'id_consumo', FILTER_VALIDATE_INT);
    $data = $_POST['data_reg'];
    $inicio = $_POST['hora_inicio'];
    $fim = $_POST['hora_fim'];
    $id_cliente = $_SESSION['id_cliente'];

    if (strtotime($fim) <= strtotime($inicio)) {
        header("Location: consumo_editar.php?id=$id_consumo&erro=hora_invalida");
        exit();
    }

    try {
        $pdo = new PDO("mysql:host=localhost;dbname=mced;charset=utf8", "root", "p1Mc3d25*");
        
        // Update Seguro com validação de propriedade no WHERE
        $sql = "UPDATE consumo cons
                JOIN eletrodomesticos e ON cons.id_eletro = e.id_eletro
                JOIN comodos c ON e.id_comodo = c.id_comodo
                JOIN imoveis i ON c.id_imovel = i.id_imovel
                SET cons.data_reg = :data, cons.hora_inicio = :inicio, cons.hora_fim = :fim
                WHERE cons.id_consumo = :id AND i.id_cliente = :cliente";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':data' => $data, 
            ':inicio' => $inicio, 
            ':fim' => $fim, 
            ':id' => $id_consumo, 
            ':cliente' => $id_cliente
        ]);

        header("Location: consumo.php?sucesso=editado");

    } catch (PDOException $e) {
        error_log($e->getMessage());
        header("Location: consumo.php?erro=erro_update");
    }
}
?>