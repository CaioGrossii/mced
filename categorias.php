<?php
$pagina_atual = basename($_SERVER['PHP_SELF']);
session_start();

// Guardião
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header("Location: login.php");
    exit();
}

// Pega o nome da sessão (se foi definido no cadastro) ou usa um padrão
$nome_usuario_logado = $_SESSION['nome_usuario'] ?? 'Usuário Demo';

$idClienteLogado = $_SESSION['id_cliente'];
$categoriasDoCliente = [];
$erro_banco = null;

// Conexão e Busca
$servidor = "localhost";
$usuario_db = "root";
$senha_db = "p1Mc3d25*";
$banco = "mced";

try {
    $conexao = new PDO("mysql:host=$servidor;dbname=$banco;charset=utf8", $usuario_db, $senha_db);
    $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "SELECT id_categoria, ds_categoria FROM categorias WHERE id_cliente = :id_cliente ORDER BY ds_categoria";
    $stmt = $conexao->prepare($sql);
    $stmt->bindParam(':id_cliente', $idClienteLogado);
    $stmt->execute();
    $categoriasDoCliente = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    error_log("Erro em categorias.php: " . $e->getMessage());
    $erro_banco = "Erro ao carregar dados.";
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
                    <li><a href="categorias.php" class="active"><i class="fas fa-tags"></i> Categorias</a></li>
                    <li><a href="eletro.php"><i class="fas fa-plug"></i> Eletrodomésticos</a></li>
                    <li><a href="relatorios.php"><i class="fas fa-chart-bar"></i> Relatórios</a></li>
                </ul>
            </nav>
            <div class="logout"><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a></div>
        </aside>

        <main class="main-content">
            <header>
                <h1>Gerenciar Categorias</h1>
                <div class="user-info">
                    <span><?php echo htmlspecialchars($nome_usuario_logado); ?></span>
                    <img src="img/perfil.png" alt="Avatar">
                </div>
            </header>

            <?php if (isset($_GET['sucesso'])): ?>
                <div class="card" style="background-color: #d1fae5; color: #065f46; padding: 15px; margin-bottom: 20px; text-align:left;">
                    Ação realizada com sucesso!
                </div>
            <?php elseif (isset($_GET['erro'])): ?>
                <div class="card" style="background-color: #fee2e2; color: #991b1b; padding: 15px; margin-bottom: 20px; text-align:left;">
                    <?php echo htmlspecialchars($_GET['erro']); ?>
                </div>
            <?php endif; ?>

            <div class="card" style="margin-bottom: 20px; text-align: left;">
                <h3>Adicionar Nova Categoria</h3>
                <form action="processa_categoria.php" method="POST" style="display: flex; gap: 10px; align-items: center; margin-top: 15px;">
                    <div style="flex-grow: 1;">
                        <label for="ds_categoria" style="display: none;">Nome da Categoria</label>
                        <input type="text" id="ds_categoria" name="ds_categoria" placeholder="Ex: Cozinha, Escritório" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                    </div>
                    <button type="submit" class="btn" style="width: auto; padding: 10px 20px;">Salvar</button>
                </form>
            </div>

            <div class="table-card">
                <h3>Minhas Categorias Cadastradas</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Nome da Categoria</th>
                            <th style="width: 150px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($erro_banco): ?>
                            <tr><td colspan="2" style="color:red;"><?php echo $erro_banco; ?></td></tr>
                        <?php elseif (empty($categoriasDoCliente)): ?>
                            <tr><td colspan="2">Nenhuma categoria cadastrada ainda.</td></tr>
                        <?php else: ?>
                            <?php foreach ($categoriasDoCliente as $categoria): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($categoria['ds_categoria']); ?></td>
                                <td>
                                    <a href="categoria_editar.php?id=<?php echo $categoria['id_categoria']; ?>" title="Editar" style="color: #2563eb;">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    <a href="categoria_excluir.php?id=<?php echo $categoria['id_categoria']; ?>" 
                                       title="Excluir" 
                                       style="margin-left: 15px; color: red;"
                                       onclick="return confirm('Tem certeza que deseja excluir esta categoria? Se houver eletrodomésticos vinculados, a exclusão será bloqueada.');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>