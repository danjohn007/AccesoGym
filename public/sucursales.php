<?php
require_once 'bootstrap.php';
Auth::requireRole('superadmin');

require_once __DIR__ . '/../app/models/Sucursal.php';

$sucursalModel = new Sucursal();
$db = Database::getInstance();
$conn = $db->getConnection();

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
                $direccion = sanitize($_POST['direccion'] ?? '');
                $telefono = sanitize($_POST['telefono'] ?? '');
                $email = sanitize($_POST['email'] ?? '');
                $activo = isset($_POST['activo']) ? 1 : 0;
                
                if (empty($nombre)) {
                    throw new Exception('El nombre es requerido');
                }
                
                if (!empty($telefono) && !validatePhone($telefono)) {
                    throw new Exception('Teléfono inválido (10 dígitos)');
                }
                
                if (!empty($email) && !validateEmail($email)) {
                    throw new Exception('Email inválido');
                }
                
                $data = [
                    'nombre' => $nombre,
                    'direccion' => $direccion,
                    'telefono' => $telefono,
                    'email' => $email,
                    'activo' => $activo
                ];
                
                if ($id) {
                    $sucursalModel->update($id, $data);
                    $message = 'Sucursal actualizada correctamente';
                } else {
                    $sucursalModel->insert($data);
                    $message = 'Sucursal creada correctamente';
                }
                
                $success = true;
                logEvent('sistema', $message, Auth::id(), null, $id);
                $action = 'list';
            } elseif ($action === 'delete') {
                $id = $_POST['id'] ?? 0;
                
                // Check if branch has members
                $stmt = $conn->prepare("SELECT COUNT(*) as total FROM socios WHERE sucursal_id = ?");
                $stmt->execute([$id]);
                $sociosCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                
                // Check if branch has staff users
                $stmt = $conn->prepare("SELECT COUNT(*) as total FROM usuarios_staff WHERE sucursal_id = ?");
                $stmt->execute([$id]);
                $staffCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                
                // Check if branch has devices
                $stmt = $conn->prepare("SELECT COUNT(*) as total FROM dispositivos_shelly WHERE sucursal_id = ?");
                $stmt->execute([$id]);
                $devicesCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                
                if ($sociosCount > 0) {
                    throw new Exception('No se puede eliminar una sucursal con socios registrados');
                }
                
                if ($staffCount > 0) {
                    throw new Exception('No se puede eliminar una sucursal con personal asignado');
                }
                
                if ($devicesCount > 0) {
                    throw new Exception('No se puede eliminar una sucursal con dispositivos registrados');
                }
                
                $sucursalModel->delete($id);
                $message = 'Sucursal eliminada correctamente';
                $success = true;
                logEvent('sistema', $message, Auth::id(), null, $id);
                $action = 'list';
            }
        } catch (Exception $e) {
            $error = true;
            $message = 'Error: ' . $e->getMessage();
        }
    }
}

$sucursales = [];
$sucursal = null;

if ($action === 'list') {
    $stmt = $conn->query("
        SELECT s.*,
            (SELECT COUNT(*) FROM socios WHERE sucursal_id = s.id) as total_socios,
            (SELECT COUNT(*) FROM usuarios_staff WHERE sucursal_id = s.id) as total_staff
        FROM sucursales s
        ORDER BY s.created_at DESC
    ");
    $sucursales = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($action === 'edit') {
    $id = $_GET['id'] ?? 0;
    $sucursal = $sucursalModel->find($id);
}

$pageTitle = 'Gestión de Sucursales';
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
                    <i class="fas fa-building text-blue-600 mr-2"></i><?php echo $pageTitle; ?>
                </h1>
                <p class="text-gray-600 mt-2">Administra las sucursales del sistema</p>
            </div>
            <?php if ($action === 'list'): ?>
            <a href="?action=create" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold">
                <i class="fas fa-plus mr-2"></i>Nueva Sucursal
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
        <!-- List View -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contacto</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Socios</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Staff</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($sucursales as $s): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($s['nombre']); ?></div>
                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($s['direccion'] ?? '-'); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($s['telefono']): ?>
                            <div class="text-sm text-gray-900"><i class="fas fa-phone mr-1"></i><?php echo htmlspecialchars($s['telefono']); ?></div>
                            <?php endif; ?>
                            <?php if ($s['email']): ?>
                            <div class="text-sm text-gray-500"><i class="fas fa-envelope mr-1"></i><?php echo htmlspecialchars($s['email']); ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full"><?php echo $s['total_socios']; ?></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full"><?php echo $s['total_staff']; ?></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php echo $s['activo'] ? '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Activa</span>' : '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Inactiva</span>'; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="?action=edit&id=<?php echo $s['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php if ($s['total_socios'] == 0): ?>
                            <form method="POST" action="?action=delete" class="inline" onsubmit="return confirm('¿Está seguro de eliminar esta sucursal?');">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                                <button type="submit" class="text-red-600 hover:text-red-900">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php else: ?>
        <!-- Form View -->
        <div class="bg-white rounded-lg shadow p-6">
            <form method="POST" action="?action=<?php echo $action; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <?php if ($sucursal): ?>
                <input type="hidden" name="id" value="<?php echo $sucursal['id']; ?>">
                <?php endif; ?>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nombre *</label>
                        <input type="text" name="nombre" required
                               value="<?php echo htmlspecialchars($sucursal['nombre'] ?? ''); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Dirección</label>
                        <textarea name="direccion" rows="2"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"><?php echo htmlspecialchars($sucursal['direccion'] ?? ''); ?></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Teléfono</label>
                        <input type="tel" name="telefono"
                               value="<?php echo htmlspecialchars($sucursal['telefono'] ?? ''); ?>"
                               placeholder="5551234567"
                               maxlength="10"
                               pattern="[0-9]{10}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <p class="mt-1 text-xs text-gray-500">10 dígitos, sin espacios ni guiones</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                        <input type="email" name="email"
                               value="<?php echo htmlspecialchars($sucursal['email'] ?? ''); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div class="col-span-2">
                        <label class="flex items-center">
                            <input type="checkbox" name="activo" <?php echo ($sucursal['activo'] ?? 1) ? 'checked' : ''; ?>
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-700">Sucursal activa</span>
                        </label>
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end space-x-3">
                    <a href="sucursales.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-6 rounded">
                        Cancelar
                    </a>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                        <i class="fas fa-save mr-2"></i><?php echo $action === 'edit' ? 'Actualizar' : 'Crear'; ?>
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
