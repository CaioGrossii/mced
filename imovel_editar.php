<?php
session_start();

if (!isset($_SESSION['usuario_logado']) || !isset($_SESSION['id_cliente'])) {
    header("Location: login.php");
    exit();
}

// 1. Validação do ID vindo da URL
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    header("Location: view_imoveis.php?erro=id_invalido");
    exit();
}

$id_imovel_a_editar = $_GET['id'];
$id_cliente_logado = $_SESSION['id_cliente'];
$imovel = null;

try {
    $servidor = "localhost";
    $usuario_db = "root";
    $senha_db = "p1Mc3d25*";
    $banco = "mced";
    $conexao = new PDO("mysql:host=$servidor;dbname=$banco;charset=utf8", $usuario_db, $senha_db);
    $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2. BUSCA SEGURA: Verifica se o imóvel pertence ao cliente logado
    $sql = "SELECT fantasia, rua, numero, bairro, cidade, estado, cep 
            FROM imoveis 
            WHERE id_imovel = :id_imovel AND id_cliente = :id_cliente";
            
    $stmt = $conexao->prepare($sql);
    $stmt->bindParam(':id_imovel', $id_imovel_a_editar);
    $stmt->bindParam(':id_cliente', $id_cliente_logado);
    $stmt->execute();
    
    $imovel = $stmt->fetch(PDO::FETCH_ASSOC);

    // 3. Se não encontrou o imóvel (ou não pertence ao usuário), redireciona
    if (!$imovel) {
        header("Location: view_imoveis.php?erro=permissao");
        exit();
    }

} catch (PDOException $e) {
    error_log("Erro ao buscar imóvel para edição: " . $e->getMessage());
    die("Erro ao carregar dados do imóvel.");
} finally {
    $conexao = null;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MCED - Editar Imóvel</title>
    <link rel="stylesheet" href="imoveis.css"> 
</head>
<body>
    <div class="login-container" style="max-width: 700px;"> 
        <h1>MCED - Editar Imóvel</h1>
        
        <form id="edicaoImovelForm" action="processa_imovel_update.php" method="POST">
            
            <input type="hidden" name="id_imovel" value="<?php echo $id_imovel_a_editar; ?>">

            <div class="form-row">
                <div class="form-group-half">
                    <label for="fantasia">Nome do Imóvel</label>
                    <input type="text" id="fantasia" name="fantasia" placeholder="Ex: Casa do Lago" required 
                           value="<?php echo htmlspecialchars($imovel['fantasia']); ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group-half">
                    <label for="rua">Rua / Logradouro</label>
                    <input type="text" id="rua" name="rua" placeholder="Ex: Rua das Flores" required
                           value="<?php echo htmlspecialchars($imovel['rua']); ?>">
                </div>

                <div class="form-group-half">
                    <label for="numero">Número</label>
                    <input type="text" id="numero" name="numero" placeholder="Ex: 123" required
                           value="<?php echo htmlspecialchars($imovel['numero']); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group-half">
                    <label for="bairro">Bairro</label>
                    <input type="text" id="bairro" name="bairro" placeholder="Ex: Centro" required
                           value="<?php echo htmlspecialchars($imovel['bairro']); ?>">
                </div>

                <div class="form-group-half">
                    <label for="cidade">Cidade</label>
                    <input type="text" id="cidade" name="cidade" placeholder="Ex: São Paulo" required
                           value="<?php echo htmlspecialchars($imovel['cidade']); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group-half">
                    <label for="estado">Estado (UF)</label>
                    <input type="text" id="estado" name="estado" placeholder="SP" required maxlength="2"
                           value="<?php echo htmlspecialchars($imovel['estado']); ?>">
                </div>

                <div class="form-group-half">
                    <label for="cep">CEP</label>
                    <input type="text" id="cep" name="cep" placeholder="12345-678" required maxlength="9"
                           value="<?php echo htmlspecialchars($imovel['cep']); ?>">
                </div>
            </div>

            <button type="submit" class="btn">Salvar Alterações</button>
        </form>

        <div id="feedback"></div>

        <div class="extra-links">
             <a href="view_imoveis.php">Cancelar e Voltar</a>
        </div> 
    </div>

    <script>
        // A mesma máscara de CEP do formulário de criação
        document.getElementById('cep').addEventListener('input', function (e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/^(\d{5})(\d)/, '$1-$2');
            e.target.value = value;
        });
    </script>
</body>
</html>