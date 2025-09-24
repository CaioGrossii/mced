<?php
session_start();

// Guardião: Verifica se o usuário está logado
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header("Location: login.php");
    exit();
}

// ID do cliente logado, pego da sessão
$idClienteLogado = $_SESSION['id_cliente'];

// --- Bloco de Conexão com o Banco de Dados ---
// Para um código mais limpo, considere mover isso para um arquivo separado (ex: 'conexao.php') e usar include.
$servidor = "localhost";
$usuario_db = "root";
$senha_db = "p1Mc3d25*";
$banco = "mced";
$conexao = null;

try {
    $conexao = new PDO("mysql:host=$servidor;dbname=$banco;charset=utf8", $usuario_db, $senha_db);
    $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Lógica para buscar as categorias APENAS do cliente logado
    $sql = "SELECT id_categoria, ds_categoria FROM categorias WHERE id_cliente = :id_cliente ORDER BY ds_categoria";
    $stmt = $conexao->prepare($sql);
    $stmt->bindParam(':id_cliente', $idClienteLogado);
    $stmt->execute();
    $categoriasDoCliente = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    error_log("Erro de banco de dados em categorias.php: " . $e->getMessage());
    die("Ocorreu um erro ao carregar os dados. Tente novamente mais tarde.");
} finally {
    $conexao = null;
}
// --- Fim do Bloco de Conexão ---

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Categorias - MCED</title>
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
                    <li><a href="categorias.php" class="active"><i class="fas fa-tags"></i> Categorias</a></li>
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
                <h1>Gerenciar Categorias</h1>
                <div class="user-info">
                    <img src="img/antero.jpg" alt="Avatar"> </div>
            </header>

            <div class="card" style="margin-bottom: 20px;">
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
                        <?php if (empty($categoriasDoCliente)): ?>
                            <tr>
                                <td colspan="2">Nenhuma categoria cadastrada ainda.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($categoriasDoCliente as $categoria): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($categoria['ds_categoria']); ?></td>
                                <td>
                                    <a href="#" title="Editar"><i class="fas fa-edit"></i></a>
                                    <a href="#" title="Excluir" style="margin-left: 15px; color: red;"><i class="fas fa-trash"></i></a>
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