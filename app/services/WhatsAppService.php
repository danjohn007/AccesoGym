<?php
/**
 * WhatsApp Service
 * Handles WhatsApp Business API integration
 */

class WhatsAppService {
    private $phoneId;
    private $token;
    private $apiUrl;
    
    public function __construct() {
        $this->phoneId = WHATSAPP_PHONE_ID;
        $this->token = WHATSAPP_TOKEN;
        $this->apiUrl = WHATSAPP_API_URL . $this->phoneId . '/messages';
    }
    
    /**
     * Send text message
     */
    public function sendMessage($to, $message) {
        if (!WHATSAPP_ENABLED) {
            return ['success' => false, 'message' => 'WhatsApp API no habilitada'];
        }
        
        $data = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'text',
            'text' => [
                'body' => $message
            ]
        ];
        
        $response = $this->makeRequest($data);
        
        // Log message
        $this->logMessage($to, 'saliente', $message);
        
        return $response;
    }
    
    /**
     * Send template message
     */
    public function sendTemplate($to, $templateName, $params = []) {
        if (!WHATSAPP_ENABLED) {
            return ['success' => false, 'message' => 'WhatsApp API no habilitada'];
        }
        
        $components = [];
        if (!empty($params)) {
            $components[] = [
                'type' => 'body',
                'parameters' => $params
            ];
        }
        
        $data = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => 'es_MX'],
                'components' => $components
            ]
        ];
        
        return $this->makeRequest($data);
    }
    
    /**
     * Process incoming webhook
     */
    public function processWebhook($data) {
        if (!isset($data['entry'][0]['changes'][0]['value']['messages'][0])) {
            return false;
        }
        
        $message = $data['entry'][0]['changes'][0]['value']['messages'][0];
        $from = $message['from'];
        $text = $message['text']['body'] ?? '';
        
        // Log incoming message
        $this->logMessage($from, 'entrante', $text);
        
        // Process command
        return $this->processCommand($from, $text);
    }
    
    /**
     * Process command from user
     */
    private function processCommand($from, $text) {
        $text = strtolower(trim($text));
        
        require_once __DIR__ . '/../models/Socio.php';
        $socioModel = new Socio();
        
        // Find socio by phone
        $socio = $socioModel->findByPhone($from);
        
        // Welcome message
        if (in_array($text, ['hola', 'hi', 'hello', 'buenos dias', 'buenas tardes'])) {
            $nombre = $socio ? $socio['nombre'] : 'Usuario';
            $response = "¡Hola {$nombre}! 👋\n\n";
            $response .= "Soy el asistente de AccessGYM. ¿En qué puedo ayudarte?\n\n";
            $response .= "Comandos disponibles:\n";
            $response .= "• 'Abrir puerta' - Solicitar acceso\n";
            $response .= "• 'Mi membresía' - Ver estado\n";
            $response .= "• 'Renovar' - Renovar membresía\n";
            $response .= "• 'Ayuda' - Información de contacto\n";
            
            $this->sendMessage($from, $response);
            return true;
        }
        
        // Open door command
        if (strpos($text, 'abrir') !== false || strpos($text, 'puerta') !== false) {
            if (!$socio) {
                $this->sendMessage($from, "No encontramos tu registro. Por favor contacta a recepción.");
                return true;
            }
            
            $access = $socioModel->canAccess($socio['id']);
            
            if ($access['allowed']) {
                // Try to open door
                require_once __DIR__ . '/ShellyService.php';
                require_once __DIR__ . '/../models/DispositivoShelly.php';
                require_once __DIR__ . '/../models/Acceso.php';
                
                $shellyService = new ShellyService();
                $dispositivoModel = new DispositivoShelly();
                $accesoModel = new Acceso();
                
                // Get first available device for this branch
                $dispositivos = $dispositivoModel->getBySucursal($socio['sucursal_id']);
                
                if (!empty($dispositivos)) {
                    $dispositivo = $dispositivos[0];
                    $result = $shellyService->openDoor($dispositivo['device_id'], $dispositivo['tiempo_apertura']);
                    
                    if ($result['success']) {
                        // Register access
                        $accesoModel->registrar(
                            $socio['id'],
                            $dispositivo['id'],
                            $socio['sucursal_id'],
                            'whatsapp',
                            'permitido',
                            'Acceso vía WhatsApp'
                        );
                        
                        $this->sendMessage($from, "✅ ¡Puerta abierta! Bienvenido al gimnasio.");
                    } else {
                        $this->sendMessage($from, "❌ Error al abrir la puerta. Por favor contacta a recepción.");
                    }
                } else {
                    $this->sendMessage($from, "No hay dispositivos disponibles. Contacta a recepción.");
                }
            } else {
                $this->sendMessage($from, "❌ Acceso denegado: " . $access['reason']);
            }
            
            return true;
        }
        
        // Membership status
        if (strpos($text, 'membresia') !== false || strpos($text, 'estado') !== false) {
            if (!$socio) {
                $this->sendMessage($from, "No encontramos tu registro. Por favor contacta a recepción.");
                return true;
            }
            
            $socioData = $socioModel->getWithMembresia($socio['id']);
            
            $response = "📋 *Estado de tu Membresía*\n\n";
            $response .= "Nombre: {$socioData['nombre']} {$socioData['apellido']}\n";
            $response .= "Código: {$socioData['codigo']}\n";
            $response .= "Tipo: {$socioData['tipo_membresia_nombre']}\n";
            $response .= "Estado: " . ucfirst($socioData['estado']) . "\n";
            $response .= "Vence: " . formatDate($socioData['fecha_vencimiento']) . "\n";
            
            $this->sendMessage($from, $response);
            return true;
        }
        
        // Renew membership
        if (strpos($text, 'renovar') !== false) {
            if (!$socio) {
                $this->sendMessage($from, "No encontramos tu registro. Por favor contacta a recepción.");
                return true;
            }
            
            $response = "💳 *Renovación de Membresía*\n\n";
            $response .= "Para renovar tu membresía, puedes:\n\n";
            $response .= "1. Visitar nuestras instalaciones\n";
            $response .= "2. Realizar pago en línea: " . APP_URL . "/renovar.php?codigo=" . $socio['codigo'] . "\n";
            $response .= "3. Transferencia bancaria (contacta para detalles)\n";
            
            $this->sendMessage($from, $response);
            return true;
        }
        
        // Help
        if (strpos($text, 'ayuda') !== false || strpos($text, 'help') !== false) {
            $response = "ℹ️ *Información de Contacto*\n\n";
            $response .= "📞 Teléfono: (555) 123-4567\n";
            $response .= "📧 Email: contacto@accessgym.com\n";
            $response .= "🕐 Horario: Lunes a Viernes 6:00 - 22:00\n";
            $response .= "         Sábado y Domingo 8:00 - 20:00\n\n";
            $response .= "Escribe 'Hola' para ver los comandos disponibles.";
            
            $this->sendMessage($from, $response);
            return true;
        }
        
        // Default response
        $this->sendMessage($from, "No entiendo ese comando. Escribe 'Ayuda' para ver los comandos disponibles.");
        return true;
    }
    
    /**
     * Log WhatsApp message
     */
    private function logMessage($telefono, $tipo, $mensaje, $comando = null) {
        try {
            $db = Database::getInstance()->getConnection();
            
            // Try to find socio
            require_once __DIR__ . '/../models/Socio.php';
            $socioModel = new Socio();
            $socio = $socioModel->findByPhone($telefono);
            $socioId = $socio ? $socio['id'] : null;
            
            $sql = "INSERT INTO mensajes_whatsapp (telefono, socio_id, tipo, mensaje, comando) 
                    VALUES (?, ?, ?, ?, ?)";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$telefono, $socioId, $tipo, $mensaje, $comando]);
        } catch (Exception $e) {
            error_log("Error logging WhatsApp message: " . $e->getMessage());
        }
    }
    
    /**
     * Make HTTP request to WhatsApp API
     */
    private function makeRequest($data) {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->token,
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($error) {
            error_log("WhatsApp API Error: " . $error);
            return ['success' => false, 'message' => $error];
        }
        
        $result = json_decode($response, true);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'data' => $result];
        }
        
        return ['success' => false, 'message' => 'HTTP Error ' . $httpCode, 'data' => $result];
    }
}
