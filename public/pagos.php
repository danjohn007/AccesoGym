<?php
require_once 'bootstrap.php';
Auth::requireAuth();

require_once __DIR__ . '/../app/models/Pago.php';
require_once __DIR__ . '/../app/models/Sucursal.php';

$pagoModel = new Pago();
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
if (!empty($_GET['metodo_pago'])) {
    $filters['metodo_pago'] = $_GET['metodo_pago'];
}
if (!empty($_GET['fecha_inicio'])) {
    $filters['fecha_inicio'] = $_GET['fecha_inicio'];
}
if (!empty($_GET['fecha_fin'])) {
    $filters['fecha_fin'] = $_GET['fecha_fin'];
}

// Build filtered query
$db = Database::getInstance();
$conn = $db->getConnection();

$sql = "SELECT p.*, 
               CONCAT(s.nombre, ' ', s.apellido) as socio_nombre,
               s.codigo as socio_codigo,
               tm.nombre as tipo_membresia_nombre,
               u.nombre as usuario_nombre
        FROM pagos p
        INNER JOIN socios s ON p.socio_id = s.id
        INNER JOIN tipos_membresia tm ON p.tipo_membresia_id = tm.id
        LEFT JOIN usuarios_staff u ON p.usuario_registro = u.id
        WHERE 1=1";

$params = [];
if (!empty($filters['sucursal_id'])) {
    $sql .= " AND p.sucursal_id = ?";
    $params[] = $filters['sucursal_id'];
}
if (!empty($filters['estado'])) {
    $sql .= " AND p.estado = ?";
    $params[] = $filters['estado'];
}
if (!empty($filters['metodo_pago'])) {
    $sql .= " AND p.metodo_pago = ?";
    $params[] = $filters['metodo_pago'];
}
if (!empty($filters['fecha_inicio'])) {
    $sql .= " AND p.fecha_pago >= ?";
    $params[] = $filters['fecha_inicio'] . ' 00:00:00';
}
if (!empty($filters['fecha_fin'])) {
    $sql .= " AND p.fecha_pago <= ?";
    $params[] = $filters['fecha_fin'] . ' 23:59:59';
}

$sql .= " ORDER BY p.fecha_pago DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$pagos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sucursales = Auth::isSuperadmin() ? $sucursalModel->getActive() : [];

$pageTitle = 'Pagos';
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
                <h1 class="text-3xl font-bold text-gray-900">Gestión de Pagos</h1>
                <p class="text-gray-600">Registro de pagos de membresías</p>
            </div>
            <a href="pago_form.php" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded inline-flex items-center">
                <i class="fas fa-plus mr-2"></i>
                Registrar Pago
            </a>
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
                        <option value="completado" <?php echo ($_GET['estado'] ?? '') == 'completado' ? 'selected' : ''; ?>>Completado</option>
                        <option value="pendiente" <?php echo ($_GET['estado'] ?? '') == 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                        <option value="cancelado" <?php echo ($_GET['estado'] ?? '') == 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Método de Pago</label>
                    <select name="metodo_pago" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Todos</option>
                        <option value="efectivo" <?php echo ($_GET['metodo_pago'] ?? '') == 'efectivo' ? 'selected' : ''; ?>>Efectivo</option>
                        <option value="tarjeta" <?php echo ($_GET['metodo_pago'] ?? '') == 'tarjeta' ? 'selected' : ''; ?>>Tarjeta</option>
                        <option value="transferencia" <?php echo ($_GET['metodo_pago'] ?? '') == 'transferencia' ? 'selected' : ''; ?>>Transferencia</option>
                        <option value="otro" <?php echo ($_GET['metodo_pago'] ?? '') == 'otro' ? 'selected' : ''; ?>>Otro</option>
                    </select>
                </div>
                
                <div class="flex items-end space-x-2">
                    <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        <i class="fas fa-search mr-2"></i>Filtrar
                    </button>
                    <a href="pagos.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </form>
        </div>
        
        <!-- Payments Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Socio</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Membresía</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Monto</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Método</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($pagos)): ?>
                            <tr>
                                <td colspan="8" class="px-6 py-4 text-center text-gray-500">No hay pagos registrados</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pagos as $pago): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo formatDateTime($pago['fecha_pago']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <a href="socio_detalle.php?id=<?php echo $pago['socio_id']; ?>" 
                                           class="text-blue-600 hover:text-blue-900 text-sm font-medium">
                                            <?php echo htmlspecialchars($pago['socio_nombre']); ?>
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($pago['socio_codigo']); ?>
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
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="pago_detalle.php?id=<?php echo $pago['id']; ?>" 
                                           class="text-blue-600 hover:text-blue-900 mr-3" title="Ver detalle">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if (Auth::isAdmin()): ?>
                                        <a href="pago_form.php?id=<?php echo $pago['id']; ?>" 
                                           class="text-green-600 hover:text-green-900" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
