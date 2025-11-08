<?php
require_once 'bootstrap.php';
Auth::requireRole(['superadmin', 'admin']);

$db = Database::getInstance();
$conn = $db->getConnection();

$action = $_GET['action'] ?? 'list';
$success = false;
$error = false;
$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token'])) {
    if (Auth::verifyCsrfToken($_POST['csrf_token'])) {
        try {
            if ($action === 'create' || $action === 'edit') {
                $id = $_POST['id'] ?? null;
                $nombre = sanitize($_POST['nombre']);
                $tipo = $_POST['tipo'];
                $descripcion = sanitize($_POST['descripcion'] ?? '');
                $color = sanitize($_POST['color'] ?? '#6B7280');
                $icono = sanitize($_POST['icono'] ?? 'fas fa-tag');
                $activo = isset($_POST['activo']) ? 1 : 0;
                
                if ($id) {
                    $stmt = $conn->prepare("UPDATE categorias_financieras SET nombre=?, tipo=?, descripcion=?, color=?, icono=?, activo=? WHERE id=?");
                    $stmt->execute([$nombre, $tipo, $descripcion, $color, $icono, $activo, $id]);
                    $message = 'Categoría actualizada correctamente';
                } else {
                    $stmt = $conn->prepare("INSERT INTO categorias_financieras (nombre, tipo, descripcion, color, icono, activo) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$nombre, $tipo, $descripcion, $color, $icono, $activo]);
                    $message = 'Categoría creada correctamente';
                }
                
                $success = true;
                logEvent('sistema', $message, Auth::id(), null, null);
                $action = 'list';
            } elseif ($action === 'delete') {
                $id = $_POST['id'];
                $stmt = $conn->prepare("DELETE FROM categorias_financieras WHERE id=?");
                $stmt->execute([$id]);
                $message = 'Categoría eliminada correctamente';
                $success = true;
                $action = 'list';
            }
        } catch (Exception $e) {
            $error = true;
            $message = 'Error: ' . $e->getMessage();
        }
    }
}

// Load data
$categorias = [];
if ($action === 'list') {
    $stmt = $conn->query("SELECT * FROM categorias_financieras ORDER BY tipo, nombre");
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($action === 'edit') {
    $id = $_GET['id'] ?? 0;
    $stmt = $conn->prepare("SELECT * FROM categorias_financieras WHERE id=?");
    $stmt->execute([$id]);
    $categoria = $stmt->fetch(PDO::FETCH_ASSOC);
}

$pageTitle = 'Categorías Financieras';
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
        <div class="mb-8 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900"><i class="fas fa-tags text-blue-600 mr-2"></i><?php echo $pageTitle; ?></h1>
                <p class="text-gray-600 mt-2">Gestiona las categorías de ingresos y egresos</p>
            </div>
            <?php if ($action === 'list'): ?>
            <a href="?action=create" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold">
                <i class="fas fa-plus mr-2"></i>Nueva Categoría
            </a>
            <?php endif; ?>
        </div>
        
        <?php if ($success): ?>
        <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded">
            <p class="text-sm text-green-800"><i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($message); ?></p>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded">
            <p class="text-sm text-red-800"><i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($message); ?></p>
        </div>
        <?php endif; ?>
        
        <?php if ($action === 'list'): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Income Categories -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 bg-green-50 border-b">
                    <h2 class="text-lg font-semibold text-green-900"><i class="fas fa-arrow-up mr-2"></i>Categorías de Ingresos</h2>
                </div>
                <div class="p-6 space-y-3">
                    <?php foreach ($categorias as $cat): ?>
                        <?php if ($cat['tipo'] === 'ingreso' || $cat['tipo'] === 'ambos'): ?>
                        <div class="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50">
                            <div class="flex items-center space-x-3">
                                <i class="<?php echo htmlspecialchars($cat['icono']); ?> text-xl" style="color: <?php echo htmlspecialchars($cat['color']); ?>"></i>
                                <div>
                                    <p class="font-medium text-gray-900"><?php echo htmlspecialchars($cat['nombre']); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($cat['descripcion']); ?></p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <?php if ($cat['activo']): ?>
                                    <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">Activa</span>
                                <?php else: ?>
                                    <span class="px-2 py-1 text-xs bg-gray-100 text-gray-800 rounded-full">Inactiva</span>
                                <?php endif; ?>
                                <a href="?action=edit&id=<?php echo $cat['id']; ?>" class="text-blue-600 hover:text-blue-900">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Expense Categories -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 bg-red-50 border-b">
                    <h2 class="text-lg font-semibold text-red-900"><i class="fas fa-arrow-down mr-2"></i>Categorías de Egresos</h2>
                </div>
                <div class="p-6 space-y-3">
                    <?php foreach ($categorias as $cat): ?>
                        <?php if ($cat['tipo'] === 'egreso' || $cat['tipo'] === 'ambos'): ?>
                        <div class="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50">
                            <div class="flex items-center space-x-3">
                                <i class="<?php echo htmlspecialchars($cat['icono']); ?> text-xl" style="color: <?php echo htmlspecialchars($cat['color']); ?>"></i>
                                <div>
                                    <p class="font-medium text-gray-900"><?php echo htmlspecialchars($cat['nombre']); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($cat['descripcion']); ?></p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <?php if ($cat['activo']): ?>
                                    <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">Activa</span>
                                <?php else: ?>
                                    <span class="px-2 py-1 text-xs bg-gray-100 text-gray-800 rounded-full">Inactiva</span>
                                <?php endif; ?>
                                <a href="?action=edit&id=<?php echo $cat['id']; ?>" class="text-blue-600 hover:text-blue-900">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <?php elseif ($action === 'create' || $action === 'edit'): ?>
        <div class="bg-white rounded-lg shadow p-8 max-w-2xl mx-auto">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <?php if ($action === 'edit'): ?>
                <input type="hidden" name="id" value="<?php echo $categoria['id']; ?>">
                <?php endif; ?>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nombre <span class="text-red-500">*</span></label>
                        <input type="text" name="nombre" required value="<?php echo htmlspecialchars($categoria['nombre'] ?? ''); ?>"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipo <span class="text-red-500">*</span></label>
                        <select name="tipo" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">Seleccionar...</option>
                            <option value="ingreso" <?php echo ($categoria['tipo'] ?? '') === 'ingreso' ? 'selected' : ''; ?>>Ingreso</option>
                            <option value="egreso" <?php echo ($categoria['tipo'] ?? '') === 'egreso' ? 'selected' : ''; ?>>Egreso</option>
                            <option value="ambos" <?php echo ($categoria['tipo'] ?? '') === 'ambos' ? 'selected' : ''; ?>>Ambos</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
                        <textarea name="descripcion" rows="3" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($categoria['descripcion'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Color</label>
                            <input type="color" name="color" value="<?php echo htmlspecialchars($categoria['color'] ?? '#6B7280'); ?>"
                                   class="w-full h-10 border rounded-lg">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Icono (clase FontAwesome)</label>
                            <input type="text" name="icono" value="<?php echo htmlspecialchars($categoria['icono'] ?? 'fas fa-tag'); ?>"
                                   placeholder="fas fa-tag"
                                   class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="activo" value="1" <?php echo ($categoria['activo'] ?? 1) ? 'checked' : ''; ?>
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">Categoría activa</span>
                        </label>
                    </div>
                </div>
                
                <div class="mt-8 flex justify-end space-x-4">
                    <a href="categorias_financieras.php" class="px-6 py-2 border rounded-lg text-gray-700 hover:bg-gray-50">Cancelar</a>
                    <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold">
                        <i class="fas fa-save mr-2"></i>Guardar
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
