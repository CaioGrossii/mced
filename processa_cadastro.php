<?php
session_start();

// Configuração para mostrar erros do PHP na tela (REMOVER EM PRODUÇÃO)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nome = trim(htmlspecialchars($_POST['nome']));
    $email = trim(htmlspecialchars($_POST['email']));
    $senha = trim(htmlspecialchars($_POST['senha']));
    $cpf = trim(htmlspecialchars($_POST['cpf']));
    $telefone = trim(htmlspecialchars($_POST['telefone']));
    $erros = [];

    if (empty($nome)) $erros[] = "Nome é obrigatório.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $erros[] = "Email inválido.";
    if (strlen($senha) < 8) $erros[] = "Senha deve ter no mínimo 8 caracteres.";

    if (empty($erros)) {
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
        
        // Credenciais do Banco
        $servidor = "localhost";
        $usuario_db = "root";
        $senha_db = "p1Mc3d25*";
        $banco = "mced";
        
        $conexao = null;

        try {
            $conexao = new PDO("mysql:host=$servidor;dbname=$banco;charset=utf8", $usuario_db, $senha_db);
            $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Limpa o CPF para salvar apenas números
            $cpf_limpo = preg_replace('/[^0-9]/', '', $cpf);

            // Query de Inserção
            $sql = "INSERT INTO clientes (nm_cliente, email_cliente, senha, cpf_cliente, tel_cliente) 
                    VALUES (:nome_cliente, :email_cliente, :senha, :cpf_cliente, :tel_cliente)";
            
            $stmt = $conexao->prepare($sql);

            $stmt->bindParam(':nome_cliente', $nome);
            $stmt->bindParam(':email_cliente', $email);
            $stmt->bindParam(':senha', $senha_hash); 
            $stmt->bindParam(':cpf_cliente', $cpf_limpo);
            $stmt->bindParam(':tel_cliente', $telefone);

            $stmt->execute();
            
            // Sucesso: Pegar ID e criar Sessão
            $id_novo_cliente = $conexao->lastInsertId();

            $_SESSION['usuario_logado'] = true;
            $_SESSION['email_usuario'] = $email;
            $_SESSION['nome_usuario'] = $nome;
            $_SESSION['id_cliente'] = $id_novo_cliente;

            // Redireciona para a Dashboard
            header("Location: dash.php"); // Removi o /mced/ para evitar erro de caminho se a pasta mudar
            exit();

        } catch(PDOException $e) {
            // --- TRATAMENTO DE ERROS ---
            
            // Código 23000 geralmente indica duplicidade (Unique Constraint)
            if ($e->getCode() == '23000') {
                echo "<div style='color: white; background: #dc3545; padding: 20px; text-align: center; font-family: Arial;'>";
                echo "<h2>Erro: Cadastro Duplicado</h2>";
                echo "<p>O E-mail <strong>$email</strong> ou o CPF informado já estão cadastrados no sistema.</p>";
                echo "<a href='cadastro.html' style='color: white; font-weight: bold;'>Voltar e tentar outro</a>";
                echo "</div>";
                exit();
            } else {
                // Outros erros (Ex: Senha do banco errada, nome da tabela errado)
                echo "<div style='color: white; background: #ff9800; padding: 20px; text-align: center; font-family: Arial;'>";
                echo "<h2>Erro Técnico (Debug)</h2>";
                echo "<p>Ocorreu um erro no banco de dados:</p>";
                echo "<pre>" . $e->getMessage() . "</pre>";
                echo "<p>Verifique se o banco 'mced' existe e se a tabela 'clientes' tem as colunas corretas.</p>";
                echo "</div>";
                die();
            }
        } finally {
            $conexao = null;
        }
        
    } else {
        // Exibir erros de validação do PHP
        foreach ($erros as $erro) {
            echo "<p>$erro</p>";
        }
    }
} else {
    header("Location: index.html");
    exit();
}
?>