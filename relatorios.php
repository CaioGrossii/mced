<?php
// ATIVA O GERENCIAMENTO DE SESSÃO
session_start();

// 1. O GUARDIÃO: Verifica se o usuário está realmente logado.
// Copiado de dash.php para garantir que esta página seja protegida.
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header("Location: login.php"); // Leva para a página de login
    exit(); // Garante que o restante do código não seja executado
}

// --- Configuração do Banco de Dados (Pego de processa_cadastro.php) ---
$servidor = "localhost";
$usuario_db = "root";
$senha_db = "p1Mc3d25*";
$banco = "mced";
$conexao = null;

// --- Configuração de Negócio ---
// Em um sistema real, isso viria do banco de dados ou de uma tabela de tarifas
define('CUSTO_POR_KWH', 0.60); // Ex: R$ 0,60 por kWh

// --- Lógica de Filtros (Segurança e Padrões) ---
// Define um período padrão (últimos 30 dias)
$data_fim_default = date('Y-m-d');
$data_inicio_default = date('Y-m-d', strtotime('-30 days'));

// Valida e obtém os filtros da URL (GET) de forma segura
$data_inicio = $data_inicio_default;
if (isset($_GET['data_inicio']) && !empty($_GET['data_inicio'])) {
    // Validação básica de formato YYYY-MM-DD
    if (preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $_GET['data_inicio'])) {
        $data_inicio = $_GET['data_inicio'];
    }
}

$data_fim = $data_fim_default;
if (isset($_GET['data_fim']) && !empty($_GET['data_fim'])) {
    if (preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $_GET['data_fim'])) {
        $data_fim = $_GET['data_fim'];
    }
}

// --- Variáveis para armazenar os resultados ---
$registros_consumo = [];
$sumario = [
    'total_kwh' => 0,
    'custo_total' => 0,
    'top_eletro' => 'N/D',
    'consumo_por_eletro' => []
];
$erro_db = null;
$nome_usuario_logado = 'Usuário'; // Valor padrão

