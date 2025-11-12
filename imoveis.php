<?php
session_start();

if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MCED - Cadastro de Imóvel</title>
    <link rel="stylesheet" href="imoveis.css"> 
</head>
<body>
    <div class="login-container" style="max-width: 700px;"> 
        <h1>MCED - Novo Imóvel</h1>
        
        <form id="cadastroImovelForm" action="processa_imovel.php" method="POST">
            
            <div class="form-row">
                <div class="form-group-half">
                    <label for="fantasia">Nome do Imóvel</label>
                    <input type="text" id="fantasia" name="fantasia" placeholder="Ex: Casa do Lago" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group-half">
                    <label for="rua">Logradouro</label>
                    <input type="text" id="rua" name="rua" placeholder="Ex: Rua das Flores" required>
                </div>

                <div class="form-group-half">
                    <label for="numero">Número</label>
                    <input type="text" id="numero" name="numero" placeholder="Ex: 123" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group-half">
                    <label for="bairro">Bairro</label>
                    <input type="text" id="bairro" name="bairro" placeholder="Ex: Centro" required>
                </div>

                <div class="form-group-half">
                    <label for="cidade">Cidade</label>
                    <input type="text" id="cidade" name="cidade" placeholder="Ex: São Paulo" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group-half">
                    <label for="estado">Estado (UF)</label>
                    <input type="text" id="estado" name="estado" placeholder="SP" required maxlength="2">
                </div>

                <div class="form-group-half">
                    <label for="cep">CEP</label>
                    <input type="text" id="cep" name="cep" placeholder="12345-678" required maxlength="9">
                </div>
            </div>

            <button type="submit" class="btn">Cadastrar Imóvel</button>
        </form>

        <div id="feedback"></div>

        <div class="extra-links">
             <a href="view_imoveis.php">Voltar para a Listagem</a>
        </div> 
    </div>

    <script>
        document.getElementById('cep').addEventListener('input', function (e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/^(\d{5})(\d)/, '$1-$2');
            e.target.value = value;
        });
    </script>
</body>
</html>