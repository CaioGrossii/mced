<?php
// 1. Inicia a sessão.
session_start();

// Configura o timezone (Brasil/São Paulo)
date_default_timezone_set('America/Sao_Paulo');

// 2. O GUARDIÃO: Verifica login.
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <?php include 'includes/head.php'; ?>
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
                <h1>Bem-vindo, <?php echo htmlspecialchars($_SESSION['nome_usuario']); ?></h1>
                
                <div class="header-right">
                    <div class="date-display">
                        <?php echo date('d/m/Y'); ?>
                        <span><?php echo date('H:i'); ?></span>
                    </div>

                    <div class="user-info">
                        <a href="perfil.php" title="Editar Perfil">
                            <img src="img/perfil.png" alt="Avatar">
                        </a>
                    </div>
                </div>
            </header>

            <section class="cardsdash">
                <div class="cardash">
                    <h3>Consumo Total</h3>
                    <p>250 kWh</p>
                    <small>Até o momento</small>
                </div>
                <div class="cardash">
                    <h3>Previsão de Conta</h3>
                    <p>R$ 150,00</p>
                    <small>Estimativa Mês</small>
                </div>
                <div class="cardash">
                    <h3>Eletrodomésticos</h3>
                    <p>12 ativos</p>
                    <small>Em uso</small>
                </div>
            </section>
            
            <div class="table-card">
                <h3>Últimas Leituras</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Imóvel</th>
                            <th>Data</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Casa Principal</td>
                            <td>01/09/2025</td>
                            <td>Concluído</td>
                        </tr>
                        <tr>
                            <td>Apartamento</td>
                            <td>02/09/2025</td>
                            <td>Concluído</td>
                        </tr>
                        <tr>
                            <td>Sítio</td>
                            <td>03/09/2025</td>
                            <td>Concluído</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>