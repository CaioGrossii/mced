<?php
session_start();

if (!isset($_SESSION['usuario_logado']) || !isset($_SESSION['id_cliente'])) {
    header("Location: login.php");
    exit();
}

// 1. Validação do ID
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    header("Location: eletro.php?erro=" . urlencode("ID do eletrodoméstico inválido."));
    exit();
}
$id_eletro_a_editar = $_GET['id'];
$id_cliente_logado = $_SESSION['id_cliente'];

// Variáveis para preencher o formulário
$eletro_a_editar = null;
$comodos_disponiveis = [];
$categorias_disponiveis = [];

try {
    $servidor = "localhost";
    $usuario_db = "root";
    $senha_db = "p1Mc3d25*";
    $banco = "mced";
    
    $conexao = new PDO("mysql:host=$servidor;dbname=$banco;charset=utf8", $usuario_db, $senha_db);
    $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2. BUSCA SEGURA: Pega os dados do eletrodoméstico E VERIFICA SE ELE PERTENCE AO CLIENTE LOGADO.
    $sql_busca_eletro = "SELECT e.id_eletro, e.nm_eletro, e.watts, e.id_comodo, e.id_categoria
                         FROM eletrodomesticos e
                         JOIN comodos c ON e.id_comodo = c.id_comodo
                         JOIN imoveis i ON c.id_imovel = i.id_imovel
                         WHERE e.id_eletro = :id_eletro AND i.id_cliente = :id_cliente";
    $stmt_busca = $conexao->prepare($sql_busca_eletro);
    $stmt_busca->bindParam(':id_eletro', $id_eletro_a_editar);
    $stmt_busca->bindParam(':id_cliente', $id_cliente_logado);
    $stmt_busca->execute();
    $eletro_a_editar = $stmt_busca->fetch(PDO::FETCH_ASSOC);

    if (!$eletro_a_editar) {
        header("Location: eletro.php?erro=" . urlencode("Eletrodoméstico não encontrado ou acesso negado."));
        exit();
    }

    // 3. Busca cômodos e categorias para preencher os <select> do formulário
    $sql_comodos_form = "SELECT c.id_comodo, c.ds_comodo, i.rua, i.numero 
                         FROM comodos c JOIN imoveis i ON c.id_imovel = i.id_imovel
                         WHERE i.id_cliente = :id_cliente ORDER BY i.rua, c.ds_comodo";
    $stmt_comodos_form = $conexao->prepare($sql_comodos_form);
    $stmt_comodos_form->bindParam(':id_cliente', $id_cliente_logado);
    $stmt_comodos_form->execute();
    $comodos_disponiveis = $stmt_comodos_form->fetchAll(PDO::FETCH_ASSOC);

    $sql_categorias = "SELECT id_categoria, ds_categoria FROM categorias WHERE id_cliente = :id_cliente ORDER BY ds_categoria ASC";
    $stmt_categorias = $conexao->prepare($sql_categorias);
    $stmt_categorias->bindParam(':id_cliente', $id_cliente_logado);
    $stmt_categorias->execute();
    $categorias_disponiveis = $stmt_categorias->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Erro na página de edição de eletrodomésticos: " . $e->getMessage());
    header("Location: eletro.php?erro=" . urlencode("Erro de banco de dados ao carregar dados para edição."));
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
                <h1>Editar: <?php echo htmlspecialchars($eletro_a_editar['nm_eletro']); ?></h1>
            </header>

            <div class="card" style="text-align: left;">
                <form action="processa_eletro_update.php" method="POST">
                    <input type="hidden" name="id_eletro" value="<?php echo $eletro_a_editar['id_eletro']; ?>">

                    <div class="form-grid">
                        
                        <div class="form-group full-width">
                            <label for="nm_eletro">Nome do Eletrodoméstico</label>
                            <input type="text" name="nm_eletro" id="nm_eletro" value="<?php echo htmlspecialchars($eletro_a_editar['nm_eletro']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="id_comodo">Localização (Cômodo)</label>
                            <select name="id_comodo" id="id_comodo" required>
                                <?php
                                $imovel_atual = '';
                                foreach ($comodos_disponiveis as $comodo):
                                    $endereco = htmlspecialchars($comodo['rua'] . ', ' . $comodo['numero']);
                                    if ($endereco !== $imovel_atual) {
                                        if ($imovel_atual !== '') echo '</optgroup>';
                                        echo '<optgroup label="' . $endereco . '">';
                                        $imovel_atual = $endereco;
                                    }
                                    // Adiciona o atributo 'selected' se o ID do cômodo corresponder ao do eletrodoméstico
                                    $selected = ($comodo['id_comodo'] == $eletro_a_editar['id_comodo']) ? 'selected' : '';
                                ?>
                                    <option value="<?php echo $comodo['id_comodo']; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($comodo['ds_comodo']); ?></option>
                                <?php endforeach; ?>
                                </optgroup>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="id_categoria">Categoria</label>
                            <select name="id_categoria" id="id_categoria" required>
                                <?php foreach ($categorias_disponiveis as $categoria): 
                                    $selected = ($categoria['id_categoria'] == $eletro_a_editar['id_categoria']) ? 'selected' : '';
                                ?>
                                    <option value="<?php echo $categoria['id_categoria']; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($categoria['ds_categoria']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="watts">Potência (Watts)</label>
                            <input type="number" name="watts" id="watts" value="<?php echo htmlspecialchars($eletro_a_editar['watts']); ?>" required placeholder="Ex: 150">
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn">Salvar Alterações</button>
                            <a href="eletro.php" class="extra-links">Cancelar</a>
                        </div>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>