<?php
session_start();
require_once '../app/db.php';
require_once '../app/functions.php';

if (!isset($_SESSION['admin_logged'])) { header('Location: login.php'); exit; }

$msg = "";

// 1. SALVAR CONFIGURAÇÕES
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $stmt = $pdo->prepare("UPDATE settings SET pix_key=?, pix_receiver_name=?, pix_city=?, whatsapp_number=?, thingspeak_write_key=?, thingspeak_channel_id=? WHERE id=1");
    $stmt->execute([
        $_POST['pix_key'], $_POST['pix_name'], $_POST['pix_city'], $_POST['whatsapp'],
        $_POST['ts_key'], $_POST['ts_channel']
    ]);
    $msg = "Configurações atualizadas!";
}

// 2. REABASTECER PRODUTOS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restock_action'])) {
    if ($_POST['restock_action'] === 'single') {
        $pid = (int)$_POST['product_id'];
        $pdo->prepare("UPDATE products SET stock = 10 WHERE id = ?")->execute([$pid]);
        $msg = "Estoque do produto atualizado para 10 unidades!";
    } elseif ($_POST['restock_action'] === 'all') {
        $pdo->query("UPDATE products SET stock = 10");
        $msg = "Todos os produtos foram reabastecidos para 10 unidades!";
    }
}

$settings = getSettings($pdo);
$totalSales = $pdo->query("SELECT SUM(price_snapshot) FROM orders WHERE status = 'PAGO'")->fetchColumn();

// Pedidos aguardando liberação
$orders = $pdo->query("SELECT o.*, p.name as prod_name FROM orders o JOIN products p ON o.product_id = p.id WHERE o.released_at IS NULL OR o.released_at = '' ORDER BY o.id DESC LIMIT 50")->fetchAll();

$allProducts = $pdo->query("SELECT * FROM products ORDER BY id ASC")->fetchAll();

// 3. DADOS DO DASHBOARD FINANCEIRO
$dashWeek = $pdo->query("SELECT SUM(price_snapshot) FROM orders WHERE status = 'PAGO' AND YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)")->fetchColumn() ?: 0;
$dashMonth = $pdo->query("SELECT SUM(price_snapshot) FROM orders WHERE status = 'PAGO' AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())")->fetchColumn() ?: 0;

// Gráfico 1: Últimos 7 dias (Ordenado do mais antigo para o mais novo para o gráfico)
$dash7Days = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%d/%m') as data_venda, SUM(price_snapshot) as total 
    FROM orders 
    WHERE status = 'PAGO' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
    GROUP BY DATE(created_at) 
    ORDER BY DATE(created_at) ASC
")->fetchAll();

$labels7Days = [];
$data7Days = [];
foreach($dash7Days as $day) {
    $labels7Days[] = $day['data_venda'];
    $data7Days[] = (float)$day['total'];
}

