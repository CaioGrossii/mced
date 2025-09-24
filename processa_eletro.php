<?php
session_start();

if (!isset($_SESSION['usuario_logado']) || !isset($_SESSION['id_cliente'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // AJUSTE 1: Adicionado id_categoria na validação de campos vazios
    if (empty($_POST['id_comodo']) || empty(trim($_POST['nm_eletro'])) || empty($_POST['watts']) || empty($_POST['id_categoria'])) {
        die("Erro: Todos os campos são obrigatórios.");
    }

    $id_comodo = $_POST['id_comodo'];
    $nm_eletro = trim(htmlspecialchars($_POST['nm_eletro']));
    $watts = filter_var($_POST['watts'], FILTER_VALIDATE_INT);
    $id_cliente_sessao = $_SESSION['id_cliente'];

    // AJUSTE 2: Capturar o id_categoria do formulário.
    $id_categoria = filter_var($_POST['id_categoria'], FILTER_VALIDATE_INT);

    if ($watts === false || $watts <= 0 || $id_categoria === false) {
        die("Erro: A potência (watts) e a categoria devem ser válidos.");
    }

    try {
        $servidor = "localhost";
        $usuario_db = "root";
        $senha_db = "p1Mc3d25*";
        $banco = "mced";
        
        $conexao = new PDO("mysql:host=$servidor;dbname=$banco;charset=utf8", $usuario_db, $senha_db);
        $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // VERIFICAÇÃO DE SEGURANÇA (seu código já estava ótimo aqui)
        $sql_check = "SELECT i.id_cliente FROM comodos c JOIN imoveis i ON c.id_imovel = i.id_imovel WHERE c.id_comodo = :id_comodo";
        $stmt_check = $conexao->prepare($sql_check);
        $stmt_check->execute([':id_comodo' => $id_comodo]); // Maneira mais limpa de executar
        $comodo_owner = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if (!$comodo_owner || $comodo_owner['id_cliente'] != $id_cliente_sessao) {
            die("Erro de permissão: Você não pode adicionar eletrodomésticos a este cômodo.");
        }

        // Se a verificação passar, insere o eletrodoméstico
        $sql_insert = "INSERT INTO eletrodomesticos (nm_eletro, watts, id_comodo, id_categoria) VALUES (:nm_eletro, :watts, :id_comodo, :id_categoria)";
        
        // AJUSTE 3 (RECOMENDADO): Passar os dados via array no execute()
        $dados_para_inserir = [
            ':nm_eletro'    => $nm_eletro,
            ':watts'        => $watts,
            ':id_comodo'    => $id_comodo,
            ':id_categoria' => $id_categoria // Agora o parâmetro está incluído
        ];

        $stmt_insert = $conexao->prepare($sql_insert);
        $stmt_insert->execute($dados_para_inserir); // Executa com todos os dados
        
        header("Location: eletro.php?sucesso=1"); // Adicionei um feedback de sucesso
        exit();

    } catch (PDOException $e) {
        error_log("Erro ao salvar eletrodoméstico: " . $e->getMessage());
        die("Ocorreu um erro ao salvar os dados. Tente novamente.");
    } finally {
        $conexao = null;
    }

} else {
    header("Location: eletrodomesticos.php");
    exit();
}
?>