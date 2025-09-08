<?php
// Inicia a sessão para poder verificar se o usuário já está logado
session_start();

// Se o usuário já estiver logado, redireciona para a dashboard
// Uma boa prática é usar caminhos absolutos para evitar problemas de diretório.
if (isset($_SESSION['usuario_logado']) && $_SESSION['usuario_logado'] === true) {
    // Ajuste o caminho conforme a estrutura do seu projeto.
    // Exemplo: '/dash.php' se estiver na raiz, ou '/mced/dash.php'
    header("Location: dash.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MCED</title>
    
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <div class="login-container">
        <h2>Login</h2>
        
        <?php
        // Verifica se existe um parâmetro 'erro' na URL e exibe a mensagem de forma segura
        if (isset($_GET['erro'])) {
            // Usar htmlspecialchars para prevenir XSS, uma boa prática de segurança.
            $mensagemErro = htmlspecialchars("E-mail ou senha inválidos. Tente novamente.", ENT_QUOTES, 'UTF-8');
            echo '<p class="error">' . $mensagemErro . '</p>';
        }
        ?>

        <form action="processa_login.php" method="POST">
            <input type="email" name="email" placeholder="Seu e-mail" required>
            <input type="password" name="senha" placeholder="Sua senha" required>
            <button type="submit">Entrar</button>
        </form>
    </div>
</body>
</html>