<?php
session_start();

// 1. Verificações de segurança
if (!isset($_SESSION['usuario_logado']) || !isset($_SESSION['id_cliente'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: view_imoveis.php");
    exit();
}

// 2. Coleta e limpeza dos dados
$id_imovel = filter_input(INPUT_POST, 'id_imovel', FILTER_VALIDATE_INT);
$id_cliente = $_SESSION['id_cliente'];

$fantasia = trim(htmlspecialchars($_POST['fantasia']));
$rua = trim(htmlspecialchars($_POST['rua']));
$numero = trim(htmlspecialchars($_POST['numero']));
$bairro = trim(htmlspecialchars($_POST['bairro']));
$cidade = trim(htmlspecialchars($_POST['cidade']));
$estado = trim(htmlspecialchars(strtoupper($_POST['estado'])));
$cep = trim(htmlspecialchars($_POST['cep']));

// 3. Validação dos dados (similar ao cadastro)
$erros = [];
if (empty($id_imovel)) $erros[] = "ID do imóvel inválido.";
if (empty($fantasia)) $erros[] = "O Nome do Imóvel é obrigatório.";
if (empty($rua)) $erros[] = "O campo Rua é obrigatório.";
// ... (adicione as outras validações de CEP, Estado, etc., do processa_imovel.php)

if (empty($erros)) {
    $servidor = "localhost";
    $usuario_db = "root";
    $senha_db = "p1Mc3d25*";
    $banco = "mced";
    $conexao = null;

    try {
        $conexao = new PDO("mysql:host=$servidor;dbname=$banco;charset=utf8", $usuario_db, $senha_db);
        $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 4. Query de UPDATE segura
        // A cláusula "WHERE id_cliente = :id_cliente" é a camada de segurança
        // que impede um usuário de atualizar o imóvel de outro.
        $sql = "UPDATE imoveis SET
                    fantasia = :fantasia,
                    rua = :rua,
                    numero = :numero,
                    bairro = :bairro,
                    cidade = :cidade,
                    estado = :estado,
                    cep = :cep
                WHERE
                    id_imovel = :id_imovel AND id_cliente = :id_cliente";
        
        $stmt = $conexao->prepare($sql);
        
        $stmt->bindParam(':fantasia', $fantasia);
        $stmt->bindParam(':rua', $rua);
        $stmt->bindParam(':numero', $numero);
        $stmt->bindParam(':bairro', $bairro);
        $stmt->bindParam(':cidade', $cidade);
        $stmt->bindParam(':estado', $estado);
        $stmt->bindParam(':cep', $cep);
        $stmt->bindParam(':id_imovel', $id_imovel);
        $stmt->bindParam(':id_cliente', $id_cliente);
        
        $stmt->execute();

        // 5. Redireciona para a listagem com mensagem de sucesso
        header("Location: view_imoveis.php?sucesso=update");
        exit();

    } catch(PDOException $e) {
        error_log("Erro ao atualizar imóvel: " . $e->getMessage());
        die("Ocorreu um erro inesperado ao salvar os dados. Tente novamente mais tarde.");
    } finally {
        $conexao = null;
    }
    
} else {
    // Tratamento de erros de validação
    echo "<h1>Erro na Edição do Imóvel</h1>";
    echo "<p>Por favor, corrija os seguintes erros:</p>";
    echo "<ul>";
    foreach ($erros as $erro) {
        echo "<li>" . htmlspecialchars($erro) . "</li>";
    }
    echo "</ul>";
    echo '<a href="imovel_editar.php?id=' . $id_imovel . '">Voltar ao formulário</a>';
}
?>