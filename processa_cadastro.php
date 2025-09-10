<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nome = trim(htmlspecialchars($_POST['nome']));
    $email = trim(htmlspecialchars($_POST['email']));
    $senha = trim(htmlspecialchars($_POST['senha']));
    $cpf = trim(htmlspecialchars($_POST['cpf']));
    $telefone = trim(htmlspecialchars($_POST['telefone']));
    $erros = [];

    // (Aqui entram as suas validações de nome, email, senha, etc...)
    if (empty($nome)) $erros[] = "Nome é obrigatório.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $erros[] = "Email inválido.";
    if (strlen($senha) < 8) $erros[] = "Senha deve ter no mínimo 8 caracteres.";

    if (empty($erros)) {
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
        $servidor = "localhost";
        $usuario_db = "root";
        $senha_db = "p1Mc3d25*";
        $banco = "mced";
        $conexao = null;

        try {
            $conexao = new PDO("mysql:host=$servidor;dbname=$banco;charset=utf8", $usuario_db, $senha_db);
            $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $sql = "INSERT INTO clientes (nm_cliente, email_cliente, senha, cpf_cliente, tel_cliente) VALUES (:nome_cliente, :email_cliente, :senha, :cpf_cliente, :tel_cliente)";
            $stmt = $conexao->prepare($sql);

            $cpf_limpo = preg_replace('/[^0-9]/', '', $cpf);
            $stmt->bindParam(':nome_cliente', $nome);
            $stmt->bindParam(':email_cliente', $email);
            $stmt->bindParam(':senha', $senha_hash); 
            $stmt->bindParam(':cpf_cliente', $cpf_limpo);
            $stmt->bindParam(':tel_cliente', $telefone);

            $stmt->execute();
            
            // --- MUDANÇA: Obter o ID do usuário que acabamos de criar ---
            $id_novo_cliente = $conexao->lastInsertId();

            $_SESSION['usuario_logado'] = true;
            $_SESSION['email_usuario'] = $email;
            $_SESSION['nome_usuario'] = $nome;
            
            // --- MUDANÇA: Salvar o novo ID na sessão ---
            $_SESSION['id_cliente'] = $id_novo_cliente;

            header("Location: /mced/dash.php");
            exit();

        } catch(PDOException $e) {
            // (Seu tratamento de erro aqui)
            error_log("Erro de banco de dados no cadastro: " . $e->getMessage());
            die("Ocorreu um erro inesperado. Por favor, tente novamente mais tarde.");
        } finally {
            $conexao = null;
        }
        
    } else {
        // (Sua exibição de erros de validação aqui)
    }
} else {
    header("Location: index.html");
    exit();
}
?>