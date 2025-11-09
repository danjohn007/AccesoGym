<?php
require_once 'bootstrap.php';
Auth::requireAuth();

require_once __DIR__ . '/../app/models/Acceso.php';
require_once __DIR__ . '/../app/models/Sucursal.php';

$accesoModel = new Acceso();
$sucursalModel = new Sucursal();

$user = Auth::user();
$sucursalId = Auth::isSuperadmin() ? null : Auth::sucursalId();

// Get filters
$filters = [];
// Allow superadmin to filter by branch
if (Auth::isSuperadmin() && !empty($_GET['sucursal_id'])) {
    $filters['sucursal_id'] = $_GET['sucursal_id'];
} elseif ($sucursalId) {
    $filters['sucursal_id'] = $sucursalId;
}
if (!empty($_GET['estado'])) {
    $filters['estado'] = $_GET['estado'];
}
if (!empty($_GET['tipo'])) {
    $filters['tipo'] = $_GET['tipo'];
}
if (!empty($_GET['fecha_inicio'])) {
    $filters['fecha_inicio'] = $_GET['fecha_inicio'];
}
if (!empty($_GET['fecha_fin'])) {
    $filters['fecha_fin'] = $_GET['fecha_fin'];
}

$accesos = $accesoModel->getAccessLog($filters, 100);
$sucursales = Auth::isSuperadmin() ? $sucursalModel->getActive() : [];

$pageTitle = 'Registro de Accesos';
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
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Registro de Accesos</h1>
            <p class="text-gray-600">Historial completo de accesos al gimnasio</p>
        </div>
        
        <!-- Filters -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-<?php echo Auth::isSuperadmin() ? '6' : '5'; ?> gap-4">
                <?php if (Auth::isSuperadmin()): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sucursal</label>
                    <select name="sucursal_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Todas</option>
                        <?php foreach ($sucursales as $sucursal): ?>
                            <option value="<?php echo $sucursal['id']; ?>" <?php echo ($_GET['sucursal_id'] ?? '') == $sucursal['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($sucursal['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Inicio</label>
                    <input type="date" name="fecha_inicio" value="<?php echo htmlspecialchars($_GET['fecha_inicio'] ?? ''); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Fin</label>
                    <input type="date" name="fecha_fin" value="<?php echo htmlspecialchars($_GET['fecha_fin'] ?? ''); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                    <select name="estado" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Todos</option>
                        <option value="permitido" <?php echo ($_GET['estado'] ?? '') == 'permitido' ? 'selected' : ''; ?>>Permitido</option>
                        <option value="denegado" <?php echo ($_GET['estado'] ?? '') == 'denegado' ? 'selected' : ''; ?>>Denegado</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tipo</label>
                    <select name="tipo" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Todos</option>
                        <option value="qr" <?php echo ($_GET['tipo'] ?? '') == 'qr' ? 'selected' : ''; ?>>QR</option>
                        <option value="manual" <?php echo ($_GET['tipo'] ?? '') == 'manual' ? 'selected' : ''; ?>>Manual</option>
                        <option value="whatsapp" <?php echo ($_GET['tipo'] ?? '') == 'whatsapp' ? 'selected' : ''; ?>>WhatsApp</option>
                        <option value="api" <?php echo ($_GET['tipo'] ?? '') == 'api' ? 'selected' : ''; ?>>API</option>
                    </select>
                </div>
                
                <div class="flex items-end space-x-2">
                    <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        <i class="fas fa-search mr-2"></i>Filtrar
                    </button>
                    <a href="accesos.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </form>
        </div>
        
        <!-- Access Log Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha/Hora</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Socio</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dispositivo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Motivo</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($accesos)): ?>
                            <tr>
                                <td colspan="7" class="px-6 py-4 text-center text-gray-500">No hay registros de acceso</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($accesos as $acceso): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo formatDateTime($acceso['fecha_hora']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <a href="socio_detalle.php?id=<?php echo $acceso['socio_id']; ?>" 
                                           class="text-blue-600 hover:text-blue-900 text-sm font-medium">
                                            <?php echo htmlspecialchars($acceso['socio_nombre']); ?>
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($acceso['socio_codigo']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($acceso['dispositivo_nombre'] ?? 'Manual'); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <span class="capitalize"><?php echo htmlspecialchars($acceso['tipo']); ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php echo statusBadge($acceso['estado']); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        <?php echo htmlspecialchars($acceso['motivo'] ?? '-'); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="mt-4 text-sm text-gray-600">
            <p>Total de registros: <?php echo count($accesos); ?></p>
        </div>
    </div>
</body>
</html>
