<?php
/**
 * WhatsApp Webhook Endpoint
 * Handles incoming WhatsApp messages from Meta Business API
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../app/services/WhatsAppService.php';

// Webhook verification (GET request from Meta)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mode = $_GET['hub_mode'] ?? '';
    $token = $_GET['hub_verify_token'] ?? '';
    $challenge = $_GET['hub_challenge'] ?? '';
    
    if ($mode === 'subscribe' && $token === WHATSAPP_VERIFY_TOKEN) {
        // Respond with the challenge token
        echo $challenge;
        http_response_code(200);
    } else {
        http_response_code(403);
    }
    exit;
}

// Handle incoming webhook (POST request)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Log incoming webhook (sanitized)
    if (defined('LOGS_PATH')) {
        $logData = is_array($data) ? json_encode(['entries' => count($data['entry'] ?? [])]) : 'invalid';
        error_log("WhatsApp Webhook received: " . $logData);
    }
    
    if (!$data) {
        http_response_code(400);
        exit;
    }
    
    try {
        $whatsappService = new WhatsAppService();
        $whatsappService->processWebhook($data);
        
        http_response_code(200);
        echo json_encode(['status' => 'ok']);
    } catch (Exception $e) {
        error_log("WhatsApp Webhook Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    
    exit;
}

// Method not allowed
http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
