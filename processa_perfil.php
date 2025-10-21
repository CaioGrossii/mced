<?php
// 1. Inicia a sessão
session_start();

// 2. O GUARDIÃO: Apenas usuários logados podem processar
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header("Location: login.php");
    exit();
}

// 3. Segurança: Apenas processar se o método for POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 4. Obter dados do formulário e sanitizar
    $nome = trim(htmlspecialchars($_POST['nome']));
    $email_novo = trim(htmlspecialchars($_POST['email'])); // MUDANÇA: Ler o novo e-mail
    $telefone = trim(htmlspecialchars($_POST['telefone']));
    $senha_nova = $_POST['senha_nova'];
    $senha_confirma = $_POST['senha_confirma'];

    // 5. Segurança: Identificar o usuário pelo E-MAIL ANTIGO da SESSÃO
    $email_antigo = $_SESSION['email_usuario'];

    // 6. Validação
    $erros = [];
    if (empty($nome) || strlen($nome) < 3) {
        $erros[] = "O nome deve ter no mínimo 3 caracteres.";
    }

    // MUDANÇA: Validar o novo e-mail
    if (!filter_var($email_novo, FILTER_VALIDATE_EMAIL)) {
        $erros[] = "Formato de e-mail inválido.";
    }

    // Validação de CPF removida, pois é readonly

    // Validação da senha (somente se o usuário tentou alterar)
    $atualizar_senha = false;
    if (!empty($senha_nova)) {
        if (strlen($senha_nova) < 8) {
            $erros[] = "A nova senha deve ter no mínimo 8 caracteres.";
        }
        if ($senha_nova !== $senha_confirma) {
            $erros[] = "As senhas não coincidem.";
        }
        $atualizar_senha = true;
    }

    // 7. Processar se não houver erros
    if (empty($erros)) {
        $servidor = "localhost";
        $usuario_db = "root";
        $senha_db = "p1Mc3d25*";
        $banco = "mced";
        $conexao = null;

        try {
            $conexao = new PDO("mysql:host=$servidor;dbname=$banco;charset=utf8", $usuario_db, $senha_db);
            $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // 8. Montar a Query SQL Dinamicamente
            $params = [
                ':nome' => $nome,
                ':email_novo' => $email_novo, // MUDANÇA: Parâmetro para o novo e-mail
                ':tel' => $telefone,
                ':email_antigo' => $email_antigo // MUDANÇA: Parâmetro para o WHERE
            ];

            // MUDANÇA: SQL atualiza email_cliente, não cpf_cliente
            $sql = "UPDATE clientes SET nm_cliente = :nome, email_cliente = :email_novo, tel_cliente = :tel";

            // Adiciona a atualização de senha APENAS se ela foi fornecida e validada
            if ($atualizar_senha) {
                $senha_hash = password_hash($senha_nova, PASSWORD_DEFAULT);
                $sql .= ", senha = :senha";
                $params[':senha'] = $senha_hash;
            }

            // MUDANÇA: WHERE usa o :email_antigo
            $sql .= " WHERE email_cliente = :email_antigo";

            // 9. Preparar e Executar
            $stmt = $conexao->prepare($sql);
            $stmt->execute($params);

            // 10. Atualizar a sessão com os novos dados
            $_SESSION['nome_usuario'] = $nome;
            $_SESSION['email_usuario'] = $email_novo; // MUDANÇA: Atualiza o e-mail na sessão

            // 11. Feedback de Sucesso
            $_SESSION['feedback'] = ['tipo' => 'sucesso', 'msg' => 'Perfil atualizado com sucesso!'];
            header("Location: perfil.php");
            exit();

        } catch (PDOException $e) {
            // MUDANÇA: Tratar erro de E-mail duplicado (alinhado ao processa_cadastro.php)
            if ($e->getCode() == 23000) {
                 $_SESSION['feedback'] = ['tipo' => 'erro', 'msg' => 'Erro: Este E-mail já está em uso por outra conta.'];
            } else {
                 $_SESSION['feedback'] = ['tipo' => 'erro', 'msg' => 'Erro inesperado no banco de dados. Tente novamente.'];
                 error_log("Erro ao atualizar perfil: " . $e->getMessage());
            }
            header("Location: perfil.php");
            exit();
        } finally {
            $conexao = null;
        }

    } else {
        // Se houver erros de validação, envia de volta com os erros
        $_SESSION['feedback'] = ['tipo' => 'erro', 'msg' => implode(' ', $erros)];
        header("Location: perfil.php");
        exit();
    }

} else {
    // Redireciona se não for POST
    header("Location: dash.php");
    exit();
}
?>