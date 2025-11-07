<?php
require_once 'bootstrap.php';
Auth::requireRole('admin');

// SuperAdmin can select branch, Admin is restricted to their branch
$user = Auth::user();
$sucursal_id = Auth::isSuperadmin() ? ($_POST['sucursal_id'] ?? Auth::sucursalId()) : Auth::sucursalId();

$success = false;
$error = false;
$message = '';
$importResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token'])) {
    if (Auth::verifyCsrfToken($_POST['csrf_token'])) {
        if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
            try {
                $tipo = $_POST['tipo_importacion'];
                $file = $_FILES['csv_file']['tmp_name'];
                $handle = fopen($file, 'r');
                
                $db = Database::getInstance();
                $conn = $db->getConnection();
                
                $imported = 0;
                $errors = 0;
                $errorMessages = [];
                
                // Skip header row
                fgetcsv($handle);
                
                while (($data = fgetcsv($handle)) !== false) {
                    try {
                        if ($tipo === 'socios') {
                            // Import members: nombre, apellido, email, telefono, tipo_membresia_id
                            $stmt = $conn->prepare("INSERT INTO socios (codigo, nombre, apellido, email, telefono, sucursal_id, estado) 
                                                   VALUES (?, ?, ?, ?, ?, ?, 'inactivo')");
                            $codigo = 'SOC' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
                            $stmt->execute([$codigo, $data[0], $data[1], $data[2], $data[3], $sucursal_id]);
                            $imported++;
                        } elseif ($tipo === 'membresias') {
                            // Import memberships: nombre, descripcion, duracion_dias, precio
                            $stmt = $conn->prepare("INSERT INTO tipos_membresia (nombre, descripcion, duracion_dias, precio) 
                                                   VALUES (?, ?, ?, ?)");
                            $stmt->execute([$data[0], $data[1], (int)$data[2], (float)$data[3]]);
                            $imported++;
                        }
                    } catch (Exception $e) {
                        $errors++;
                        $errorMessages[] = "Fila " . ($imported + $errors + 1) . ": " . $e->getMessage();
                    }
                }
                
                fclose($handle);
                
                $importResult = [
                    'imported' => $imported,
                    'errors' => $errors,
                    'errorMessages' => $errorMessages
                ];
                
                $success = true;
                $message = "Importación completada: $imported registros importados, $errors errores";
                logEvent('sistema', 'Importación de datos: ' . $tipo, Auth::id(), null, null);
                
            } catch (Exception $e) {
                $error = true;
                $message = 'Error al importar: ' . $e->getMessage();
            }
        } else {
            $error = true;
            $message = 'Por favor selecciona un archivo CSV válido';
        }
    }
}

$pageTitle = 'Importar Datos';
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
    
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">
                <i class="fas fa-file-import text-blue-600 mr-2"></i><?php echo $pageTitle; ?>
            </h1>
            <p class="text-gray-600 mt-2">Importa datos masivos desde archivos CSV</p>
        </div>
        
        <?php if ($success && $importResult): ?>
        <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded">
            <div class="flex">
                <i class="fas fa-check-circle text-green-400 text-xl mr-3"></i>
                <div class="flex-1">
                    <p class="text-sm font-medium text-green-800"><?php echo htmlspecialchars($message); ?></p>
                    <?php if ($importResult['errors'] > 0): ?>
                        <details class="mt-2">
                            <summary class="text-xs text-green-700 cursor-pointer">Ver errores (<?php echo $importResult['errors']; ?>)</summary>
                            <ul class="mt-2 text-xs text-green-700 list-disc list-inside">
                                <?php foreach (array_slice($importResult['errorMessages'], 0, 10) as $errorMsg): ?>
                                    <li><?php echo htmlspecialchars($errorMsg); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </details>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded">
            <p class="text-sm text-red-800"><i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($message); ?></p>
        </div>
        <?php endif; ?>
        
        <!-- Import Form -->
        <div class="bg-white rounded-lg shadow p-8">
            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Tipo de Importación <span class="text-red-500">*</span>
                    </label>
                    <select name="tipo_importacion" required 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">Seleccionar...</option>
                        <option value="socios">Socios</option>
                        <option value="membresias">Tipos de Membresía</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Archivo CSV <span class="text-red-500">*</span>
                    </label>
                    <input type="file" name="csv_file" accept=".csv" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <p class="mt-2 text-xs text-gray-500">Archivo CSV con codificación UTF-8. Primera fila debe contener encabezados.</p>
                </div>
                
                <div class="bg-blue-50 border-l-4 border-blue-500 p-4">
                    <h4 class="font-semibold text-blue-900 mb-2">Formato de Archivos CSV</h4>
                    <div class="space-y-2 text-sm text-blue-800">
                        <div>
                            <strong>Socios:</strong>
                            <code class="bg-white px-2 py-1 rounded text-xs">nombre,apellido,email,telefono</code>
                        </div>
                        <div>
                            <strong>Membresías:</strong>
                            <code class="bg-white px-2 py-1 rounded text-xs">nombre,descripcion,duracion_dias,precio</code>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-4">
                    <a href="dashboard.php" class="px-6 py-2 border rounded-lg text-gray-700 hover:bg-gray-50">Cancelar</a>
                    <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold">
                        <i class="fas fa-upload mr-2"></i>Importar Datos
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Templates -->
        <div class="mt-6 bg-white rounded-lg shadow p-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-download mr-2"></i>Plantillas CSV
            </h3>
            <p class="text-sm text-gray-600 mb-4">Descarga plantillas de ejemplo para importar datos correctamente</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <a href="data:text/csv;charset=utf-8,nombre,apellido,email,telefono%0AJuan,Pérez,juan@example.com,1234567890%0A" 
                   download="plantilla_socios.csv"
                   class="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50">
                    <div class="flex items-center">
                        <i class="fas fa-file-csv text-2xl text-green-600 mr-3"></i>
                        <div>
                            <div class="font-medium text-gray-900">Plantilla Socios</div>
                            <div class="text-xs text-gray-500">CSV con ejemplo de socios</div>
                        </div>
                    </div>
                    <i class="fas fa-download text-gray-400"></i>
                </a>
                
                <a href="data:text/csv;charset=utf-8,nombre,descripcion,duracion_dias,precio%0AMensual,Acceso mensual,30,500%0A" 
                   download="plantilla_membresias.csv"
                   class="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50">
                    <div class="flex items-center">
                        <i class="fas fa-file-csv text-2xl text-blue-600 mr-3"></i>
                        <div>
                            <div class="font-medium text-gray-900">Plantilla Membresías</div>
                            <div class="text-xs text-gray-500">CSV con ejemplo de membresías</div>
                        </div>
                    </div>
                    <i class="fas fa-download text-gray-400"></i>
                </a>
            </div>
        </div>
    </div>
</body>
</html>