// --- Bloco Principal de Lógica de Banco de Dados ---
try {
    $conexao = new PDO("mysql:host=$servidor;dbname=$banco;charset=utf8", $usuario_db, $senha_db);
    $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Buscar o ID e Nome do cliente logado
    // O script processa_login.php só salva o email, então precisamos buscar o ID e o Nome.
    // O script processa_cadastro.php salva o nome, mas o login não.
    // Esta consulta unifica isso, buscando os dados do usuário pelo email da sessão.
    
    $sql_user = "SELECT id_cliente, nm_cliente FROM clientes WHERE email_cliente = :email LIMIT 1";
    $stmt_user = $conexao->prepare($sql_user);
    // Usamos o email_usuario que foi salvo na sessão durante o login
    $stmt_user->bindParam(':email', $_SESSION['email_usuario']);
    $stmt_user->execute();
    $usuario = $stmt_user->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        // Se o usuário da sessão não for encontrado no banco, é um erro de integridade.
        // Deslogamos o usuário por segurança.
        session_unset();
        session_destroy();
        header("Location: login.php?erro=sessao_invalida");
        exit();
    }
    
    $id_cliente_logado = $usuario['id_cliente'];
    $nome_usuario_logado = $usuario['nm_cliente'];
    
    // Aproveitamos para salvar o nome na sessão, caso o login não o tenha feito (consistência com dash.php)
    $_SESSION['nome_usuario'] = $nome_usuario_logado;


    // 2. Buscar os dados de consumo para os relatórios
    // Esta query junta consumo, eletrodomésticos e cômodos,
    // filtrando APENAS pelo id_cliente_logado e pelo período selecionado.
    $sql_report = "
        SELECT 
            c.data_reg,
            c.hora_inicio,
            c.hora_fim,
            e.nm_eletro,
            e.watts,
            co.ds_comodo
        FROM consumo c
        JOIN eletrodomesticos e ON c.id_eletro = e.id_eletro
        JOIN comodos co ON e.id_comodo = co.id_comodo
        WHERE co.id_cliente = :id_cliente 
        AND c.data_reg BETWEEN :data_inicio AND :data_fim
        ORDER BY c.data_reg DESC, c.hora_inicio DESC
    ";
    
    $stmt_report = $conexao->prepare($sql_report);
    $stmt_report->bindParam(':id_cliente', $id_cliente_logado, PDO::PARAM_INT);
    $stmt_report->bindParam(':data_inicio', $data_inicio, PDO::PARAM_STR);
    $stmt_report->bindParam(':data_fim', $data_fim, PDO::PARAM_STR);
    $stmt_report->execute();

    $consumo_por_eletro_temp = [];

    // 3. Processar os dados e calcular o consumo
    while ($linha = $stmt_report->fetch(PDO::FETCH_ASSOC)) {
        
        // Cálculo de Consumo:
        // O schema (criar bd.txt) usa VARCHAR para hora, o que não é ideal.
        // Precisamos converter para timestamp para calcular a diferença.
        $timestamp_inicio = strtotime($linha['hora_inicio']);
        $timestamp_fim = strtotime($linha['hora_fim']);
        
        $duracao_segundos = $timestamp_fim - $timestamp_inicio;
        
        // Evita consumo negativo se os dados estiverem errados (ex: 00:00 - 23:59)
        if ($duracao_segundos <= 0) $duracao_segundos = 0; 
        
        $duracao_horas = $duracao_segundos / 3600; // Converte segundos para horas
        
        // Fórmula: Consumo (kWh) = (Potência (Watts) * Tempo (Horas)) / 1000
        $consumo_kwh = ($linha['watts'] * $duracao_horas) / 1000;
        
        // Adiciona os dados calculados à linha
        $linha['duracao_formatada'] = gmdate("H:i:s", $duracao_segundos); // Formata HH:MM:SS
        $linha['consumo_kwh'] = $consumo_kwh;
        
        // Adiciona ao array de registros
        $registros_consumo[] = $linha;
        
        // --- Acumula para o sumário ---
        $sumario['total_kwh'] += $consumo_kwh;
        
        // Acumula para o ranking de "vilões"
        $eletro_nome = $linha['nm_eletro'];
        if (!isset($consumo_por_eletro_temp[$eletro_nome])) {
            $consumo_por_eletro_temp[$eletro_nome] = 0;
        }
        $consumo_por_eletro_temp[$eletro_nome] += $consumo_kwh;
    }
    
    // 4. Finaliza os cálculos do sumário
    $sumario['custo_total'] = $sumario['total_kwh'] * CUSTO_POR_KWH;
    
    // Ordena o consumo por eletrodoméstico (do maior para o menor)
    if (!empty($consumo_por_eletro_temp)) {
        arsort($consumo_por_eletro_temp); // Ordena mantendo as chaves
        $sumario['top_eletro'] = key($consumo_por_eletro_temp); // Pega o nome do primeiro (maior)
        $sumario['consumo_por_eletro'] = $consumo_por_eletro_temp;
    }

} catch (PDOException $e) {
    // Em um ambiente de produção, logaríamos o erro em um arquivo
    // error_log("Erro de banco de dados em relatorios.php: " . $e->getMessage());
    // E exibiríamos uma mensagem genérica para o usuário
    $erro_db = "Ocorreu um erro ao carregar os relatórios. Por favor, tente novamente mais tarde.";
    
    // Para depuração, podemos exibir o erro real:
    // $erro_db = $e->getMessage(); 
} finally {
    // Fecha a conexão
    $conexao = null;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>MCED - Relatórios</title>
    <link rel="stylesheet" href="dash.css">
    <link rel="stylesheet" href="relatorios.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="logo">
                <h2>MCED</h2>
            </div>
            <nav>
                <ul>
                    <li><a href="dash.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>
                    <li><a href="#"><i class="fa-solid fa-bolt-lightning"></i> Consumo</a></li>
                    <li><a href="view_imoveis.php"><i class="fas fa-building"></i> Imóveis</a></li>
                    <li><a href="comodos.php"><i class="fas fa-door-open"></i> Cômodos</a></li>
                    <li><a href="categorias.php"><i class="fas fa-tags"></i> Categorias</a></li>
                    <li><a href="eletro.php"><i class="fas fa-plug"></i> Eletrodomésticos</a></li>
                    <li><a href="relatorios.php"><i class="fas fa-chart-bar"></i> Relatórios</a></li>
                </ul>
            </nav>
            <div class="logout">
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a>
            </div>
        </aside>

        <main class="main-content">
            <header>
                <h1>Relatórios de Consumo</h1>
                <div class="user-info">
                    <span><?php echo htmlspecialchars($nome_usuario_logado); ?></span>
                    <img src="img/antero.jpg" alt="Avatar">
                </div>
            </header>

            <section class="filters-bar">
                <form method="GET" action="relatorios.php" style="display: flex; flex-wrap: wrap; gap: 20px; align-items: center;">
                    <div class="form-group">
                        <label for="data_inicio">Data Início</label>
                        <input type="date" id="data_inicio" name="data_inicio" value="<?php echo htmlspecialchars($data_inicio); ?>">
                    </div>
                    <div class="form-group">
                        <label for="data_fim">Data Fim</label>
                        <input type="date" id="data_fim" name="data_fim" value="<?php echo htmlspecialchars($data_fim); ?>">
                    </div>
                    <button type="submit">Filtrar</button>
                </form>
            </section>
            
            <?php if ($erro_db): ?>
                <div class="card" style="background-color: #ffebee; border: 1px solid #ef5350; color: #c62828;">
                    <h3>Erro ao Carregar Dados</h3>
                    <p style="font-size: 14px;"><?php echo htmlspecialchars($erro_db); ?></p>
                </div>
            <?php endif; ?>

            <section class="cards">
                <div class="card">
                    <h3>Consumo Total (Período)</h3>
                    <p><?php echo number_format($sumario['total_kwh'], 2, ',', '.'); ?> kWh</p>
                    <p class="details">Período: <?php echo htmlspecialchars(date("d/m/Y", strtotime($data_inicio))) . " a " . htmlspecialchars(date("d/m/Y", strtotime($data_fim))); ?></p>
                </div>
                <div class="card">
                    <h3>Custo Estimado (Período)</h3>
                    <p>R$ <?php echo number_format($sumario['custo_total'], 2, ',', '.'); ?></p>
                    <p class="details">Tarifa usada: R$ <?php echo number_format(CUSTO_POR_KWH, 2, ',', '.'); ?> / kWh</p>
                </div>
                <div class="card">
                    <h3>Principal Vilão</h3>
                    <p><?php echo htmlspecialchars($sumario['top_eletro']); ?></p>
                    <?php if($sumario['top_eletro'] !== 'N/D'): ?>
                        <p class="details">Consumo: <?php echo number_format($sumario['consumo_por_eletro'][$sumario['top_eletro']], 2, ',', '.'); ?> kWh</p>
                    <?php else: ?>
                        <p class="details">Sem dados de consumo.</p>
                    <?php endif; ?>
                </div>
            </section>
            
            <div class="table-card">
                <h3>Detalhamento de Consumo</h3>
                
                <?php if (!$erro_db && empty($registros_consumo)): ?>
                    <p>Nenhum registro de consumo encontrado para o período selecionado.</p>
                <?php elseif (!$erro_db): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Eletrodoméstico</th>
                                <th>Cômodo</th>
                                <th>Duração (HH:MM:SS)</th>
                                <th>Consumo (kWh)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($registros_consumo as $registro): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(date("d/m/Y", strtotime($registro['data_reg']))); ?></td>
                                <td><?php echo htmlspecialchars($registro['nm_eletro']); ?></td>
                                <td><?php echo htmlspecialchars($registro['ds_comodo']); ?></td>
                                <td><?php echo htmlspecialchars($registro['duracao_formatada']); ?></td>
                                <td><?php echo number_format($registro['consumo_kwh'], 3, ',', '.'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>