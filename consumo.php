<?php

session_start();
date_default_timezone_set('America/Sao_Paulo');

// --- 1. GUARDIÃO DE SEGURANÇA ---
if (!isset($_SESSION['usuario_logado']) || !isset($_SESSION['id_cliente'])) {
    header("Location: login.php");
    exit();
}

$id_cliente = $_SESSION['id_cliente'];
$nome_usuario_logado = $_SESSION['nome_usuario'] ?? 'Usuário';
$feedback_msg = null;
$feedback_tipo = null;

// Configuração do Banco de Dados
$servidor = "localhost";
$usuario_db = "root";
$senha_db = "p1Mc3d25*";
$banco = "mced";

try {
    $conexao = new PDO("mysql:host=$servidor;dbname=$banco;charset=utf8", $usuario_db, $senha_db);
    $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // --- 2. PROCESSAMENTO DO FORMULÁRIO (POST) ---
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['acao']) && $_POST['acao'] === 'registrar') {
        
        // Sanitização e Validação Básica
        $id_eletro = filter_input(INPUT_POST, 'id_eletro', FILTER_VALIDATE_INT);
        $data_reg = $_POST['data_reg'];
        $hora_inicio = $_POST['hora_inicio'];
        $hora_fim = $_POST['hora_fim'];

        // Validação Lógica
        if (!$id_eletro || empty($data_reg) || empty($hora_inicio) || empty($hora_fim)) {
            $feedback_msg = "Todos os campos são obrigatórios.";
            $feedback_tipo = "erro";
        } elseif (strtotime($hora_fim) <= strtotime($hora_inicio)) {
            $feedback_msg = "A hora final deve ser maior que a hora inicial.";
            $feedback_tipo = "erro";
        } else {
            // VERIFICAÇÃO DE PROPRIEDADE E BUSCA DE WATTS
            $sql_check = "SELECT e.watts 
                          FROM eletrodomesticos e 
                          JOIN comodos c ON e.id_comodo = c.id_comodo
                          JOIN imoveis i ON c.id_imovel = i.id_imovel
                          WHERE e.id_eletro = :id_eletro AND i.id_cliente = :id_cliente";
            $stmt_check = $conexao->prepare($sql_check);
            $stmt_check->execute([':id_eletro' => $id_eletro, ':id_cliente' => $id_cliente]);
            $dados_eletro = $stmt_check->fetch(PDO::FETCH_ASSOC);

            if ($dados_eletro) {
                // --- LÓGICA DE CÁLCULO DE CONSUMO (kWh) ---
                $watts = $dados_eletro['watts'];
                
                $inicio_ts = strtotime($hora_inicio);
                $fim_ts = strtotime($hora_fim);
                $segundos_uso = $fim_ts - $inicio_ts;
                $horas_uso = $segundos_uso / 3600;
                
                // Fórmula: (Watts * Horas) / 1000
                $consumo_kwh = ($watts * $horas_uso) / 1000;

                // --- INSERÇÃO NO BANCO DE DADOS ---
                $sql_insert = "INSERT INTO consumo (data_reg, hora_inicio, hora_fim, id_eletro, consumokwh) 
                               VALUES (:data, :inicio, :fim, :id_eletro, :consumokwh)";
                $stmt_insert = $conexao->prepare($sql_insert);
                $stmt_insert->execute([
                    ':data' => $data_reg,
                    ':inicio' => $hora_inicio,
                    ':fim' => $hora_fim,
                    ':id_eletro' => $id_eletro,
                    ':consumokwh' => $consumo_kwh
                ]);

                header("Location: consumo.php?sucesso=1");
                exit();
            } else {
                $feedback_msg = "Erro de permissão: Eletrodoméstico inválido.";
                $feedback_tipo = "erro";
            }
        }
    }

    // --- 3. CARREGAMENTO DE DADOS PARA A VIEW ---

    // Busca Imóveis
    $sql_imoveis = "SELECT id_imovel, fantasia FROM imoveis WHERE id_cliente = :id_cliente ORDER BY fantasia";
    $stmt_imoveis = $conexao->prepare($sql_imoveis);
    $stmt_imoveis->execute([':id_cliente' => $id_cliente]);
    $imoveis_disponiveis = $stmt_imoveis->fetchAll(PDO::FETCH_ASSOC);

    // Busca Eletrodomésticos
    $sql_eletros = "SELECT e.id_eletro, e.nm_eletro, c.ds_comodo, i.id_imovel 
                    FROM eletrodomesticos e
                    JOIN comodos c ON e.id_comodo = c.id_comodo
                    JOIN imoveis i ON c.id_imovel = i.id_imovel
                    WHERE i.id_cliente = :id_cliente
                    ORDER BY e.nm_eletro";
    $stmt_eletros = $conexao->prepare($sql_eletros);
    $stmt_eletros->execute([':id_cliente' => $id_cliente]);
    $todos_eletros = $stmt_eletros->fetchAll(PDO::FETCH_ASSOC);

    // Busca Histórico
    $sql_consumo = "SELECT 
                        cons.id_consumo, cons.data_reg, cons.hora_inicio, cons.hora_fim, cons.consumokwh,
                        e.nm_eletro, c.ds_comodo, i.fantasia,
                        TIMEDIFF(cons.hora_fim, cons.hora_inicio) as duracao
                    FROM consumo cons
                    JOIN eletrodomesticos e ON cons.id_eletro = e.id_eletro
                    JOIN comodos c ON e.id_comodo = c.id_comodo
                    JOIN imoveis i ON c.id_imovel = i.id_imovel
                    WHERE i.id_cliente = :id_cliente
                    ORDER BY cons.data_reg DESC, cons.hora_inicio DESC LIMIT 50";
    $stmt_consumo = $conexao->prepare($sql_consumo);
    $stmt_consumo->execute([':id_cliente' => $id_cliente]);
    $historico_consumo = $stmt_consumo->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Erro Crítico em consumo.php: " . $e->getMessage());
    $feedback_msg = "Erro ao conectar ao banco de dados. Tente novamente mais tarde.";
    $feedback_tipo = "erro";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <?php include 'includes/head.php'; ?>
    <style>
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; }
        .alert-erro { background: #fee2e2; color: #991b1b; border: 1px solid #f87171; }
        .alert-sucesso { background: #d1fae5; color: #065f46; border: 1px solid #34d399; }
    </style>
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
                    <!-- <li><a href="relatorios.php"><i class="fas fa-chart-bar"></i> Relatórios</a></li> -->
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
                <div class="alert alert-sucesso">Consumo registrado com sucesso!</div>
            <?php endif; ?>
            
            <?php if ($feedback_msg): ?>
                <div class="alert alert-<?php echo $feedback_tipo; ?>"><?php echo htmlspecialchars($feedback_msg); ?></div>
            <?php endif; ?>

            <div class="card" style="margin-bottom: 20px;">
                <h3>Registrar Novo Consumo</h3>
                <form action="" method="POST" class="form-grid" style="margin-top: 15px;">
                    <input type="hidden" name="acao" value="registrar">

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
                <?php if (empty($historico_consumo)): ?>
                    <p style="padding: 20px; text-align: center; color: #666;">Nenhum registro de consumo encontrado.</p>
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
                            <?php foreach ($historico_consumo as $registro): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($registro['data_reg'])); ?></td>
                                <td><small><?php echo htmlspecialchars($registro['fantasia']); ?></small></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($registro['nm_eletro']); ?></strong><br>
                                    <small style="color:#aaa;"><?php echo htmlspecialchars($registro['ds_comodo']); ?></small>
                                </td>
                                <td><?php echo substr($registro['duracao'], 0, 5); ?> h</td>
                                <td style="color: var(--color-primary); font-weight:bold;">
                                    <?php 
                                    // CORREÇÃO AQUI: Convertendo para float antes de formatar
                                    echo number_format((float)($registro['consumokwh'] ?? 0), 3, ',', '.'); 
                                    ?>
                                </td>
                                <td>
                                    <a href="consumo_editar.php?id=<?php echo $registro['id_consumo']; ?>" title="Editar"><i class="fas fa-edit"></i></a>
                                    
                                    <form action="processa_consumo_delete.php" method="POST" style="display:inline;" onsubmit="return confirm('Tem certeza que deseja excluir este registro de consumo?');">
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
        const todosEletros = <?php echo json_encode($todos_eletros); ?>;

        function filtrarEletros() {
            const imovelSelect = document.getElementById('select_imovel');
            const eletroSelect = document.getElementById('id_eletro');
            const imovelId = imovelSelect.value;

            eletroSelect.innerHTML = '<option value="">Selecione...</option>';

            if (imovelId === "") {
                eletroSelect.disabled = true;
                eletroSelect.style.cursor = 'not-allowed';
                eletroSelect.style.backgroundColor = 'rgba(255,255,255,0.02)';
                eletroSelect.innerHTML = '<option value="">Selecione um imóvel primeiro...</option>';
                return;
            }

            const eletrosFiltrados = todosEletros.filter(eletro => eletro.id_imovel == imovelId);

            eletroSelect.disabled = false;
            eletroSelect.style.cursor = 'pointer';
            eletroSelect.style.backgroundColor = '';

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