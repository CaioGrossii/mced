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

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <?php include 'includes/head.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            
                <div class="card chart-card">
                    <h3>Consumo por Eletrodoméstico (kWh)</h3>
                    <div style="position: relative; height: 300px; width: 100%;">
                        <canvas id="consumoChart"></canvas>
                    </div>
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
    <script>
document.addEventListener("DOMContentLoaded", function() {
    const ctx = document.getElementById('consumoChart').getContext('2d');

    // Criando um degradê futurista para as barras
    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(0, 242, 255, 0.8)'); // Ciano Neon (Topo)
    gradient.addColorStop(1, 'rgba(0, 123, 255, 0.1)'); // Azul Transparente (Base)

    new Chart(ctx, {
        type: 'bar', // Gráfico de Barras
        data: {
            // Rótulos dos Eletrodomésticos (Dados Fakes)
            labels: ['Ar Condicionado', 'Chuveiro', 'Geladeira', 'Computador', 'Lavadora', 'Microondas'],
            datasets: [{
                label: 'Consumo (kWh)',
                data: [112.5, 85.2, 60.4, 45.0, 32.1, 15.5], // Valores Fakes
                backgroundColor: gradient,
                borderColor: '#00f2ff', // Borda Neon
                borderWidth: 1,
                borderRadius: 5, // Barras arredondadas
                barPercentage: 0.6, // Largura da barra
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false // Esconde a legenda para ficar mais limpo
                },
                tooltip: {
                    backgroundColor: 'rgba(20, 20, 30, 0.9)',
                    titleColor: '#fff',
                    bodyColor: '#00f2ff',
                    borderColor: 'rgba(255,255,255,0.1)',
                    borderWidth: 1,
                    padding: 10
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(255, 255, 255, 0.05)' // Linhas de grade sutis
                    },
                    ticks: {
                        color: '#a0a0a0', // Cor do texto do eixo Y
                        font: { family: "'Poppins', sans-serif" }
                    }
                },
                x: {
                    grid: {
                        display: false // Remove grade vertical
                    },
                    ticks: {
                        color: '#e0e0e0', // Cor do texto do eixo X
                        font: { family: "'Poppins', sans-serif" }
                    }
                }
            },
            animation: {
                duration: 2000, // Animação suave de 2 segundos
                easing: 'easeOutQuart'
            }
        }
    });
});
</script>

    </body>
</html>