<?php
/**
 * HikvisionService
 * Service for interacting with Hikvision devices via official API
 */

class HikvisionService {
    
    /**
     * Open door via Hikvision API
     */
    public function openDoor($ip, $port, $username, $password, $doorNumber = 1) {
        try {
            // Hikvision ISAPI endpoint for door control
            $url = "http://{$ip}:{$port}/ISAPI/AccessControl/RemoteControl/door/{$doorNumber}";
            
            // XML payload for door open command
            $xml = '<?xml version="1.0" encoding="UTF-8"?>' .
                   '<RemoteControlDoor>' .
                   '<cmd>open</cmd>' .
                   '</RemoteControlDoor>';
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
            curl_setopt($ch, CURLOPT_USERPWD, "{$username}:{$password}");
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/xml',
                'Content-Length: ' . strlen($xml)
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                return [
                    'success' => false,
                    'message' => 'Error de conexión: ' . $error
                ];
            }
            
            if ($httpCode >= 200 && $httpCode < 300) {
                return [
                    'success' => true,
                    'message' => 'Puerta abierta correctamente'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error HTTP ' . $httpCode
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get device status
     */
    public function getDeviceStatus($ip, $port, $username, $password) {
        try {
            $url = "http://{$ip}:{$port}/ISAPI/System/status";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
            curl_setopt($ch, CURLOPT_USERPWD, "{$username}:{$password}");
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode >= 200 && $httpCode < 300) {
                return [
                    'success' => true,
                    'status' => 'online',
                    'message' => 'Dispositivo en línea'
                ];
            } else {
                return [
                    'success' => false,
                    'status' => 'offline',
                    'message' => 'Dispositivo fuera de línea'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Update all device statuses
     */
    public function updateAllDeviceStatuses() {
        require_once __DIR__ . '/../models/DispositivoHikvision.php';
        
        $dispositivoModel = new DispositivoHikvision();
        $dispositivos = $dispositivoModel->getActive();
        
        $results = [];
        foreach ($dispositivos as $dispositivo) {
            $status = $this->getDeviceStatus(
                $dispositivo['ip'],
                $dispositivo['puerto'],
                $dispositivo['usuario'],
                $dispositivo['password']
            );
            
            $dispositivoModel->updateStatus($dispositivo['id'], $status['status']);
            $results[] = [
                'id' => $dispositivo['id'],
                'nombre' => $dispositivo['nombre'],
                'status' => $status['status']
            ];
        }
        
        return $results;
    }
}
