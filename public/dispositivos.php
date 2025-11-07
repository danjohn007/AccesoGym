<?php
require_once 'bootstrap.php';
Auth::requireRole(['superadmin', 'admin']);

require_once __DIR__ . '/../app/models/DispositivoShelly.php';
require_once __DIR__ . '/../app/models/Sucursal.php';
require_once __DIR__ . '/../app/services/ShellyService.php';

$dispositivoModel = new DispositivoShelly();
$sucursalModel = new Sucursal();
$shellyService = new ShellyService();

$user = Auth::user();
$sucursalId = Auth::isSuperadmin() ? null : Auth::sucursalId();

// Handle test door open
if (isset($_POST['test_door']) && isset($_POST['device_id'])) {
    $deviceId = (int)$_POST['device_id'];
    $dispositivo = $dispositivoModel->find($deviceId);
    
    if ($dispositivo && (Auth::isSuperadmin() || $dispositivo['sucursal_id'] == $sucursalId)) {
        $result = $shellyService->openDoor($dispositivo['device_id'], $dispositivo['tiempo_apertura']);
        
        if ($result['success']) {
            $message = 'Puerta abierta correctamente';
            $messageType = 'success';
            
            logEvent('dispositivo', "Prueba de apertura: {$dispositivo['nombre']}", Auth::id(), null, $dispositivo['sucursal_id']);
        } else {
            $message = 'Error al abrir puerta: ' . ($result['message'] ?? 'Error desconocido');
            $messageType = 'error';
        }
    }
}

// Handle status update
if (isset($_POST['update_status'])) {
    $results = $shellyService->updateAllDeviceStatuses();
    $message = 'Estados actualizados: ' . count($results) . ' dispositivos';
    $messageType = 'success';
}

$dispositivos = $dispositivoModel->getAllWithSucursal($sucursalId);
$sucursales = Auth::isSuperadmin() ? $sucursalModel->getActive() : [];

$pageTitle = 'Dispositivos';
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
        <div class="mb-8 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Dispositivos Shelly</h1>
                <p class="text-gray-600">Gestión de dispositivos IoT</p>
            </div>
            <div class="space-x-2">
                <form method="POST" class="inline">
                    <button type="submit" name="update_status" 
                            class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded inline-flex items-center">
                        <i class="fas fa-sync-alt mr-2"></i>
                        Actualizar Estados
                    </button>
                </form>
                <a href="dispositivo_form.php" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded inline-flex items-center">
                    <i class="fas fa-plus mr-2"></i>
                    Nuevo Dispositivo
                </a>
            </div>
        </div>
        
        <!-- Messages -->
        <?php if (isset($message)): ?>
            <div class="mb-6 <?php echo $messageType === 'success' ? 'bg-green-50 border-green-500' : 'bg-red-50 border-red-500'; ?> border-l-4 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle text-green-400' : 'fa-exclamation-circle text-red-400'; ?>"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium <?php echo $messageType === 'success' ? 'text-green-800' : 'text-red-800'; ?>">
                            <?php echo htmlspecialchars($message); ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Devices Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if (empty($dispositivos)): ?>
                <div class="col-span-full bg-white rounded-lg shadow p-8 text-center text-gray-500">
                    <i class="fas fa-wifi text-4xl mb-4"></i>
                    <p>No hay dispositivos registrados</p>
                </div>
            <?php else: ?>
                <?php foreach ($dispositivos as $dispositivo): ?>
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    <?php echo htmlspecialchars($dispositivo['nombre']); ?>
                                </h3>
                                <?php echo statusBadge($dispositivo['estado']); ?>
                            </div>
                            
                            <div class="space-y-2 mb-4">
                                <div class="flex items-center text-sm text-gray-600">
                                    <i class="fas fa-fingerprint w-5 mr-2"></i>
                                    <span class="font-mono text-xs"><?php echo htmlspecialchars($dispositivo['device_id']); ?></span>
                                </div>
                                <div class="flex items-center text-sm text-gray-600">
                                    <i class="fas fa-building w-5 mr-2"></i>
                                    <?php echo htmlspecialchars($dispositivo['sucursal_nombre']); ?>
                                </div>
                                <div class="flex items-center text-sm text-gray-600">
                                    <i class="fas fa-map-marker-alt w-5 mr-2"></i>
                                    <?php echo htmlspecialchars($dispositivo['ubicacion'] ?: 'No especificada'); ?>
                                </div>
                                <div class="flex items-center text-sm text-gray-600">
                                    <i class="fas fa-clock w-5 mr-2"></i>
                                    Apertura: <?php echo $dispositivo['tiempo_apertura']; ?>s
                                </div>
                                <?php if ($dispositivo['ultima_conexion']): ?>
                                <div class="flex items-center text-sm text-gray-600">
                                    <i class="fas fa-history w-5 mr-2"></i>
                                    <?php echo formatDateTime($dispositivo['ultima_conexion']); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="flex space-x-2">
                                <form method="POST" class="flex-1">
                                    <input type="hidden" name="test_door" value="1">
                                    <input type="hidden" name="device_id" value="<?php echo $dispositivo['id']; ?>">
                                    <button type="submit" 
                                            class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded text-sm">
                                        <i class="fas fa-door-open mr-1"></i>Probar
                                    </button>
                                </form>
                                <a href="dispositivo_form.php?id=<?php echo $dispositivo['id']; ?>" 
                                   class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-center text-sm">
                                    <i class="fas fa-edit mr-1"></i>Editar
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Info Box -->
        <div class="mt-8 bg-blue-50 border-l-4 border-blue-500 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-info-circle text-blue-400"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800">Integración con Shelly Cloud</h3>
                    <div class="mt-2 text-sm text-blue-700">
                        <p>Los dispositivos Shelly permiten el control remoto de puertas magnéticas.</p>
                        <p class="mt-1">Configure los Device IDs desde la consola de Shelly Cloud y asegúrese de que la API Key esté configurada correctamente.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
