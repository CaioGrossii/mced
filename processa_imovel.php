<?php
session_start();

// 1. VERIFICAÇÃO DE SESSÃO
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header("Location: login.php");
    exit();
}

// 2. VERIFICAÇÃO DO MÉTODO DA REQUISIÇÃO
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 3. COLETA E LIMPEZA DOS DADOS DO FORMULÁRIO
    $rua = trim(htmlspecialchars($_POST['rua']));
    $numero = trim(htmlspecialchars($_POST['numero']));
    $bairro = trim(htmlspecialchars($_POST['bairro']));
    $cidade = trim(htmlspecialchars($_POST['cidade']));
    $estado = trim(htmlspecialchars(strtoupper($_POST['estado']))); // Garante UF em maiúsculo
    $cep = trim(htmlspecialchars($_POST['cep']));
    
    // Assumindo que o ID do cliente está na sessão. É crucial que isso seja definido no login.
    $id_cliente = $_SESSION['id_cliente']; // ATENÇÃO: Verifique se 'id_cliente' é o nome correto na sua sessão!

    // 4. VALIDAÇÃO DOS DADOS NO SERVIDOR
    $erros = [];
    if (empty($rua)) $erros[] = "O campo Rua é obrigatório.";
    if (empty($numero)) $erros[] = "O campo Número é obrigatório.";
    if (empty($bairro)) $erros[] = "O campo Bairro é obrigatório.";
    if (empty($cidade)) $erros[] = "O campo Cidade é obrigatório.";
    if (strlen($estado) != 2) $erros[] = "O Estado (UF) deve ter exatamente 2 caracteres.";
    if (!preg_match('/^\d{5}-\d{3}$/', $cep)) $erros[] = "O formato do CEP é inválido. Use XXXXX-XXX.";
    if (empty($id_cliente)) $erros[] = "Erro de autenticação. Faça login novamente."; // Validação de segurança

    // 5. PROCESSAMENTO (SE NÃO HOUVER ERROS)
    if (empty($erros)) {
        // Credenciais do banco de dados (idealmente, estariam fora do docroot em um arquivo .env)
        $servidor = "localhost";
        $usuario_db = "root";
        $senha_db = "p1Mc3d25*";
        $banco = "mced";
        $conexao = null;

        try {
            $conexao = new PDO("mysql:host=$servidor;dbname=$banco;charset=utf8", $usuario_db, $senha_db);
            $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Query SQL parametrizada para evitar SQL Injection
            $sql = "INSERT INTO imoveis (rua, numero, bairro, cidade, estado, cep, id_cliente) 
                    VALUES (:rua, :numero, :bairro, :cidade, :estado, :cep, :id_cliente)";
            
            $stmt = $conexao->prepare($sql);
            
            // Vinculando os parâmetros
            $stmt->bindParam(':rua', $rua);
            $stmt->bindParam(':numero', $numero);
            $stmt->bindParam(':bairro', $bairro);
            $stmt->bindParam(':cidade', $cidade);
            $stmt->bindParam(':estado', $estado);
            $stmt->bindParam(':cep', $cep);
            $stmt->bindParam(':id_cliente', $id_cliente);

            $stmt->execute();
            
            // Redireciona para a dashboard com mensagem de sucesso
            header("Location: /mced/dash.php?msg=imovel_sucesso");
            exit();

        } catch(PDOException $e) {
            // Log do erro para depuração e mensagem genérica para o usuário
            error_log("Erro no cadastro de imóvel: " . $e->getMessage());
            die("Ocorreu um erro inesperado ao salvar os dados. Tente novamente mais tarde.");
        } finally {
            $conexao = null; // Garante que a conexão seja sempre fechada
        }
        
    } else {
        // Se houver erros de validação, exibe-os de forma clara
        echo "<h1>Erro no Cadastro do Imóvel</h1>";
        echo "<p>Por favor, corrija os seguintes erros:</p>";
        echo "<ul>";
        foreach ($erros as $erro) {
            echo "<li>" . htmlspecialchars($erro) . "</li>";
        }
        echo "</ul>";
        echo '<a href="imoveis.php">Voltar ao formulário</a>';
    }

} else {
    // Se o script for acessado diretamente (via GET), redireciona para o formulário
    header("Location: imoveis.php");
    exit();
}
?>