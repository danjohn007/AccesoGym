<?php
require_once 'bootstrap.php';
Auth::requireRole('superadmin');

$success = false;
$message = '';

// Handle configuration updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (Auth::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $updates = [];
        
        // General settings
        if (isset($_POST['sitio_nombre'])) {
            $updates['sitio_nombre'] = sanitize($_POST['sitio_nombre']);
        }
        
        // Shelly settings
        if (isset($_POST['shelly_enabled'])) {
            $updates['shelly_enabled'] = $_POST['shelly_enabled'] === '1' ? 'true' : 'false';
        }
        if (isset($_POST['shelly_api_key'])) {
            $updates['shelly_api_key'] = sanitize($_POST['shelly_api_key']);
        }
        
        // WhatsApp settings
        if (isset($_POST['whatsapp_enabled'])) {
            $updates['whatsapp_enabled'] = $_POST['whatsapp_enabled'] === '1' ? 'true' : 'false';
        }
        if (isset($_POST['whatsapp_phone_id'])) {
            $updates['whatsapp_phone_id'] = sanitize($_POST['whatsapp_phone_id']);
        }
        if (isset($_POST['whatsapp_token'])) {
            $updates['whatsapp_token'] = sanitize($_POST['whatsapp_token']);
        }
        
        // Update config file
        if (!empty($updates)) {
            $configFile = __DIR__ . '/../config/config.php';
            $configContent = file_get_contents($configFile);
            
            foreach ($updates as $key => $value) {
                $pattern = "/define\('" . strtoupper($key) . "',\s*[^)]+\);/";
                $replacement = "define('" . strtoupper($key) . "', '{$value}');";
                $configContent = preg_replace($pattern, $replacement, $configContent);
            }
            
            file_put_contents($configFile, $configContent);
            
            $success = true;
            $message = 'Configuración actualizada correctamente';
            
            logEvent('sistema', 'Configuración actualizada', Auth::id(), null, null);
        }
    }
}

$pageTitle = 'Configuración';
$csrfToken = Auth::generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-100">
    <?php include __DIR__ . '/../app/views/partials/navbar.php'; ?>
    
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Configuración del Sistema</h1>
            <p class="text-gray-600">Administra las configuraciones globales</p>
        </div>
        
        <?php if ($success): ?>
            <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-green-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800"><?php echo htmlspecialchars($message); ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            
            <!-- General Settings -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">
                        <i class="fas fa-cog mr-2"></i>Configuración General
                    </h2>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nombre del Sistema</label>
                        <input type="text" name="sitio_nombre" value="<?php echo APP_NAME; ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Zona Horaria</label>
                        <p class="text-sm text-gray-600"><?php echo APP_TIMEZONE; ?></p>
                        <p class="text-xs text-gray-500 mt-1">Editar en config/config.php</p>
                    </div>
                </div>
            </div>
            
            <!-- Shelly Cloud Settings -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">
                        <i class="fas fa-wifi mr-2"></i>Shelly Cloud API
                    </h2>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="shelly_enabled" value="1" 
                                   <?php echo SHELLY_ENABLED ? 'checked' : ''; ?>
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-700">Habilitar integración Shelly Cloud</span>
                        </label>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">API Key</label>
                        <input type="text" name="shelly_api_key" value="<?php echo SHELLY_API_KEY; ?>"
                               placeholder="Ingrese su API Key de Shelly Cloud"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 font-mono text-sm">
                        <p class="mt-1 text-xs text-gray-500">Obtén tu API Key desde <a href="https://control.shelly.cloud/" target="_blank" class="text-blue-600">Shelly Cloud Console</a></p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">API URL</label>
                        <input type="text" value="<?php echo SHELLY_API_URL; ?>" disabled
                               class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 font-mono text-sm">
                    </div>
                </div>
            </div>
            
            <!-- WhatsApp Settings -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">
                        <i class="fab fa-whatsapp mr-2"></i>WhatsApp Business API
                    </h2>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="whatsapp_enabled" value="1" 
                                   <?php echo WHATSAPP_ENABLED ? 'checked' : ''; ?>
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-700">Habilitar integración WhatsApp</span>
                        </label>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number ID</label>
                        <input type="text" name="whatsapp_phone_id" value="<?php echo WHATSAPP_PHONE_ID; ?>"
                               placeholder="Ej: 123456789012345"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 font-mono text-sm">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Access Token</label>
                        <input type="text" name="whatsapp_token" value="<?php echo WHATSAPP_TOKEN; ?>"
                               placeholder="Ingrese su Access Token"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 font-mono text-sm">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Webhook URL</label>
                        <input type="text" value="<?php echo APP_URL; ?>/webhook_whatsapp.php" disabled
                               class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 font-mono text-sm">
                        <p class="mt-1 text-xs text-gray-500">Configure esta URL en Meta for Developers</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Verify Token</label>
                        <input type="text" value="<?php echo WHATSAPP_VERIFY_TOKEN; ?>" disabled
                               class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 font-mono text-sm">
                        <p class="mt-1 text-xs text-gray-500">Use este token al configurar el webhook</p>
                    </div>
                </div>
            </div>
            
            <!-- Payment Gateways -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">
                        <i class="fas fa-credit-card mr-2"></i>Pasarelas de Pago
                    </h2>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="border-b pb-4">
                            <h3 class="font-medium text-gray-900 mb-2">Stripe</h3>
                            <p class="text-sm text-gray-600">
                                Estado: <span class="font-semibold"><?php echo STRIPE_ENABLED ? 'Habilitado' : 'Deshabilitado'; ?></span>
                            </p>
                            <p class="text-xs text-gray-500 mt-1">Configurar en config/config.php</p>
                        </div>
                        
                        <div class="border-b pb-4">
                            <h3 class="font-medium text-gray-900 mb-2">MercadoPago</h3>
                            <p class="text-sm text-gray-600">
                                Estado: <span class="font-semibold"><?php echo MERCADOPAGO_ENABLED ? 'Habilitado' : 'Deshabilitado'; ?></span>
                            </p>
                            <p class="text-xs text-gray-500 mt-1">Configurar en config/config.php</p>
                        </div>
                        
                        <div>
                            <h3 class="font-medium text-gray-900 mb-2">Conekta</h3>
                            <p class="text-sm text-gray-600">
                                Estado: <span class="font-semibold"><?php echo CONEKTA_ENABLED ? 'Habilitado' : 'Deshabilitado'; ?></span>
                            </p>
                            <p class="text-xs text-gray-500 mt-1">Configurar en config/config.php</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Save Button -->
            <div class="flex justify-end">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg">
                    <i class="fas fa-save mr-2"></i>Guardar Configuración
                </button>
            </div>
        </form>
    </div>
</body>
</html>
