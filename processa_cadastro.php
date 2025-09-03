<?php
// ATIVA O GERENCIAMENTO DE SESSÃO
session_start();

// Verifica se o formulário foi enviado usando o método POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- MUDANÇA 1: Obter o campo NOME ---
    $nome = trim(htmlspecialchars($_POST['nome']));
    $email = trim(htmlspecialchars($_POST['email']));
    $senha = trim(htmlspecialchars($_POST['senha']));
    $cpf = trim(htmlspecialchars($_POST['cpf']));
    $telefone = trim(htmlspecialchars($_POST['telefone']));

    // 2. VALIDAR OS DADOS NO SERVIDOR
    $erros = []; // Array para armazenar mensagens de erro

    // --- MUDANÇA 2: Validar o campo NOME ---
    if (empty($nome) || strlen($nome) < 3) {
        $erros[] = "O nome é obrigatório e deve ter no mínimo 3 caracteres.";
    }

    // Validação do e-mail
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = "Formato de e-mail inválido.";
    }

    // Validação da senha
    if (strlen($senha) < 8) {
        $erros[] = "A senha deve ter no mínimo 8 caracteres.";
    }

    // Validação de CPF
    $cpf_limpo = preg_replace('/[^0-9]/', '', $cpf);
    if (strlen($cpf_limpo) != 11) {
        $erros[] = "CPF deve conter 11 dígitos.";
    }

    // 3. PROCESSAR OS DADOS (SE NÃO HOUVER ERROS)
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

            // --- MUDANÇA 3: Adicionar nm_cliente na query SQL ---
            $sql = "INSERT INTO clientes (nm_cliente, email_cliente, senha, cpf_cliente, tel_cliente) VALUES (:nome_cliente, :email_cliente, :senha, :cpf_cliente, :tel_cliente)";
            $stmt = $conexao->prepare($sql);

            // --- MUDANÇA 4: Fazer o bind do novo parâmetro ---
            $stmt->bindParam(':nome_cliente', $nome);
            $stmt->bindParam(':email_cliente', $email);
            $stmt->bindParam(':senha', $senha_hash); 
            $stmt->bindParam(':cpf_cliente', $cpf_limpo);
            $stmt->bindParam(':tel_cliente', $telefone);

            $stmt->execute();

            // SUCESSO! CRIA A SESSÃO PARA "LOGAR" O USUÁRIO
            $_SESSION['usuario_logado'] = true;
            $_SESSION['email_usuario'] = $email;
            // --- MUDANÇA 5: Guardar também o nome do usuário na sessão ---
            $_SESSION['nome_usuario'] = $nome;

            // REDIRECIONA PARA A DASHBOARD PROTEGIDA (.PHP)
            header("Location: /mced/dash.php");
            exit();

        } catch(PDOException $e) {
            if ($e->getCode() == 23000) {
                echo "<h1>Erro no Cadastro</h1>";
                echo "<p>Este e-mail ou CPF já está cadastrado em nosso sistema.</p>";
                echo '<a href="index.php">Voltar ao formulário</a>';
            } else {
                error_log("Erro de banco de dados no cadastro: " . $e->getMessage());
                die("Ocorreu um erro inesperado. Por favor, tente novamente mais tarde.");
            }
        } finally {
            $conexao = null;
        }
        
    } else {
        // Se houver erros de validação, exibe-os
        echo "<h1>Erro no Cadastro</h1>";
        echo "<p>Por favor, corrija os seguintes erros:</p>";
        echo "<ul>";
        foreach ($erros as $erro) {
            echo "<li>" . $erro . "</li>";
        }
        echo "</ul>";
        echo '<a href="index.php">Voltar ao formulário</a>';
    }

} else {
    // Se alguém tentar acessar o script diretamente, redireciona para o formulário
    header("Location: index.php");
    exit();
}
?>