<?php
/**
 * ARQUIVO: dash.php
 * OBJETIVO: Dashboard principal com indicadores e gestão de tarifa.
 * ATUALIZAÇÃO: Botão padronizado com classe .btn-header e Modal com variáveis do tema.
 */

session_start();
date_default_timezone_set('America/Sao_Paulo');

// 1. O GUARDIÃO: Verifica login.
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header("Location: login.php");
    exit();
}

// Inicialização de variáveis
$total_eletros = 0;
$total_kwh = 0;
$ultimas_leituras = [];
$tarifa_atual = 0.0;
$feedback_msg = null;

try {
    // Conexão com Banco de Dados
    $servidor = "localhost";
    $usuario_db = "root";
    $senha_db = "p1Mc3d25*";
    $banco = "mced";
    
    $conexao = new PDO("mysql:host=$servidor;dbname=$banco;charset=utf8", $usuario_db, $senha_db);
    $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // --- 0. PROCESSAR ATUALIZAÇÃO DE TARIFA (POST) ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'atualizar_tarifa') {
        $nova_tarifa = $_POST['nova_tarifa'];
        // Converte vírgula para ponto (ex: 0,85 -> 0.85)
        $nova_tarifa = str_replace(',', '.', $nova_tarifa);
        
        if (is_numeric($nova_tarifa) && $nova_tarifa >= 0) {
            $sql_update = "UPDATE clientes SET tarifa = :tarifa WHERE id_cliente = :id";
            $stmt_up = $conexao->prepare($sql_update);
            $stmt_up->execute([
                ':tarifa' => $nova_tarifa,
                ':id' => $_SESSION['id_cliente']
            ]);
            $feedback_msg = "Tarifa atualizada com sucesso!";
        } else {
            $feedback_msg = "Valor de tarifa inválido.";
        }
    }

    // --- 1. BUSCAR TARIFA ATUAL DO CLIENTE ---
    $sql_tarifa = "SELECT tarifa FROM clientes WHERE id_cliente = :id";
    $stmt_tarifa = $conexao->prepare($sql_tarifa);
    $stmt_tarifa->execute([':id' => $_SESSION['id_cliente']]);
    $tarifa_atual = (float) $stmt_tarifa->fetchColumn();
    
    // Valor padrão caso a tarifa seja 0 ou nula
    if ($tarifa_atual <= 0) $tarifa_atual = 0.85;

    // --- A. Contagem de Eletrodomésticos ---
    $sql_contagem = "SELECT COUNT(*) 
                     FROM eletrodomesticos e
                     JOIN comodos c ON e.id_comodo = c.id_comodo
                     JOIN imoveis i ON c.id_imovel = i.id_imovel
                     WHERE i.id_cliente = :id_cliente";
    $stmt = $conexao->prepare($sql_contagem);
    $stmt->bindValue(':id_cliente', $_SESSION['id_cliente'], PDO::PARAM_INT);
    $stmt->execute();
    $total_eletros = $stmt->fetchColumn();

    // --- B. Soma Total de kWh Consumidos ---
    $sql_soma_kwh = "SELECT SUM(cons.consumokwh) 
                     FROM consumo cons
                     JOIN eletrodomesticos e ON cons.id_eletro = e.id_eletro
                     JOIN comodos c ON e.id_comodo = c.id_comodo
                     JOIN imoveis i ON c.id_imovel = i.id_imovel
                     WHERE i.id_cliente = :id_cliente";
    
    $stmt_soma = $conexao->prepare($sql_soma_kwh);
    $stmt_soma->bindValue(':id_cliente', $_SESSION['id_cliente'], PDO::PARAM_INT);
    $stmt_soma->execute();
    $total_kwh = (float) $stmt_soma->fetchColumn();

    // --- C. Busca das 3 Últimas Leituras ---
    $sql_leituras = "SELECT 
                        i.fantasia AS imovel, 
                        cons.data_reg, 
                        e.nm_eletro
                     FROM consumo cons
                     JOIN eletrodomesticos e ON cons.id_eletro = e.id_eletro
                     JOIN comodos c ON e.id_comodo = c.id_comodo
                     JOIN imoveis i ON c.id_imovel = i.id_imovel
                     WHERE i.id_cliente = :id_cliente
                     ORDER BY cons.data_reg DESC, cons.hora_inicio DESC, cons.id_consumo DESC
                     LIMIT 3";
                     
    $stmt_leituras = $conexao->prepare($sql_leituras);
    $stmt_leituras->bindValue(':id_cliente', $_SESSION['id_cliente'], PDO::PARAM_INT);
    $stmt_leituras->execute();
    $ultimas_leituras = $stmt_leituras->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Erro no Dashboard: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <?php include 'includes/head.php'; ?>
    <style>
        /* Ajuste para o Modal usar as variáveis do tema (variables.css) */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(8px);
        }
        .modal-content {
            background: var(--color-bg-surface, #121212);
            border: var(--glass-border, 1px solid rgba(255, 255, 255, 0.1));
            padding: 30px;
            border-radius: 16px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
            text-align: center;
            color: var(--color-text-main, #fff);
        }
        .modal-content h3 { 
            margin-bottom: 20px; 
            color: var(--color-primary, #007bff);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .modal-content input {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid #333;
            color: #fff;
            font-size: 1.2rem;
            text-align: center;
            margin-bottom: 20px;
        }
        .modal-actions { display: flex; gap: 10px; justify-content: center; }
        
        /* Feedback Flutuante */
        .feedback-float {
            position: fixed; top: 20px; right: 20px;
            background: rgba(5, 242, 155, 0.2); /* Verde Neon Transparente */
            border: 1px solid var(--color-success, #05f29b);
            color: #fff; padding: 15px 25px;
            border-radius: 8px; z-index: 1100; 
            box-shadow: 0 0 15px rgba(5, 242, 155, 0.3);
            animation: fadeOut 4s forwards;
            font-weight: 600;
        }
        @keyframes fadeOut {
            0% { opacity: 1; }
            80% { opacity: 1; }
            100% { opacity: 0; visibility: hidden; }
        }
    </style>
</head>
<body>
    <?php if ($feedback_msg): ?>
        <div class="feedback-float">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($feedback_msg); ?>
        </div>
    <?php endif; ?>

    <div id="modalTarifa" class="modal-overlay">
        <div class="modal-content">
            <h3>Configurar Tarifa</h3>
            <p style="font-size: 0.9rem; color: #aaa; margin-bottom: 20px;">Defina o valor do kWh (R$).</p>
            
            <form method="POST" action="">
                <input type="hidden" name="acao" value="atualizar_tarifa">
                <div style="position: relative;">
                    <input type="text" name="nova_tarifa" value="<?php echo number_format($tarifa_atual, 4, ',', ''); ?>" required>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-delete" onclick="toggleModal(false)">Cancelar</button>
                    <button type="submit" class="btn">Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="container">
        <aside class="sidebar">
            <div class="logo"><h2>MCED</h2></div>
            <nav>
                <ul>
                    <li><a href="dash.php" class="active"><i class="fa-solid fa-house"></i> Dashboard</a></li>
                    <li><a href="consumo.php"><i class="fa-solid fa-bolt-lightning"></i> Consumo</a></li>
                    <li><a href="view_imoveis.php"><i class="fas fa-building"></i> Imóveis</a></li>
                    <li><a href="comodos.php"><i class="fas fa-door-open"></i> Cômodos</a></li>
                    <li><a href="categorias.php"><i class="fas fa-tags"></i> Categorias</a></li>
                    <li><a href="eletro.php"><i class="fas fa-plug"></i> Eletrodomésticos</a></li>
                    <!-- <li><a href="relatorios.php"><i class="fas fa-chart-bar"></i> Relatórios</a></li> -->
                </ul>
            </nav>
            <div class="logout"><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a></div>
        </aside>

        <main class="main-content">
            <header>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <h1>Bem-vindo, <?php echo htmlspecialchars($_SESSION['nome_usuario']); ?></h1>
                    
                    <button onclick="toggleModal(true)" class="btn-header" style="width: auto; height: auto; padding: 5px 15px; display: flex; align-items: center; gap: 8px; cursor: pointer; border: none; color: #fff;">
                        <i class="fas fa-cog"></i> 
                        <span>Tarifa: R$ <?php echo number_format($tarifa_atual, 2, ',', '.'); ?></span>
                    </button>
                </div>
                
                <div class="header-right">
                    <div class="date-display">
                        <?php echo date('d/m/Y'); ?>
                        <span><?php echo date('H:i'); ?></span>
                    </div>
                    <div class="user-info">
                        <a href="perfil.php"><img src="img/perfil.png" alt="Avatar"></a>
                    </div>
                </div>
            </header>

            <section class="cardsdash">
                <div class="cardash">
                    <h3>Consumo Total</h3>
                    <p><?php echo number_format($total_kwh, 2, ',', '.'); ?> kWh</p> 
                    <small>Até o momento</small>
                </div>
                <div class="cardash">
                    <h3>Previsão de Conta</h3>
                    <p>R$ <?php echo number_format($total_kwh * $tarifa_atual, 2, ',', '.'); ?></p>
                    <small>Estimativa Mês</small>
                </div>
                <div class="cardash">
                    <h3>Eletrodomésticos</h3>
                    <p><?php echo $total_eletros; ?></p>
                    <small>Cadastrados</small>
                </div>
            </section>
            
            <div class="table-card">
                <h3>Últimas Leituras</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Imóvel</th>
                            <th>Data</th>
                            <th>Eletrodoméstico / Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ultimas_leituras)): ?>
                            <tr>
                                <td colspan="3" style="text-align: center; color: #666;">Nenhum consumo registrado ainda.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($ultimas_leituras as $leitura): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($leitura['imovel']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($leitura['data_reg'])); ?></td>
                                    <td>
                                        <span style="font-weight: 600; color: #1e3a8a;">
                                            <?php echo htmlspecialchars($leitura['nm_eletro']); ?>
                                        </span>
                                        <small style="color: #28a745; margin-left: 5px;">(Concluído)</small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        function toggleModal(show) {
            const modal = document.getElementById('modalTarifa');
            modal.style.display = show ? 'flex' : 'none';
        }

        // Fecha o modal ao clicar fora
        window.onclick = function(event) {
            const modal = document.getElementById('modalTarifa');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>