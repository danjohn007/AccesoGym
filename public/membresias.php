<?php
require_once 'bootstrap.php';
Auth::requireRole('admin');

$db = Database::getInstance();
$conn = $db->getConnection();

// Handle CRUD operations
$action = $_GET['action'] ?? 'list';
$success = false;
$error = false;
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token'])) {
    if (Auth::verifyCsrfToken($_POST['csrf_token'])) {
        try {
            if ($action === 'create' || $action === 'edit') {
                $id = $_POST['id'] ?? null;
                $nombre = sanitize($_POST['nombre']);
                $descripcion = sanitize($_POST['descripcion']);
                $duracion_dias = (int)$_POST['duracion_dias'];
                $precio = (float)$_POST['precio'];
                $acceso_horario_inicio = $_POST['acceso_horario_inicio'];
                $acceso_horario_fin = $_POST['acceso_horario_fin'];
                $dias_semana = implode(',', $_POST['dias_semana'] ?? []);
                $color = $_POST['color'];
                $activo = isset($_POST['activo']) ? 1 : 0;
                
                if ($id) {
                    $stmt = $conn->prepare("UPDATE tipos_membresia SET nombre=?, descripcion=?, duracion_dias=?, 
                                          precio=?, acceso_horario_inicio=?, acceso_horario_fin=?, 
                                          dias_semana=?, color=?, activo=? WHERE id=?");
                    $stmt->execute([$nombre, $descripcion, $duracion_dias, $precio, $acceso_horario_inicio, 
                                  $acceso_horario_fin, $dias_semana, $color, $activo, $id]);
                    $message = 'Membresía actualizada correctamente';
                } else {
                    $stmt = $conn->prepare("INSERT INTO tipos_membresia (nombre, descripcion, duracion_dias, precio, 
                                          acceso_horario_inicio, acceso_horario_fin, dias_semana, color, activo) 
                                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$nombre, $descripcion, $duracion_dias, $precio, $acceso_horario_inicio, 
                                  $acceso_horario_fin, $dias_semana, $color, $activo]);
                    $message = 'Membresía creada correctamente';
                }
                
                $success = true;
                logEvent('sistema', $message, Auth::id(), null, null);
                $action = 'list';
            } elseif ($action === 'delete') {
                $id = $_POST['id'];
                $stmt = $conn->prepare("UPDATE tipos_membresia SET activo=0 WHERE id=?");
                $stmt->execute([$id]);
                $message = 'Membresía desactivada correctamente';
                $success = true;
                $action = 'list';
            }
        } catch (Exception $e) {
            $error = true;
            $message = 'Error: ' . $e->getMessage();
        }
    }
}

