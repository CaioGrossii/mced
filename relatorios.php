<?php
// ATIVA O GERENCIAMENTO DE SESSÃO
session_start();

// 1. O GUARDIÃO: Verifica se o usuário está realmente logado.
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header("Location: login.php");
    exit();
}

// ------------------------------------------------------------------
// --- LÓGICA DE BANCO DE DADOS REMOVIDA PARA DEMONSTRAÇÃO ---
// --- INÍCIO DOS DADOS FAKES (MOCKUP) ---
// ------------------------------------------------------------------

// Define uma tarifa fake para os cálculos
define('CUSTO_POR_KWH', 0.60); // Ex: R$ 0,60 por kWh

// Pega o nome da sessão (se foi definido no cadastro) ou usa um padrão
$nome_usuario_logado = $_SESSION['nome_usuario'] ?? 'Usuário Demo'; //

// Pega as datas do filtro (ou usa padrão) apenas para exibição
$data_inicio = $_GET['data_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
$data_fim = $_GET['data_fim'] ?? date('Y-m-d');

// --- Sumário FAKE para os Cards ---
$sumario = [
    'total_kwh' => 258.40,
    'custo_total' => 258.40 * CUSTO_POR_KWH,
    'top_eletro' => 'Ar Condicionado',
    'consumo_top_eletro' => 112.5
];

// --- Registros FAKES para a Tabela ---
$registros_consumo = [
    [
        'data_reg' => '2025-10-27',
        'nm_eletro' => 'Ar Condicionado',
        'ds_comodo' => 'Quarto Suíte',
        'duracao_formatada' => '08:30:00',
        'consumo_kwh' => 13.5
    ],
    [
        'data_reg' => '2025-10-27',
        'nm_eletro' => 'Chuveiro Elétrico',
        'ds_comodo' => 'Banheiro Suíte',
        'duracao_formatada' => '00:45:00',
        'consumo_kwh' => 4.12
    ],
    [
        'data_reg' => '2025-10-26',
        'nm_eletro' => 'Geladeira Frost-Free',
        'ds_comodo' => 'Cozinha',
        'duracao_formatada' => '24:00:00',
        'consumo_kwh' => 2.1
    ],
    // ... (outros dados fakes)
];

// Nenhuma variável de erro é definida
$erro_db = null; 

// ------------------------------------------------------------------
// --- FIM DOS DADOS FAKES (MOCKUP) ---
// ------------------------------------------------------------------

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>MCED - Relatórios</title>
    <link rel="stylesheet" href="dash.css"> <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"> <style>
        .filters-bar {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap; /* Permite quebrar linha em telas menores */
            gap: 20px;
            align-items: center;
        }
        .filters-bar .form-group {
            display: flex;
            flex-direction: column;
        }
        .filters-bar label {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 5px;
            font-weight: 600;
        }
        .filters-bar input[type="date"] {
            padding: 8px 10px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            transition: 0.3s;
        }
        .filters-bar input[type="date"]:focus {
            border-color: #2563eb;
            box-shadow: 0 0 6px rgba(37,99,235,0.3);
        }
        .filters-bar button {
            padding: 10px 15px;
            background: #2563eb;
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.3s;
            align-self: flex-end; /* Alinha o botão com a base dos inputs */
        }
        .filters-bar button:hover {
            background: #1e40af;
        }

        /* Ajustes nos cards (usando a classe .card de dash.css) */
        .card p.details {
            font-size: 12px;
            color: #6b7280;
            margin-top: 5px;
            font-weight: normal;
        }

        /* *** NOVO: Estilo para a imagem do gráfico *** */
        .chart-card {
            margin-bottom: 20px;
        }
        .chart-card img {
            width: 100%; /* Faz a imagem ocupar 100% do card */
            height: auto; /* Mantém a proporção */
            border-radius: 8px; /* Opcional: arredonda as bordas */
            border: 1px solid #e5e7eb; /* Opcional: borda suave */
        }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="logo">
                <h2>MCED</h2>
            </div>
            <nav>
                <ul>
                    <li><a href="dash.php"><i class="fa-solid fa-house"></i> Dashboard</a></li> <li><a href="#"><i class="fa-solid fa-bolt-lightning"></i> Consumo</a></li> <li><a href="#"><i class="fas fa-building"></i> Imóveis</a></li> <li><a href="#"><i class="fas fa-plug"></i> Eletrodomésticos</a></li> <li><a href="relatorios.php" class="active"><i class="fas fa-chart-bar"></i> Relatórios</a></li> </ul>
            </nav>
            <div class="logout">
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a> </div>
        </aside>

        <main class="main-content">
            <header>
                <h1>Relatórios de Consumo</h1>
                <div class="user-info">
                    <span><?php echo htmlspecialchars($nome_usuario_logado); ?></span>
                    <img src="img/antero.jpg" alt="Avatar"> </div>
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
                    <p class="details">Consumo: <?php echo number_format($sumario['consumo_top_eletro'], 2, ',', '.'); ?> kWh</p>
                </div>
            </section>
            
            <div class="chart-card"> <h3>Consumo por Eletrodoméstico (kWh)</h3>
                
                <a href="img/meu_grafico_fake.png" target="_blank">
                    <img src="https://via.placeholder.com/800x300.png?text=Placeholder+para+Gráfico+de+Barras" alt="Gráfico de Consumo por Eletrodoméstico">
                </a>
                </div>

            <div class="table-card"> <h3>Detalhamento de Consumo</h3>
                
                <?php if (empty($registros_consumo)): ?>
                    <p>Nenhum registro de consumo encontrado para o período selecionado.</p>
                <?php else: ?>
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