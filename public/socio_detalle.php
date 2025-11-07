<?php
require_once 'bootstrap.php';
Auth::requireAuth();

require_once __DIR__ . '/../app/models/Socio.php';
require_once __DIR__ . '/../app/models/Acceso.php';
require_once __DIR__ . '/../app/models/Pago.php';

$socioId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$nuevo = isset($_GET['nuevo']);

if (!$socioId) {
    redirect('socios.php');
}

$socioModel = new Socio();
$accesoModel = new Acceso();
$pagoModel = new Pago();

$socio = $socioModel->getWithMembresia($socioId);

if (!$socio) {
    redirect('socios.php');
}

// Check access permissions
if (!Auth::isSuperadmin() && $socio['sucursal_id'] != Auth::sucursalId()) {
    die('Acceso no autorizado');
}

// Get recent access logs
$recentAccess = $accesoModel->getAccessLog(['socio_id' => $socioId], 10);

// Get payments
$sql = "SELECT p.*, tm.nombre as tipo_membresia_nombre, u.nombre as usuario_nombre
        FROM pagos p
        INNER JOIN tipos_membresia tm ON p.tipo_membresia_id = tm.id
        LEFT JOIN usuarios_staff u ON p.usuario_registro = u.id
        WHERE p.socio_id = ?
        ORDER BY p.fecha_pago DESC
        LIMIT 10";
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare($sql);
$stmt->execute([$socioId]);
$pagos = $stmt->fetchAll();

