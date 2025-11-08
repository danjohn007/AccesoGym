<?php
/**
 * Shelly API Service
 * Handles communication with Shelly Cloud devices
 */

class ShellyService {
    private $serverUrl;
    private $authToken;
    
    public function __construct() {
        // Use the cloud server from config or default
        $this->serverUrl = defined('SHELLY_SERVER_URL') ? SHELLY_SERVER_URL : 'https://shelly-208-eu.shelly.cloud';
        $this->authToken = defined('SHELLY_AUTH_TOKEN') ? SHELLY_AUTH_TOKEN : SHELLY_API_KEY;
    }
    
    /**
     * Open door (activate relay)
     */
    public function openDoor($deviceId, $duration = 5) {
        if (!SHELLY_ENABLED) {
            return ['success' => false, 'message' => 'Shelly API no habilitada'];
        }
        
        // Shelly Cloud API endpoint for controlling device
        $url = $this->serverUrl . '/device/relay/control';
        
        $data = [
            'channel' => 0,
            'turn' => 'on',
            'timer' => $duration,
            'id' => $deviceId,
            'auth_key' => $this->authToken
        ];
        
        $response = $this->makeRequest($url, $data);
        
        if ($response && isset($response['isok']) && $response['isok']) {
            return ['success' => true, 'message' => 'Puerta abierta'];
        }
        
        // Return more detailed error message
        $errorMsg = 'Error al abrir puerta';
        if ($response && isset($response['errors'])) {
            $errorMsg .= ': ' . json_encode($response['errors']);
        }
        
        return ['success' => false, 'message' => $errorMsg];
    }
    
    /**
     * Get device status
     */
    public function getDeviceStatus($deviceId) {
        if (!SHELLY_ENABLED) {
            return ['success' => false, 'message' => 'Shelly API no habilitada'];
        }
        
        $url = $this->serverUrl . '/device/status';
        
        $data = [
            'id' => $deviceId,
            'auth_key' => $this->authToken
        ];
        
        $response = $this->makeRequest($url, $data);
        
        if ($response && isset($response['isok']) && $response['isok']) {
            return [
                'success' => true,
                'online' => $response['data']['online'] ?? false,
                'device_status' => $response['data']['device_status'] ?? []
            ];
        }
        
        return ['success' => false, 'message' => 'Error al obtener estado del dispositivo'];
    }
    
    /**
     * Check if device is online
     */
    public function isDeviceOnline($deviceId) {
        $status = $this->getDeviceStatus($deviceId);
        return $status['success'] && ($status['online'] ?? false);
    }
    
    /**
     * Update all device statuses
     */
    public function updateAllDeviceStatuses() {
        require_once __DIR__ . '/../models/DispositivoShelly.php';
        $dispositivoModel = new DispositivoShelly();
        
        $devices = $dispositivoModel->findAll();
        $results = [];
        
        foreach ($devices as $device) {
            $isOnline = $this->isDeviceOnline($device['device_id']);
            $status = $isOnline ? 'online' : 'offline';
            
            $dispositivoModel->updateStatus($device['id'], $status);
            
            $results[] = [
                'device_id' => $device['device_id'],
                'nombre' => $device['nombre'],
                'status' => $status
            ];
        }
        
        return $results;
    }
    
    /**
     * Make HTTP request to Shelly API
     */
    private function makeRequest($url, $data) {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // Enable SSL verification for production
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            error_log("Shelly API Error: " . $error);
            return false;
        }
        
        return json_decode($response, true);
    }
}
