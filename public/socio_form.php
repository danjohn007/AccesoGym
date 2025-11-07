<?php
require_once 'bootstrap.php';
Auth::requireAuth();

require_once __DIR__ . '/../app/models/Socio.php';
require_once __DIR__ . '/../app/models/TipoMembresia.php';
require_once __DIR__ . '/../app/models/Sucursal.php';

$socioModel = new Socio();
$tipoMembresiaModel = new TipoMembresia();
$sucursalModel = new Sucursal();

$user = Auth::user();
$isEdit = isset($_GET['id']) && !empty($_GET['id']);
$socioId = $isEdit ? (int)$_GET['id'] : null;

$socio = $isEdit ? $socioModel->find($socioId) : null;
$tiposMembresia = $tipoMembresiaModel->getActive();
$sucursales = Auth::isSuperadmin() ? $sucursalModel->getActive() : [['id' => Auth::sucursalId()]];

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!Auth::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de seguridad inválido';
    } else {
        $nombre = sanitize($_POST['nombre'] ?? '');
        $apellido = sanitize($_POST['apellido'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $telefono = sanitize($_POST['telefono'] ?? '');
        $telefono_emergencia = sanitize($_POST['telefono_emergencia'] ?? '');
        $direccion = sanitize($_POST['direccion'] ?? '');
        $fecha_nacimiento = sanitize($_POST['fecha_nacimiento'] ?? '');
        $sucursal_id = (int)($_POST['sucursal_id'] ?? Auth::sucursalId());
        $tipo_membresia_id = !empty($_POST['tipo_membresia_id']) ? (int)$_POST['tipo_membresia_id'] : null;
        $estado = $_POST['estado'] ?? 'inactivo';
        $notas = sanitize($_POST['notas'] ?? '');
        
        // Validation
        if (empty($nombre)) $errors[] = 'El nombre es requerido';
        if (empty($apellido)) $errors[] = 'El apellido es requerido';
        if (empty($telefono)) $errors[] = 'El teléfono es requerido';
        if (!empty($email) && !validateEmail($email)) $errors[] = 'Email inválido';
        if (!validatePhone($telefono)) $errors[] = 'Teléfono inválido (10 dígitos)';
        
        if (empty($errors)) {
            $data = [
                'nombre' => $nombre,
                'apellido' => $apellido,
                'email' => $email,
                'telefono' => $telefono,
                'telefono_emergencia' => $telefono_emergencia,
                'direccion' => $direccion,
                'fecha_nacimiento' => $fecha_nacimiento ?: null,
                'sucursal_id' => $sucursal_id,
                'tipo_membresia_id' => $tipo_membresia_id,
                'estado' => $estado,
                'notas' => $notas
            ];
            
            // Handle photo upload
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = uploadFile($_FILES['foto'], 'photos');
                if ($uploadResult['success']) {
                    // Delete old photo if editing
                    if ($isEdit && !empty($socio['foto'])) {
                        deleteFile($socio['foto'], 'photos');
                    }
                    $data['foto'] = $uploadResult['filename'];
                } else {
                    $errors[] = $uploadResult['message'];
                }
            }
            
            if (empty($errors)) {
                if ($isEdit) {
                    // Update existing member
                    $socioModel->update($socioId, $data);
                    
                    logEvent('modificacion', "Socio actualizado: {$nombre} {$apellido}", Auth::id(), $socioId, $sucursal_id);
                    
                    $success = true;
                    $successMessage = 'Socio actualizado correctamente';
                } else {
                    // Generate member code
                    $codigo = $socioModel->generateCode($sucursal_id);
                    $data['codigo'] = $codigo;
                    
                    // Generate QR code filename
                    $qrFilename = $codigo . '_' . time() . '.png';
                    $data['qr_code'] = $qrFilename;
                    
                    // Insert new member
                    $newId = $socioModel->insert($data);
                    
                    // Generate QR code image
                    $qrUrl = generateQrCode($codigo);
                    $qrImagePath = UPLOAD_PATH . 'photos/' . $qrFilename;
                    
                    // Download QR code with validation
                    $context = stream_context_create([
                        'http' => [
                            'timeout' => 10,
                            'user_agent' => 'AccessGYM/1.0'
                        ]
                    ]);
                    
                    $qrContent = @file_get_contents($qrUrl, false, $context);
                    if ($qrContent !== false && strlen($qrContent) > 0) {
                        file_put_contents($qrImagePath, $qrContent);
                    }
                    
                    logEvent('sistema', "Nuevo socio registrado: {$nombre} {$apellido}", Auth::id(), $newId, $sucursal_id);
                    
                    $success = true;
                    $successMessage = 'Socio registrado correctamente con código: ' . $codigo;
                    
                    // Redirect to member detail
                    header("Location: socio_detalle.php?id={$newId}&nuevo=1");
                    exit;
                }
            }
        }
    }
}

