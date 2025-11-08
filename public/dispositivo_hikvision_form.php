<?php
require_once 'bootstrap.php';
Auth::requireRole(['superadmin', 'admin']);

require_once __DIR__ . '/../app/models/DispositivoHikvision.php';
require_once __DIR__ . '/../app/models/Sucursal.php';

$dispositivoModel = new DispositivoHikvision();
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
        $ip = sanitize($_POST['ip'] ?? '');
        $puerto = (int)($_POST['puerto'] ?? 80);
        $usuario = sanitize($_POST['usuario'] ?? '');
        $password = $_POST['password'] ?? '';
        $numero_puerta = (int)($_POST['numero_puerta'] ?? 1);
        $sucursal_id = (int)($_POST['sucursal_id'] ?? Auth::sucursalId());
        $ubicacion = sanitize($_POST['ubicacion'] ?? '');
        $activo = isset($_POST['activo']) ? 1 : 0;
        
        if (empty($nombre)) $errors[] = 'El nombre es requerido';
        if (empty($ip)) $errors[] = 'La dirección IP es requerida';
        if (!filter_var($ip, FILTER_VALIDATE_IP)) $errors[] = 'Dirección IP inválida';
        if ($puerto < 1 || $puerto > 65535) $errors[] = 'Puerto inválido';
        if (empty($usuario)) $errors[] = 'El usuario es requerido';
        if (!$isEdit && empty($password)) $errors[] = 'La contraseña es requerida';
        if ($numero_puerta < 1 || $numero_puerta > 8) $errors[] = 'El número de puerta debe estar entre 1 y 8';
        
        if (empty($errors)) {
            $data = [
                'nombre' => $nombre,
                'ip' => $ip,
                'puerto' => $puerto,
                'usuario' => $usuario,
                'numero_puerta' => $numero_puerta,
                'sucursal_id' => $sucursal_id,
                'ubicacion' => $ubicacion,
                'activo' => $activo
            ];
            
            // Only update password if provided
            if (!empty($password)) {
                $data['password'] = $password;
            }
            
            if ($isEdit) {
                $dispositivoModel->update($dispositivoId, $data);
                logEvent('modificacion', "Dispositivo HikVision actualizado: {$nombre}", Auth::id(), null, $sucursal_id);
            } else {
                $dispositivoId = $dispositivoModel->insert($data);
                logEvent('sistema', "Nuevo dispositivo HikVision registrado: {$nombre}", Auth::id(), null, $sucursal_id);
            }
            
            $success = true;
            $successMessage = $isEdit ? 'Dispositivo actualizado correctamente' : 'Dispositivo registrado correctamente';
            
            if (!$isEdit) {
                header("Location: dispositivos_hikvision.php");
                exit;
            }
        }
    }
}

$pageTitle = $isEdit ? 'Editar Dispositivo HikVision' : 'Nuevo Dispositivo HikVision';
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
</head>
<body class="bg-gray-100">
    <?php include __DIR__ . '/../app/views/partials/navbar.php'; ?>
    
    <div class="container mx-auto px-4 py-8 max-w-2xl">
        <!-- Header -->
        <div class="mb-8 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900"><?php echo $pageTitle; ?></h1>
                <p class="text-gray-600">Configure un dispositivo HikVision</p>
            </div>
            <a href="dispositivos_hikvision.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded inline-flex items-center">
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
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Dirección IP *</label>
                        <input type="text" name="ip" required
                               value="<?php echo htmlspecialchars($dispositivo['ip'] ?? ''); ?>"
                               placeholder="192.168.1.100"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 font-mono text-sm">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Puerto *</label>
                        <input type="number" name="puerto" min="1" max="65535" required
                               value="<?php echo htmlspecialchars($dispositivo['puerto'] ?? 80); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <p class="mt-1 text-xs text-gray-500">Por defecto: 80</p>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Usuario *</label>
                    <input type="text" name="usuario" required
                           value="<?php echo htmlspecialchars($dispositivo['usuario'] ?? ''); ?>"
                           placeholder="admin"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Contraseña <?php echo $isEdit ? '(dejar en blanco para mantener)' : '*'; ?>
                    </label>
                    <div class="relative">
                        <input type="password" name="password" <?php echo $isEdit ? '' : 'required'; ?>
                               placeholder="<?php echo $isEdit ? 'Sin cambios' : 'Contraseña del dispositivo'; ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <button type="button" 
                                onclick="const input = this.previousElementSibling; input.type = input.type === 'password' ? 'text' : 'password';"
                                class="absolute right-2 top-2 text-gray-500 hover:text-gray-700">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
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
                    <label class="block text-sm font-medium text-gray-700 mb-2">Número de Puerta *</label>
                    <select name="numero_puerta" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <?php for ($i = 1; $i <= 8; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo ($dispositivo['numero_puerta'] ?? 1) == $i ? 'selected' : ''; ?>>
                                Puerta <?php echo $i; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Seleccione el número de puerta del dispositivo (1-8)</p>
                </div>
                
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="activo" <?php echo ($dispositivo['activo'] ?? 1) ? 'checked' : ''; ?>
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        <span class="ml-2 text-sm text-gray-700">Dispositivo habilitado</span>
                    </label>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="mt-6 flex justify-end space-x-3">
                <a href="dispositivos_hikvision.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-6 rounded">
                    Cancelar
                </a>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                    <i class="fas fa-save mr-2"></i>
                    <?php echo $isEdit ? 'Actualizar' : 'Registrar'; ?>
                </button>
            </div>
        </form>
        
        <!-- Info Box -->
        <div class="mt-6 bg-blue-50 border-l-4 border-blue-500 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-info-circle text-blue-400"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800">Configuración de HikVision</h3>
                    <div class="mt-2 text-sm text-blue-700">
                        <p>Asegúrese de que el dispositivo HikVision esté accesible en la red.</p>
                        <p class="mt-1">El puerto predeterminado es 80 para HTTP y 443 para HTTPS.</p>
                        <p class="mt-1">Configure las credenciales de administrador del dispositivo.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
