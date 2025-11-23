<?php
// consumo.php
session_start();

// 1. Segurança: Verifica login
if (!isset($_SESSION['usuario_logado']) || !isset($_SESSION['id_cliente'])) {
    header("Location: login.php");
    exit();
}

$id_cliente = $_SESSION['id_cliente'];
$nome_usuario_logado = $_SESSION['nome_usuario'] ?? 'Usuário';

// Inicialização
$imoveis_disponiveis = [];
$todos_eletros = []; // Carregamos todos para filtrar via JS
$historico_consumo = [];
$erro_banco = null;

try {
    $servidor = "localhost"; $usuario_db = "root"; $senha_db = "p1Mc3d25*"; $banco = "mced";
    $conexao = new PDO("mysql:host=$servidor;dbname=$banco;charset=utf8", $usuario_db, $senha_db);
    $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2. Busca IMÓVEIS para o primeiro select
    $sql_imoveis = "SELECT id_imovel, fantasia FROM imoveis WHERE id_cliente = :id_cliente ORDER BY fantasia";
    $stmt_imoveis = $conexao->prepare($sql_imoveis);
    $stmt_imoveis->execute([':id_cliente' => $id_cliente]);
    $imoveis_disponiveis = $stmt_imoveis->fetchAll(PDO::FETCH_ASSOC);

    // 3. Busca TODOS os Eletrodomésticos (incluindo id_imovel para o filtro JS)
    $sql_eletros = "SELECT e.id_eletro, e.nm_eletro, c.ds_comodo, i.id_imovel 
                    FROM eletrodomesticos e
                    JOIN comodos c ON e.id_comodo = c.id_comodo
                    JOIN imoveis i ON c.id_imovel = i.id_imovel
                    WHERE i.id_cliente = :id_cliente
                    ORDER BY e.nm_eletro";
    $stmt_eletros = $conexao->prepare($sql_eletros);
    $stmt_eletros->execute([':id_cliente' => $id_cliente]);
    $todos_eletros = $stmt_eletros->fetchAll(PDO::FETCH_ASSOC);

    // 4. Busca Histórico de Consumo
    $sql_consumo = "SELECT 
                        cons.id_consumo, cons.data_reg, cons.hora_inicio, cons.hora_fim,
                        e.nm_eletro, e.watts, c.ds_comodo, i.fantasia,
                        TIMEDIFF(cons.hora_fim, cons.hora_inicio) as duracao
                    FROM consumo cons
                    JOIN eletrodomesticos e ON cons.id_eletro = e.id_eletro
                    JOIN comodos c ON e.id_comodo = c.id_comodo
                    JOIN imoveis i ON c.id_imovel = i.id_imovel
                    WHERE i.id_cliente = :id_cliente
                    ORDER BY cons.data_reg DESC, cons.hora_inicio DESC";
    
    $stmt_consumo = $conexao->prepare($sql_consumo);
    $stmt_consumo->execute([':id_cliente' => $id_cliente]);
    $historico_consumo = $stmt_consumo->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Erro em consumo.php: " . $e->getMessage());
    $erro_banco = "Erro ao carregar dados.";
} finally {
    $conexao = null;
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
            <div class="logo"><h2>MCED</h2></div>
            <nav>
                <ul>
                    <li><a href="dash.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>
                    <li><a href="consumo.php" class="active"><i class="fa-solid fa-bolt-lightning"></i> Consumo</a></li>
                    <li><a href="view_imoveis.php"><i class="fas fa-building"></i> Imóveis</a></li>
                    <li><a href="comodos.php"><i class="fas fa-door-open"></i> Cômodos</a></li>
                    <li><a href="categorias.php"><i class="fas fa-tags"></i> Categorias</a></li>
                    <li><a href="eletro.php"><i class="fas fa-plug"></i> Eletrodomésticos</a></li>
                    <li><a href="relatorios.php"><i class="fas fa-chart-bar"></i> Relatórios</a></li>
                </ul>
            </nav>
            <div class="logout"><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a></div>
        </aside>

        <main class="main-content">
            <header>
                <h1>Registro de Consumo</h1>
                <div class="user-info">
                    <span><?php echo htmlspecialchars($nome_usuario_logado); ?></span>
                    <img src="img/perfil.png" alt="Avatar">
                </div>
            </header>

            <?php if (isset($_GET['sucesso'])): ?>
                <div class="card" style="background: #d1fae5; color: #065f46; padding: 15px; margin-bottom: 20px;">
                    Operação realizada com sucesso!
                </div>
            <?php elseif (isset($_GET['erro'])): ?>
                <div class="card" style="background: #fee2e2; color: #991b1b; padding: 15px; margin-bottom: 20px;">
                    <?php echo htmlspecialchars($_GET['erro']); ?>
                </div>
            <?php endif; ?>

            <div class="card" style="margin-bottom: 20px;">
                <h3>Registrar Novo Consumo</h3>
                <form action="processa_consumo.php" method="POST" class="form-grid" style="margin-top: 15px;">
                    
                    <div class="form-group">
                        <label for="select_imovel">Selecione o Imóvel</label>
                        <select id="select_imovel" required onchange="filtrarEletros()">
                            <option value="">Selecione...</option>
                            <?php foreach ($imoveis_disponiveis as $imovel): ?>
                                <option value="<?php echo $imovel['id_imovel']; ?>">
                                    <?php echo htmlspecialchars($imovel['fantasia']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="id_eletro">Eletrodoméstico</label>
                        <select name="id_eletro" id="id_eletro" required disabled style="background-color: rgba(255,255,255,0.02); cursor: not-allowed;">
                            <option value="">Selecione um imóvel primeiro...</option>
                            </select>
                    </div>

                    <div class="form-group">
                        <label for="data_reg">Data de Uso</label>
                        <input type="date" name="data_reg" id="data_reg" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="form-group">
                        <div style="display: flex; gap: 10px;">
                            <div style="flex: 1;">
                                <label for="hora_inicio">Início</label>
                                <input type="time" name="hora_inicio" id="hora_inicio" required>
                            </div>
                            <div style="flex: 1;">
                                <label for="hora_fim">Fim</label>
                                <input type="time" name="hora_fim" id="hora_fim" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions" style="grid-column: 1 / -1; display:flex; justify-content: flex-end;">
                        <button type="submit" class="btn">Registrar</button>
                    </div>
                </form>
            </div>

            <div class="table-card">
                <h3>Histórico Recente</h3>
                <?php if ($erro_banco): ?>
                    <p style="color: red;"><?php echo $erro_banco; ?></p>
                <?php elseif (empty($historico_consumo)): ?>
                    <p>Nenhum registro de consumo encontrado.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Imóvel</th>
                                <th>Eletrodoméstico</th>
                                <th>Duração</th>
                                <th>Consumo (kWh)</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historico_consumo as $registro): 
                                $partes = explode(':', $registro['duracao']);
                                $horas_decimais = $partes[0] + ($partes[1]/60) + ($partes[2]/3600);
                                $kwh = ($registro['watts'] * $horas_decimais) / 1000;
                            ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($registro['data_reg'])); ?></td>
                                <td><small><?php echo htmlspecialchars($registro['fantasia']); ?></small></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($registro['nm_eletro']); ?></strong><br>
                                    <small style="color:#aaa;"><?php echo htmlspecialchars($registro['ds_comodo']); ?></small>
                                </td>
                                <td><?php echo substr($registro['duracao'], 0, 5); ?> h</td>
                                <td style="color: var(--color-primary); font-weight:bold;">
                                    <?php echo number_format($kwh, 3, ',', '.'); ?>
                                </td>
                                <td>
                                    <a href="consumo_editar.php?id=<?php echo $registro['id_consumo']; ?>" title="Editar"><i class="fas fa-edit"></i></a>
                                    <form action="processa_consumo_delete.php" method="POST" style="display:inline;" onsubmit="return confirm('Excluir este registro?');">
                                        <input type="hidden" name="id_consumo" value="<?php echo $registro['id_consumo']; ?>">
                                        <button type="submit" style="background:none; border:none; color:red; cursor:pointer; margin-left:10px;"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        // Passa o array PHP para o JavaScript com segurança
        const todosEletros = <?php echo json_encode($todos_eletros); ?>;

        function filtrarEletros() {
            const imovelSelect = document.getElementById('select_imovel');
            const eletroSelect = document.getElementById('id_eletro');
            const imovelId = imovelSelect.value;

            // Limpa o select de eletros
            eletroSelect.innerHTML = '<option value="">Selecione...</option>';

            if (imovelId === "") {
                eletroSelect.disabled = true;
                eletroSelect.style.cursor = 'not-allowed';
                eletroSelect.style.backgroundColor = 'rgba(255,255,255,0.02)';
                eletroSelect.innerHTML = '<option value="">Selecione um imóvel primeiro...</option>';
                return;
            }

            // Filtra os eletros que pertencem ao imóvel selecionado
            const eletrosFiltrados = todosEletros.filter(eletro => eletro.id_imovel == imovelId);

            // Habilita o select
            eletroSelect.disabled = false;
            eletroSelect.style.cursor = 'pointer';
            eletroSelect.style.backgroundColor = ''; // Volta ao padrão do CSS

            if (eletrosFiltrados.length > 0) {
                eletrosFiltrados.forEach(eletro => {
                    const option = document.createElement('option');
                    option.value = eletro.id_eletro;
                    option.textContent = `${eletro.nm_eletro} (${eletro.ds_comodo})`;
                    eletroSelect.appendChild(option);
                });
            } else {
                eletroSelect.innerHTML = '<option value="">Nenhum eletrodoméstico neste imóvel</option>';
            }
        }
    </script>
</body>
</html>