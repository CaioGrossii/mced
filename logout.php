<?php
// Inicia a sessão para poder manipulá-la
session_start();

// Destrói todas as variáveis da sessão (limpa a "chave de acesso")
session_unset();
session_destroy();

// Redireciona o usuário para a página de login
header("Location: login.php");
exit();
?>