// Gráfico 2: Produtos mais vendidos
$topProducts = $pdo->query("
    SELECT p.name, COUNT(o.id) as qtd 
    FROM orders o 
    JOIN products p ON o.product_id = p.id 
    WHERE o.status = 'PAGO' 
    GROUP BY p.id 
    ORDER BY qtd DESC 
    LIMIT 5
")->fetchAll();

$labelsTopProd = [];
$dataTopProd = [];
foreach($topProducts as $prod) {
    $labelsTopProd[] = $prod['name'];
    $dataTopProd[] = (int)$prod['qtd'];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Vending</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root { --primary: #2563eb; --bg: #f8fafc; --surface: #ffffff; --text: #1e293b; --border: #e2e8f0; --success: #10b981; --warning: #f59e0b; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); margin: 0; padding-bottom: 50px; }
        * { box-sizing: border-box; }

        .navbar { background: var(--surface); height: 60px; display: flex; align-items: center; justify-content: space-between; padding: 0 20px; border-bottom: 1px solid var(--border); }
        .brand { font-weight: 700; color: var(--primary); font-size: 1.1rem; }
        .btn-logout { color: #ef4444; text-decoration: none; font-size: 0.9rem; font-weight: 600; }

        .container { max-width: 1000px; margin: 30px auto; padding: 0 15px; }
        .alert { background: #dcfce7; color: #166534; padding: 10px; border-radius: 6px; margin-bottom: 20px; text-align: center; font-weight: 500; }

        details, .card { background: var(--surface); border: 1px solid var(--border); border-radius: 8px; margin-bottom: 20px; }
        summary { padding: 15px; cursor: pointer; font-weight: 600; list-style: none; }
        .config-body { padding: 20px; display: grid; gap: 15px; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); border-top: 1px solid var(--border); }
        input { width: 100%; padding: 8px; border: 1px solid var(--border); border-radius: 4px; margin-top: 5px; }
        label { font-size: 0.85rem; font-weight: 600; color: #64748b; }
        .btn-save { background: var(--text); color: white; border: none; padding: 10px; border-radius: 4px; cursor: pointer; width: 100%; margin-top: 10px; }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px 15px; background: #f1f5f9; font-size: 0.75rem; text-transform: uppercase; color: #64748b; }
        td { padding: 12px 15px; border-bottom: 1px solid var(--border); font-size: 0.9rem; }
        
        button { border: none; padding: 6px 12px; border-radius: 4px; font-weight: 600; cursor: pointer; font-size: 0.8rem; transition: 0.2s; }
        .btn-pay { background: var(--warning); color: white; }
        .btn-release { background: var(--success); color: white; display: inline-flex; align-items: center; gap: 5px; }
        .btn-release:disabled { background: #cbd5e1; cursor: not-allowed; }
        
        .badge { padding: 3px 8px; border-radius: 10px; font-size: 0.75rem; font-weight: 700; }
        .st-pago { background: #dcfce7; color: #166534; }
        .st-pend { background: #ffedd5; color: #9a3412; }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="brand">🍿 Admin Vending</div>
        <div style="display:flex; gap:15px; align-items:center;">
            <span style="font-size:0.9rem; font-weight:600; color:#059669;">
                R$ <?php echo number_format($totalSales ?: 0, 2, ',', '.'); ?>
            </span>
            <a href="logout.php" class="btn-logout">Sair</a>
        </div>
    </nav>

    <div class="container">
        <?php if($msg): ?><div class="alert"><?php echo $msg; ?></div><?php endif; ?>

        <details>
            <summary>⚙️ Configurações (Pix e ThingSpeak)</summary>
            <form method="POST" class="config-body">
                <input type="hidden" name="save_settings" value="1">
                <div><label>Chave Pix</label><input name="pix_key" value="<?php echo htmlspecialchars($settings['pix_key']); ?>"></div>
                <div><label>Nome Recebedor</label><input name="pix_name" value="<?php echo htmlspecialchars($settings['pix_receiver_name']); ?>"></div>
                <div><label>Cidade</label><input name="pix_city" value="<?php echo htmlspecialchars($settings['pix_city']); ?>"></div>
                
                <div style="grid-column: 1 / -1; border-top: 1px solid #eee; margin-top: 10px; padding-top: 10px;">
                    <label style="color:#2563eb;">ThingSpeak Write API Key (Obrigatório)</label>
                    <input name="ts_key" value="<?php echo htmlspecialchars($settings['thingspeak_write_key']); ?>" placeholder="Cole a Write Key aqui">
                </div>
                <div style="grid-column: 1 / -1;">
                    <label>ThingSpeak Channel ID</label>
                    <input name="ts_channel" value="<?php echo htmlspecialchars($settings['thingspeak_channel_id'] ?? ''); ?>">
                </div>
                <button type="submit" class="btn-save">Salvar Configurações</button>
            </form>
        </details>

        <details>
            <summary>🔄 Reabastecer Produtos</summary>
            <div class="config-body" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                <div style="grid-column: 1 / -1; margin-bottom: 10px;">
                    <form method="POST" onsubmit="return confirm('Tem certeza que deseja resetar TODOS os produtos para 10 unidades?');">
                        <input type="hidden" name="restock_action" value="all">
                        <button type="submit" style="background: var(--primary); color: white; padding: 12px; width: 100%; font-size: 0.9rem;">
                            🔄 Resetar TODOS os produtos para 10 unidades
                        </button>
                    </form>
                </div>

                <?php foreach($allProducts as $p): ?>
                <div style="border: 1px solid var(--border); padding: 12px; border-radius: 6px; text-align: center; background: #f8fafc;">
                    <div style="font-weight: 600; margin-bottom: 5px; font-size: 0.95rem;"><?php echo htmlspecialchars($p['name']); ?></div>
                    <div style="font-size: 0.8rem; color: #64748b; margin-bottom: 10px;">
                        Estoque atual: <strong style="color: <?php echo $p['stock'] <= 0 ? '#ef4444' : '#10b981'; ?>;"><?php echo $p['stock']; ?></strong>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="restock_action" value="single">
                        <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                        <button type="submit" style="background: var(--success); color: white; width: 100%; padding: 8px;">Repor (10 Unid)</button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        </details>

        <div class="card">
            <div style="padding:15px; font-weight:bold; border-bottom:1px solid #eee;">📦 Pedidos Aguardando Liberação</div>
            <div style="overflow-x:auto;">
                <table>
                    <thead><tr><th>ID</th><th>Produto</th><th>Valor</th><th>Status</th><th style="text-align:right">Ação</th></tr></thead>
                    <tbody>
                        <?php if(count($orders) > 0): ?>
                            <?php foreach($orders as $o): $st = strtoupper(trim($o['status'])); ?>
                            <tr id="row-<?php echo $o['id']; ?>">
                                <td>#<?php echo $o['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($o['prod_name']); ?></strong><br>
                                    <span style="font-size:0.8rem; color:#666;"><?php echo htmlspecialchars($o['buyer_name']); ?></span>
                                </td>
                                <td>R$ <?php echo number_format($o['price_snapshot'], 2, ',', '.'); ?></td>
                                <td><span class="badge <?php echo $st=='PAGO'?'st-pago':'st-pend'; ?>"><?php echo $st; ?></span></td>
                                <td style="text-align:right;">
                                    <?php if($st === 'PENDENTE'): ?>
                                        <button class="btn-pay" onclick="confirmar(<?php echo $o['id']; ?>)">💰 Confirmar</button>
                                    <?php elseif($st === 'PAGO'): ?>
                                        <button class="btn-release" id="btn-<?php echo $o['id']; ?>" onclick="liberarProdutoHttp(<?php echo $o['id']; ?>, this)">🚀 Liberar</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align:center; padding:20px; color:#94a3b8;">Nenhum pedido pendente.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div style="padding:15px; font-weight:bold; border-bottom:1px solid #eee;">📊 Dashboard de Vendas</div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; padding: 15px;">
                <div style="background: #f1f5f9; padding: 15px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 0.85rem; color: #64748b; font-weight: 600;">Acumulado na Semana</div>
                    <div style="font-size: 1.5rem; font-weight: 700; color: var(--primary);">R$ <?php echo number_format($dashWeek, 2, ',', '.'); ?></div>
                </div>
                <div style="background: #f1f5f9; padding: 15px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 0.85rem; color: #64748b; font-weight: 600;">Acumulado no Mês</div>
                    <div style="font-size: 1.5rem; font-weight: 700; color: var(--primary);">R$ <?php echo number_format($dashMonth, 2, ',', '.'); ?></div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; padding: 20px;">
                <div>
                    <h4 style="margin: 0 0 15px 0; text-align: center; color: #334155;">Receita Diária (Últimos 7 dias)</h4>
                    <canvas id="chart7Days"></canvas>
                </div>
                <div>
                    <h4 style="margin: 0 0 15px 0; text-align: center; color: #334155;">Produtos Mais Vendidos</h4>
                    <canvas id="chartTopProducts"></canvas>
                </div>
            </div>
        </div>

    </div>

   <script>
    // --- LÓGICA DE BOTÕES E API ---
    function confirmar(id) {
        if(!confirm('O dinheiro caiu na conta?')) return;
        callApi('confirm_payment', id);
    }

    function liberarProdutoHttp(orderId, btn) {
        if(!confirm('Enviar comando para a máquina?')) return;
        travarBotao(btn);
        callApi('release_product', orderId);
    }

    function callApi(action, id) {
        let fd = new FormData();
        fd.append('action', action);
        fd.append('order_id', id);

        fetch('api.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if(data.success) {
                if(action === 'confirm_payment') {
                    location.reload();
                } else {
                    alert("✅ Comando enviado com sucesso!");
                    let row = document.getElementById('row-' + id);
                    if(row) row.style.display = 'none';
                }
            } else {
                alert("❌ Erro: " + data.message);
            }
        })
        .catch(e => {
            alert("Erro de conexão com o servidor.");
        });
    }

    function travarBotao(btn) {
        btn.disabled = true;
        let originalText = "🚀 Liberar";
        let sec = 15;
        btn.innerHTML = `⏳ ${sec}s`;
        btn.style.background = "#94a3b8";
        
        let timer = setInterval(() => {
            sec--;
            btn.innerHTML = `⏳ ${sec}s`;
            if(sec <= 0) {
                clearInterval(timer);
                btn.innerHTML = originalText;
                btn.disabled = false;
                btn.style.background = "#10b981";
            }
        }, 1000);
    }

    // --- LÓGICA DOS GRÁFICOS (Chart.js) ---
    
    // Gráfico 1: Últimos 7 Dias (Receita)
    const ctx7Days = document.getElementById('chart7Days').getContext('2d');
    new Chart(ctx7Days, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($labels7Days); ?>,
            datasets: [{
                label: 'Receita (R$)',
                data: <?php echo json_encode($data7Days); ?>,
                backgroundColor: '#2563eb', // Azul primário
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true }
            },
            plugins: {
                legend: { display: false } // Esconde a legenda para ficar mais limpo
            }
        }
    });

    // Gráfico 2: Produtos Mais Vendidos (Quantidade)
    const ctxTopProducts = document.getElementById('chartTopProducts').getContext('2d');
    new Chart(ctxTopProducts, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($labelsTopProd); ?>,
            datasets: [{
                label: 'Unidades Vendidas',
                data: <?php echo json_encode($dataTopProd); ?>,
                backgroundColor: '#10b981', // Verde sucesso
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { 
                    beginAtZero: true,
                    ticks: { stepSize: 1 } // Garante que o eixo Y mostre números inteiros (1, 2, 3...)
                }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
    </script>
</body>
</html>