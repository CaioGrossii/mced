<?php
session_start();

if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Coleta e limpeza dos dados
    $fantasia = trim(htmlspecialchars($_POST['fantasia'])); // NOVO CAMPO
    $rua = trim(htmlspecialchars($_POST['rua']));
    $numero = trim(htmlspecialchars($_POST['numero']));
    $bairro = trim(htmlspecialchars($_POST['bairro']));
    $cidade = trim(htmlspecialchars($_POST['cidade']));
    $estado = trim(htmlspecialchars(strtoupper($_POST['estado'])));
    $cep = trim(htmlspecialchars($_POST['cep']));
    $id_cliente = $_SESSION['id_cliente'];

    // Validação dos dados no servidor
    $erros = [];
    if (empty($fantasia)) $erros[] = "O Nome do Imóvel é obrigatório."; // VALIDAÇÃO DO NOVO CAMPO
    if (empty($rua)) $erros[] = "O campo Rua é obrigatório.";
    if (empty($numero)) $erros[] = "O campo Número é obrigatório.";
    if (empty($bairro)) $erros[] = "O campo Bairro é obrigatório.";
    if (empty($cidade)) $erros[] = "O campo Cidade é obrigatório.";
    if (strlen($estado) != 2) $erros[] = "O Estado (UF) deve ter exatamente 2 caracteres.";
    if (!preg_match('/^\d{5}-\d{3}$/', $cep)) $erros[] = "O formato do CEP é inválido. Use XXXXX-XXX.";
    if (empty($id_cliente)) $erros[] = "Erro de autenticação. Faça login novamente.";

    if (empty($erros)) {
        $servidor = "localhost";
        $usuario_db = "root";
        $senha_db = "p1Mc3d25*";
        $banco = "mced";
        $conexao = null;

        try {
            $conexao = new PDO("mysql:host=$servidor;dbname=$banco;charset=utf8", $usuario_db, $senha_db);
            $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Query SQL ATUALIZADA com o campo 'fantasia'
            $sql = "INSERT INTO imoveis (fantasia, rua, numero, bairro, cidade, estado, cep, id_cliente) 
                    VALUES (:fantasia, :rua, :numero, :bairro, :cidade, :estado, :cep, :id_cliente)";
            
            $stmt = $conexao->prepare($sql);
            
            // Vinculando os parâmetros (bindParam)
            $stmt->bindParam(':fantasia', $fantasia); // BIND DO NOVO CAMPO
            $stmt->bindParam(':rua', $rua);
            $stmt->bindParam(':numero', $numero);
            $stmt->bindParam(':bairro', $bairro);
            $stmt->bindParam(':cidade', $cidade);
            $stmt->bindParam(':estado', $estado);
            $stmt->bindParam(':cep', $cep);
            $stmt->bindParam(':id_cliente', $id_cliente);

            $stmt->execute();
            
            // Redireciona para a listagem (view_imoveis.php)
            header("Location: view_imoveis.php?msg=imovel_sucesso");
            exit();

        } catch(PDOException $e) {
            error_log("Erro no cadastro de imóvel: " . $e->getMessage());
            die("Ocorreu um erro inesperado ao salvar os dados. Tente novamente mais tarde.");
        } finally {
            $conexao = null;
        }
        
    } else {
        // Tratamento de erros de validação
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
    header("Location: imoveis.php");
    exit();
}
?>