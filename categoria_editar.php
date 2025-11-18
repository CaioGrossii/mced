<?php
session_start();

// 1. Segurança
if (!isset($_SESSION['usuario_logado']) || !isset($_SESSION['id_cliente'])) {
    header("Location: login.php");
    exit();
}

// 2. Validação do ID via GET
$id_categoria = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_categoria) {
    header("Location: categorias.php?erro=" . urlencode("Categoria inválida."));
    exit();
}

$id_cliente_logado = $_SESSION['id_cliente'];
$categoria = null;

try {
    $servidor = "localhost";
    $usuario_db = "root";
    $senha_db = "p1Mc3d25*";
    $banco = "mced";
    
    $conexao = new PDO("mysql:host=$servidor;dbname=$banco;charset=utf8", $usuario_db, $senha_db);
    $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 3. Busca Segura: Só busca se pertencer ao cliente logado (Prevenção IDOR)
    $sql = "SELECT id_categoria, ds_categoria FROM categorias WHERE id_categoria = :id AND id_cliente = :id_cliente";
    $stmt = $conexao->prepare($sql);
    $stmt->bindParam(':id', $id_categoria);
    $stmt->bindParam(':id_cliente', $id_cliente_logado);
    $stmt->execute();
    
    $categoria = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$categoria) {
        header("Location: categorias.php?erro=" . urlencode("Categoria não encontrada ou acesso negado."));
        exit();
    }

} catch (PDOException $e) {
    error_log("Erro em categoria_editar.php: " . $e->getMessage());
    header("Location: categorias.php?erro=" . urlencode("Erro no servidor."));
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>MCED - Editar Categoria</title>
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
                    <li><a href="view_imoveis.php"><i class="fas fa-building"></i> Imóveis</a></li>
                    <li><a href="comodos.php"><i class="fas fa-door-open"></i> Cômodos</a></li>
                    <li><a href="categorias.php" class="active"><i class="fas fa-tags"></i> Categorias</a></li>
                    <li><a href="eletro.php"><i class="fas fa-plug"></i> Eletrodomésticos</a></li>
                </ul>
            </nav>
            <div class="logout"><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a></div>
        </aside>

        <main class="main-content">
            <header>
                <h1>Editar Categoria</h1>
            </header>

            <div class="card" style="text-align: left; max-width: 600px;">
                <form action="processa_categoria_update.php" method="POST">
                    <input type="hidden" name="id_categoria" value="<?php echo $categoria['id_categoria']; ?>">
                    
                    <div style="margin-bottom: 20px;">
                        <label for="ds_categoria" style="display:block; margin-bottom:5px; font-weight:600;">Nome da Categoria</label>
                        <input type="text" name="ds_categoria" id="ds_categoria" 
                               value="<?php echo htmlspecialchars($categoria['ds_categoria']); ?>" 
                               required 
                               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                    </div>

                    <div style="display: flex; gap: 15px;">
                        <button type="submit" class="btn">Salvar Alterações</button>
                        <a href="categorias.php" style="display:inline-flex; align-items:center; color: #666; text-decoration: none;">Cancelar</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>