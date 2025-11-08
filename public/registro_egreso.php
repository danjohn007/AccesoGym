<?php
require_once 'bootstrap.php';
Auth::requireRole(['superadmin', 'admin']);

$db = Database::getInstance();
$conn = $db->getConnection();

$user = Auth::user();
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de seguridad inválido';
    } else {
        $concepto = sanitize($_POST['concepto'] ?? '');
        $categoria = sanitize($_POST['categoria'] ?? '');
        $monto = (float)($_POST['monto'] ?? 0);
        $fecha_gasto = sanitize($_POST['fecha_gasto'] ?? '');
        $sucursal_id = Auth::isSuperadmin() ? (int)($_POST['sucursal_id'] ?? 0) : Auth::sucursalId();
        $notas = sanitize($_POST['notas'] ?? '');
        
        if (empty($concepto)) $errors[] = 'El concepto es requerido';
        if (empty($categoria)) $errors[] = 'La categoría es requerida';
        if ($monto <= 0) $errors[] = 'El monto debe ser mayor a 0';
        if (empty($fecha_gasto)) $errors[] = 'La fecha es requerida';
        if (!$sucursal_id) $errors[] = 'La sucursal es requerida';
        
        // Handle file upload (mandatory)
        $comprobante = null;
        if (!isset($_FILES['comprobante']) || $_FILES['comprobante']['error'] === UPLOAD_ERR_NO_FILE) {
            $errors[] = 'El comprobante es requerido';
        } elseif ($_FILES['comprobante']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = uploadFile($_FILES['comprobante'], 'documents');
            if ($uploadResult['success']) {
                $comprobante = $uploadResult['filename'];
            } else {
                $errors[] = $uploadResult['message'];
            }
        } else {
            $errors[] = 'Error al cargar el comprobante';
        }
        
        if (empty($errors)) {
            try {
                $stmt = $conn->prepare(
                    "INSERT INTO gastos (concepto, categoria, monto, fecha_gasto, sucursal_id, usuario_registro, comprobante, notas) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->execute([
                    $concepto,
                    $categoria,
                    $monto,
                    $fecha_gasto,
                    $sucursal_id,
                    Auth::id(),
                    $comprobante,
                    $notas
                ]);
                
                logEvent('financiero', "Egreso registrado: {$concepto} - \${$monto}", Auth::id(), null, $sucursal_id);
                
                $success = true;
                $successMessage = 'Egreso registrado correctamente';
                
                // Reset form
                $_POST = [];
            } catch (Exception $e) {
                $errors[] = 'Error al registrar el egreso: ' . $e->getMessage();
            }
        }
    }
}

$sucursales = [];
if (Auth::isSuperadmin()) {
    $stmt = $conn->query("SELECT id, nombre FROM sucursales WHERE activo=1 ORDER BY nombre");
    $sucursales = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get financial categories for expenses
$stmt = $conn->query("SELECT * FROM categorias_financieras WHERE activo=1 AND (tipo='egreso' OR tipo='ambos') ORDER BY nombre");
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Registrar Egreso';
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
                <h1 class="text-3xl font-bold text-gray-900">
                    <i class="fas fa-minus-circle text-red-600 mr-2"></i><?php echo $pageTitle; ?>
                </h1>
                <p class="text-gray-600">Registrar un nuevo movimiento de egreso</p>
            </div>
            <a href="modulo_financiero.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded inline-flex items-center">
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
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Concepto *</label>
                    <input type="text" name="concepto" required
                           value="<?php echo htmlspecialchars($_POST['concepto'] ?? ''); ?>"
                           placeholder="Ej: Pago de electricidad"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Categoría *</label>
                    <select name="categoria" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Seleccionar categoría</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['nombre']); ?>" 
                                    <?php echo ($_POST['categoria'] ?? '') === $cat['nombre'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Monto *</label>
                        <div class="relative">
                            <span class="absolute left-3 top-2 text-gray-500">$</span>
                            <input type="number" name="monto" step="0.01" min="0.01" required
                                   value="<?php echo htmlspecialchars($_POST['monto'] ?? ''); ?>"
                                   placeholder="0.00"
                                   class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fecha *</label>
                        <input type="date" name="fecha_gasto" required
                               value="<?php echo htmlspecialchars($_POST['fecha_gasto'] ?? date('Y-m-d')); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
                
                <?php if (Auth::isSuperadmin()): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sucursal *</label>
                    <select name="sucursal_id" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Seleccionar sucursal</option>
                        <?php foreach ($sucursales as $sucursal): ?>
                            <option value="<?php echo $sucursal['id']; ?>"
                                    <?php echo ($_POST['sucursal_id'] ?? '') == $sucursal['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($sucursal['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Comprobante *</label>
                    <input type="file" name="comprobante" accept=".pdf,.jpg,.jpeg,.png" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <p class="mt-1 text-xs text-gray-500">PDF, JPG o PNG (máximo 5MB)</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Notas</label>
                    <textarea name="notas" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"><?php echo htmlspecialchars($_POST['notas'] ?? ''); ?></textarea>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="mt-6 flex justify-end space-x-3">
                <a href="modulo_financiero.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-6 rounded">
                    Cancelar
                </a>
                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-6 rounded">
                    <i class="fas fa-save mr-2"></i>Registrar Egreso
                </button>
            </div>
        </form>
    </div>
</body>
</html>
