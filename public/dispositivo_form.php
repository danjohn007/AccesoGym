<?php
require_once 'bootstrap.php';
Auth::requireRole(['superadmin', 'admin']);

require_once __DIR__ . '/../app/models/DispositivoShelly.php';
require_once __DIR__ . '/../app/models/Sucursal.php';

$dispositivoModel = new DispositivoShelly();
$sucursalModel = new Sucursal();

$user = Auth::user();
$isEdit = isset($_GET['id']) && !empty($_GET['id']);
$dispositivoId = $isEdit ? (int)$_GET['id'] : null;

$dispositivo = $isEdit ? $dispositivoModel->find($dispositivoId) : null;
$sucursales = Auth::isSuperadmin() ? $sucursalModel->getActive() : [['id' => Auth::sucursalId()]];

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de seguridad inválido';
    } else {
        $nombre = sanitize($_POST['nombre'] ?? '');
        $device_id = sanitize($_POST['device_id'] ?? '');
        $auth_token = sanitize($_POST['auth_token'] ?? '');
        $servidor_cloud = sanitize($_POST['servidor_cloud'] ?? 'shelly-208-eu.shelly.cloud');
        $tipo = sanitize($_POST['tipo'] ?? 'puerta_magnetica');
        $sucursal_id = (int)($_POST['sucursal_id'] ?? Auth::sucursalId());
        $ubicacion = sanitize($_POST['ubicacion'] ?? '');
        $area = sanitize($_POST['area'] ?? '');
        $tiempo_apertura = (int)($_POST['tiempo_apertura'] ?? 5);
        $canal_entrada = (int)($_POST['canal_entrada'] ?? 1);
        $canal_salida = (int)($_POST['canal_salida'] ?? 0);
        $duracion_pulso = (int)($_POST['duracion_pulso'] ?? 4000);
        $activo = isset($_POST['activo']) ? 1 : 0;
        $invertido = isset($_POST['invertido']) ? 1 : 0;
        $simultaneo = isset($_POST['simultaneo']) ? 1 : 0;
        
        if (empty($nombre)) $errors[] = 'El nombre es requerido';
        if (empty($device_id)) $errors[] = 'El Device ID es requerido';
        if ($tiempo_apertura < 1 || $tiempo_apertura > 60) $errors[] = 'El tiempo de apertura debe estar entre 1 y 60 segundos';
        if ($duracion_pulso < 1000 || $duracion_pulso > 10000) $errors[] = 'La duración del pulso debe estar entre 1000 y 10000 ms';
        
        // Check if device_id already exists
        $existing = $dispositivoModel->findByDeviceId($device_id);
        if ($existing && (!$isEdit || $existing['id'] != $dispositivoId)) {
            $errors[] = 'Ya existe un dispositivo con ese Device ID';
        }
        
        if (empty($errors)) {
            $data = [
                'nombre' => $nombre,
                'device_id' => $device_id,
                'auth_token' => $auth_token,
                'servidor_cloud' => $servidor_cloud,
                'tipo' => $tipo,
                'sucursal_id' => $sucursal_id,
                'ubicacion' => $ubicacion,
                'area' => $area,
                'tiempo_apertura' => $tiempo_apertura,
                'canal_entrada' => $canal_entrada,
                'canal_salida' => $canal_salida,
                'duracion_pulso' => $duracion_pulso,
                'activo' => $activo,
                'invertido' => $invertido,
                'simultaneo' => $simultaneo
            ];
            
            if ($isEdit) {
                $dispositivoModel->update($dispositivoId, $data);
                logEvent('modificacion', "Dispositivo actualizado: {$nombre}", Auth::id(), null, $sucursal_id);
            } else {
                $dispositivoId = $dispositivoModel->insert($data);
                logEvent('sistema', "Nuevo dispositivo registrado: {$nombre}", Auth::id(), null, $sucursal_id);
            }
            
            $success = true;
            $successMessage = $isEdit ? 'Dispositivo actualizado correctamente' : 'Dispositivo registrado correctamente';
            
            if (!$isEdit) {
                header("Location: dispositivos.php");
                exit;
            }
        }
    }
}

