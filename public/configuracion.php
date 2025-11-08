<?php
require_once 'bootstrap.php';
Auth::requireRole('superadmin');

$db = Database::getInstance();
$conn = $db->getConnection();

$success = false;
$error = false;
$message = '';

// Handle logo upload
if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    $maxSize = 2097152; // 2MB
    
    if (in_array($_FILES['logo']['type'], $allowedTypes) && $_FILES['logo']['size'] <= $maxSize) {
        $logoDir = __DIR__ . '/../uploads/logos/';
        if (!is_dir($logoDir)) {
            mkdir($logoDir, 0755, true);
        }
        
        $extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        $filename = 'logo_' . time() . '.' . $extension;
        $destination = $logoDir . $filename;
        
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $destination)) {
            $_POST['sitio_logo'] = '/uploads/logos/' . $filename;
        }
    }
}

// Handle configuration updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token'])) {
    if (Auth::verifyCsrfToken($_POST['csrf_token'])) {
        try {
            // Update configuration in database
            $configKeys = [
                'sitio_nombre', 'sitio_logo', 'sitio_eslogan',
                'email_principal', 'email_respuesta', 
                'telefono_principal', 'telefono_secundario',
                'horario_apertura', 'horario_cierre', 'dias_operacion',
                'color_primario', 'color_secundario', 'color_acento',
                'fuente_principal', 'border_radius',
                'paypal_enabled', 'paypal_client_id', 'paypal_secret',
                'qr_api_enabled', 'qr_api_url', 'qr_api_key',
                'mantenimiento_modo', 'registros_por_pagina', 'zona_horaria'
            ];
            
            $stmt = $conn->prepare("INSERT INTO configuracion (clave, valor, tipo, grupo) VALUES (?, ?, 'texto', ?) 
                                   ON DUPLICATE KEY UPDATE valor = VALUES(valor), updated_at = CURRENT_TIMESTAMP");
            
            foreach ($configKeys as $key) {
                if (isset($_POST[$key])) {
                    $value = sanitize($_POST[$key]);
                    $grupo = 'general';
                    
                    if (strpos($key, 'email_') === 0) $grupo = 'email';
                    if (strpos($key, 'telefono_') === 0 || strpos($key, 'horario_') === 0 || $key === 'dias_operacion') $grupo = 'contacto';
                    if (strpos($key, 'color_') === 0) $grupo = 'estilos';
                    if (strpos($key, 'paypal_') === 0) $grupo = 'pagos';
                    if (strpos($key, 'qr_') === 0) $grupo = 'integracion';
                    if (in_array($key, ['mantenimiento_modo', 'registros_por_pagina', 'zona_horaria'])) $grupo = 'sistema';
                    
                    $stmt->execute([$key, $value, $grupo]);
                }
            }
            
            $success = true;
            $message = 'Configuración actualizada correctamente';
            logEvent('sistema', 'Configuración del sistema actualizada', Auth::id(), null, null);
            
        } catch (Exception $e) {
            $error = true;
            $message = 'Error al actualizar configuración: ' . $e->getMessage();
        }
    }
}

// Load current configuration from database
$currentConfig = [];
try {
    $stmt = $conn->query("SELECT clave, valor FROM configuracion");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $currentConfig[$row['clave']] = $row['valor'];
    }
} catch (Exception $e) {
    // If table doesn't exist, use defaults
}

// Helper function to get config value
function getConfig($key, $default = '') {
    global $currentConfig;
    return $currentConfig[$key] ?? $default;
}

