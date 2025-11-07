<?php
require_once 'bootstrap.php';
Auth::requireAuth();

require_once __DIR__ . '/../app/models/Pago.php';
require_once __DIR__ . '/../app/models/Socio.php';

$pagoId = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : 0;

if (!$pagoId || $pagoId <= 0) {
    redirect('pagos.php');
}

$pagoModel = new Pago();
$socioModel = new Socio();

$user = Auth::user();

// Get payment details
$db = Database::getInstance()->getConnection();
$sql = "SELECT p.*, 
               s.nombre as socio_nombre, s.apellido as socio_apellido, s.codigo as socio_codigo,
               tm.nombre as tipo_membresia_nombre, tm.duracion_dias,
               u.nombre as usuario_nombre,
               su.nombre as sucursal_nombre
        FROM pagos p
        INNER JOIN socios s ON p.socio_id = s.id
        INNER JOIN tipos_membresia tm ON p.tipo_membresia_id = tm.id
        LEFT JOIN usuarios_staff u ON p.usuario_registro = u.id
        LEFT JOIN sucursales su ON p.sucursal_id = su.id
        WHERE p.id = ?";
$stmt = $db->prepare($sql);
$stmt->execute([$pagoId]);
$pago = $stmt->fetch();

if (!$pago) {
    redirect('pagos.php');
}

// Check access permissions
if (!Auth::isSuperadmin() && $pago['sucursal_id'] != Auth::sucursalId()) {
    header('HTTP/1.0 403 Forbidden');
    echo '<!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Acceso Denegado - ' . APP_NAME . '</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-gray-100">
        <div class="min-h-screen flex items-center justify-center">
            <div class="bg-white p-8 rounded-lg shadow-lg text-center max-w-md">
                <i class="fas fa-exclamation-triangle text-red-500 text-6xl mb-4"></i>
                <h1 class="text-2xl font-bold text-gray-900 mb-2">Acceso No Autorizado</h1>
                <p class="text-gray-600 mb-6">No tienes permisos para ver este pago.</p>
                <a href="pagos.php" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                    Volver a Pagos
                </a>
            </div>
        </div>
    </body>
    </html>';
    exit;
}

$pageTitle = 'Detalle del Pago';
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
        <!-- Header -->
        <div class="mb-8 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Detalle del Pago</h1>
                <p class="text-gray-600">Información completa del pago registrado</p>
            </div>
            <div class="space-x-2">
                <a href="pago_form.php?id=<?php echo $pago['id']; ?>" 
                   class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded inline-flex items-center">
                    <i class="fas fa-edit mr-2"></i>
                    Editar
                </a>
                <a href="pagos.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded inline-flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Volver
                </a>
            </div>
        </div>
        
        <!-- Success Message -->
        <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle text-green-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800">¡Pago registrado exitosamente!</p>
                </div>
            </div>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Payment Information -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4 border-b pb-2">
                    <i class="fas fa-file-invoice-dollar mr-2 text-blue-600"></i>
                    Información del Pago
                </h2>
                
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Fecha de Pago:</span>
                        <span class="font-medium"><?php echo formatDate($pago['fecha_pago']); ?></span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="text-gray-600">Monto:</span>
                        <span class="font-bold text-lg text-green-600"><?php echo formatMoney($pago['monto']); ?></span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="text-gray-600">Tipo de Membresía:</span>
                        <span class="font-medium"><?php echo htmlspecialchars($pago['tipo_membresia_nombre']); ?></span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="text-gray-600">Duración:</span>
                        <span class="font-medium"><?php echo $pago['duracion_dias']; ?> días</span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="text-gray-600">Método de Pago:</span>
                        <span class="font-medium capitalize"><?php echo htmlspecialchars($pago['metodo_pago']); ?></span>
                    </div>
                    
                    <?php if ($pago['referencia']): ?>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Referencia:</span>
                        <span class="font-medium"><?php echo htmlspecialchars($pago['referencia']); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="flex justify-between">
                        <span class="text-gray-600">Estado:</span>
                        <span class="font-medium">
                            <?php
                            $estadoClass = [
                                'completado' => 'bg-green-100 text-green-800',
                                'pendiente' => 'bg-yellow-100 text-yellow-800',
                                'cancelado' => 'bg-red-100 text-red-800'
                            ][$pago['estado']] ?? 'bg-gray-100 text-gray-800';
                            ?>
                            <span class="px-2 py-1 rounded-full text-xs <?php echo $estadoClass; ?> capitalize">
                                <?php echo htmlspecialchars($pago['estado']); ?>
                            </span>
                        </span>
                    </div>
                    
                    <?php if ($pago['usuario_nombre']): ?>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Registrado por:</span>
                        <span class="font-medium"><?php echo htmlspecialchars($pago['usuario_nombre']); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($pago['sucursal_nombre']): ?>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Sucursal:</span>
                        <span class="font-medium"><?php echo htmlspecialchars($pago['sucursal_nombre']); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($pago['notas']): ?>
                    <div class="pt-3 border-t">
                        <span class="text-gray-600 block mb-2">Notas:</span>
                        <p class="text-sm bg-gray-50 p-3 rounded"><?php echo nl2br(htmlspecialchars($pago['notas'])); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Member Information -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4 border-b pb-2">
                    <i class="fas fa-user mr-2 text-blue-600"></i>
                    Información del Socio
                </h2>
                
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Código:</span>
                        <span class="font-medium"><?php echo htmlspecialchars($pago['socio_codigo']); ?></span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="text-gray-600">Nombre:</span>
                        <span class="font-medium"><?php echo htmlspecialchars($pago['socio_nombre'] . ' ' . $pago['socio_apellido']); ?></span>
                    </div>
                </div>
                
                <div class="mt-6">
                    <a href="socio_detalle.php?id=<?php echo $pago['socio_id']; ?>" 
                       class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded inline-flex items-center justify-center">
                        <i class="fas fa-user mr-2"></i>
                        Ver Perfil del Socio
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="mt-8 bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">
                <i class="fas fa-cog mr-2 text-blue-600"></i>
                Acciones
            </h2>
            
            <div class="flex flex-wrap gap-3">
                <a href="pago_form.php?socio_id=<?php echo $pago['socio_id']; ?>" 
                   class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded inline-flex items-center">
                    <i class="fas fa-plus mr-2"></i>
                    Nuevo Pago para este Socio
                </a>
                
                <a href="pagos.php" 
                   class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded inline-flex items-center">
                    <i class="fas fa-list mr-2"></i>
                    Ver Todos los Pagos
                </a>
                
                <button onclick="window.print()" 
                        class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded inline-flex items-center">
                    <i class="fas fa-print mr-2"></i>
                    Imprimir
                </button>
            </div>
        </div>
    </div>
</body>
</html>