$pageTitle = $isEdit ? 'Editar Dispositivo' : 'Nuevo Dispositivo';
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
    
    <div class="container mx-auto px-4 py-8 max-w-2xl">
        <!-- Header -->
        <div class="mb-8 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900"><?php echo $pageTitle; ?></h1>
                <p class="text-gray-600">Configure un dispositivo Shelly</p>
            </div>
            <a href="dispositivos.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded inline-flex items-center">
                <i class="fas fa-arrow-left mr-2"></i>
                Volver
            </a>
        </div>
        
        <!-- Messages -->
        <?php if (!empty($errors)): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                <ul class="list-disc list-inside text-sm text-red-700">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6">
                <p class="text-sm text-green-800"><?php echo $successMessage; ?></p>
            </div>
        <?php endif; ?>
        
        <!-- Form -->
        <form method="POST" class="bg-white rounded-lg shadow p-6">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nombre *</label>
                    <input type="text" name="nombre" required
                           value="<?php echo htmlspecialchars($dispositivo['nombre'] ?? ''); ?>"
                           placeholder="Ej: Puerta Principal"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Token de Autenticación *</label>
                    <div class="relative">
                        <input type="password" name="auth_token" required
                               value="<?php echo htmlspecialchars($dispositivo['auth_token'] ?? ''); ?>"
                               placeholder="Token de Shelly Cloud"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 font-mono text-sm">
                        <button type="button" 
                                onclick="const input = this.previousElementSibling; input.type = input.type === 'password' ? 'text' : 'password';"
                                class="absolute right-2 top-2 text-gray-500 hover:text-gray-700">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Token de autenticación para Shelly Cloud API</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Device ID *</label>
                    <input type="text" name="device_id" required
                           value="<?php echo htmlspecialchars($dispositivo['device_id'] ?? ''); ?>"
                           placeholder="Ej: 34987A67DA6C"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 font-mono text-sm">
                    <p class="mt-1 text-xs text-gray-500">ID único del dispositivo en Shelly Cloud</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Servidor Cloud</label>
                    <input type="text" name="servidor_cloud"
                           value="<?php echo htmlspecialchars($dispositivo['servidor_cloud'] ?? 'shelly-208-eu.shelly.cloud'); ?>"
                           placeholder="shelly-208-eu.shelly.cloud"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 font-mono text-sm">
                    <p class="mt-1 text-xs text-gray-500">Sin https:// ni puerto</p>
                </div>
                
                <?php if (Auth::isSuperadmin() && count($sucursales) > 1): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sucursal *</label>
                    <select name="sucursal_id" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <?php foreach ($sucursales as $sucursal): ?>
                            <option value="<?php echo $sucursal['id']; ?>"
                                    <?php echo ($dispositivo['sucursal_id'] ?? Auth::sucursalId()) == $sucursal['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($sucursal['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php else: ?>
                    <input type="hidden" name="sucursal_id" value="<?php echo Auth::sucursalId(); ?>">
                <?php endif; ?>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ubicación</label>
                    <input type="text" name="ubicacion"
                           value="<?php echo htmlspecialchars($dispositivo['ubicacion'] ?? ''); ?>"
                           placeholder="Ej: Entrada principal, Área de pesas"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Área</label>
                    <input type="text" name="area"
                           value="<?php echo htmlspecialchars($dispositivo['area'] ?? ''); ?>"
                           placeholder="Ej: Entrada Puerta 1"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Acción</label>
                    <select name="tipo" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="puerta_magnetica" <?php echo ($dispositivo['tipo'] ?? 'puerta_magnetica') == 'puerta_magnetica' ? 'selected' : ''; ?>>
                            Abrir/Cerrar
                        </option>
                    </select>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Canal de Entrada (Apertura)</label>
                        <select name="canal_entrada" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <option value="0" <?php echo ($dispositivo['canal_entrada'] ?? 1) == 0 ? 'selected' : ''; ?>>Canal 0</option>
                            <option value="1" <?php echo ($dispositivo['canal_entrada'] ?? 1) == 1 ? 'selected' : ''; ?>>Canal 1</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Pulso de 5 segundos al entrar</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Canal de Salida (Cierre)</label>
                        <select name="canal_salida" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <option value="0" <?php echo ($dispositivo['canal_salida'] ?? 0) == 0 ? 'selected' : ''; ?>>Canal 0</option>
                            <option value="1" <?php echo ($dispositivo['canal_salida'] ?? 0) == 1 ? 'selected' : ''; ?>>Canal 1</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Activación al salir</p>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Duración Pulso (ms)</label>
                    <input type="number" name="duracion_pulso" min="1000" max="10000" step="100"
                           value="<?php echo htmlspecialchars($dispositivo['duracion_pulso'] ?? 4000); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <p class="mt-1 text-xs text-gray-500">Por defecto: 4000 ms. Máximo: 10 seg</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tiempo de Apertura (segundos)</label>
                    <input type="number" name="tiempo_apertura" min="1" max="60"
                           value="<?php echo htmlspecialchars($dispositivo['tiempo_apertura'] ?? 5); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <p class="mt-1 text-xs text-gray-500">Tiempo que la puerta permanecerá abierta (1-60 segundos)</p>
                </div>
                
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input type="checkbox" name="activo" <?php echo ($dispositivo['activo'] ?? 1) ? 'checked' : ''; ?>
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        <span class="ml-2 text-sm text-gray-700">Dispositivo habilitado</span>
                    </label>
                    
                    <label class="flex items-center">
                        <input type="checkbox" name="invertido" <?php echo ($dispositivo['invertido'] ?? 0) ? 'checked' : ''; ?>
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        <span class="ml-2 text-sm text-gray-700">Invertido (off → on)</span>
                    </label>
                    
                    <label class="flex items-center">
                        <input type="checkbox" name="simultaneo" <?php echo ($dispositivo['simultaneo'] ?? 0) ? 'checked' : ''; ?>
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        <span class="ml-2 text-sm text-gray-700">Dispositivo simultáneo</span>
                    </label>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="mt-6 flex justify-end space-x-3">
                <a href="dispositivos.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-6 rounded">
                    Cancelar
                </a>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                    <i class="fas fa-save mr-2"></i>
                    <?php echo $isEdit ? 'Actualizar' : 'Registrar'; ?>
                </button>
            </div>
        </form>
    </div>
</body>
</html>
