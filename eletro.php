<?php
session_start();

// Garante que o usuário está logado e que temos seu ID.
if (!isset($_SESSION['usuario_logado']) || !isset($_SESSION['id_cliente'])) {
    header("Location: login.php");
    exit();
}

// Inicializa os arrays que serão preenchidos com dados do banco.
$comodos_disponiveis = [];
$categorias_disponiveis = [];
$eletrodomesticos_agrupados = [];
$erro_banco = null;

try {
    // Configurações e conexão com o banco de dados
    $servidor = "localhost";
    $usuario_db = "root";
    $senha_db = "p1Mc3d25*";
    $banco = "mced";
    
    $conexao = new PDO("mysql:host=$servidor;dbname=$banco;charset=utf8", $usuario_db, $senha_db);
    $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Busca todos os cômodos do cliente para popular o formulário.
    $sql_comodos_form = "SELECT c.id_comodo, c.ds_comodo, i.rua, i.numero 
                         FROM comodos c
                         JOIN imoveis i ON c.id_imovel = i.id_imovel
                         WHERE i.id_cliente = :id_cliente
                         ORDER BY i.rua, c.ds_comodo";
    $stmt_comodos_form = $conexao->prepare($sql_comodos_form);
    $stmt_comodos_form->bindParam(':id_cliente', $_SESSION['id_cliente']);
    $stmt_comodos_form->execute();
    $comodos_disponiveis = $stmt_comodos_form->fetchAll(PDO::FETCH_ASSOC);

    // 2. Busca todas as categorias disponíveis para popular o formulário.
    $sql_categorias = "SELECT id_categoria, ds_categoria FROM categorias ORDER BY ds_categoria ASC";
    $stmt_categorias = $conexao->prepare($sql_categorias);
    $stmt_categorias->execute();
    $categorias_disponiveis = $stmt_categorias->fetchAll(PDO::FETCH_ASSOC);


    // 3. Busca todos os eletrodomésticos do cliente para a listagem.
    $sql_eletros = "SELECT e.nm_eletro, e.watts, c.ds_comodo, cat.ds_categoria, i.rua, i.numero
                    FROM eletrodomesticos e
                    JOIN comodos c ON e.id_comodo = c.id_comodo
                    JOIN imoveis i ON c.id_imovel = i.id_imovel
                    LEFT JOIN categorias cat ON e.id_categoria = cat.id_categoria
                    WHERE i.id_cliente = :id_cliente
                    ORDER BY i.rua, c.ds_comodo, e.nm_eletro";
    $stmt_eletros = $conexao->prepare($sql_eletros);
    $stmt_eletros->bindParam(':id_cliente', $_SESSION['id_cliente']);
    $stmt_eletros->execute();
    $resultados = $stmt_eletros->fetchAll(PDO::FETCH_ASSOC);

    // Organiza os resultados em um array agrupado para facilitar a exibição.
    foreach ($resultados as $eletro) {
        $endereco = $eletro['rua'] . ', ' . $eletro['numero'];
        $comodo = $eletro['ds_comodo'];
        $eletrodomesticos_agrupados[$endereco][$comodo][] = [
            'nome' => $eletro['nm_eletro'], 
            'watts' => $eletro['watts'],
            'categoria' => $eletro['ds_categoria'] ?? 'Não definida' // Usa 'Não definida' se a categoria for nula
        ];
    }

} catch (PDOException $e) {
    error_log("Erro ao buscar dados para a página de eletrodomésticos: " . $e->getMessage());
    $erro_banco = "Não foi possível carregar os dados. Tente novamente mais tarde.";
} finally {
    $conexao = null; // Garante que a conexão seja fechada.
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>MCED - Meus Eletrodomésticos</title>
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
                    <li><a href="eletrodomesticos.php" class="active"><i class="fas fa-plug"></i> Eletrodomésticos</a></li>
                    <li><a href="categorias.php"><i class="fas fa-tags"></i> Categorias</a></li>
                    <li><a href="#"><i class="fas fa-chart-bar"></i> Relatórios</a></li>
                </ul>
            </nav>
            <div class="logout"><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a></div>
        </aside>

        <main class="main-content">
            <header><h1>Gerenciar Eletrodomésticos</h1></header>

            <div class="card" style="margin-bottom: 20px; text-align: left;">
                <h3>Cadastrar Novo Eletrodoméstico</h3>
                <form action="processa_eletro.php" method="POST" style="margin-top: 15px;">
                    <?php if (count($comodos_disponiveis) > 0): ?>
                        <div style="display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end;">
                            
                            <div style="flex: 2; min-width: 200px;">
                                <label for="id_comodo">Cômodo</label>
                                <select name="id_comodo" id="id_comodo" required style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">
                                    <option value="">Selecione um cômodo...</option>
                                    <?php
                                    $imovel_atual = '';
                                    foreach ($comodos_disponiveis as $comodo):
                                        $endereco = htmlspecialchars($comodo['rua'] . ', ' . $comodo['numero']);
                                        if ($endereco !== $imovel_atual) {
                                            if ($imovel_atual !== '') echo '</optgroup>';
                                            echo '<optgroup label="' . $endereco . '">';
                                            $imovel_atual = $endereco;
                                        }
                                    ?>
                                        <option value="<?php echo $comodo['id_comodo']; ?>"><?php echo htmlspecialchars($comodo['ds_comodo']); ?></option>
                                    <?php endforeach; ?>
                                    </optgroup>
                                </select>
                            </div>
                            
                            <div style="flex: 1; min-width: 150px;">
                                <label for="id_categoria">Categoria</label>
                                <select name="id_categoria" id="id_categoria" required style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">
                                    <option value="">Selecione...</option>
                                    <?php foreach ($categorias_disponiveis as $categoria): ?>
                                        <option value="<?php echo $categoria['id_categoria']; ?>"><?php echo htmlspecialchars($categoria['ds_categoria']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div style="flex: 1; min-width: 150px;">
                                <label for="nm_eletro">Nome do Eletrodoméstico</label>
                                <input type="text" name="nm_eletro" id="nm_eletro" placeholder="Ex: Geladeira" required style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">
                            </div>

                            <div style="flex: 1; min-width: 100px;">
                                <label for="watts">Potência (Watts)</label>
                                <input type="number" name="watts" id="watts" placeholder="Ex: 150" required style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">
                            </div>

                            <div>
                                <button type="submit" style="padding: 10px 15px; border-radius: 4px; background-color: #2563eb; color: white; border: none; cursor: pointer;">Salvar</button>
                            </div>
                        </div>
                    <?php else: ?>
                        <p>Você precisa <a href="comodos.php">cadastrar um cômodo</a> antes de poder adicionar eletrodomésticos.</p>
                    <?php endif; ?>
                </form>
            </div>

            <div class="table-card">
                <h3>Meus Eletrodomésticos</h3>
                <?php if ($erro_banco): ?>
                    <p style="color: red;"><?php echo htmlspecialchars($erro_banco); ?></p>
                <?php elseif (empty($eletrodomesticos_agrupados)): ?>
                    <p>Nenhum eletrodoméstico cadastrado.</p>
                <?php else: ?>
                    <?php foreach ($eletrodomesticos_agrupados as $endereco => $comodos): ?>
                        <div class="card" style="margin-bottom: 15px; text-align: left;">
                            <h4><i class="fas fa-home"></i> <?php echo htmlspecialchars($endereco); ?></h4>
                            <hr style="margin: 10px 0;">
                            <?php foreach ($comodos as $nome_comodo => $eletros): ?>
                                <h5 style="margin-top: 10px;"><i class="fas fa-door-open"></i> <?php echo htmlspecialchars($nome_comodo); ?></h5>
                                <table>
                                    <thead><tr><th>Eletrodoméstico</th><th>Categoria</th><th>Potência</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($eletros as $eletro): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($eletro['nome']); ?></td>
                                            <td><?php echo htmlspecialchars($eletro['categoria']); ?></td>
                                            <td><?php echo htmlspecialchars($eletro['watts']); ?> W</td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>