$pageTitle = 'Configuración';
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
    
    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">
                <i class="fas fa-cog text-blue-600 mr-2"></i>
                Configuración del Sistema
            </h1>
            <p class="text-gray-600 mt-2">Administra las configuraciones globales de AccessGYM</p>
        </div>
        
        <?php if ($success): ?>
            <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-green-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800"><?php echo htmlspecialchars($message); ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-red-800"><?php echo htmlspecialchars($message); ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Configuration Tabs -->
        <div x-data="{ activeTab: 'general' }" class="bg-white rounded-lg shadow">
            <!-- Tab Navigation -->
            <div class="border-b border-gray-200">
                <nav class="flex flex-wrap -mb-px" aria-label="Tabs">
                    <button @click="activeTab = 'general'" 
                            :class="activeTab === 'general' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="py-4 px-6 border-b-2 font-medium text-sm transition-colors">
                        <i class="fas fa-building mr-2"></i>General
                    </button>
                    <button @click="activeTab = 'email'" 
                            :class="activeTab === 'email' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="py-4 px-6 border-b-2 font-medium text-sm transition-colors">
                        <i class="fas fa-envelope mr-2"></i>Email
                    </button>
                    <button @click="activeTab = 'contacto'" 
                            :class="activeTab === 'contacto' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="py-4 px-6 border-b-2 font-medium text-sm transition-colors">
                        <i class="fas fa-phone mr-2"></i>Contacto
                    </button>
                    <button @click="activeTab = 'estilos'" 
                            :class="activeTab === 'estilos' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="py-4 px-6 border-b-2 font-medium text-sm transition-colors">
                        <i class="fas fa-palette mr-2"></i>Estilos
                    </button>
                    <button @click="activeTab = 'pagos'" 
                            :class="activeTab === 'pagos' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="py-4 px-6 border-b-2 font-medium text-sm transition-colors">
                        <i class="fas fa-credit-card mr-2"></i>Pagos
                    </button>
                    <button @click="activeTab = 'integraciones'" 
                            :class="activeTab === 'integraciones' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="py-4 px-6 border-b-2 font-medium text-sm transition-colors">
                        <i class="fas fa-plug mr-2"></i>Integraciones
                    </button>
                    <button @click="activeTab = 'sistema'" 
                            :class="activeTab === 'sistema' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="py-4 px-6 border-b-2 font-medium text-sm transition-colors">
                        <i class="fas fa-server mr-2"></i>Sistema
                    </button>
                </nav>
            </div>
            
            <form method="POST" enctype="multipart/form-data" class="p-8">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                
                <!-- General Tab -->
                <div x-show="activeTab === 'general'" class="space-y-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Configuración General</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Nombre del Sitio <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="sitio_nombre" 
                                   value="<?php echo htmlspecialchars(getConfig('sitio_nombre', APP_NAME)); ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <p class="mt-1 text-xs text-gray-500">Nombre que aparecerá en todo el sistema</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Eslogan
                            </label>
                            <input type="text" name="sitio_eslogan" 
                                   value="<?php echo htmlspecialchars(getConfig('sitio_eslogan', '')); ?>"
                                   placeholder="Tu gimnasio, tu estilo de vida"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <p class="mt-1 text-xs text-gray-500">Frase descriptiva del gimnasio</p>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Logotipo
                        </label>
                        <?php $currentLogo = getConfig('sitio_logo', ''); ?>
                        <?php if ($currentLogo): ?>
                            <div class="mb-3">
                                <img src="<?php echo htmlspecialchars($currentLogo); ?>" 
                                     alt="Logo actual" class="h-16 border rounded">
                                <p class="text-xs text-gray-500 mt-1">Logo actual</p>
                            </div>
                        <?php endif; ?>
                        <input type="file" name="logo" accept="image/png,image/jpeg,image/jpg"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="mt-1 text-xs text-gray-500">PNG, JPG o JPEG. Máximo 2MB. Recomendado: 200x50px</p>
                    </div>
                </div>
                
                <!-- Email Tab -->
                <div x-show="activeTab === 'email'" class="space-y-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Configuración de Email</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Email Principal <span class="text-red-500">*</span>
                            </label>
                            <input type="email" name="email_principal" 
                                   value="<?php echo htmlspecialchars(getConfig('email_principal', '')); ?>"
                                   placeholder="info@gimnasio.com"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <p class="mt-1 text-xs text-gray-500">Email desde el que se envían los mensajes del sistema</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Email de Respuesta
                            </label>
                            <input type="email" name="email_respuesta" 
                                   value="<?php echo htmlspecialchars(getConfig('email_respuesta', '')); ?>"
                                   placeholder="contacto@gimnasio.com"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <p class="mt-1 text-xs text-gray-500">Email al que responderán los clientes</p>
                        </div>
                    </div>
                    
                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-info-circle text-blue-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">
                                    Para configurar el servidor SMTP (Gmail, SendGrid, etc.), edita el archivo <code>config/config.php</code>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Contacto Tab -->
                <div x-show="activeTab === 'contacto'" class="space-y-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Información de Contacto y Horarios</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Teléfono Principal <span class="text-red-500">*</span>
                            </label>
                            <input type="tel" name="telefono_principal" 
                                   value="<?php echo htmlspecialchars(getConfig('telefono_principal', '')); ?>"
                                   placeholder="+52 123 456 7890"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Teléfono Secundario
                            </label>
                            <input type="tel" name="telefono_secundario" 
                                   value="<?php echo htmlspecialchars(getConfig('telefono_secundario', '')); ?>"
                                   placeholder="+52 098 765 4321"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Horario de Apertura <span class="text-red-500">*</span>
                            </label>
                            <input type="time" name="horario_apertura" 
                                   value="<?php echo htmlspecialchars(getConfig('horario_apertura', '06:00')); ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Horario de Cierre <span class="text-red-500">*</span>
                            </label>
                            <input type="time" name="horario_cierre" 
                                   value="<?php echo htmlspecialchars(getConfig('horario_cierre', '22:00')); ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Días de Operación
                        </label>
                        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3">
                            <?php 
                            $dias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
                            $diasSeleccionados = explode(',', getConfig('dias_operacion', '1,2,3,4,5,6,7'));
                            for ($i = 1; $i <= 7; $i++): 
                            ?>
                                <label class="flex items-center space-x-2 p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                                    <input type="checkbox" name="dias_operacion[]" value="<?php echo $i; ?>" 
                                           <?php echo in_array((string)$i, $diasSeleccionados) ? 'checked' : ''; ?>
                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm text-gray-700"><?php echo $dias[$i-1]; ?></span>
                                </label>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Estilos Tab -->
                <div x-show="activeTab === 'estilos'" class="space-y-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Personalización de Estilos</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Color Primario
                            </label>
                            <div class="flex items-center space-x-3">
                                <input type="color" name="color_primario" 
                                       value="<?php echo htmlspecialchars(getConfig('color_primario', '#3B82F6')); ?>"
                                       class="h-12 w-20 border border-gray-300 rounded cursor-pointer">
                                <input type="text" value="<?php echo htmlspecialchars(getConfig('color_primario', '#3B82F6')); ?>"
                                       class="flex-1 px-3 py-2 border border-gray-300 rounded-lg font-mono text-sm"
                                       readonly>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Color principal del sistema (botones, enlaces)</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Color Secundario
                            </label>
                            <div class="flex items-center space-x-3">
                                <input type="color" name="color_secundario" 
                                       value="<?php echo htmlspecialchars(getConfig('color_secundario', '#10B981')); ?>"
                                       class="h-12 w-20 border border-gray-300 rounded cursor-pointer">
                                <input type="text" value="<?php echo htmlspecialchars(getConfig('color_secundario', '#10B981')); ?>"
                                       class="flex-1 px-3 py-2 border border-gray-300 rounded-lg font-mono text-sm"
                                       readonly>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Color complementario</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Color de Acento
                            </label>
                            <div class="flex items-center space-x-3">
                                <input type="color" name="color_acento" 
                                       value="<?php echo htmlspecialchars(getConfig('color_acento', '#F59E0B')); ?>"
                                       class="h-12 w-20 border border-gray-300 rounded cursor-pointer">
                                <input type="text" value="<?php echo htmlspecialchars(getConfig('color_acento', '#F59E0B')); ?>"
                                       class="flex-1 px-3 py-2 border border-gray-300 rounded-lg font-mono text-sm"
                                       readonly>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Color para destacar elementos importantes</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Fuente Principal
                            </label>
                            <select name="fuente_principal" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="system" <?php echo getConfig('fuente_principal', 'system') === 'system' ? 'selected' : ''; ?>>Fuente del Sistema</option>
                                <option value="inter" <?php echo getConfig('fuente_principal', 'system') === 'inter' ? 'selected' : ''; ?>>Inter</option>
                                <option value="roboto" <?php echo getConfig('fuente_principal', 'system') === 'roboto' ? 'selected' : ''; ?>>Roboto</option>
                                <option value="opensans" <?php echo getConfig('fuente_principal', 'system') === 'opensans' ? 'selected' : ''; ?>>Open Sans</option>
                                <option value="poppins" <?php echo getConfig('fuente_principal', 'system') === 'poppins' ? 'selected' : ''; ?>>Poppins</option>
                            </select>
                            <p class="mt-1 text-xs text-gray-500">Tipografía del sistema</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Bordes Redondeados
                            </label>
                            <select name="border_radius" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="none" <?php echo getConfig('border_radius', 'medium') === 'none' ? 'selected' : ''; ?>>Sin redondeo</option>
                                <option value="small" <?php echo getConfig('border_radius', 'medium') === 'small' ? 'selected' : ''; ?>>Pequeño</option>
                                <option value="medium" <?php echo getConfig('border_radius', 'medium') === 'medium' ? 'selected' : ''; ?>>Mediano</option>
                                <option value="large" <?php echo getConfig('border_radius', 'medium') === 'large' ? 'selected' : ''; ?>>Grande</option>
                            </select>
                            <p class="mt-1 text-xs text-gray-500">Estilo de bordes de elementos</p>
                        </div>
                    </div>
                    
                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mt-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-info-circle text-blue-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">
                                    Los cambios de estilo se aplicarán inmediatamente después de guardar. Recarga la página para ver los cambios.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Pagos Tab -->
                <div x-show="activeTab === 'pagos'" class="space-y-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Configuración de Pasarelas de Pago</h3>
                    
                    <!-- PayPal -->
                    <div class="border rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center">
                                <i class="fab fa-paypal text-3xl text-blue-600 mr-3"></i>
                                <div>
                                    <h4 class="font-semibold text-gray-900">PayPal</h4>
                                    <p class="text-sm text-gray-500">Acepta pagos con PayPal</p>
                                </div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="paypal_enabled" value="1" 
                                       <?php echo getConfig('paypal_enabled', '0') === '1' ? 'checked' : ''; ?>
                                       class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Client ID</label>
                                <input type="text" name="paypal_client_id" 
                                       value="<?php echo htmlspecialchars(getConfig('paypal_client_id', '')); ?>"
                                       placeholder="AXXXXXXXXxxxxxxxx"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono text-sm">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Secret Key</label>
                                <input type="password" name="paypal_secret" 
                                       value="<?php echo htmlspecialchars(getConfig('paypal_secret', '')); ?>"
                                       placeholder="EXXXXXXXXxxxxxxxx"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono text-sm">
                            </div>
                        </div>
                        
                        <p class="mt-3 text-xs text-gray-500">
                            <i class="fas fa-info-circle mr-1"></i>
                            Obtén tus credenciales en <a href="https://developer.paypal.com/" target="_blank" class="text-blue-600 hover:underline">PayPal Developer</a>
                        </p>
                    </div>
                    
                    <!-- Other Payment Gateways -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="border rounded-lg p-4 bg-gray-50">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-credit-card text-2xl text-gray-400 mr-3"></i>
                                <h4 class="font-semibold text-gray-700">Stripe</h4>
                            </div>
                            <p class="text-sm text-gray-600">Estado: <?php echo defined('STRIPE_ENABLED') && STRIPE_ENABLED ? 'Habilitado' : 'Deshabilitado'; ?></p>
                            <p class="text-xs text-gray-500 mt-1">Configurar en config/config.php</p>
                        </div>
                        
                        <div class="border rounded-lg p-4 bg-gray-50">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-money-check-alt text-2xl text-gray-400 mr-3"></i>
                                <h4 class="font-semibold text-gray-700">MercadoPago</h4>
                            </div>
                            <p class="text-sm text-gray-600">Estado: <?php echo defined('MERCADOPAGO_ENABLED') && MERCADOPAGO_ENABLED ? 'Habilitado' : 'Deshabilitado'; ?></p>
                            <p class="text-xs text-gray-500 mt-1">Configurar en config/config.php</p>
                        </div>
                    </div>
                </div>
                
                <!-- Integraciones Tab -->
                <div x-show="activeTab === 'integraciones'" class="space-y-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Integraciones y APIs</h3>
                    
                    <!-- QR API -->
                    <div class="border rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center">
                                <i class="fas fa-qrcode text-3xl text-gray-700 mr-3"></i>
                                <div>
                                    <h4 class="font-semibold text-gray-900">API de Generación de QR</h4>
                                    <p class="text-sm text-gray-500">Para generar códigos QR masivos</p>
                                </div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="qr_api_enabled" value="1" 
                                       <?php echo getConfig('qr_api_enabled', '1') === '1' ? 'checked' : ''; ?>
                                       class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">URL de la API</label>
                                <input type="url" name="qr_api_url" 
                                       value="<?php echo htmlspecialchars(getConfig('qr_api_url', 'https://api.qrserver.com/v1/create-qr-code/')); ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono text-sm">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">API Key (Opcional)</label>
                                <input type="text" name="qr_api_key" 
                                       value="<?php echo htmlspecialchars(getConfig('qr_api_key', '')); ?>"
                                       placeholder="Si la API requiere autenticación"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono text-sm">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Shelly & WhatsApp -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="border rounded-lg p-4 bg-gray-50">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-wifi text-2xl text-gray-400 mr-3"></i>
                                <h4 class="font-semibold text-gray-700">Shelly Cloud</h4>
                            </div>
                            <p class="text-sm text-gray-600">Estado: <?php echo defined('SHELLY_ENABLED') && SHELLY_ENABLED ? 'Habilitado' : 'Deshabilitado'; ?></p>
                            <p class="text-xs text-gray-500 mt-1">Configurar en config/config.php</p>
                        </div>
                        
                        <div class="border rounded-lg p-4 bg-gray-50">
                            <div class="flex items-center mb-2">
                                <i class="fab fa-whatsapp text-2xl text-gray-400 mr-3"></i>
                                <h4 class="font-semibold text-gray-700">WhatsApp Business</h4>
                            </div>
                            <p class="text-sm text-gray-600">Estado: <?php echo defined('WHATSAPP_ENABLED') && WHATSAPP_ENABLED ? 'Habilitado' : 'Deshabilitado'; ?></p>
                            <p class="text-xs text-gray-500 mt-1">Configurar en config/config.php</p>
                        </div>
                    </div>
                </div>
                
                <!-- Sistema Tab -->
                <div x-show="activeTab === 'sistema'" class="space-y-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Configuraciones del Sistema</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Zona Horaria
                            </label>
                            <select name="zona_horaria" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <?php 
                                $zonas = [
                                    'America/Mexico_City' => 'Ciudad de México',
                                    'America/Cancun' => 'Cancún',
                                    'America/Tijuana' => 'Tijuana',
                                    'America/Monterrey' => 'Monterrey',
                                    'America/Mazatlan' => 'Mazatlán'
                                ];
                                $zonaActual = getConfig('zona_horaria', APP_TIMEZONE);
                                foreach ($zonas as $valor => $nombre):
                                ?>
                                    <option value="<?php echo $valor; ?>" <?php echo $zonaActual === $valor ? 'selected' : ''; ?>>
                                        <?php echo $nombre; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Registros por Página
                            </label>
                            <select name="registros_por_pagina" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <?php 
                                $opciones = [10, 25, 50, 100];
                                $actual = (int)getConfig('registros_por_pagina', '25');
                                foreach ($opciones as $opcion):
                                ?>
                                    <option value="<?php echo $opcion; ?>" <?php echo $actual === $opcion ? 'selected' : ''; ?>>
                                        <?php echo $opcion; ?> registros
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div>
                        <label class="flex items-center space-x-3 p-4 border rounded-lg hover:bg-gray-50 cursor-pointer">
                            <input type="checkbox" name="mantenimiento_modo" value="1" 
                                   <?php echo getConfig('mantenimiento_modo', '0') === '1' ? 'checked' : ''; ?>
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 h-5 w-5">
                            <div>
                                <span class="text-sm font-medium text-gray-900">Modo Mantenimiento</span>
                                <p class="text-xs text-gray-500">Deshabilita el acceso al sistema excepto para superadmin</p>
                            </div>
                        </label>
                    </div>
                    
                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4">
                        <h4 class="font-semibold text-blue-900 mb-2">Configuraciones Recomendadas</h4>
                        <ul class="text-sm text-blue-700 space-y-1">
                            <li><i class="fas fa-check mr-2"></i>Zona horaria correcta para reportes precisos</li>
                            <li><i class="fas fa-check mr-2"></i>Email principal configurado para notificaciones</li>
                            <li><i class="fas fa-check mr-2"></i>Teléfonos de contacto actualizados</li>
                            <li><i class="fas fa-check mr-2"></i>Horarios de operación correctos</li>
                            <li><i class="fas fa-check mr-2"></i>Backup regular de la base de datos</li>
                            <li><i class="fas fa-check mr-2"></i>Actualizaciones de seguridad aplicadas</li>
                        </ul>
                    </div>
                </div>
                
                <!-- Submit Button -->
                <div class="flex justify-end space-x-4 mt-8 pt-6 border-t">
                    <a href="dashboard.php" class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                        <i class="fas fa-times mr-2"></i>Cancelar
                    </a>
                    <button type="submit" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold transition-colors">
                        <i class="fas fa-save mr-2"></i>Guardar Configuración
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Update color input text when color picker changes
        document.querySelectorAll('input[type="color"]').forEach(colorInput => {
            colorInput.addEventListener('change', function() {
                const textInput = this.parentElement.querySelector('input[type="text"]');
                if (textInput) {
                    textInput.value = this.value;
                }
            });
        });
        
        // Convert dias_operacion checkboxes to comma-separated string
        document.querySelector('form').addEventListener('submit', function(e) {
            const checkboxes = document.querySelectorAll('input[name="dias_operacion[]"]:checked');
            const values = Array.from(checkboxes).map(cb => cb.value).join(',');
            
            // Create hidden input with the comma-separated values
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'dias_operacion';
            hiddenInput.value = values;
            this.appendChild(hiddenInput);
            
            // Remove the original checkboxes from submission
            checkboxes.forEach(cb => cb.disabled = true);
        });
    </script>
</body>
</html>