// Load membership data
$membresias = [];
if ($action === 'list') {
    $stmt = $conn->query("SELECT * FROM tipos_membresia ORDER BY activo DESC, precio ASC");
    $membresias = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($action === 'edit') {
    $id = $_GET['id'] ?? 0;
    $stmt = $conn->prepare("SELECT * FROM tipos_membresia WHERE id=?");
    $stmt->execute([$id]);
    $membresia = $stmt->fetch(PDO::FETCH_ASSOC);
}

$pageTitle = 'Gestión de Membresías';
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
                <h1 class="text-3xl font-bold text-gray-900">
                    <i class="fas fa-id-card text-blue-600 mr-2"></i><?php echo $pageTitle; ?>
                </h1>
                <p class="text-gray-600 mt-2">Administra los tipos de membresías del gimnasio</p>
            </div>
            <?php if ($action === 'list'): ?>
            <a href="?action=create" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold">
                <i class="fas fa-plus mr-2"></i>Nueva Membresía
            </a>
            <?php endif; ?>
        </div>
        
        <?php if ($success): ?>
        <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded">
            <div class="flex">
                <i class="fas fa-check-circle text-green-400 mr-3"></i>
                <p class="text-sm text-green-800"><?php echo htmlspecialchars($message); ?></p>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded">
            <div class="flex">
                <i class="fas fa-exclamation-circle text-red-400 mr-3"></i>
                <p class="text-sm text-red-800"><?php echo htmlspecialchars($message); ?></p>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($action === 'list'): ?>
        <!-- Memberships List -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Membresía</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duración</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Precio</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Horario</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($membresias as $m): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <span class="w-4 h-4 rounded-full mr-3" style="background-color: <?php echo htmlspecialchars($m['color']); ?>"></span>
                                <div>
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($m['nombre']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($m['descripcion']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900"><?php echo $m['duracion_dias']; ?> días</td>
                        <td class="px-6 py-4 text-sm font-semibold text-gray-900">$<?php echo number_format($m['precio'], 2); ?></td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <?php echo substr($m['acceso_horario_inicio'], 0, 5); ?> - <?php echo substr($m['acceso_horario_fin'], 0, 5); ?>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs rounded-full <?php echo $m['activo'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo $m['activo'] ? 'Activo' : 'Inactivo'; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <a href="?action=edit&id=<?php echo $m['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                            <?php if ($m['activo']): ?>
                            <form method="POST" class="inline" onsubmit="return confirm('¿Desactivar esta membresía?');">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="id" value="<?php echo $m['id']; ?>">
                                <button type="submit" name="action" value="delete" class="text-red-600 hover:text-red-900">
                                    <i class="fas fa-trash"></i> Desactivar
                                </button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php elseif ($action === 'create' || $action === 'edit'): ?>
        <!-- Membership Form -->
        <div class="bg-white rounded-lg shadow p-8">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <?php if ($action === 'edit'): ?>
                <input type="hidden" name="id" value="<?php echo $membresia['id']; ?>">
                <?php endif; ?>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nombre <span class="text-red-500">*</span></label>
                        <input type="text" name="nombre" required 
                               value="<?php echo htmlspecialchars($membresia['nombre'] ?? ''); ?>"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Duración (días) <span class="text-red-500">*</span></label>
                        <input type="number" name="duracion_dias" required min="1"
                               value="<?php echo $membresia['duracion_dias'] ?? 30; ?>"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
                        <textarea name="descripcion" rows="3"
                                  class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($membresia['descripcion'] ?? ''); ?></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Precio <span class="text-red-500">*</span></label>
                        <input type="number" name="precio" required min="0" step="0.01"
                               value="<?php echo $membresia['precio'] ?? 0; ?>"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Color</label>
                        <input type="color" name="color" 
                               value="<?php echo $membresia['color'] ?? '#3B82F6'; ?>"
                               class="w-full h-10 border rounded-lg">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Horario Inicio</label>
                        <input type="time" name="acceso_horario_inicio" 
                               value="<?php echo $membresia['acceso_horario_inicio'] ?? '06:00'; ?>"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Horario Fin</label>
                        <input type="time" name="acceso_horario_fin" 
                               value="<?php echo $membresia['acceso_horario_fin'] ?? '22:00'; ?>"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Días de la Semana</label>
                        <div class="grid grid-cols-7 gap-2">
                            <?php 
                            $dias = ['L', 'M', 'X', 'J', 'V', 'S', 'D'];
                            $diasSel = explode(',', $membresia['dias_semana'] ?? '1,2,3,4,5,6,7');
                            for ($i = 1; $i <= 7; $i++): 
                            ?>
                            <label class="flex items-center justify-center p-2 border rounded cursor-pointer hover:bg-gray-50">
                                <input type="checkbox" name="dias_semana[]" value="<?php echo $i; ?>" 
                                       <?php echo in_array((string)$i, $diasSel) ? 'checked' : ''; ?> class="mr-1">
                                <?php echo $dias[$i-1]; ?>
                            </label>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="flex items-center">
                            <input type="checkbox" name="activo" value="1" 
                                   <?php echo ($membresia['activo'] ?? 1) ? 'checked' : ''; ?>
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">Membresía activa</span>
                        </label>
                    </div>
                </div>
                
                <div class="mt-8 flex justify-end space-x-4">
                    <a href="membresias.php" class="px-6 py-2 border rounded-lg text-gray-700 hover:bg-gray-50">Cancelar</a>
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
