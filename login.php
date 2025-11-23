<?php
// Inicia a sessão para poder verificar se o usuário já está logado
session_start();

// Se o usuário já estiver logado, redireciona para a dashboard
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
<body class="auth-body">
</body>
</html>