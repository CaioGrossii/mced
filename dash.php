<?php
// 1. Inicia a sessão para poder acessar as variáveis de sessão.
session_start();

// 2. O GUARDIÃO: Verifica se o usuário está realmente logado.
// Se a variável de sessão 'usuario_logado' não existir ou não for true,
// redireciona o usuário para a página de login e encerra o script.
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header("Location: login.php"); // Leva para a página de login
    exit(); // Garante que o restante do código não seja executado
}

// 3. Se o script chegou até aqui, o usuário está logado e pode ver a página.
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>MCED - Dashboard</title>
    <link rel="stylesheet" href="dash.css">
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
                    <li><a href="eletro.php"><i class="fas fa-plug"></i> Eletrodomésticos</a></li>
                    <li><a href="categorias.php"><i class="fas fa-tags"></i> Categorias</a></li>
                    <li><a href="#"><i class="fas fa-chart-bar"></i> Relatórios</a></li>
                </ul>
            </nav>
            <div class="logout">
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a>
            </div>
        </aside>

        <main class="main-content">
            <header>
                <h1>Bem-vindo, <?php echo htmlspecialchars($_SESSION['nome_usuario'] ?? 'Usuário'); ?></h1>
                <div class="user-info">
                    <img src="img/antero.jpg" alt="Avatar">
                </div>
            </header>

            <section class="cards">
                <div class="card">
                    <h3>Consumo Total</h3>
                    <p>250 kWh</p>
                </div>
                <div class="card">
                    <h3>Custo Estimado - Mês</h3>
                    <p>R$ 150,00</p>
                </div>
                <div class="card">
                    <h3>Eletrodomésticos</h3>
                    <p>12 ativos</p>
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