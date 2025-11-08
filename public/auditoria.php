<?php
require_once 'bootstrap.php';
Auth::requireRole(['superadmin', 'admin']);

$db = Database::getInstance();
$conn = $db->getConnection();

// Filters
$tipo = $_GET['tipo'] ?? '';
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('-7 days'));
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');

// SuperAdmin can filter by branch, Admin is restricted to their branch
$sucursal_id = Auth::isSuperadmin() ? ($_GET['sucursal_id'] ?? null) : Auth::sucursalId();
$usuario_id = $_GET['usuario_id'] ?? '';
$page = $_GET['page'] ?? 1;
$perPage = 50;
$offset = ($page - 1) * $perPage;

// Build query
$where = ["fecha_hora BETWEEN ? AND ?"];
$params = [$fecha_inicio . ' 00:00:00', $fecha_fin . ' 23:59:59'];

if ($tipo) {
    $where[] = "tipo = ?";
    $params[] = $tipo;
}

if ($usuario_id) {
    $where[] = "usuario_id = ?";
    $params[] = $usuario_id;
}

// Filter by branch for non-superadmin users
if ($sucursal_id) {
    $where[] = "(b.sucursal_id = ? OR b.sucursal_id IS NULL)";
    $params[] = $sucursal_id;
}

$whereClause = implode(' AND ', $where);

// Get logs
$stmt = $conn->prepare("SELECT b.*, u.nombre as usuario_nombre, u.email as usuario_email 
                       FROM bitacora_eventos b 
                       LEFT JOIN usuarios_staff u ON b.usuario_id = u.id
                       WHERE $whereClause 
                       ORDER BY b.fecha_hora DESC 
                       LIMIT ? OFFSET ?");
$params[] = $perPage;
$params[] = $offset;
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count total
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM bitacora_eventos WHERE $whereClause");
$stmt->execute(array_slice($params, 0, -2));
$total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($total / $perPage);

// Get users for filter
$usuarios = $conn->query("SELECT id, nombre FROM usuarios_staff ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

// Get branches for SuperAdmin filter
$sucursales = [];
if (Auth::isSuperadmin()) {
    $sucursales = $conn->query("SELECT id, nombre FROM sucursales WHERE activo=1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
}

$pageTitle = 'Auditoría del Sistema';
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
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">
                <i class="fas fa-clipboard-list text-blue-600 mr-2"></i><?php echo $pageTitle; ?>
            </h1>
            <p class="text-gray-600 mt-2">Registro completo de actividades y eventos del sistema</p>
        </div>
        
        <!-- Filters -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-<?php echo Auth::isSuperadmin() ? '6' : '5'; ?> gap-4">
                <?php if (Auth::isSuperadmin()): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sucursal</label>
                    <select name="sucursal_id" class="w-full px-4 py-2 border rounded-lg">
                        <option value="">Todas</option>
                        <?php foreach ($sucursales as $suc): ?>
                        <option value="<?php echo $suc['id']; ?>" <?php echo $sucursal_id == $suc['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($suc['nombre']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tipo</label>
                    <select name="tipo" class="w-full px-4 py-2 border rounded-lg">
                        <option value="">Todos</option>
                        <option value="acceso" <?php echo $tipo === 'acceso' ? 'selected' : ''; ?>>Acceso</option>
                        <option value="pago" <?php echo $tipo === 'pago' ? 'selected' : ''; ?>>Pago</option>
                        <option value="modificacion" <?php echo $tipo === 'modificacion' ? 'selected' : ''; ?>>Modificación</option>
                        <option value="sistema" <?php echo $tipo === 'sistema' ? 'selected' : ''; ?>>Sistema</option>
                        <option value="dispositivo" <?php echo $tipo === 'dispositivo' ? 'selected' : ''; ?>>Dispositivo</option>
                        <option value="error" <?php echo $tipo === 'error' ? 'selected' : ''; ?>>Error</option>
                        <option value="whatsapp" <?php echo $tipo === 'whatsapp' ? 'selected' : ''; ?>>WhatsApp</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Usuario</label>
                    <select name="usuario_id" class="w-full px-4 py-2 border rounded-lg">
                        <option value="">Todos</option>
                        <?php foreach ($usuarios as $u): ?>
                        <option value="<?php echo $u['id']; ?>" <?php echo $usuario_id == $u['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($u['nombre']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Inicio</label>
                    <input type="date" name="fecha_inicio" value="<?php echo $fecha_inicio; ?>" 
                           class="w-full px-4 py-2 border rounded-lg">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Fin</label>
                    <input type="date" name="fecha_fin" value="<?php echo $fecha_fin; ?>" 
                           class="w-full px-4 py-2 border rounded-lg">
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="w-full px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold">
                        <i class="fas fa-filter mr-2"></i>Filtrar
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-sm text-gray-600">Total Eventos</div>
                <div class="text-2xl font-bold text-gray-900"><?php echo number_format($total); ?></div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-sm text-gray-600">Período</div>
                <div class="text-lg font-semibold text-gray-900">
                    <?php echo date('d/m', strtotime($fecha_inicio)); ?> - <?php echo date('d/m', strtotime($fecha_fin)); ?>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-sm text-gray-600">Tipo Seleccionado</div>
                <div class="text-lg font-semibold text-gray-900 capitalize"><?php echo $tipo ?: 'Todos'; ?></div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-sm text-gray-600">Página</div>
                <div class="text-lg font-semibold text-gray-900"><?php echo $page; ?> de <?php echo $totalPages; ?></div>
            </div>
        </div>
        
        <!-- Logs Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha/Hora</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usuario</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Descripción</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($logs as $log): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm text-gray-900 whitespace-nowrap">
                                <?php echo date('d/m/Y H:i:s', strtotime($log['fecha_hora'])); ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs rounded-full capitalize
                                    <?php 
                                    $colors = [
                                        'acceso' => 'bg-blue-100 text-blue-800',
                                        'pago' => 'bg-green-100 text-green-800',
                                        'modificacion' => 'bg-yellow-100 text-yellow-800',
                                        'sistema' => 'bg-purple-100 text-purple-800',
                                        'dispositivo' => 'bg-cyan-100 text-cyan-800',
                                        'error' => 'bg-red-100 text-red-800',
                                        'whatsapp' => 'bg-green-100 text-green-800'
                                    ];
                                    echo $colors[$log['tipo']] ?? 'bg-gray-100 text-gray-800';
                                    ?>">
                                    <?php echo htmlspecialchars($log['tipo']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <?php if ($log['usuario_nombre']): ?>
                                    <div class="font-medium text-gray-900"><?php echo htmlspecialchars($log['usuario_nombre']); ?></div>
                                    <div class="text-gray-500"><?php echo htmlspecialchars($log['usuario_email']); ?></div>
                                <?php else: ?>
                                    <span class="text-gray-400">Sistema</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 max-w-md">
                                <?php echo htmlspecialchars($log['descripcion']); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 font-mono">
                                <?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="bg-gray-50 px-6 py-4 flex items-center justify-between border-t">
                <div class="text-sm text-gray-700">
                    Mostrando <?php echo $offset + 1; ?> a <?php echo min($offset + $perPage, $total); ?> de <?php echo $total; ?> registros
                </div>
                <div class="flex space-x-2">
                    <?php if ($page > 1): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                       class="px-4 py-2 border rounded-lg hover:bg-gray-100">Anterior</a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                       class="px-4 py-2 border rounded-lg <?php echo $i === (int)$page ? 'bg-blue-600 text-white' : 'hover:bg-gray-100'; ?>">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                       class="px-4 py-2 border rounded-lg hover:bg-gray-100">Siguiente</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
