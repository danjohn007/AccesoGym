<?php
require_once 'bootstrap.php';
Auth::requireRole('admin');

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
                $email = sanitize($_POST['email']);
                $telefono = sanitize($_POST['telefono'] ?? '');
                $rol = $_POST['rol'];
                $sucursal_id = $_POST['sucursal_id'];
                $activo = isset($_POST['activo']) ? 1 : 0;
                
                if ($id) {
                    $sql = "UPDATE usuarios_staff SET nombre=?, email=?, telefono=?, rol=?, sucursal_id=?, activo=? WHERE id=?";
                    $params = [$nombre, $email, $telefono, $rol, $sucursal_id, $activo, $id];
                    if (!empty($_POST['password'])) {
                        $password = password_hash($_POST['password'], PASSWORD_BCRYPT, ['cost' => PASSWORD_COST]);
                        $sql = "UPDATE usuarios_staff SET nombre=?, email=?, telefono=?, rol=?, sucursal_id=?, activo=?, password=? WHERE id=?";
                        $params = [$nombre, $email, $telefono, $rol, $sucursal_id, $activo, $password, $id];
                    }
                    $stmt = $conn->prepare($sql);
                    $stmt->execute($params);
                    $message = 'Usuario actualizado correctamente';
                } else {
                    $password = password_hash($_POST['password'], PASSWORD_BCRYPT, ['cost' => PASSWORD_COST]);
                    $stmt = $conn->prepare("INSERT INTO usuarios_staff (nombre, email, telefono, password, rol, sucursal_id, activo) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$nombre, $email, $telefono, $password, $rol, $sucursal_id, $activo]);
                    $message = 'Usuario creado correctamente';
                }
                $success = true;
                logEvent('sistema', $message, Auth::id(), null, null);
                $action = 'list';
            }
        } catch (Exception $e) {
            $error = true;
            $message = 'Error: ' . $e->getMessage();
        }
    }
}

$usuarios = [];
$sucursales = [];
if ($action === 'list') {
    // For SuperAdmin: see all users
    // For Admin: see only users from their branch
    if (Auth::isSuperadmin()) {
        $stmt = $conn->prepare("SELECT u.*, s.nombre as sucursal_nombre FROM usuarios_staff u LEFT JOIN sucursales s ON u.sucursal_id=s.id ORDER BY u.created_at DESC");
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("SELECT u.*, s.nombre as sucursal_nombre FROM usuarios_staff u LEFT JOIN sucursales s ON u.sucursal_id=s.id WHERE u.sucursal_id = ? ORDER BY u.created_at DESC");
        $stmt->execute([Auth::sucursalId()]);
    }
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($action === 'edit') {
    $id = $_GET['id'] ?? 0;
    $stmt = $conn->prepare("SELECT * FROM usuarios_staff WHERE id=?");
    $stmt->execute([$id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Load branches based on role
if (Auth::isSuperadmin()) {
    $stmt = $conn->query("SELECT id, nombre FROM sucursales WHERE activo=1");
} else {
    $stmt = $conn->prepare("SELECT id, nombre FROM sucursales WHERE activo=1 AND id=?");
    $stmt->execute([Auth::sucursalId()]);
}
$sucursales = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Gestión de Usuarios';
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
                <h1 class="text-3xl font-bold text-gray-900"><i class="fas fa-user-shield text-blue-600 mr-2"></i><?php echo $pageTitle; ?></h1>
                <p class="text-gray-600 mt-2">Administra los usuarios del sistema</p>
            </div>
            <?php if ($action === 'list'): ?>
            <a href="?action=create" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold">
                <i class="fas fa-plus mr-2"></i>Nuevo Usuario
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
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usuario</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rol</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sucursal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($usuarios as $u): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($u['nombre']); ?></div>
                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($u['email']); ?></div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs rounded-full capitalize
                                <?php echo $u['rol'] === 'superadmin' ? 'bg-purple-100 text-purple-800' : 
                                          ($u['rol'] === 'admin' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'); ?>">
                                <?php echo htmlspecialchars($u['rol']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500"><?php echo htmlspecialchars($u['sucursal_nombre'] ?? 'N/A'); ?></td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs rounded-full <?php echo $u['activo'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo $u['activo'] ? 'Activo' : 'Inactivo'; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <a href="?action=edit&id=<?php echo $u['id']; ?>" class="text-blue-600 hover:text-blue-900">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php elseif ($action === 'create' || $action === 'edit'): ?>
        <div class="bg-white rounded-lg shadow p-8">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <?php if ($action === 'edit'): ?>
                <input type="hidden" name="id" value="<?php echo $usuario['id']; ?>">
                <?php endif; ?>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nombre <span class="text-red-500">*</span></label>
                        <input type="text" name="nombre" required value="<?php echo htmlspecialchars($usuario['nombre'] ?? ''); ?>"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email <span class="text-red-500">*</span></label>
                        <input type="email" name="email" required value="<?php echo htmlspecialchars($usuario['email'] ?? ''); ?>"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Teléfono</label>
                        <input type="tel" name="telefono" value="<?php echo htmlspecialchars($usuario['telefono'] ?? ''); ?>"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Contraseña <?php echo $action === 'edit' ? '(dejar vacío para mantener)' : '<span class="text-red-500">*</span>'; ?></label>
                        <input type="password" name="password" <?php echo $action === 'create' ? 'required' : ''; ?>
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Rol <span class="text-red-500">*</span></label>
                        <select name="rol" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">Seleccionar...</option>
                            <?php if (Auth::isSuperadmin()): ?>
                            <option value="superadmin" <?php echo ($usuario['rol'] ?? '') === 'superadmin' ? 'selected' : ''; ?>>Superadmin</option>
                            <?php endif; ?>
                            <option value="admin" <?php echo ($usuario['rol'] ?? '') === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            <option value="recepcionista" <?php echo ($usuario['rol'] ?? '') === 'recepcionista' ? 'selected' : ''; ?>>Recepcionista</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Sucursal <span class="text-red-500">*</span></label>
                        <select name="sucursal_id" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">Seleccionar...</option>
                            <?php foreach ($sucursales as $s): ?>
                            <option value="<?php echo $s['id']; ?>" <?php echo ($usuario['sucursal_id'] ?? '') == $s['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($s['nombre']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="flex items-center">
                            <input type="checkbox" name="activo" value="1" <?php echo ($usuario['activo'] ?? 1) ? 'checked' : ''; ?>
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">Usuario activo</span>
                        </label>
                    </div>
                </div>
                
                <div class="mt-8 flex justify-end space-x-4">
                    <a href="usuarios.php" class="px-6 py-2 border rounded-lg text-gray-700 hover:bg-gray-50">Cancelar</a>
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
