<?php
// Inicia a sessão para poder verificar se o usuário já está logado
session_start();

// Se o usuário já estiver logado, redireciona para a dashboard
if (isset($_SESSION['usuario_logado']) && $_SESSION['usuario_logado'] === true) {
    header("Location: /mced/dash.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Login - MCED</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-container { background-color: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); width: 300px; text-align: center; }
        h2 { margin-bottom: 20px; }
        input[type="email"], input[type="password"] { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background-color: #0056b3; }
        .error { color: #d93025; margin-bottom: 15px; }
        .signup-link { margin-top: 15px; font-size: 14px; }
        .signup-link a { color: #007bff; text-decoration: none; }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Login</h2>
        
        <?php
        // Verifica se existe um parâmetro 'erro' na URL e exibe a mensagem
        if (isset($_GET['erro']) && $_GET['erro'] == 1) {
            echo '<p class="error">E-mail ou senha inválidos. Tente novamente.</p>';
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