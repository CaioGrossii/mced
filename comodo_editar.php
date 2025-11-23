<?php
session_start();

if (!isset($_SESSION['usuario_logado']) || !isset($_SESSION['id_cliente'])) {
    header("Location: login.php");
    exit();
}

// 1. Validação do ID do cômodo vindo da URL
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    header("Location: comodos.php?erro=" . urlencode("ID do cômodo inválido."));
    exit();
}
$id_comodo_a_editar = $_GET['id'];
$id_cliente_logado = $_SESSION['id_cliente'];

$comodo_a_editar = null;
$imoveis_disponiveis = [];

try {
    // Conexão com o banco de dados
    $servidor = "localhost";
    $usuario_db = "root";
    $senha_db = "p1Mc3d25*";
    $banco = "mced";
    $conexao = new PDO("mysql:host=$servidor;dbname=$banco;charset=utf8", $usuario_db, $senha_db);
    $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2. BUSCA SEGURA: Verifica se o cômodo pertence ao cliente logado
    $sql_busca_comodo = "SELECT c.id_comodo, c.ds_comodo, c.id_imovel
                         FROM comodos c
                         JOIN imoveis i ON c.id_imovel = i.id_imovel
                         WHERE c.id_comodo = :id_comodo AND i.id_cliente = :id_cliente";
    $stmt_busca = $conexao->prepare($sql_busca_comodo);
    $stmt_busca->bindParam(':id_comodo', $id_comodo_a_editar);
    $stmt_busca->bindParam(':id_cliente', $id_cliente_logado);
    $stmt_busca->execute();
    $comodo_a_editar = $stmt_busca->fetch(PDO::FETCH_ASSOC);

    if (!$comodo_a_editar) {
        header("Location: comodos.php?erro=" . urlencode("Cômodo não encontrado ou acesso negado."));
        exit();
    }

    // 3. Busca todos os imóveis do cliente para popular o <select>
    $sql_imoveis = "SELECT id_imovel, rua, numero FROM imoveis WHERE id_cliente = :id_cliente ORDER BY rua";
    $stmt_imoveis = $conexao->prepare($sql_imoveis);
    $stmt_imoveis->bindParam(':id_cliente', $id_cliente_logado);
    $stmt_imoveis->execute();
    $imoveis_disponiveis = $stmt_imoveis->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Erro na página de edição de cômodo: " . $e->getMessage());
    header("Location: comodos.php?erro=" . urlencode("Erro de banco de dados."));
    exit();
} finally {
    $conexao = null;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <?php include 'includes/head.php'; ?>
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
                    <li><a href="categorias.php"><i class="fas fa-tags"></i> Categorias</a></li>
                    <li><a href="eletro.php" class="active"><i class="fas fa-plug"></i> Eletrodomésticos</a></li>
                    <li><a href="#"><i class="fas fa-chart-bar"></i> Relatórios</a></li>
                </ul>
            </nav>
            <div class="logout"><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a></div>
        </aside>

        <main class="main-content">
            <header>
                <h1>Editar Cômodo: <?php echo htmlspecialchars($comodo_a_editar['ds_comodo']); ?></h1>
            </header>

            <div class="card" style="text-align: left;">
                <form action="processa_comodo_update.php" method="POST">
                    <input type="hidden" name="id_comodo" value="<?php echo $comodo_a_editar['id_comodo']; ?>">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="ds_comodo">Nome do Cômodo</label>
                            <input type="text" name="ds_comodo" id="ds_comodo" value="<?php echo htmlspecialchars($comodo_a_editar['ds_comodo']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="id_imovel">Imóvel</label>
                            <select name="id_imovel" id="id_imovel" required>
                                <?php foreach ($imoveis_disponiveis as $imovel): 
                                    $endereco = htmlspecialchars($imovel['rua'] . ', ' . $imovel['numero']);
                                    $selected = ($imovel['id_imovel'] == $comodo_a_editar['id_imovel']) ? 'selected' : '';
                                ?>
                                    <option value="<?php echo $imovel['id_imovel']; ?>" <?php echo $selected; ?>><?php echo $endereco; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn">Salvar Alterações</button>
                            <a href="comodos.php" class="extra-links">Cancelar</a>
                        </div>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>