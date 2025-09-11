<?php
session_start();

// 1. GUARDIÃO: Protege a página contra acesso de usuários não logados.
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true || !isset($_SESSION['id_cliente'])) {
    header("Location: login.php");
    exit();
}

// 2. LÓGICA PARA BUSCAR OS IMÓVEIS NO BANCO DE DADOS
$imoveis = [];
$erro_banco = null;

try {
    $servidor = "localhost";
    $usuario_db = "root";
    $senha_db = "p1Mc3d25*";
    $banco = "mced";
    
    $conexao = new PDO("mysql:host=$servidor;dbname=$banco;charset=utf8", $usuario_db, $senha_db);
    $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "SELECT rua, numero, bairro, cidade, estado, cep FROM imoveis WHERE id_cliente = :id_cliente ORDER BY id_imovel DESC";
    $stmt = $conexao->prepare($sql);
    
    $stmt->bindParam(':id_cliente', $_SESSION['id_cliente']);
    $stmt->execute();
    
    $imoveis = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Erro ao buscar imóveis: " . $e->getMessage());
    $erro_banco = "Não foi possível carregar os imóveis. Tente novamente mais tarde.";
} finally {
    $conexao = null;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>MCED - Meus Imóveis</title>
    <link rel="stylesheet" href="dash.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        .btn-header {
            background-color: #2563eb;
            color: #fff;
            padding: 10px 15px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background-color 0.3s;
        }
        .btn-header:hover {
            background-color: #1e40af;
        }
    </style>
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
                    <li><a href="comodos.php"><i class="fas fa-door-open"></i> Cômodos</a></li>
                    <li><a href="categorias.php"><i class="fas fa-tags"></i> Categorias</a></li>
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
                <h1>Meus Imóveis Cadastrados</h1>
                <a href="imoveis.php" class="btn-header">
                    <i class="fas fa-plus"></i>
                    Novo Imóvel
                </a>
            </header>
            <div class="table-card">
                <h3>Lista de Imóveis</h3>
                
                <?php if ($erro_banco): ?>
                    <p style="color: red;"><?php echo htmlspecialchars($erro_banco); ?></p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Rua / Logradouro</th>
                                <th>Número</th>
                                <th>Bairro</th>
                                <th>Cidade</th>
                                <th>Estado</th>
                                <th>CEP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($imoveis) > 0): ?>
                                <?php foreach ($imoveis as $imovel): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($imovel['rua']); ?></td>
                                        <td><?php echo htmlspecialchars($imovel['numero']); ?></td>
                                        <td><?php echo htmlspecialchars($imovel['bairro']); ?></td>
                                        <td><?php echo htmlspecialchars($imovel['cidade']); ?></td>
                                        <td><?php echo htmlspecialchars($imovel['estado']); ?></td>
                                        <td><?php echo htmlspecialchars($imovel['cep']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center;">Nenhum imóvel cadastrado ainda.</td>
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