$pageTitle = $isEdit ? 'Editar Socio' : 'Nuevo Socio';
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
        <div class="mb-8 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900"><?php echo $pageTitle; ?></h1>
                <p class="text-gray-600">Complete los datos del socio</p>
            </div>
            <a href="socios.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded inline-flex items-center">
                <i class="fas fa-arrow-left mr-2"></i>
                Volver
            </a>
        </div>
        
        <!-- Messages -->
        <?php if (!empty($errors)): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-400"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Se encontraron los siguientes errores:</h3>
                        <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-green-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800"><?php echo $successMessage; ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Form -->
        <form method="POST" enctype="multipart/form-data" class="bg-white rounded-lg shadow p-6">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Personal Information -->
                <div class="col-span-2">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 border-b pb-2">
                        <i class="fas fa-user mr-2"></i>Información Personal
                    </h2>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nombre *</label>
                    <input type="text" name="nombre" required
                           value="<?php echo htmlspecialchars($socio['nombre'] ?? ''); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Apellido *</label>
                    <input type="text" name="apellido" required
                           value="<?php echo htmlspecialchars($socio['apellido'] ?? ''); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <input type="email" name="email"
                           value="<?php echo htmlspecialchars($socio['email'] ?? ''); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Teléfono *</label>
                    <input type="tel" name="telefono" required
                           value="<?php echo htmlspecialchars($socio['telefono'] ?? ''); ?>"
                           placeholder="5551234567"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <p class="mt-1 text-xs text-gray-500">10 dígitos, sin espacios ni guiones</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Teléfono de Emergencia</label>
                    <input type="tel" name="telefono_emergencia"
                           value="<?php echo htmlspecialchars($socio['telefono_emergencia'] ?? ''); ?>"
                           placeholder="5551234567"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha de Nacimiento</label>
                    <input type="date" name="fecha_nacimiento"
                           value="<?php echo htmlspecialchars($socio['fecha_nacimiento'] ?? ''); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Dirección</label>
                    <textarea name="direccion" rows="2"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"><?php echo htmlspecialchars($socio['direccion'] ?? ''); ?></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fotografía</label>
                    <input type="file" name="foto" accept="image/*"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <?php if ($isEdit && !empty($socio['foto'])): ?>
                        <div class="mt-2">
                            <img src="<?php echo '/uploads/photos/' . htmlspecialchars($socio['foto']); ?>" 
                                 alt="Foto actual" class="h-20 w-20 rounded object-cover">
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Membership Information -->
                <div class="col-span-2 mt-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 border-b pb-2">
                        <i class="fas fa-id-card mr-2"></i>Información de Membresía
                    </h2>
                </div>
                
                <?php if (Auth::isSuperadmin() && count($sucursales) > 1): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sucursal *</label>
                    <select name="sucursal_id" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <?php foreach ($sucursales as $sucursal): ?>
                            <option value="<?php echo $sucursal['id']; ?>"
                                    <?php echo ($socio['sucursal_id'] ?? Auth::sucursalId()) == $sucursal['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($sucursal['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php else: ?>
                    <input type="hidden" name="sucursal_id" value="<?php echo Auth::sucursalId(); ?>">
                <?php endif; ?>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Membresía</label>
                    <select name="tipo_membresia_id"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Sin membresía</option>
                        <?php foreach ($tiposMembresia as $tipo): ?>
                            <option value="<?php echo $tipo['id']; ?>"
                                    <?php echo ($socio['tipo_membresia_id'] ?? '') == $tipo['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($tipo['nombre']); ?> - <?php echo formatMoney($tipo['precio']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                    <select name="estado"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="activo" <?php echo ($socio['estado'] ?? 'inactivo') == 'activo' ? 'selected' : ''; ?>>Activo</option>
                        <option value="inactivo" <?php echo ($socio['estado'] ?? 'inactivo') == 'inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                        <option value="suspendido" <?php echo ($socio['estado'] ?? '') == 'suspendido' ? 'selected' : ''; ?>>Suspendido</option>
                        <option value="vencido" <?php echo ($socio['estado'] ?? '') == 'vencido' ? 'selected' : ''; ?>>Vencido</option>
                    </select>
                </div>
                
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Notas</label>
                    <textarea name="notas" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"><?php echo htmlspecialchars($socio['notas'] ?? ''); ?></textarea>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="mt-6 flex justify-end space-x-3">
                <a href="socios.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-6 rounded">
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
