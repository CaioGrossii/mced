<?php
// login.php
// Objetivo: Autenticação de usuários no sistema MCED
// Segurança: Verifica sessão ativa antes de renderizar o formulário

session_start();

// Se o usuário já estiver logado, redireciona imediatamente para o dashboard
if (isset($_SESSION['usuario_logado']) && $_SESSION['usuario_logado'] === true) {
    header("Location: dash.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <?php include 'includes/head.php'; ?>
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body class="auth-body"> <div class="login-container">
        <h1>MCED <span>Login</span></h1>
        
        <?php if (isset($_GET['erro'])): ?>
            <div style="color: var(--color-danger); margin-bottom: 15px; font-weight: 600; background: rgba(255, 42, 109, 0.1); padding: 10px; border-radius: 8px;">
                <i class="fas fa-exclamation-circle"></i> E-mail ou senha incorretos.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['sucesso'])): ?>
            <div style="color: var(--color-success); margin-bottom: 15px; font-weight: 600; background: rgba(5, 242, 155, 0.1); padding: 10px; border-radius: 8px;">
                <i class="fas fa-check-circle"></i> Conta criada! Faça login.
            </div>
        <?php endif; ?>

        <form action="processa_login.php" method="POST">
            <div style="margin-bottom: 15px;">
                <input type="email" 
                       name="email" 
                       placeholder="Seu e-mail" 
                       required 
                       autocomplete="email" 
                       aria-label="E-mail">
            </div>
            
            <div style="margin-bottom: 20px;">
                <input type="password" 
                       name="senha" 
                       placeholder="Sua senha" 
                       required 
                       autocomplete="current-password" 
                       aria-label="Senha">
            </div>

            <button type="submit" class="btn" style="width: 100%;">Entrar</button>
        </form>

        <div class="extra-links">
            <p>Não tem uma conta? <a href="cadastro.html">Cadastre-se</a></p>
        </div>
    </div>

</body>
</html>