<?php
require_once 'bootstrap.php';
Auth::requireAuth();

require_once __DIR__ . '/../app/models/Socio.php';
require_once __DIR__ . '/../app/models/Acceso.php';
require_once __DIR__ . '/../app/models/DispositivoShelly.php';
require_once __DIR__ . '/../app/services/ShellyService.php';

$socioModel = new Socio();
$accesoModel = new Acceso();
$dispositivoModel = new DispositivoShelly();
$shellyService = new ShellyService();

$user = Auth::user();
$sucursalId = Auth::sucursalId();

$socioId = isset($_GET['socio_id']) ? (int)$_GET['socio_id'] : null;
$codigo = isset($_GET['codigo']) ? sanitize($_GET['codigo']) : null;

$socio = null;
$message = '';
$messageType = '';

// Search for member
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])) {
    $searchCode = sanitize($_POST['codigo'] ?? '');
    
    if (!empty($searchCode)) {
        $socio = $socioModel->findByCode($searchCode);
        
        if (!$socio) {
            $message = 'Socio no encontrado';
            $messageType = 'error';
        } elseif (!Auth::isSuperadmin() && $socio['sucursal_id'] != $sucursalId) {
            $message = 'Este socio pertenece a otra sucursal';
            $messageType = 'error';
            $socio = null;
        }
    }
}

// Grant access
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grant_access'])) {
    $socioId = (int)$_POST['socio_id'];
    $socio = $socioModel->find($socioId);
    
    if ($socio) {
        // Check access permission
        $accessCheck = $socioModel->canAccess($socioId);
        
        if ($accessCheck['allowed']) {
            // Get devices for this branch
            $dispositivos = $dispositivoModel->getBySucursal($socio['sucursal_id']);
            
            $dispositivoId = null;
            $doorOpened = false;
            
            if (!empty($dispositivos) && SHELLY_ENABLED) {
                // Try to open the first available device
                $dispositivo = $dispositivos[0];
                $result = $shellyService->openDoor($dispositivo['device_id'], $dispositivo['tiempo_apertura']);
                
                if ($result['success']) {
                    $dispositivoId = $dispositivo['id'];
                    $doorOpened = true;
                }
            }
            
            // Register access
            $accesoModel->registrar(
                $socioId,
                $dispositivoId,
                $socio['sucursal_id'],
                'manual',
                'permitido',
                'Acceso manual autorizado',
                Auth::id()
            );
            
            logEvent('acceso', "Acceso manual permitido: {$socio['nombre']} {$socio['apellido']}", Auth::id(), $socioId, $socio['sucursal_id']);
            
            $message = $doorOpened ? 
                '¡Acceso permitido! Puerta abierta' : 
                '¡Acceso registrado! (Apertura manual de puerta requerida)';
            $messageType = 'success';
        } else {
            // Register denied access
            $accesoModel->registrar(
                $socioId,
                null,
                $socio['sucursal_id'],
                'manual',
                'denegado',
                $accessCheck['reason'],
                Auth::id()
            );
            
            logEvent('acceso', "Acceso manual denegado: {$socio['nombre']} {$socio['apellido']} - {$accessCheck['reason']}", 
                     Auth::id(), $socioId, $socio['sucursal_id']);
            
            $message = 'Acceso denegado: ' . $accessCheck['reason'];
            $messageType = 'error';
        }
    }
}

// Load socio if provided
if ($socioId && !$socio) {
    $socio = $socioModel->find($socioId);
} elseif ($codigo && !$socio) {
    $socio = $socioModel->findByCode($codigo);
}

if ($socio) {
    $socio = $socioModel->getWithMembresia($socio['id']);
}

$pageTitle = 'Acceso Manual';
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
                <h1 class="text-3xl font-bold text-gray-900">Acceso Manual</h1>
                <p class="text-gray-600">Otorgar acceso manual a socios</p>
            </div>
            <a href="socios.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded inline-flex items-center">
                <i class="fas fa-arrow-left mr-2"></i>
                Volver
            </a>
        </div>
        
        <!-- Messages -->
        <?php if ($message): ?>
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
        
        <!-- Search Form -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Buscar Socio</h2>
            <form method="POST" class="flex gap-4">
                <input type="hidden" name="search" value="1">
                <div class="flex-1">
                    <input type="text" name="codigo" placeholder="Ingrese el código del socio" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-lg">
                </div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded">
                    <i class="fas fa-search mr-2"></i>Buscar
                </button>
            </form>
        </div>
        
        <!-- Member Info and Access Control -->
        <?php if ($socio): ?>
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="p-6">
                    <div class="flex items-start space-x-6">
                        <!-- Photo -->
                        <div class="flex-shrink-0">
                            <?php if ($socio['foto']): ?>
                                <img src="<?php echo UPLOAD_URL . '/uploads/photos/' . htmlspecialchars($socio['foto']); ?>" 
                                     alt="Foto" class="h-32 w-32 rounded-lg object-cover border-4 border-blue-500">
                            <?php else: ?>
                                <div class="h-32 w-32 rounded-lg bg-gray-200 flex items-center justify-center border-4 border-gray-300">
                                    <i class="fas fa-user text-gray-400 text-5xl"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Member Info -->
                        <div class="flex-1">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h2 class="text-2xl font-bold text-gray-900">
                                        <?php echo htmlspecialchars($socio['nombre'] . ' ' . $socio['apellido']); ?>
                                    </h2>
                                    <p class="text-gray-600">Código: <span class="font-semibold"><?php echo htmlspecialchars($socio['codigo']); ?></span></p>
                                </div>
                                <div>
                                    <?php echo statusBadge($socio['estado']); ?>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm text-gray-500">Tipo de Membresía</p>
                                    <p class="font-medium text-gray-900">
                                        <?php echo htmlspecialchars($socio['tipo_membresia_nombre'] ?? 'Sin membresía'); ?>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Fecha de Vencimiento</p>
                                    <p class="font-medium text-gray-900">
                                        <?php echo $socio['fecha_vencimiento'] ? formatDate($socio['fecha_vencimiento']) : '-'; ?>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Teléfono</p>
                                    <p class="font-medium text-gray-900"><?php echo formatPhone($socio['telefono']); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Sucursal</p>
                                    <p class="font-medium text-gray-900"><?php echo htmlspecialchars($socio['sucursal_nombre']); ?></p>
                                </div>
                            </div>
                            
                            <!-- Access Control -->
                            <div class="mt-6 pt-6 border-t border-gray-200">
                                <?php 
                                $accessCheck = $socioModel->canAccess($socio['id']);
                                if ($accessCheck['allowed']): 
                                ?>
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center text-green-600">
                                            <i class="fas fa-check-circle text-2xl mr-3"></i>
                                            <div>
                                                <p class="font-semibold">Acceso Permitido</p>
                                                <p class="text-sm">El socio puede acceder al gimnasio</p>
                                            </div>
                                        </div>
                                        <form method="POST">
                                            <input type="hidden" name="grant_access" value="1">
                                            <input type="hidden" name="socio_id" value="<?php echo $socio['id']; ?>">
                                            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-8 rounded-lg text-lg">
                                                <i class="fas fa-door-open mr-2"></i>Otorgar Acceso
                                            </button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <div class="flex items-center text-red-600">
                                        <i class="fas fa-times-circle text-2xl mr-3"></i>
                                        <div>
                                            <p class="font-semibold">Acceso Denegado</p>
                                            <p class="text-sm"><?php echo htmlspecialchars($accessCheck['reason']); ?></p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
