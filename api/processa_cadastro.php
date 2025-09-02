<?php
// Verifica se o formulário foi enviado usando o método POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. OBTER E LIMPAR OS DADOS VINDOS DO FORMULÁRIO
    $email = trim(htmlspecialchars($_POST['email']));
    $senha = trim(htmlspecialchars($_POST['senha']));
    $cpf = trim(htmlspecialchars($_POST['cpf']));
    $telefone = trim(htmlspecialchars($_POST['telefone']));

    // 2. VALIDAR OS DADOS NO SERVIDOR
    $erros = []; // Array para armazenar mensagens de erro

    // Validação do e-mail
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = "Formato de e-mail inválido.";
    }

    // Validação da senha
    if (strlen($senha) < 8) {
        $erros[] = "A senha deve ter no mínimo 8 caracteres.";
    }

    // Validação de CPF e Telefone (aqui você pode adicionar validações mais robustas se necessário)
    $cpf_limpo = preg_replace('/[^0-9]/', '', $cpf); // Remove pontos e traço
    if (strlen($cpf_limpo) != 11) {
        $erros[] = "CPF deve conter 11 dígitos.";
    }

    // 3. PROCESSAR OS DADOS (SE NÃO HOUVER ERROS)
    if (empty($erros)) {
        // AÇÃO PRINCIPAL: Salvar no banco de dados

        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

        // --- INÍCIO: LÓGICA DE CONEXÃO E INSERÇÃO NO BANCO DE DADOS ---
        $servidor = "localhost";
        $usuario_db = "root";
        $senha_db = "p1Mc3d25*";
        $banco = "mced";

        try {
            $conexao = new PDO("mysql:host=$servidor;dbname=$banco;charset=utf8", $usuario_db, $senha_db);
            $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $sql = "INSERT INTO clientes (email, senha_hash, cpf, telefone) VALUES (:email_cliente, :senha, :cpf_cliente, :tel_cliente)";
            $stmt = $conexao->prepare($sql);

            $stmt->bindParam(':email_cliente', $email);
            $stmt->bindParam(':senha', $senha_hash); 
            $stmt->bindParam(':cpf_cliente', $cpf_limpo);
            $stmt->bindParam(':tel_cliente', $telefone);

            $stmt->execute();

            // AÇÃO CORRETA: Redireciona PRIMEIRO
            header("Location: pagina_de_sucesso.html");
            exit(); // Encerra o script para garantir que o redirecionamento ocorra

        } catch(PDOException $e) {
            // Em um ambiente de produção, não exiba o erro detalhado para o usuário
            // Idealmente, você faria um log do erro aqui.
            die("Erro ao cadastrar. Por favor, tente novamente mais tarde.");
            // echo "Erro ao cadastrar: " . $e->getMessage(); // Apenas para depuração
        }
        
        $conexao = null; // Fecha a conexão
        // --- FIM: LÓGICA DE BANCO DE DADOS ---

        // Mensagem provisória enquanto o banco de dados não está configurado
        echo "<h1>Cadastro Válido!</h1>";
        echo "<p>Os dados recebidos foram:</p>";
        echo "<ul>";
        echo "<li><strong>E-mail:</strong> " . $email . "</li>";
        echo "<li><strong>CPF:</strong> " . $cpf . "</li>";
        echo "<li><strong>Telefone:</strong> " . $telefone . "</li>";
        echo "<li><strong>Senha (hash):</strong> " . $senha_hash . "</li>";
        echo "</ul>";

    } else {
        // Se houver erros, exibe-os
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
    // Se alguém tentar acessar o script diretamente sem enviar o formulário
    echo "<h1>Acesso Inválido</h1>";
    echo "<p>Este script deve ser acessado através do formulário de cadastro.</p>";
    header("Location: index.php"); // Redireciona de volta para o formulário
    exit(); // Encerra o script
}
?>