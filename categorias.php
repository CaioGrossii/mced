<?php
session_start();

if (!isset($_SESSION['usuario_logado']) || !isset($_SESSION['id_cliente'])) {
    header("Location: login.php");
    exit();
}

// Lógica para buscar as categorias existentes no banco de dados
$categorias = [];
$erro_banco = null;

try {
    $servidor = "localhost";
    $usuario_db = "root";
    $senha_db = "p1Mc3d25*";
    $banco = "mced";
    
    $conexao = new PDO("mysql:host=$servidor;dbname=$banco;charset=utf8", $usuario_db, $senha_db);
    $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Busca todas as categorias da tabela
    $sql = "SELECT id_categoria, ds_categoria FROM categorias ORDER BY ds_categoria ASC";
    $stmt = $conexao->prepare($sql);
    $stmt->execute();
    
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Erro ao buscar categorias: " . $e->getMessage());
    $erro_banco = "Não foi possível carregar as categorias. Tente novamente mais tarde.";
} finally {
    $conexao = null;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>MCED - Categorias</title>
    <link rel="stylesheet" href="dash.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="logo"><h2>MCED</h2></div>
            <nav>
                <ul>
                    <li><a href="dash.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>
                    <li><a href="#"><i class="fa-solid fa-bolt-lightning"></i> Consumo</a></li>
                    <li><a href="view_imoveis.php"><i class="fas fa-building"></i> Imóveis</a></li>
                    <li><a href="comodos.php"><i class="fas fa-door-open"></i> Cômodos</a></li>
                    <li><a href="categorias.php" class="active"><i class="fas fa-tags"></i> Categorias</a></li>
                    <li><a href="eletro.php"><i class="fas fa-plug"></i> Eletrodomésticos</a></li>
                    <li><a href="#"><i class="fas fa-chart-bar"></i> Relatórios</a></li>
                </ul>
            </nav>
            <div class="logout"><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a></div>
        </aside>

        <main class="main-content">
            <header><h1>Gerenciar Categorias de Eletrodomésticos</h1></header>

            <div class="card" style="margin-bottom: 20px; text-align: left;">
                <h3>Cadastrar Nova Categoria</h3>
                <form action="processa_categoria.php" method="POST" style="margin-top: 15px;">
                    <div style="display: flex; gap: 15px; align-items: flex-end;">
                        <div style="flex-grow: 1;">
                            <label for="ds_categoria">Nome da Categoria</label>
                            <input type="text" name="ds_categoria" id="ds_categoria" placeholder="Ex: Cozinha, Limpeza, Vídeo" required style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">
                        </div>
                        <div>
                            <button type="submit" style="padding: 10px 15px; border-radius: 4px; background-color: #2563eb; color: white; border: none; cursor: pointer;">Salvar</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="table-card">
                <h3>Categorias Cadastradas</h3>
                <?php if ($erro_banco): ?>
                    <p style="color: red;"><?php echo htmlspecialchars($erro_banco); ?></p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Nome da Categoria</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($categorias) > 0): ?>
                                <?php foreach ($categorias as $categoria): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($categoria['ds_categoria']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td style="text-align: center;">Nenhuma categoria cadastrada ainda.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>