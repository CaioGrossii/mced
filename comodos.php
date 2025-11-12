<?php
// Inicia a sessão para acessar as variáveis de usuário.
session_start();

// Guardião de segurança: Garante que o usuário está logado.
if (!isset($_SESSION['usuario_logado']) || !isset($_SESSION['id_cliente'])) {
    header("Location: login.php");
    exit();
}

// Inicializa variáveis para evitar erros.
$imoveis_disponiveis = []; // Para o formulário de cadastro
$comodos_agrupados = [];   // Para a listagem
$erro_banco = null;

try {
    // Configurações e conexão com o banco de dados
    $servidor = "localhost";
    $usuario_db = "root";
    $senha_db = "p1Mc3d25*";
    $banco = "mced";
    
    $conexao = new PDO("mysql:host=$servidor;dbname=$banco;charset=utf8", $usuario_db, $senha_db);
    $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $id_cliente_logado = $_SESSION['id_cliente'];

    // 1. Busca os imóveis do cliente para popular o <select> do formulário de cadastro.
    $sql_imoveis_form = "SELECT id_imovel, fantasia, numero FROM imoveis WHERE id_cliente = :id_cliente ORDER BY fantasia ASC";
    $stmt_imoveis_form = $conexao->prepare($sql_imoveis_form);
    $stmt_imoveis_form->bindParam(':id_cliente', $id_cliente_logado);
    $stmt_imoveis_form->execute();
    $imoveis_disponiveis = $stmt_imoveis_form->fetchAll(PDO::FETCH_ASSOC);

    // 2. Busca todos os cômodos do cliente, já ordenados por imóvel.
    $sql_comodos_lista = "SELECT 
                              c.id_comodo, c.ds_comodo, 
                              i.id_imovel, i.fantasia, i.numero
                          FROM comodos c
                          JOIN imoveis i ON c.id_imovel = i.id_imovel
                          WHERE i.id_cliente = :id_cliente
                          ORDER BY i.fantasia, i.numero, c.ds_comodo ASC";
    $stmt_comodos_lista = $conexao->prepare($sql_comodos_lista);
    $stmt_comodos_lista->bindParam(':id_cliente', $id_cliente_logado);
    $stmt_comodos_lista->execute();
    $resultados = $stmt_comodos_lista->fetchAll(PDO::FETCH_ASSOC);

    // 3. Agrupa os cômodos por imóvel para facilitar a exibição.
    foreach ($resultados as $comodo) {
        $endereco_imovel = $comodo['fantasia'] . ', ' . $comodo['numero'];
        $comodos_agrupados[$endereco_imovel][] = [
            'id' => $comodo['id_comodo'],
            'nome' => $comodo['ds_comodo']
        ];
    }

} catch (PDOException $e) {
    error_log("Erro ao buscar dados para a página de cômodos: " . $e->getMessage());
    $erro_banco = "Não foi possível carregar os dados. Tente novamente mais tarde.";
} finally {
    $conexao = null; // Fecha a conexão.
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>MCED - Gerenciar Cômodos</title>
    <link rel="stylesheet" href="dash.css">
    <link rel="stylesheet" href="index.css">
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
                    <li><a href="comodos.php" class="active"><i class="fas fa-door-open"></i> Cômodos</a></li>
                    <li><a href="categorias.php"><i class="fas fa-tags"></i> Categorias</a></li>
                    <li><a href="eletro.php"><i class="fas fa-plug"></i> Eletrodomésticos</a></li>
                    <li><a href="#"><i class="fas fa-chart-bar"></i> Relatórios</a></li>
                </ul>
            </nav>
            <div class="logout"><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a></div>
        </aside>

        <main class="main-content">
            <header><h1>Gerenciar Cômodos</h1></header>

            <?php if (isset($_GET['sucesso_edicao'])): ?>
                <div class="card" style="background-color: #d4edda; color: #155724; padding: 15px; margin-bottom: 20px; text-align:left;">
                    Cômodo atualizado com sucesso!
                </div>
            <?php elseif (isset($_GET['erro'])): ?>
                <div class="card" style="background-color: #f8d7da; color: #721c24; padding: 15px; margin-bottom: 20px; text-align:left;">
                    Ocorreu um erro: <?php echo htmlspecialchars(urldecode($_GET['erro'])); ?>
                </div>
            <?php endif; ?>

            <div class="card" style="margin-bottom: 20px; text-align: left;">
                <h3>Cadastrar Novo Cômodo</h3>
                <form action="processa_comodo.php" method="POST" style="margin-top: 1.5rem;">
                    <?php if (count($imoveis_disponiveis) > 0): ?>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="id_imovel">Selecione o Imóvel</label>
                                <select name="id_imovel" id="id_imovel" required>
                                    <option value="">Escolha um imóvel...</option>
                                    <?php foreach ($imoveis_disponiveis as $imovel): ?>
                                        <option value="<?php echo $imovel['id_imovel']; ?>">
                                            <?php echo htmlspecialchars($imovel['fantasia'] ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="ds_comodo">Nome do Cômodo</label>
                                <input type="text" name="ds_comodo" id="ds_comodo" placeholder="Ex: Cozinha" required>
                            </div>
                            <div class="form-actions" style="margin-top: 0; justify-content: flex-end;">
                                <button type="submit" class="btn">Salvar</button>
                            </div>
                        </div>
                    <?php else: ?>
                        <p>Você precisa <a href="view_imoveis.php">cadastrar um imóvel</a> antes de adicionar cômodos.</p>
                    <?php endif; ?>
                </form>
            </div>

            <div class="table-card">
                <h3>Cômodos por Imóvel</h3>
                <?php if ($erro_banco): ?>
                    <p style="color: red;"><?php echo htmlspecialchars($erro_banco); ?></p>
                <?php elseif (empty($comodos_agrupados)): ?>
                    <p>Nenhum cômodo cadastrado até o momento.</p>
                <?php else: ?>
                    <?php foreach ($comodos_agrupados as $endereco => $comodos): ?>
                        <div style="padding: 15px 0;">
                            <h4 style="font-size: 1.1rem; color: #1e3a8a;"><i class="fas fa-home" style="margin-right: 8px;"></i><?php echo htmlspecialchars($endereco); ?></h4>
                            <hr style="margin: 10px 0; border-color: #f0f0f0;">
                            
                            <?php foreach ($comodos as $comodo): ?>
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 5px; border-bottom: 1px solid #f9f9f9;">
                                    <span><i class="fas fa-door-open" style="margin-right: 8px; color: #6b7280;"></i><?php echo htmlspecialchars($comodo['nome']); ?></span>
                                    
                                    <a href="comodo_editar.php?id=<?php echo $comodo['id']; ?>" title="Editar Cômodo" style="color: #2563eb; text-decoration: none;">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>