$pageTitle = 'Detalle del Socio';
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
                <h1 class="text-3xl font-bold text-gray-900">Detalle del Socio</h1>
                <p class="text-gray-600"><?php echo htmlspecialchars($socio['nombre'] . ' ' . $socio['apellido']); ?></p>
            </div>
            <div class="space-x-2">
                <a href="socio_form.php?id=<?php echo $socio['id']; ?>" 
                   class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded inline-flex items-center">
                    <i class="fas fa-edit mr-2"></i>
                    Editar
                </a>
                <a href="socios.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded inline-flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Volver
                </a>
            </div>
        </div>
        
        <?php if ($nuevo): ?>
            <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-green-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800">¡Socio registrado exitosamente!</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Member Info Card -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow p-6">
                    <!-- Photo -->
                    <div class="flex justify-center mb-4">
                        <?php if ($socio['foto']): ?>
                            <img src="<?php echo '/uploads/photos/' . htmlspecialchars($socio['foto']); ?>" 
                                 alt="Foto" class="h-32 w-32 rounded-full object-cover border-4 border-blue-500">
                        <?php else: ?>
                            <div class="h-32 w-32 rounded-full bg-gray-200 flex items-center justify-center border-4 border-gray-300">
                                <i class="fas fa-user text-gray-400 text-5xl"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Code and Status -->
                    <div class="text-center mb-4">
                        <p class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($socio['codigo']); ?></p>
                        <div class="mt-2"><?php echo statusBadge($socio['estado']); ?></div>
                    </div>
                    
                    <!-- QR Code -->
                    <div class="text-center mb-4">
                        <p class="text-sm font-medium text-gray-700 mb-2">Código QR</p>
                        <?php if ($socio['qr_code'] && file_exists(UPLOAD_PATH . 'photos/' . $socio['qr_code'])): ?>
                            <img src="<?php echo '/uploads/photos/' . htmlspecialchars($socio['qr_code']); ?>" 
                                 alt="QR Code" class="mx-auto border-2 border-gray-300 p-2 rounded">
                        <?php else: ?>
                            <img src="<?php echo generateQrCode($socio['codigo']); ?>" 
                                 alt="QR Code" class="mx-auto border-2 border-gray-300 p-2 rounded">
                        <?php endif; ?>
                        <button onclick="printQR()" class="mt-2 text-blue-600 hover:text-blue-800 text-sm">
                            <i class="fas fa-print mr-1"></i>Imprimir QR
                        </button>
                    </div>
                    
                    <!-- Actions -->
                    <div class="space-y-2">
                        <a href="acceso_manual.php?socio_id=<?php echo $socio['id']; ?>" 
                           class="block w-full bg-green-600 hover:bg-green-700 text-white text-center font-bold py-2 px-4 rounded">
                            <i class="fas fa-door-open mr-2"></i>Acceso Manual
                        </a>
                        <a href="pago_form.php?socio_id=<?php echo $socio['id']; ?>" 
                           class="block w-full bg-blue-600 hover:bg-blue-700 text-white text-center font-bold py-2 px-4 rounded">
                            <i class="fas fa-dollar-sign mr-2"></i>Registrar Pago
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Member Details -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Personal Information -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">
                            <i class="fas fa-user mr-2"></i>Información Personal
                        </h2>
                    </div>
                    <div class="p-6">
                        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Nombre Completo</dt>
                                <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($socio['nombre'] . ' ' . $socio['apellido']); ?></dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Email</dt>
                                <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($socio['email'] ?: 'No registrado'); ?></dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Teléfono</dt>
                                <dd class="mt-1 text-sm text-gray-900"><?php echo formatPhone($socio['telefono']); ?></dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Teléfono de Emergencia</dt>
                                <dd class="mt-1 text-sm text-gray-900"><?php echo $socio['telefono_emergencia'] ? formatPhone($socio['telefono_emergencia']) : 'No registrado'; ?></dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Fecha de Nacimiento</dt>
                                <dd class="mt-1 text-sm text-gray-900"><?php echo $socio['fecha_nacimiento'] ? formatDate($socio['fecha_nacimiento']) : 'No registrado'; ?></dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Sucursal</dt>
                                <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($socio['sucursal_nombre']); ?></dd>
                            </div>
                            <div class="col-span-2">
                                <dt class="text-sm font-medium text-gray-500">Dirección</dt>
                                <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($socio['direccion'] ?: 'No registrada'); ?></dd>
                            </div>
                        </dl>
                    </div>
                </div>
                
                <!-- Membership Information -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">
                            <i class="fas fa-id-card mr-2"></i>Información de Membresía
                        </h2>
                    </div>
                    <div class="p-6">
                        <?php if ($socio['tipo_membresia_nombre']): ?>
                            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Tipo de Membresía</dt>
                                    <dd class="mt-1">
                                        <span class="px-2 py-1 text-sm font-semibold rounded-full" 
                                              style="background-color: <?php echo $socio['membresia_color']; ?>20; color: <?php echo $socio['membresia_color']; ?>">
                                            <?php echo htmlspecialchars($socio['tipo_membresia_nombre']); ?>
                                        </span>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Estado</dt>
                                    <dd class="mt-1"><?php echo statusBadge($socio['estado']); ?></dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Fecha de Inicio</dt>
                                    <dd class="mt-1 text-sm text-gray-900"><?php echo formatDate($socio['fecha_inicio']); ?></dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Fecha de Vencimiento</dt>
                                    <dd class="mt-1 text-sm text-gray-900 font-semibold">
                                        <?php 
                                        echo formatDate($socio['fecha_vencimiento']);
                                        $diasRestantes = (strtotime($socio['fecha_vencimiento']) - time()) / (60 * 60 * 24);
                                        if ($diasRestantes > 0 && $diasRestantes <= 7) {
                                            echo ' <span class="text-red-600">(¡' . ceil($diasRestantes) . ' días restantes!)</span>';
                                        }
                                        ?>
                                    </dd>
                                </div>
                            </dl>
                        <?php else: ?>
                            <p class="text-gray-500">No tiene membresía activa</p>
                        <?php endif; ?>
                        
                        <?php if ($socio['notas']): ?>
                            <div class="mt-4">
                                <dt class="text-sm font-medium text-gray-500">Notas</dt>
                                <dd class="mt-1 text-sm text-gray-900"><?php echo nl2br(htmlspecialchars($socio['notas'])); ?></dd>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Recent Access -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">
                            <i class="fas fa-door-open mr-2"></i>Accesos Recientes
                        </h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha/Hora</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dispositivo</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($recentAccess)): ?>
                                    <tr>
                                        <td colspan="4" class="px-6 py-4 text-center text-gray-500">No hay registros de acceso</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recentAccess as $acceso): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php echo formatDateTime($acceso['fecha_hora']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 capitalize">
                                                <?php echo htmlspecialchars($acceso['tipo']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php echo statusBadge($acceso['estado']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo htmlspecialchars($acceso['dispositivo_nombre'] ?? 'Manual'); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Payments History -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">
                            <i class="fas fa-dollar-sign mr-2"></i>Historial de Pagos
                        </h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Membresía</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Monto</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Método</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($pagos)): ?>
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">No hay registros de pagos</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($pagos as $pago): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php echo formatDate($pago['fecha_pago']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo htmlspecialchars($pago['tipo_membresia_nombre']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                                <?php echo formatMoney($pago['monto']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 capitalize">
                                                <?php echo htmlspecialchars($pago['metodo_pago']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php echo statusBadge($pago['estado']); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function printQR() {
            const qrImage = document.querySelector('img[alt="QR Code"]').src;
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>QR - <?php echo htmlspecialchars($socio['codigo']); ?></title>
                    <style>
                        body {
                            display: flex;
                            flex-direction: column;
                            align-items: center;
                            justify-content: center;
                            min-height: 100vh;
                            margin: 0;
                            font-family: Arial, sans-serif;
                        }
                        .qr-container {
                            text-align: center;
                            padding: 20px;
                            border: 2px solid #000;
                        }
                        h1 { margin: 0 0 10px 0; }
                        p { margin: 5px 0; }
                        img { margin: 20px 0; }
                    </style>
                </head>
                <body>
                    <div class="qr-container">
                        <h1><?php echo APP_NAME; ?></h1>
                        <p><strong><?php echo htmlspecialchars($socio['nombre'] . ' ' . $socio['apellido']); ?></strong></p>
                        <p>Código: <?php echo htmlspecialchars($socio['codigo']); ?></p>
                        <img src="${qrImage}" alt="QR Code" />
                    </div>
                    <script>
                        window.onload = function() {
                            window.print();
                            window.onafterprint = function() {
                                window.close();
                            };
                        };
                    </script>
                </body>
                </html>
            `);
            printWindow.document.close();
        }
    </script>
</body>
</html>
