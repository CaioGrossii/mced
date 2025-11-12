<?php
session_start();

if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true || !isset($_SESSION['id_cliente'])) {
    header("Location: login.php");
    exit();
}

$imoveis = [];
$erro_banco = null;
$feedback_sucesso = null;

// --- Feedback de Sucesso/Erro ---
if (isset($_GET['sucesso'])) {
    if ($_GET['sucesso'] == 'update') $feedback_sucesso = "Imóvel atualizado com sucesso!";
    if ($_GET['sucesso'] == 'delete') $feedback_sucesso = "Imóvel excluído com sucesso!";
}
if (isset($_GET['erro'])) {
    if ($_GET['erro'] == 'dependencia') $feedback_erro = "Erro: Não é possível excluir um imóvel que possui cômodos cadastrados.";
    if ($_GET['erro'] == 'permissao') $feedback_erro = "Erro: Você não tem permissão para realizar esta ação.";
}
// --- Fim do Feedback ---

try {
    $servidor = "localhost";
    $usuario_db = "root";
    $senha_db = "p1Mc3d25*";
    $banco = "mced";
    
    $conexao = new PDO("mysql:host=$servidor;dbname=$banco;charset=utf8", $usuario_db, $senha_db);
    $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Seleciona o id_imovel (para os links) e o novo campo fantasia
    $sql = "SELECT id_imovel, fantasia, rua, numero, bairro, cidade, estado, cep 
            FROM imoveis 
            WHERE id_cliente = :id_cliente 
            ORDER BY fantasia ASC";
            
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
            background-color: #2563eb; color: #fff; padding: 10px 15px;
            border-radius: 8px; text-decoration: none; font-size: 14px;
            font-weight: 600; display: inline-flex; align-items: center;
            gap: 8px; transition: background-color 0.3s;
        }
        .btn-header:hover { background-color: #1e40af; }
        
        /* Estilos para botões de ação na tabela */
        .action-link {
            text-decoration: none;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            margin-right: 5px;
        }
        .btn-edit {
            background-color: #f0f0f0; color: #333;
        }
        .btn-delete {
            background-color: #fee2e2; color: #991b1b;
        }
        .feedback-sucesso {
            background-color: #d4edda; color: #155724; padding: 15px; margin-bottom: 20px; border-radius: 8px;
        }
        .feedback-erro {
            background-color: #f8d7da; color: #721c24; padding: 15px; margin-bottom: 20px; border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="logo"><h2>MCED</h2></div>
            <nav>
                 <ul>
                    <li><a href="dash.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>
                    <li><a href="#"><i class="fa-solid fa-bolt-lightning"></i> Consumo</a></li>
                    <li><a href="view_imoveis.php" class="active"><i class="fas fa-building"></i> Imóveis</a></li>
                    <li><a href="comodos.php"><i class="fas fa-door-open"></i> Cômodos</a></li>
                    <li><a href="categorias.php"><i class="fas fa-tags"></i> Categorias</a></li>
                    <li><a href="eletro.php"><i class="fas fa-plug"></i> Eletrodomésticos</a></li>
                    <li><a href="relatorios.php"><i class="fas fa-chart-bar"></i> Relatórios</a></li>
                </ul>
            </nav>
            <div class="logout"><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a></div>
        </aside>

        <main class="main-content">
            <header>
                <h1>Meus Imóveis Cadastrados</h1>
                <a href="imoveis.php" class="btn-header">
                    <i class="fas fa-plus"></i>
                    Novo Imóvel
                </a>
            </header>
            
            <?php if ($feedback_sucesso): ?>
                <div class="feedback-sucesso"><?php echo htmlspecialchars($feedback_sucesso); ?></div>
            <?php endif; ?>
            <?php if (isset($feedback_erro)): ?>
                <div class="feedback-erro"><?php echo htmlspecialchars($feedback_erro); ?></div>
            <?php endif; ?>

            <div class="table-card">
                <h3>Lista de Imóveis</h3>
                
                <?php if ($erro_banco): ?>
                    <p style="color: red;"><?php echo htmlspecialchars($erro_banco); ?></p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Nome do Imóvel</th>
                                <th>Rua / Logradouro</th>
                                <th>Número</th>
                                <th>Bairro</th>
                                <th>Cidade</th>
                                <th>UF</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($imoveis) > 0): ?>
                                <?php foreach ($imoveis as $imovel): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($imovel['fantasia']); ?></td>
                                        <td><?php echo htmlspecialchars($imovel['rua']); ?></td>
                                        <td><?php echo htmlspecialchars($imovel['numero']); ?></td>
                                        <td><?php echo htmlspecialchars($imovel['bairro']); ?></td>
                                        <td><?php echo htmlspecialchars($imovel['cidade']); ?></td>
                                        <td><?php echo htmlspecialchars($imovel['estado']); ?></td>
                                        <td>
                                            <a href="imovel_editar.php?id=<?php echo $imovel['id_imovel']; ?>" class="action-link btn-edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="processa_imovel_delete.php?id=<?php echo $imovel['id_imovel']; ?>" class="action-link btn-delete" onclick="return confirm('Atenção: Esta ação não pode ser desfeita. Deseja realmente excluir este imóvel?');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center;">Nenhum imóvel cadastrado ainda.</td>
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