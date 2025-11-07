<?php
require_once 'bootstrap.php';

// If already logged in, redirect to dashboard
if (Auth::check()) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Por favor ingrese email y contraseña';
    } else {
        require_once __DIR__ . '/../app/models/Usuario.php';
        $usuarioModel = new Usuario();
        
        $user = $usuarioModel->authenticate($email, $password);
        
        if ($user) {
            Auth::login($user);
            
            // Log login event
            logEvent('sistema', "Inicio de sesión: {$user['nombre']}", $user['id'], null, $user['sucursal_id']);
            
            redirect('dashboard.php');
        } else {
            $error = 'Credenciales incorrectas';
        }
    }
}

$pageTitle = 'Iniciar Sesión';
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
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <div class="flex justify-center">
                    <i class="fas fa-dumbbell text-6xl text-blue-600"></i>
                </div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    <?php echo APP_NAME; ?>
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Sistema de Control de Acceso
                </p>
            </div>
            
            <form class="mt-8 space-y-6" method="POST">
                <?php if ($error): ?>
                    <div class="rounded-md bg-red-50 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-circle text-red-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-red-800"><?php echo $error; ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="rounded-md shadow-sm -space-y-px">
                    <div>
                        <label for="email" class="sr-only">Email</label>
                        <input id="email" name="email" type="email" required 
                               class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" 
                               placeholder="Email" value="<?php echo htmlspecialchars($email ?? ''); ?>">
                    </div>
                    <div>
                        <label for="password" class="sr-only">Contraseña</label>
                        <input id="password" name="password" type="password" required 
                               class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" 
                               placeholder="Contraseña">
                    </div>
                </div>

                <div>
                    <button type="submit" 
                            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <i class="fas fa-lock text-blue-500 group-hover:text-blue-400"></i>
                        </span>
                        Iniciar Sesión
                    </button>
                </div>
            </form>
            
            <div class="text-center text-sm text-gray-600">
                <p>Usuario por defecto: admin@accessgym.com</p>
                <p>Contraseña: admin123</p>
            </div>
        </div>
    </div>
</body>
</html>
