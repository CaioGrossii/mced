<?php
// 1. Inicia a sessão
session_start();

// 2. O GUARDIÃO: Protege a página
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header("Location: login.php");
    exit();
}

// 3. Conexão com o BD para buscar dados atuais
$servidor = "localhost";
$usuario_db = "root";
$senha_db = "p1Mc3d25*";
$banco = "mced";
$conexao = null;
$usuario = null;

try {
    $conexao = new PDO("mysql:host=$servidor;dbname=$banco;charset=utf8", $usuario_db, $senha_db);
    $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Busca os dados do usuário logado (usando o e-mail da sessão)
    $sql = "SELECT nm_cliente, email_cliente, cpf_cliente, tel_cliente FROM clientes WHERE email_cliente = :email";
    $stmt = $conexao->prepare($sql);
    $stmt->bindParam(':email', $_SESSION['email_usuario']);
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        // Se não encontrar o usuário (raro, mas defensivo)
        header("Location: logout.php");
        exit();
    }

} catch (PDOException $e) {
    die("Erro ao buscar dados do perfil: " . $e->getMessage());
} finally {
    $conexao = null;
}

// 4. Verificar se há mensagens de feedback (sucesso ou erro)
$feedback = null;
if (isset($_SESSION['feedback'])) {
    $feedback = $_SESSION['feedback'];
    unset($_SESSION['feedback']); // Limpa a mensagem após exibi-la
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
            <div class="logo">
                <h2>MCED</h2>
            </div>
            <nav>
                <ul>
                    <li><a href="dash.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>
                    <li><a href="consumo.php"><i class="fa-solid fa-bolt-lightning"></i> Consumo</a></li>
                    <li><a href="view_imoveis.php"><i class="fas fa-building"></i> Imóveis</a></li>
                    <li><a href="comodos.php"><i class="fas fa-door-open"></i> Cômodos</a></li>
                    <li><a href="categorias.php"><i class="fas fa-tags"></i> Categorias</a></li>
                    <li><a href="http://172.20.10.4"><i class="fas fa-plug"></i> Monitoramento</a></li>
                    <li><a href="eletro.php"><i class="fas fa-plug"></i> Eletrodomésticos</a></li>
                    <!-- <li><a href="relatorios.php"><i class="fas fa-chart-bar"></i> Relatórios</a></li> -->
                </ul>
            </nav>
            <div class="logout">
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a>
            </div>
        </aside>

        <main class="main-content">
            <header>
                <h1>Meu Perfil</h1>
                <div class="user-info">
                    <a href="perfil.php" title="Editar Perfil">
                        <img src="img/perfil.png" alt="Avatar">
                    </a>
                </div>
            </header>

            <div class="form-card">
                <h3>Editar Informações Pessoais</h3>
                
                <?php if ($feedback): ?>
                    <div class="feedback <?php echo htmlspecialchars($feedback['tipo']); ?>">
                        <?php echo htmlspecialchars($feedback['msg']); ?>
                    </div>
                <?php endif; ?>

                <form action="processa_perfil.php" method="POST">
                    <div class="form-group">
                        <label for="nome">Nome Completo</label>
                        <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($usuario['nm_cliente']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">E-mail</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($usuario['email_cliente']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="cpf">CPF (não pode ser alterado)</label>
                        <input type="text" id="cpf" name="cpf" value="<?php echo htmlspecialchars($usuario['cpf_cliente']); ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label for="telefone">Telefone</label>
                        <input type="tel" id="telefone" name="telefone" value="<?php echo htmlspecialchars($usuario['tel_cliente']); ?>">
                    </div>

                    <hr style="margin: 25px 0; border: 0; border-top: 1px solid #e5e7eb;">
                    
                    <h3>Alterar Senha (Opcional)</h3>
                    <p style="font-size: 14px; color: #6b7280; margin-bottom: 15px;">Deixe em branco se não quiser alterar a senha.</p>
                    
                    <div class="form-group">
                        <label for="senha_nova">Nova Senha</label>
                        <input type="password" id="senha_nova" name="senha_nova" placeholder="Mínimo de 8 caracteres">
                    </div>
                    <div class="form-group">
                        <label for="senha_confirma">Confirmar Nova Senha</label>
                        <input type="password" id="senha_confirma" name="senha_confirma" placeholder="Repita a nova senha">
                    </div>

                    <button type="submit" class="btn-submit">Salvar Alterações</button>
                </form>
            </div>
        </main>
    </div>
    <script src="script.js" defer></script> 
</body>
</html>