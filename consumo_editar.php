<?php
session_start();
if (!isset($_SESSION['usuario_logado'])) header("Location: login.php");

$id_consumo = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$id_cliente = $_SESSION['id_cliente'];

if (!$id_consumo) header("Location: consumo.php");

$dados = null;
try {
    $pdo = new PDO("mysql:host=localhost;dbname=mced;charset=utf8", "root", "p1Mc3d25*");
    
    // Busca dados do consumo APENAS se pertencer ao usuário
    $sql = "SELECT cons.*, e.nm_eletro 
            FROM consumo cons
            JOIN eletrodomesticos e ON cons.id_eletro = e.id_eletro
            JOIN comodos c ON e.id_comodo = c.id_comodo
            JOIN imoveis i ON c.id_imovel = i.id_imovel
            WHERE cons.id_consumo = :id AND i.id_cliente = :cliente";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id_consumo, ':cliente' => $id_cliente]);
    $dados = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$dados) die("Registro não encontrado.");

} catch (PDOException $e) {
    die("Erro: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <?php include 'includes/head.php'; ?>
</head>
<body>
    <div class="container">
        <aside class="sidebar"></aside>
        <main class="main-content">
            <header><h1>Editar Consumo</h1></header>
            
            <div class="card">
                <h3>Editando: <?php echo htmlspecialchars($dados['nm_eletro']); ?></h3>
                <form action="processa_consumo_update.php" method="POST" class="form-grid">
                    <input type="hidden" name="id_consumo" value="<?php echo $dados['id_consumo']; ?>">
                    
                    <div class="form-group">
                        <label>Data</label>
                        <input type="date" name="data_reg" value="<?php echo $dados['data_reg']; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Início</label>
                        <input type="time" name="hora_inicio" value="<?php echo $dados['hora_inicio']; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Fim</label>
                        <input type="time" name="hora_fim" value="<?php echo $dados['hora_fim']; ?>" required>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn">Salvar Alterações</button>
                        <a href="consumo.php" class="btn-delete" style="text-decoration:none;">Cancelar</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>