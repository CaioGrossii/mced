<?php
session_start();

if (!isset($_SESSION['usuario_logado']) || !isset($_SESSION['id_cliente'])) {
    header("Location: login.php");
    exit();
}

// Lógica para buscar os imóveis e seus respectivos cômodos
$imoveis_com_comodos = [];
$imoveis_disponiveis = []; // Para o formulário
$erro_banco = null;

try {
    $servidor = "localhost";
    $usuario_db = "root";
    $senha_db = "p1Mc3d25*";
    $banco = "mced";
    
    $conexao = new PDO("mysql:host=$servidor;dbname=$banco;charset=utf8", $usuario_db, $senha_db);
    $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Busca todos os imóveis do cliente para o dropdown do formulário
    $sql_imoveis = "SELECT id_imovel, rua, numero FROM imoveis WHERE id_cliente = :id_cliente";
    $stmt_imoveis = $conexao->prepare($sql_imoveis);
    $stmt_imoveis->bindParam(':id_cliente', $_SESSION['id_cliente']);
    $stmt_imoveis->execute();
    $imoveis_disponiveis = $stmt_imoveis->fetchAll(PDO::FETCH_ASSOC);

    // 2. Busca os cômodos e os agrupa por imóvel
    $sql_comodos = "SELECT i.id_imovel, i.rua, i.numero, c.ds_comodo
                    FROM imoveis i
                    LEFT JOIN comodos c ON i.id_imovel = c.id_imovel
                    WHERE i.id_cliente = :id_cliente
                    ORDER BY i.rua, c.ds_comodo";
    $stmt_comodos = $conexao->prepare($sql_comodos);
    $stmt_comodos->bindParam(':id_cliente', $_SESSION['id_cliente']);
    $stmt_comodos->execute();
    
    $resultados = $stmt_comodos->fetchAll(PDO::FETCH_ASSOC);

    // Organiza os dados em um array agrupado
    foreach ($resultados as $resultado) {
        $imoveis_com_comodos[$resultado['id_imovel']]['endereco'] = $resultado['rua'] . ', ' . $resultado['numero'];
        if ($resultado['ds_comodo']) {
            $imoveis_com_comodos[$resultado['id_imovel']]['comodos'][] = $resultado['ds_comodo'];
        }
    }

} catch (PDOException $e) {
    error_log("Erro ao buscar cômodos: " . $e->getMessage());
    $erro_banco = "Não foi possível carregar os dados. Tente novamente mais tarde.";
} finally {
    $conexao = null;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>MCED - Meus Cômodos</title>
    <link rel="stylesheet" href="dash.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="logo">
                <h2>MCED</h2>
            </div>
            <nav>
                <ul>
                    <li><a href="dash.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>
                    <li><a href="#"><i class="fa-solid fa-bolt-lightning"></i> Consumo</a></li>
                    <li><a href="view_imoveis.php"><i class="fas fa-building"></i> Imóveis</a></li>
                    <li><a href="comodos.php" class="active"><i class="fas fa-door-open"></i> Cômodos</a></li>
                    <li><a href="eletro.php"><i class="fas fa-plug"></i> Eletrodomésticos</a></li>
                    <li><a href="#"><i class="fas fa-chart-bar"></i> Relatórios</a></li>
                </ul>
            </nav>
            <div class="logout">
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a>
            </div>
        </aside>

        <main class="main-content">
            <header>
                <h1>Gerenciar Cômodos</h1>
            </header>

            <div class="card" style="margin-bottom: 20px; text-align: left;">
                <h3>Cadastrar Novo Cômodo</h3>
                <form action="processa_comodo.php" method="POST" style="margin-top: 15px;">
                    <?php if (count($imoveis_disponiveis) > 0): ?>
                        <div style="display: flex; gap: 15px;">
                            <div style="flex: 2;">
                                <label for="id_imovel">Selecione o Imóvel</label>
                                <select name="id_imovel" id="id_imovel" required style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">
                                    <?php foreach ($imoveis_disponiveis as $imovel): ?>
                                        <option value="<?php echo $imovel['id_imovel']; ?>">
                                            <?php echo htmlspecialchars($imovel['rua'] . ', ' . $imovel['numero']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div style="flex: 1;">
                                <label for="ds_comodo">Nome do Cômodo</label>
                                <input type="text" name="ds_comodo" id="ds_comodo" placeholder="Ex: Cozinha" required style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">
                            </div>
                            <div style="align-self: flex-end;">
                                <button type="submit" style="padding: 10px 15px; border-radius: 4px; background-color: #2563eb; color: white; border: none; cursor: pointer;">Salvar</button>
                            </div>
                        </div>
                    <?php else: ?>
                        <p>Você precisa <a href="imoveis.php">cadastrar um imóvel</a> antes de poder adicionar cômodos.</p>
                    <?php endif; ?>
                </form>
            </div>

            <div class="table-card">
                <h3>Cômodos por Imóvel</h3>
                <?php if ($erro_banco): ?>
                    <p style="color: red;"><?php echo htmlspecialchars($erro_banco); ?></p>
                <?php elseif (empty($imoveis_com_comodos)): ?>
                    <p>Nenhum cômodo cadastrado.</p>
                <?php else: ?>
                    <?php foreach ($imoveis_com_comodos as $imovel_id => $data): ?>
                        <div class="card" style="margin-bottom: 15px; text-align: left;">
                            <h4><i class="fas fa-home"></i> <?php echo htmlspecialchars($data['endereco']); ?></h4>
                            <hr style="margin: 10px 0;">
                            <?php if (isset($data['comodos'])): ?>
                                <ul style="list-style: none; padding-left: 0;">
                                    <?php foreach ($data['comodos'] as $comodo): ?>
                                        <li style="padding: 5px 0;"><i class="fas fa-door-open" style="margin-right: 8px;"></i> <?php echo htmlspecialchars($comodo); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p>Nenhum cômodo cadastrado para este imóvel.</p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>