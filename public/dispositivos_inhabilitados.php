<?php
require_once 'bootstrap.php';
Auth::requireRole(['superadmin', 'admin']);

require_once __DIR__ . '/../app/models/DispositivoShelly.php';
require_once __DIR__ . '/../app/models/DispositivoHikvision.php';
require_once __DIR__ . '/../app/models/Sucursal.php';

$dispositivoShellyModel = new DispositivoShelly();
$dispositivoHikvisionModel = new DispositivoHikvision();
$sucursalModel = new Sucursal();

$user = Auth::user();
$sucursalId = Auth::isSuperadmin() ? null : Auth::sucursalId();

// Handle enable device
if (isset($_POST['enable_device']) && isset($_POST['device_id']) && isset($_POST['device_type'])) {
    $deviceId = (int)$_POST['device_id'];
    $deviceType = $_POST['device_type'];
    
    if ($deviceType === 'shelly') {
        $dispositivo = $dispositivoShellyModel->find($deviceId);
        if ($dispositivo && (Auth::isSuperadmin() || $dispositivo['sucursal_id'] == $sucursalId)) {
            $dispositivoShellyModel->update($deviceId, ['activo' => 1]);
            logEvent('modificacion', "Dispositivo Shelly habilitado: {$dispositivo['nombre']}", Auth::id(), null, $dispositivo['sucursal_id']);
            $message = 'Dispositivo habilitado correctamente';
            $messageType = 'success';
        }
    } elseif ($deviceType === 'hikvision') {
        $dispositivo = $dispositivoHikvisionModel->find($deviceId);
        if ($dispositivo && (Auth::isSuperadmin() || $dispositivo['sucursal_id'] == $sucursalId)) {
            $dispositivoHikvisionModel->update($deviceId, ['activo' => 1]);
            logEvent('modificacion', "Dispositivo HikVision habilitado: {$dispositivo['nombre']}", Auth::id(), null, $dispositivo['sucursal_id']);
            $message = 'Dispositivo habilitado correctamente';
            $messageType = 'success';
        }
    }
}

$dispositivosShelly = $dispositivoShellyModel->getDisabled($sucursalId);
$dispositivosHikvision = $dispositivoHikvisionModel->getDisabled($sucursalId);

$pageTitle = 'Dispositivos Inhabilitados';
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
                <h1 class="text-3xl font-bold text-gray-900">Dispositivos Inhabilitados</h1>
                <p class="text-gray-600">Gestión de dispositivos desactivados</p>
            </div>
            <a href="dispositivos.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded inline-flex items-center">
                <i class="fas fa-arrow-left mr-2"></i>
                Volver a Dispositivos
            </a>
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
        
        <!-- Shelly Devices -->
        <?php if (!empty($dispositivosShelly)): ?>
        <div class="mb-8">
            <h2 class="text-xl font-bold text-gray-900 mb-4">
                <i class="fas fa-wifi text-blue-600 mr-2"></i>Dispositivos Shelly Inhabilitados
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($dispositivosShelly as $dispositivo): ?>
                    <div class="bg-white rounded-lg shadow overflow-hidden opacity-75">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    <?php echo htmlspecialchars($dispositivo['nombre']); ?>
                                </h3>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                    Inhabilitado
                                </span>
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
                            </div>
                            
                            <form method="POST">
                                <input type="hidden" name="enable_device" value="1">
                                <input type="hidden" name="device_id" value="<?php echo $dispositivo['id']; ?>">
                                <input type="hidden" name="device_type" value="shelly">
                                <button type="submit" 
                                        class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                    <i class="fas fa-check mr-2"></i>Habilitar
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- HikVision Devices -->
        <?php if (!empty($dispositivosHikvision)): ?>
        <div class="mb-8">
            <h2 class="text-xl font-bold text-gray-900 mb-4">
                <i class="fas fa-video text-blue-600 mr-2"></i>Dispositivos HikVision Inhabilitados
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($dispositivosHikvision as $dispositivo): ?>
                    <div class="bg-white rounded-lg shadow overflow-hidden opacity-75">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    <?php echo htmlspecialchars($dispositivo['nombre']); ?>
                                </h3>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                    Inhabilitado
                                </span>
                            </div>
                            
                            <div class="space-y-2 mb-4">
                                <div class="flex items-center text-sm text-gray-600">
                                    <i class="fas fa-network-wired w-5 mr-2"></i>
                                    <span class="font-mono text-xs"><?php echo htmlspecialchars($dispositivo['ip']); ?></span>
                                </div>
                                <div class="flex items-center text-sm text-gray-600">
                                    <i class="fas fa-building w-5 mr-2"></i>
                                    <?php echo htmlspecialchars($dispositivo['sucursal_nombre']); ?>
                                </div>
                                <div class="flex items-center text-sm text-gray-600">
                                    <i class="fas fa-map-marker-alt w-5 mr-2"></i>
                                    <?php echo htmlspecialchars($dispositivo['ubicacion'] ?: 'No especificada'); ?>
                                </div>
                            </div>
                            
                            <form method="POST">
                                <input type="hidden" name="enable_device" value="1">
                                <input type="hidden" name="device_id" value="<?php echo $dispositivo['id']; ?>">
                                <input type="hidden" name="device_type" value="hikvision">
                                <button type="submit" 
                                        class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                    <i class="fas fa-check mr-2"></i>Habilitar
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Empty State -->
        <?php if (empty($dispositivosShelly) && empty($dispositivosHikvision)): ?>
            <div class="bg-white rounded-lg shadow p-8 text-center text-gray-500">
                <i class="fas fa-check-circle text-4xl mb-4 text-green-500"></i>
                <p class="text-lg font-semibold">No hay dispositivos inhabilitados</p>
                <p class="mt-2">Todos los dispositivos están habilitados y activos.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
