<?php
session_start();

// Verifica se o usuário está logado
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
                    <li><a href="view_imoveis.php" class="active"><i class="fas fa-building"></i> Imóveis</a></li>
                    <li><a href="comodos.php"><i class="fas fa-door-open"></i> Cômodos</a></li>
                    <li><a href="categorias.php"><i class="fas fa-tags"></i> Categorias</a></li>
                    <li><a href="eletro.php"><i class="fas fa-plug"></i> Eletrodomésticos</a></li>
                    <!-- <li><a href="relatorios.php"><i class="fas fa-chart-bar"></i> Relatórios</a></li> -->
                </ul>
            </nav>
            <div class="logout">
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a>
            </div>
        </aside>

        <main class="main-content">
            <header>
                <h1>Novo Imóvel</h1>
                <div class="user-info">
                    <span><?php echo htmlspecialchars($_SESSION['nome_usuario'] ?? 'Usuário'); ?></span>
                    <img src="img/perfil.png" alt="Avatar">
                </div>
            </header>

            <div class="card form-card">
                <form id="cadastroImovelForm" action="processa_imovel.php" method="POST">
                    
                    <div class="form-grid">
                        
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label for="fantasia">Nome do Imóvel</label>
                            <input type="text" id="fantasia" name="fantasia" placeholder="Ex: Casa do Lago, Apartamento Centro..." required>
                        </div>

                        <div class="form-group">
                            <label for="cep">CEP</label>
                            <input type="text" id="cep" name="cep" placeholder="00000-000" required maxlength="9">
                        </div>

                        <div class="form-group">
                            <label for="rua">Endereço</label>
                            <input type="text" id="rua" name="rua" placeholder="Rua, Avenida..." required>
                        </div>

                        <div class="form-group">
                            <label for="numero">Número</label>
                            <input type="text" id="numero" name="numero" placeholder="123" required>
                        </div>

                        <div class="form-group">
                            <label for="bairro">Bairro</label>
                            <input type="text" id="bairro" name="bairro" placeholder="Ex: Centro" required>
                        </div>

                        <div class="form-group">
                            <label for="cidade">Cidade</label>
                            <input type="text" id="cidade" name="cidade" placeholder="Ex: São Paulo" required>
                        </div>

                        <div class="form-group">
                            <label for="estado">Estado (UF)</label>
                            <select id="estado" name="estado" required>
                                <option value="" disabled selected>Selecione...</option>
                                <option value="AC">Acre</option>
                                <option value="AL">Alagoas</option>
                                <option value="AP">Amapá</option>
                                <option value="AM">Amazonas</option>
                                <option value="BA">Bahia</option>
                                <option value="CE">Ceará</option>
                                <option value="DF">Distrito Federal</option>
                                <option value="ES">Espírito Santo</option>
                                <option value="GO">Goiás</option>
                                <option value="MA">Maranhão</option>
                                <option value="MT">Mato Grosso</option>
                                <option value="MS">Mato Grosso do Sul</option>
                                <option value="MG">Minas Gerais</option>
                                <option value="PA">Pará</option>
                                <option value="PB">Paraíba</option>
                                <option value="PR">Paraná</option>
                                <option value="PE">Pernambuco</option>
                                <option value="PI">Piauí</option>
                                <option value="RJ">Rio de Janeiro</option>
                                <option value="RN">Rio Grande do Norte</option>
                                <option value="RS">Rio Grande do Sul</option>
                                <option value="RO">Rondônia</option>
                                <option value="RR">Roraima</option>
                                <option value="SC">Santa Catarina</option>
                                <option value="SP">São Paulo</option>
                                <option value="SE">Sergipe</option>
                                <option value="TO">Tocantins</option>
                                </select>
                        </div>

                    </div>

                    <div style="margin-top: 30px; display: flex; gap: 15px; align-items: center;">
                        <button type="submit" class="btn">
                            <i class="fas fa-save"></i> Cadastrar Imóvel
                        </button>
                        <a href="view_imoveis.php" style="color: var(--color-text-muted); font-size: 0.9rem;">
                            Cancelar e Voltar
                        </a>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        document.getElementById('cep').addEventListener('input', function (e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 5) {
                value = value.replace(/^(\d{5})(\d)/, '$1-$2');
            }
            e.target.value = value;
        });
    </script>
</body>
</html>