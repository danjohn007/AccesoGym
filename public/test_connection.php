<?php
/**
 * Connection Test File
 * Tests database connection and verifies Base URL configuration
 */

// Prevent caching
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Start output
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test de Conexión - AccessGYM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">
                    <i class="fas fa-flask text-blue-600 mr-2"></i>
                    Test de Conexión y Configuración
                </h1>
                <p class="text-gray-600">Verificación del sistema AccessGYM</p>
            </div>

            <?php
            $tests = [];
            $allPassed = true;

            // Test 1: PHP Version
            $phpVersion = phpversion();
            $phpTest = version_compare($phpVersion, '7.4.0', '>=');
            $tests[] = [
                'name' => 'Versión de PHP',
                'status' => $phpTest,
                'message' => $phpTest 
                    ? "PHP $phpVersion (OK)" 
                    : "PHP $phpVersion (Se requiere PHP 7.4 o superior)",
                'details' => "Versión actual: $phpVersion"
            ];
            $allPassed = $allPassed && $phpTest;

            // Test 2: Configuration File
            $configFile = __DIR__ . '/../config/config.php';
            $configExists = file_exists($configFile);
            $tests[] = [
                'name' => 'Archivo de Configuración',
                'status' => $configExists,
                'message' => $configExists 
                    ? 'config.php encontrado' 
                    : 'config.php no encontrado (copiar config.example.php)',
                'details' => $configExists ? "Ubicación: $configFile" : ''
            ];
            $allPassed = $allPassed && $configExists;

            // Test 3: Load Configuration
            if ($configExists) {
                try {
                    require_once $configFile;
                    $tests[] = [
                        'name' => 'Carga de Configuración',
                        'status' => true,
                        'message' => 'Configuración cargada correctamente',
                        'details' => ''
                    ];
                } catch (Exception $e) {
                    $tests[] = [
                        'name' => 'Carga de Configuración',
                        'status' => false,
                        'message' => 'Error al cargar configuración',
                        'details' => $e->getMessage()
                    ];
                    $allPassed = false;
                    $configExists = false;
                }
            }

            // Test 4: Required PHP Extensions
            $requiredExtensions = ['pdo', 'pdo_mysql', 'mbstring', 'json', 'session', 'curl'];
            $missingExtensions = [];
            foreach ($requiredExtensions as $ext) {
                if (!extension_loaded($ext)) {
                    $missingExtensions[] = $ext;
                }
            }
            $extensionsTest = empty($missingExtensions);
            $tests[] = [
                'name' => 'Extensiones PHP Requeridas',
                'status' => $extensionsTest,
                'message' => $extensionsTest 
                    ? 'Todas las extensiones están instaladas' 
                    : 'Faltan extensiones: ' . implode(', ', $missingExtensions),
                'details' => 'Requeridas: ' . implode(', ', $requiredExtensions)
            ];
            $allPassed = $allPassed && $extensionsTest;

            // Test 5: Database Connection
            if ($configExists && defined('DB_HOST')) {
                try {
                    require_once __DIR__ . '/../config/Database.php';
                    $db = Database::getInstance();
                    $conn = $db->getConnection();
                    
                    $tests[] = [
                        'name' => 'Conexión a Base de Datos',
                        'status' => true,
                        'message' => 'Conexión exitosa',
                        'details' => 'Host: ' . DB_HOST . ', Database: ' . DB_NAME
                    ];

                    // Test 6: Database Tables
                    $stmt = $conn->query("SHOW TABLES");
                    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    $tableCount = count($tables);
                    $expectedTables = ['sucursales', 'usuarios_staff', 'socios', 'tipos_membresia', 
                                      'accesos', 'pagos', 'dispositivos_shelly', 'configuracion'];
                    $hasRequiredTables = true;
                    foreach ($expectedTables as $table) {
                        if (!in_array($table, $tables)) {
                            $hasRequiredTables = false;
                            break;
                        }
                    }
                    
                    $tests[] = [
                        'name' => 'Tablas de Base de Datos',
                        'status' => $hasRequiredTables,
                        'message' => $hasRequiredTables 
                            ? "Encontradas $tableCount tablas" 
                            : 'Faltan tablas requeridas',
                        'details' => $hasRequiredTables 
                            ? 'Todas las tablas principales existen' 
                            : 'Importar database/schema.sql'
                    ];
                    $allPassed = $allPassed && $hasRequiredTables;

                } catch (PDOException $e) {
                    $tests[] = [
                        'name' => 'Conexión a Base de Datos',
                        'status' => false,
                        'message' => 'Error de conexión',
                        'details' => $e->getMessage()
                    ];
                    $allPassed = false;
                } catch (Exception $e) {
                    $tests[] = [
                        'name' => 'Conexión a Base de Datos',
                        'status' => false,
                        'message' => 'Error inesperado',
                        'details' => $e->getMessage()
                    ];
                    $allPassed = false;
                }
            }

            // Test 7: Base URL Configuration
            if ($configExists && defined('APP_URL')) {
                $currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") 
                            . "://" . $_SERVER['HTTP_HOST'];
                $baseUrlCorrect = (strpos($currentUrl, rtrim(APP_URL, '/')) === 0);
                
                $tests[] = [
                    'name' => 'URL Base (APP_URL)',
                    'status' => $baseUrlCorrect,
                    'message' => $baseUrlCorrect 
                        ? 'URL configurada correctamente' 
                        : 'URL no coincide con la configuración',
                    'details' => "Configurado: " . APP_URL . " | Actual: " . $currentUrl
                ];
                
                if (!$baseUrlCorrect) {
                    $allPassed = false;
                }
            }

            // Test 8: Write Permissions
            $uploadPath = __DIR__ . '/../uploads/';
            $logsPath = __DIR__ . '/../logs/';
            $uploadWritable = is_writable($uploadPath);
            $logsWritable = is_writable($logsPath);
            $permissionsTest = $uploadWritable && $logsWritable;
            
            $permDetails = [];
            if (!$uploadWritable) $permDetails[] = 'uploads/ no es escribible';
            if (!$logsWritable) $permDetails[] = 'logs/ no es escribible';
            
            $tests[] = [
                'name' => 'Permisos de Escritura',
                'status' => $permissionsTest,
                'message' => $permissionsTest 
                    ? 'Todos los directorios son escribibles' 
                    : implode(', ', $permDetails),
                'details' => "uploads/: " . ($uploadWritable ? 'OK' : 'NO') . 
                           " | logs/: " . ($logsWritable ? 'OK' : 'NO')
            ];
            $allPassed = $allPassed && $permissionsTest;

            // Test 9: .htaccess Files
            $rootHtaccess = file_exists(__DIR__ . '/../.htaccess');
            $tests[] = [
                'name' => 'Archivo .htaccess',
                'status' => $rootHtaccess,
                'message' => $rootHtaccess ? '.htaccess encontrado' : '.htaccess no encontrado',
                'details' => $rootHtaccess ? 'Configuración de rewrite activa' : ''
            ];

            // Test 10: Session
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            $sessionTest = (session_status() === PHP_SESSION_ACTIVE);
            $tests[] = [
                'name' => 'Soporte de Sesiones',
                'status' => $sessionTest,
                'message' => $sessionTest ? 'Sesiones funcionando' : 'Error con sesiones',
                'details' => 'Session ID: ' . ($sessionTest ? substr(session_id(), 0, 10) . '...' : 'N/A')
            ];
            $allPassed = $allPassed && $sessionTest;

            // Display Results
            ?>

            <!-- Overall Status -->
            <div class="<?php echo $allPassed ? 'bg-green-50 border-green-500' : 'bg-red-50 border-red-500'; ?> border-l-4 p-6 mb-6 rounded-lg">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas <?php echo $allPassed ? 'fa-check-circle text-green-500' : 'fa-exclamation-circle text-red-500'; ?> text-3xl"></i>
                    </div>
                    <div class="ml-4">
                        <h2 class="text-xl font-bold <?php echo $allPassed ? 'text-green-900' : 'text-red-900'; ?>">
                            <?php echo $allPassed ? '✓ Sistema Operativo' : '✗ Requiere Atención'; ?>
                        </h2>
                        <p class="<?php echo $allPassed ? 'text-green-700' : 'text-red-700'; ?>">
                            <?php 
                            $passedCount = count(array_filter($tests, function($t) { return $t['status']; }));
                            echo "$passedCount de " . count($tests) . " pruebas pasadas";
                            ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Test Results -->
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">
                        <i class="fas fa-list-check mr-2"></i>Resultados de las Pruebas
                    </h2>
                </div>
                <div class="divide-y divide-gray-200">
                    <?php foreach ($tests as $test): ?>
                        <div class="p-6 hover:bg-gray-50 transition">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <i class="fas <?php echo $test['status'] ? 'fa-check-circle text-green-500' : 'fa-times-circle text-red-500'; ?> text-xl"></i>
                                </div>
                                <div class="ml-4 flex-1">
                                    <h3 class="text-base font-semibold text-gray-900">
                                        <?php echo htmlspecialchars($test['name']); ?>
                                    </h3>
                                    <p class="mt-1 text-sm <?php echo $test['status'] ? 'text-gray-600' : 'text-red-600'; ?>">
                                        <?php echo htmlspecialchars($test['message']); ?>
                                    </p>
                                    <?php if (!empty($test['details'])): ?>
                                        <p class="mt-1 text-xs text-gray-500 font-mono">
                                            <?php echo htmlspecialchars($test['details']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- System Information -->
            <?php if ($configExists): ?>
            <div class="mt-6 bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-info-circle mr-2"></i>Información del Sistema
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="font-semibold text-gray-700">Nombre de la Aplicación:</span>
                        <span class="text-gray-600"><?php echo defined('APP_NAME') ? APP_NAME : 'N/A'; ?></span>
                    </div>
                    <div>
                        <span class="font-semibold text-gray-700">URL Base:</span>
                        <span class="text-gray-600 font-mono text-xs"><?php echo defined('APP_URL') ? APP_URL : 'N/A'; ?></span>
                    </div>
                    <div>
                        <span class="font-semibold text-gray-700">Zona Horaria:</span>
                        <span class="text-gray-600"><?php echo defined('APP_TIMEZONE') ? APP_TIMEZONE : 'N/A'; ?></span>
                    </div>
                    <div>
                        <span class="font-semibold text-gray-700">Versión PHP:</span>
                        <span class="text-gray-600"><?php echo PHP_VERSION; ?></span>
                    </div>
                    <div>
                        <span class="font-semibold text-gray-700">Servidor:</span>
                        <span class="text-gray-600"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'N/A'; ?></span>
                    </div>
                    <div>
                        <span class="font-semibold text-gray-700">IP del Servidor:</span>
                        <span class="text-gray-600"><?php echo $_SERVER['SERVER_ADDR'] ?? 'N/A'; ?></span>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Actions -->
            <div class="mt-6 bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-tools mr-2"></i>Acciones
                </h2>
                <div class="flex flex-wrap gap-3">
                    <a href="index.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg inline-flex items-center">
                        <i class="fas fa-home mr-2"></i>Ir al Sistema
                    </a>
                    <a href="login.php" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg inline-flex items-center">
                        <i class="fas fa-sign-in-alt mr-2"></i>Iniciar Sesión
                    </a>
                    <button onclick="location.reload()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg inline-flex items-center">
                        <i class="fas fa-sync-alt mr-2"></i>Recargar Pruebas
                    </button>
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-6 text-center text-sm text-gray-500">
                <p>AccessGYM - Sistema de Control de Gimnasio</p>
                <p class="mt-1">
                    <i class="fas fa-clock mr-1"></i>
                    <?php echo date('Y-m-d H:i:s'); ?>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
