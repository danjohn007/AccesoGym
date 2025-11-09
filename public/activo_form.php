<?php
require_once 'bootstrap.php';
Auth::requireRole(['superadmin', 'admin']);

$db = Database::getInstance();
$conn = $db->getConnection();

$user = Auth::user();
$isEdit = isset($_GET['id']) && !empty($_GET['id']);
$activoId = $isEdit ? (int)$_GET['id'] : null;

$activo = null;
if ($isEdit) {
    $stmt = $conn->prepare("SELECT * FROM activos_inventario WHERE id = ?");
    $stmt->execute([$activoId]);
    $activo = $stmt->fetch(PDO::FETCH_ASSOC);
}

$sucursales = [];
if (Auth::isSuperadmin()) {
    $sucursales = $conn->query("SELECT id, nombre FROM sucursales WHERE activo=1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $conn->prepare("SELECT id, nombre FROM sucursales WHERE id = ?");
    $stmt->execute([Auth::sucursalId()]);
    $sucursales = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de seguridad inválido';
    } else {
        $nombre = sanitize($_POST['nombre'] ?? '');
        $codigo = sanitize($_POST['codigo'] ?? '');
        $tipo = sanitize($_POST['tipo'] ?? '');
        $sucursal_id = (int)($_POST['sucursal_id'] ?? Auth::sucursalId());
        $ubicacion = sanitize($_POST['ubicacion'] ?? '');
        $estado = sanitize($_POST['estado'] ?? 'bueno');
        $cantidad = (int)($_POST['cantidad'] ?? 1);
        $valor_compra = !empty($_POST['valor_compra']) ? (float)$_POST['valor_compra'] : null;
        $fecha_compra = !empty($_POST['fecha_compra']) ? sanitize($_POST['fecha_compra']) : null;
        $proveedor = sanitize($_POST['proveedor'] ?? '');
        $descripcion = sanitize($_POST['descripcion'] ?? '');
        $notas = sanitize($_POST['notas'] ?? '');
        
        if (empty($nombre)) $errors[] = 'El nombre es requerido';
        if (empty($codigo)) $errors[] = 'El código es requerido';
        if (empty($tipo)) $errors[] = 'El tipo es requerido';
        if (!$sucursal_id) $errors[] = 'La sucursal es requerida';
        if ($cantidad < 1) $errors[] = 'La cantidad debe ser al menos 1';
        
        // Check if code already exists
        if (!$isEdit || $activo['codigo'] !== $codigo) {
            $stmt = $conn->prepare("SELECT id FROM activos_inventario WHERE codigo = ?");
            $stmt->execute([$codigo]);
            if ($stmt->fetch()) {
                $errors[] = 'Ya existe un activo con ese código';
            }
        }
        
        // Handle photo upload
        $foto = $activo['foto'] ?? null;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = uploadFile($_FILES['foto'], 'activos');
            if ($uploadResult['success']) {
                // Delete old photo if editing
                if ($isEdit && !empty($activo['foto'])) {
                    deleteFile($activo['foto'], 'activos');
                }
                $foto = $uploadResult['filename'];
            } else {
                $errors[] = $uploadResult['message'];
            }
        }
        
        if (empty($errors)) {
            try {
                if ($isEdit) {
                    $stmt = $conn->prepare(
                        "UPDATE activos_inventario SET 
                         nombre = ?, codigo = ?, tipo = ?, sucursal_id = ?, ubicacion = ?,
                         estado = ?, cantidad = ?, valor_compra = ?, fecha_compra = ?,
                         proveedor = ?, descripcion = ?, notas = ?, foto = ?
                         WHERE id = ?"
                    );
                    $stmt->execute([
                        $nombre, $codigo, $tipo, $sucursal_id, $ubicacion,
                        $estado, $cantidad, $valor_compra, $fecha_compra,
                        $proveedor, $descripcion, $notas, $foto, $activoId
                    ]);
                    
                    logEvent('modificacion', "Activo actualizado: {$nombre}", Auth::id(), null, $sucursal_id);
                    $successMessage = 'Activo actualizado correctamente';
                } else {
                    $stmt = $conn->prepare(
                        "INSERT INTO activos_inventario 
                         (nombre, codigo, tipo, sucursal_id, ubicacion, estado, cantidad, 
                          valor_compra, fecha_compra, proveedor, descripcion, notas, foto) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                    );
                    $stmt->execute([
                        $nombre, $codigo, $tipo, $sucursal_id, $ubicacion,
                        $estado, $cantidad, $valor_compra, $fecha_compra,
                        $proveedor, $descripcion, $notas, $foto
                    ]);
                    
                    logEvent('sistema', "Nuevo activo registrado: {$nombre}", Auth::id(), null, $sucursal_id);
                    $successMessage = 'Activo registrado correctamente';
                    
                    header("Location: activos_inventario.php");
                    exit;
                }
                
                $success = true;
            } catch (Exception $e) {
                $errors[] = 'Error al guardar el activo: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = $isEdit ? 'Editar Activo' : 'Nuevo Activo';
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
    
    <div class="container mx-auto px-4 py-8 max-w-3xl">
        <!-- Header -->
        <div class="mb-8 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900"><?php echo $pageTitle; ?></h1>
                <p class="text-gray-600">Registrar equipos, mobiliario e inventario</p>
            </div>
            <a href="activos_inventario.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded inline-flex items-center">
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
        <form method="POST" enctype="multipart/form-data" class="bg-white rounded-lg shadow p-6">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Basic Information -->
                <div class="col-span-2">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 border-b pb-2">
                        <i class="fas fa-info-circle mr-2"></i>Información Básica
                    </h2>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nombre *</label>
                    <input type="text" name="nombre" required
                           value="<?php echo htmlspecialchars($activo['nombre'] ?? ''); ?>"
                           placeholder="Ej: Caminadora Profesional"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Código *</label>
                    <input type="text" name="codigo" required
                           value="<?php echo htmlspecialchars($activo['codigo'] ?? ''); ?>"
                           placeholder="Ej: EQ-001"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tipo *</label>
                    <select name="tipo" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Seleccionar tipo</option>
                        <option value="equipo" <?php echo ($activo['tipo'] ?? '') === 'equipo' ? 'selected' : ''; ?>>Equipo</option>
                        <option value="mobiliario" <?php echo ($activo['tipo'] ?? '') === 'mobiliario' ? 'selected' : ''; ?>>Mobiliario</option>
                        <option value="electronico" <?php echo ($activo['tipo'] ?? '') === 'electronico' ? 'selected' : ''; ?>>Electrónico</option>
                        <option value="consumible" <?php echo ($activo['tipo'] ?? '') === 'consumible' ? 'selected' : ''; ?>>Consumible</option>
                        <option value="otro" <?php echo ($activo['tipo'] ?? '') === 'otro' ? 'selected' : ''; ?>>Otro</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Estado *</label>
                    <select name="estado" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="excelente" <?php echo ($activo['estado'] ?? 'bueno') === 'excelente' ? 'selected' : ''; ?>>Excelente</option>
                        <option value="bueno" <?php echo ($activo['estado'] ?? 'bueno') === 'bueno' ? 'selected' : ''; ?>>Bueno</option>
                        <option value="regular" <?php echo ($activo['estado'] ?? '') === 'regular' ? 'selected' : ''; ?>>Regular</option>
                        <option value="malo" <?php echo ($activo['estado'] ?? '') === 'malo' ? 'selected' : ''; ?>>Malo</option>
                        <option value="fuera_servicio" <?php echo ($activo['estado'] ?? '') === 'fuera_servicio' ? 'selected' : ''; ?>>Fuera de Servicio</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sucursal *</label>
                    <select name="sucursal_id" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <?php foreach ($sucursales as $sucursal): ?>
                            <option value="<?php echo $sucursal['id']; ?>"
                                    <?php echo ($activo['sucursal_id'] ?? Auth::sucursalId()) == $sucursal['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($sucursal['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ubicación</label>
                    <input type="text" name="ubicacion"
                           value="<?php echo htmlspecialchars($activo['ubicacion'] ?? ''); ?>"
                           placeholder="Ej: Área de pesas, Piso 2"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Cantidad *</label>
                    <input type="number" name="cantidad" min="1" required
                           value="<?php echo htmlspecialchars($activo['cantidad'] ?? 1); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <!-- Purchase Information -->
                <div class="col-span-2 mt-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 border-b pb-2">
                        <i class="fas fa-shopping-cart mr-2"></i>Información de Compra
                    </h2>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Valor de Compra</label>
                    <div class="relative">
                        <span class="absolute left-3 top-2 text-gray-500">$</span>
                        <input type="number" name="valor_compra" step="0.01" min="0"
                               value="<?php echo htmlspecialchars($activo['valor_compra'] ?? ''); ?>"
                               placeholder="0.00"
                               class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha de Compra</label>
                    <input type="date" name="fecha_compra"
                           value="<?php echo htmlspecialchars($activo['fecha_compra'] ?? ''); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Proveedor</label>
                    <input type="text" name="proveedor"
                           value="<?php echo htmlspecialchars($activo['proveedor'] ?? ''); ?>"
                           placeholder="Nombre del proveedor"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <!-- Additional Information -->
                <div class="col-span-2 mt-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 border-b pb-2">
                        <i class="fas fa-file-alt mr-2"></i>Información Adicional
                    </h2>
                </div>
                
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
                    <textarea name="descripcion" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"><?php echo htmlspecialchars($activo['descripcion'] ?? ''); ?></textarea>
                </div>
                
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Notas</label>
                    <textarea name="notas" rows="2"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"><?php echo htmlspecialchars($activo['notas'] ?? ''); ?></textarea>
                </div>
                
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fotografía</label>
                    <input type="file" name="foto" accept="image/*"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <?php if ($isEdit && !empty($activo['foto'])): ?>
                        <div class="mt-2">
                            <img src="<?php echo UPLOAD_URL . '/uploads/activos/' . htmlspecialchars($activo['foto']); ?>" 
                                 alt="Foto actual" class="h-32 w-32 rounded object-cover">
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="mt-6 flex justify-end space-x-3">
                <a href="activos_inventario.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-6 rounded">
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
