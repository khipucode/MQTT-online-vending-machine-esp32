<?php
// 1. PREPARAÇÃO DO AMBIENTE
ob_start();
session_start();
header('Content-Type: application/json');

// Desativa erros visuais para não quebrar o JSON
ini_set('display_errors', 0); 
error_reporting(E_ALL);

// 2. CARREGA DEPENDÊNCIAS
if(file_exists('../app/db.php')) require_once '../app/db.php';
if(file_exists('../app/functions.php')) require_once '../app/functions.php';
// Carrega a função de envio HTTP
if(file_exists('../app/thingspeak.php')) require_once '../app/thingspeak.php';

$response = ['success' => false, 'message' => 'Erro desconhecido'];

try {
    // 3. SEGURANÇA
    if (!isset($_SESSION['admin_logged'])) throw new Exception('Sessão expirada.');
    if (!isset($pdo)) throw new Exception('Erro de conexão com banco de dados.');

    $orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
    $action  = isset($_POST['action']) ? $_POST['action'] : '';

    if (!$orderId) throw new Exception('ID do pedido inválido.');

    // --- AÇÃO 1: CONFIRMAR PAGAMENTO ---
    if ($action === 'confirm_payment') {
        $stmt = $pdo->prepare("UPDATE orders SET status='PAGO', paid_at=NOW() WHERE id=? AND status='PENDENTE'");
        $stmt->execute([$orderId]);
        
        $pdo->prepare("UPDATE products SET stock=stock-1 WHERE id=(SELECT product_id FROM orders WHERE id=?) AND stock>0")->execute([$orderId]);
        
        $response = ['success' => true, 'message' => 'Pagamento Confirmado!'];
    }

    // --- AÇÃO 2: LIBERAR PRODUTO (ENVIA PREÇO NO FIELD 2) ---
    elseif ($action === 'release_product') {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();

        if ($order && strtoupper(trim($order['status'])) === 'PAGO') {
            
            $settings = getSettings($pdo);
            $writeKey = $settings['thingspeak_write_key'];
            
            // DADOS PARA O THINGSPEAK
            $prodId = $order['product_id'];        // Field 1 (Qual motor girar)
            $preco  = $order['price_snapshot'];    // Field 2 (Valor da venda)

            if (!function_exists('sendToThingSpeak')) {
                throw new Exception('Função HTTP não encontrada.');
            }

            // ENVIAR: (Chave, Campo1, Campo2)
            // Agora o Campo 2 recebe o $preco
            $resultado = sendToThingSpeak($writeKey, $prodId, $preco);

            if ($resultado === true) {
                // SUCESSO
                $pdo->prepare("UPDATE orders SET released_at=NOW() WHERE id=?")->execute([$orderId]);
                
                $response = [
                    'success' => true, 
                    'message' => 'Liberado! (Preço R$ ' . $preco . ' enviado)'
                ];
            } else {
                // FALHA
                $response = [
                    'success' => false, 
                    'message' => 'Erro ThingSpeak: ' . $resultado
                ];
            }

        } else {
            throw new Exception('O pedido precisa estar PAGO.');
        }
    } 
    else {
        throw new Exception('Ação inválida.');
    }

} catch (Exception $e) {
    if(isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    $response['message'] = $e->getMessage();
}

ob_clean();
echo json_encode($response);
exit;