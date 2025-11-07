<?php
require_once 'bootstrap.php';
Auth::requireAuth();

require_once __DIR__ . '/../app/models/Usuario.php';

$usuarioModel = new Usuario();
$user = Auth::user();
$userId = Auth::id();

$userData = $usuarioModel->find($userId);

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de seguridad inválido';
    } else {
        $nombre = sanitize($_POST['nombre'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $telefono = sanitize($_POST['telefono'] ?? '');
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($nombre)) $errors[] = 'El nombre es requerido';
        if (empty($email)) $errors[] = 'El email es requerido';
        if (!validateEmail($email)) $errors[] = 'Email inválido';
        if (!empty($telefono) && !validatePhone($telefono)) $errors[] = 'Teléfono inválido (10 dígitos)';
        
        // Check if email is already taken by another user
        if ($usuarioModel->emailExists($email, $userId)) {
            $errors[] = 'El email ya está en uso por otro usuario';
        }
        
        // Validate password change
        if (!empty($new_password)) {
            if (empty($current_password)) {
                $errors[] = 'Debe ingresar su contraseña actual';
            } elseif (!password_verify($current_password, $userData['password'])) {
                $errors[] = 'La contraseña actual es incorrecta';
            } elseif (strlen($new_password) < 6) {
                $errors[] = 'La nueva contraseña debe tener al menos 6 caracteres';
            } elseif ($new_password !== $confirm_password) {
                $errors[] = 'Las contraseñas nuevas no coinciden';
            }
        }
        
        if (empty($errors)) {
            $data = [
                'nombre' => $nombre,
                'email' => $email,
                'telefono' => $telefono
            ];
            
            $usuarioModel->update($userId, $data);
            
            // Update password if provided
            if (!empty($new_password)) {
                $usuarioModel->updatePassword($userId, $new_password);
            }
            
            // Update session
            $_SESSION['user_name'] = $nombre;
            $_SESSION['user_email'] = $email;
            
            logEvent('modificacion', "Perfil actualizado", $userId, null, $user['sucursal_id']);
            
            $success = true;
            $userData = $usuarioModel->find($userId);
        }
    }
}

$pageTitle = 'Mi Perfil';
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
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Mi Perfil</h1>
            <p class="text-gray-600">Administra tu información personal</p>
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
                <p class="text-sm text-green-800">Perfil actualizado correctamente</p>
            </div>
        <?php endif; ?>
        
        <!-- Profile Form -->
        <form method="POST" class="bg-white rounded-lg shadow">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            
            <!-- Personal Information -->
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">
                    <i class="fas fa-user mr-2"></i>Información Personal
                </h2>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nombre *</label>
                    <input type="text" name="nombre" required
                           value="<?php echo htmlspecialchars($userData['nombre']); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                    <input type="email" name="email" required
                           value="<?php echo htmlspecialchars($userData['email']); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Teléfono</label>
                    <input type="tel" name="telefono"
                           value="<?php echo htmlspecialchars($userData['telefono'] ?? ''); ?>"
                           placeholder="5551234567"
                           maxlength="10"
                           pattern="[0-9]{10}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <p class="mt-1 text-xs text-gray-500">10 dígitos, sin espacios ni guiones</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Rol</label>
                    <input type="text" value="<?php echo ucfirst($userData['rol']); ?>" disabled
                           class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50">
                </div>
            </div>
            
            <!-- Change Password -->
            <div class="px-6 py-4 border-b border-gray-200 border-t">
                <h2 class="text-lg font-semibold text-gray-900">
                    <i class="fas fa-lock mr-2"></i>Cambiar Contraseña
                </h2>
                <p class="text-sm text-gray-600 mt-1">Deja los campos en blanco si no deseas cambiar tu contraseña</p>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Contraseña Actual</label>
                    <input type="password" name="current_password"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nueva Contraseña</label>
                    <input type="password" name="new_password"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <p class="mt-1 text-xs text-gray-500">Mínimo 6 caracteres</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Confirmar Nueva Contraseña</label>
                    <input type="password" name="confirm_password"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
            
            <!-- Actions -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                    <i class="fas fa-save mr-2"></i>Guardar Cambios
                </button>
            </div>
        </form>
        
        <!-- Account Info -->
        <div class="mt-6 bg-blue-50 border-l-4 border-blue-500 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-info-circle text-blue-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-blue-700">
                        Último inicio de sesión: <?php echo $userData['ultimo_login'] ? formatDateTime($userData['ultimo_login']) : 'Nunca'; ?>
                    </p>
                    <p class="text-sm text-blue-700 mt-1">
                        Cuenta creada: <?php echo formatDate($userData['created_at']); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
