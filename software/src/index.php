<?php
require_once 'app/db.php';
require_once 'app/pix.php';
require_once 'app/functions.php';

// Busca produtos ativos e com estoque
$products = $pdo->query("SELECT * FROM products ORDER BY id ASC")->fetchAll();
$settings = getSettings($pdo);

$orderData = null;

// PROCESSAMENTO DO FORMULÁRIO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_order') {
    $buyer = trim($_POST['buyer_name']);
    $prodId = (int)$_POST['product_id'];
    
    // Valida se o produto existe, tem estoque e está ativo
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND stock > 0 AND active = 1");
    $stmt->execute([$prodId]);
    $product = $stmt->fetch();

    if ($product && !empty($buyer)) {
        // 1. Gera um ID de transação único (txid)
        $txid = substr(md5(uniqid(rand(), true)), 0, 20);
        
        // 2. Gera o Payload (Texto do Copia e Cola)
        $payload = PixPayload::generate(
            $settings['pix_key'], 
            $settings['pix_receiver_name'], 
            $settings['pix_city'], 
            $product['price'], 
            $txid
        );
        
        // 3. Salva o Pedido no Banco de Dados (Status PENDENTE)
        $sql = "INSERT INTO orders (buyer_name, product_id, price_snapshot, pix_payload, created_at) VALUES (?, ?, ?, ?, NOW())";
        $insert = $pdo->prepare($sql);
        $insert->execute([$buyer, $product['id'], $product['price'], $payload]);
        $orderId = $pdo->lastInsertId();

        // 4. Prepara o Link do WhatsApp (Opcional)
        $waLink = null;
        if (!empty($settings['whatsapp_number'])) {
            $phone = preg_replace('/[^0-9]/', '', $settings['whatsapp_number']);
            $priceFmt = number_format($product['price'], 2, ',', '.');
            $msg = "Olá! Pagamento feito referente ao Pedido #{$orderId} ({$product['name']}) no valor de R$ {$priceFmt}. Segue comprovante:";
            $waLink = "https://wa.me/{$phone}?text=" . urlencode($msg);
        }

        // 5. Prepara os dados para exibir no Modal
        $orderData = [
            'id' => $orderId,
            'qr_image' => "https://api.qrserver.com/v1/create-qr-code/?size=300x300&margin=10&data=" . urlencode($payload),
            'payload' => $payload,
            'price' => $product['price'],
            'wa_link' => $waLink
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>VendingMachine Fiap/CpQD</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="container">
    <h1>VendingMachine Fiap/CpQD</h1>

    <form method="POST" id="shopForm">
        <input type="hidden" name="action" value="create_order">
        
        <div class="grid">
            <?php 
            // Inicia o contador de imagens em 101
            $imgIndex = 101; 
            ?>
            <?php foreach($products as $p): ?>
                <?php $disabled = ($p['stock'] <= 0 || !$p['active']) ? 'disabled' : ''; ?>
                
                <label class="card <?php echo $disabled; ?>" onclick="selectCard(this)">
                    <?php if($p['stock'] <= 0): ?>
                        <div class="tag-stock" style="background:var(--accent);">ESGOTADO</div>
                    <?php endif; ?>
                    
                    <img src="img/<?php echo $imgIndex; ?>.png" alt="<?php echo htmlspecialchars($p['name']); ?>">
                    
                    <h3><?php echo htmlspecialchars($p['name']); ?></h3>
                    <div class="price">R$ <?php echo number_format($p['price'], 2, ',', '.'); ?></div>
                    
                    <input type="radio" name="product_id" value="<?php echo $p['id']; ?>" <?php echo $disabled ? 'disabled' : ''; ?> required>
                </label>
                
                <?php 
                // Aumenta o contador para a próxima imagem (102, 103, etc)
                $imgIndex++; 
                ?>
            <?php endforeach; ?>
        </div>

        <div class="checkout-bar">
            <input type="text" name="buyer_name" placeholder="Seu Nome Completo" required autocomplete="off">
            <button type="submit">Gerar Pagamento Pix</button>
        </div>
    </form>
</div>

<?php if ($orderData): ?>
<div class="modal" style="display:flex;">
    <div class="modal-content">
        <h2 style="color:var(--primary); margin-top:0;">Pedido #<?php echo $orderData['id']; ?></h2>
        <p style="font-size:1.2rem; font-weight:bold; margin:5px 0;">
            Total: R$ <?php echo number_format($orderData['price'], 2, ',', '.'); ?>
        </p>
        
        <div class="qr-container" style="background:#f1f5f9; padding:10px; border-radius:8px; display:inline-block;">
            <img src="<?php echo $orderData['qr_image']; ?>" alt="QR Code Pix" style="display:block; max-width:100%; height:auto;">
        </div>
        
        <div style="margin-top:15px; text-align:left;">
            <p style="font-size:0.85rem; margin-bottom:5px; color:#666; font-weight:bold;">Pix Copia e Cola:</p>
            <div style="display:flex; gap:8px;">
                <input type="text" value="<?php echo htmlspecialchars($orderData['payload']); ?>" id="pixCopy" readonly style="font-size:11px; background:#f8fafc; color:#333;">
                <button type="button" onclick="copyPix()" class="secondary" style="width:auto; padding:10px 15px; font-size:0.8rem;">Copiar</button>
            </div>
        </div>

        <?php if ($orderData['wa_link']): ?>
            <a href="<?php echo $orderData['wa_link']; ?>" target="_blank" class="btn-whatsapp-action">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="white"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                Pagamento feito, enviar comprovante
            </a>
        <?php endif; ?>
        
        <p style="margin-top:15px;">
            <a href="index.php" style="color:#64748b; text-decoration:none; font-size:0.9rem;">Fechar Janela</a>
        </p>
    </div>
</div>
<?php endif; ?>

<script>
// Função para marcar o card visualmente
function selectCard(card) {
    if(card.classList.contains('disabled')) return;
    
    // Remove seleção dos outros
    document.querySelectorAll('.card').forEach(c => c.classList.remove('selected'));
    
    // Seleciona o atual
    card.classList.add('selected');
    card.querySelector('input').checked = true;
}

// Função para copiar o código Pix
function copyPix() {
    var copyText = document.getElementById("pixCopy");
    
    // Seleciona o texto (mobile friendly)
    copyText.select();
    copyText.setSelectionRange(0, 99999); 
    
    try {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(copyText.value).then(function() {
                alert("Código Pix copiado!");
            }, function() {
                document.execCommand("copy"); // Fallback
                alert("Código Pix copiado!");
            });
        } else {
            document.execCommand("copy"); // Fallback antigo
            alert("Código Pix copiado!");
        }
    } catch (err) {
        alert("Erro ao copiar, tente manualmente.");
    }
}
</script>

</body>
</html>