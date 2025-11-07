<?php
/**
 * Helper Functions
 * Common utility functions used throughout the application
 */

/**
 * Sanitize input data
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect to URL
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Format currency
 */
function formatMoney($amount) {
    return '$' . number_format($amount, 2);
}

/**
 * Format date
 */
function formatDate($date, $format = 'd/m/Y') {
    if (!$date) return '-';
    return date($format, strtotime($date));
}

/**
 * Format datetime
 */
function formatDateTime($datetime, $format = 'd/m/Y H:i') {
    if (!$datetime) return '-';
    return date($format, strtotime($datetime));
}

/**
 * Get status badge HTML
 */
function statusBadge($status) {
    $badges = [
        'activo' => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Activo</span>',
        'inactivo' => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Inactivo</span>',
        'suspendido' => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Suspendido</span>',
        'vencido' => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Vencido</span>',
        'permitido' => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Permitido</span>',
        'denegado' => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Denegado</span>',
        'online' => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Online</span>',
        'offline' => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Offline</span>',
        'completado' => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Completado</span>',
        'pendiente' => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Pendiente</span>',
        'cancelado' => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Cancelado</span>',
    ];
    
    return $badges[$status] ?? '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">' . ucfirst($status) . '</span>';
}

/**
 * Generate QR Code URL
 */
function generateQrCode($data) {
    $size = QR_SIZE;
    $url = QR_API_URL . "?size={$size}&data=" . urlencode($data);
    return $url;
}

/**
 * Upload file
 */
function uploadFile($file, $directory = 'photos', $allowedTypes = null) {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Error en la carga del archivo'];
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'El archivo es demasiado grande'];
    }
    
    $allowedTypes = $allowedTypes ?? ALLOWED_IMAGE_TYPES;
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'message' => 'Tipo de archivo no permitido'];
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $uploadPath = UPLOAD_PATH . $directory . '/' . $filename;
    
    if (!is_dir(UPLOAD_PATH . $directory)) {
        mkdir(UPLOAD_PATH . $directory, 0755, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return ['success' => true, 'filename' => $filename, 'path' => $uploadPath];
    }
    
    return ['success' => false, 'message' => 'Error al guardar el archivo'];
}

/**
 * Delete file
 */
function deleteFile($filename, $directory = 'photos') {
    $filepath = UPLOAD_PATH . $directory . '/' . $filename;
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    return false;
}

/**
 * Log event
 */
function logEvent($tipo, $descripcion, $usuarioId = null, $socioId = null, $sucursalId = null, $datosAdicionales = null) {
    try {
        $db = Database::getInstance()->getConnection();
        
        $sql = "INSERT INTO bitacora_eventos (tipo, descripcion, usuario_id, socio_id, sucursal_id, ip_address, user_agent, datos_adicionales) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $datosJson = $datosAdicionales ? json_encode($datosAdicionales) : null;
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$tipo, $descripcion, $usuarioId, $socioId, $sucursalId, $ipAddress, $userAgent, $datosJson]);
        
        return true;
    } catch (Exception $e) {
        error_log("Error logging event: " . $e->getMessage());
        return false;
    }
}

/**
 * Send JSON response
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Get days of week in Spanish
 */
function getDaysOfWeek() {
    return [
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
        7 => 'Domingo'
    ];
}

/**
 * Validate email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone (Mexican format)
 */
function validatePhone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    return strlen($phone) === 10;
}

/**
 * Format phone number
 */
function formatPhone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($phone) === 10) {
        return substr($phone, 0, 3) . '-' . substr($phone, 3, 3) . '-' . substr($phone, 6);
    }
    return $phone;
}
