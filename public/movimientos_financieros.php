<?php
require_once 'bootstrap.php';
Auth::requireRole(['superadmin', 'admin']);

$db = Database::getInstance();
$conn = $db->getConnection();
$user = Auth::user();

// Filters
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
$tipo = $_GET['tipo'] ?? '';
$buscar = $_GET['buscar'] ?? '';
$categoria = $_GET['categoria'] ?? '';
$sucursal_id = Auth::isSuperadmin() ? ($_GET['sucursal_id'] ?? null) : $user['sucursal_id'];

// Pagination
$page = $_GET['page'] ?? 1;
$perPage = 50;
$offset = ($page - 1) * $perPage;

try {
    // Build WHERE clause for date range filter
    $dateCondition = "fecha BETWEEN '{$fecha_inicio} 00:00:00' AND '{$fecha_fin} 23:59:59'";
    
    // Build the query dynamically based on filters
    $queries = [];
    
    // Payments query
    if ($tipo === '' || $tipo === 'pago') {
        $queries[] = "(SELECT 'pago' as tipo, fecha_pago as fecha, monto, metodo_pago as descripcion, 
                CONCAT('Pago de ', s.nombre, ' ', s.apellido) as concepto, p.sucursal_id,
                su.nombre as sucursal_nombre
         FROM pagos p
         LEFT JOIN socios s ON p.socio_id = s.id
         LEFT JOIN sucursales su ON p.sucursal_id = su.id
         WHERE estado='completado'
         " . ($sucursal_id ? "AND p.sucursal_id = {$sucursal_id}" : "") . ")";
    }
    
    // Extra income query
    if ($tipo === '' || $tipo === 'ingreso') {
        $queries[] = "(SELECT 'ingreso' as tipo, fecha_ingreso as fecha, monto, categoria as descripcion, 
                concepto, sucursal_id,
                su.nombre as sucursal_nombre
         FROM ingresos_extra i
         LEFT JOIN sucursales su ON i.sucursal_id = su.id
         WHERE 1=1
         " . ($sucursal_id ? "AND sucursal_id = {$sucursal_id}" : "") . ")";
    }
    
    // Expenses query
    if ($tipo === '' || $tipo === 'gasto') {
        $queries[] = "(SELECT 'gasto' as tipo, fecha_gasto as fecha, -monto as monto, categoria as descripcion, 
                concepto, sucursal_id,
                su.nombre as sucursal_nombre
         FROM gastos g
         LEFT JOIN sucursales su ON g.sucursal_id = su.id
         WHERE 1=1
         " . ($sucursal_id ? "AND sucursal_id = {$sucursal_id}" : "") . ")";
    }
    
    // If no queries match (shouldn't happen), add empty result
    if (empty($queries)) {
        $queries[] = "SELECT 'pago' as tipo, NOW() as fecha, 0 as monto, '' as descripcion, '' as concepto, NULL as sucursal_id, '' as sucursal_nombre WHERE 1=0";
    }
    
    // Combine all queries
    $movimientos_sql = implode(" UNION ALL ", $queries);
    
    // Apply filters to combined result
    $filterConditions = [];
    $filterConditions[] = $dateCondition;
    
    if (!empty($buscar)) {
        $buscarSafe = $conn->quote('%' . $buscar . '%');
        $filterConditions[] = "(concepto LIKE $buscarSafe OR descripcion LIKE $buscarSafe)";
    }
    
    if (!empty($categoria)) {
        $categoriaSafe = $conn->quote('%' . $categoria . '%');
        $filterConditions[] = "descripcion LIKE $categoriaSafe";
    }
    
    $filterClause = implode(' AND ', $filterConditions);
    
    // Final query with filters and pagination
    $final_sql = "SELECT * FROM ($movimientos_sql) as combined_movements 
                  WHERE $filterClause 
                  ORDER BY fecha DESC 
                  LIMIT $perPage OFFSET $offset";
    
    $stmt = $conn->query($final_sql);
    $movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Count total for pagination
    $count_sql = "SELECT COUNT(*) as total FROM ($movimientos_sql) as combined_movements WHERE $filterClause";
    $stmt = $conn->query($count_sql);
    $total_movimientos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_movimientos / $perPage);
    
    // Get branches for filter
    $sucursales = [];
    if (Auth::isSuperadmin()) {
        $sucursales = $conn->query("SELECT id, nombre FROM sucursales WHERE activo=1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get unique categories for autocomplete
    $categorias_pagos = $conn->query("SELECT DISTINCT metodo_pago as categoria FROM pagos WHERE metodo_pago IS NOT NULL AND metodo_pago != ''")->fetchAll(PDO::FETCH_COLUMN);
    $categorias_ingresos = $conn->query("SELECT DISTINCT categoria FROM ingresos_extra WHERE categoria IS NOT NULL AND categoria != ''")->fetchAll(PDO::FETCH_COLUMN);
    $categorias_gastos = $conn->query("SELECT DISTINCT categoria FROM gastos WHERE categoria IS NOT NULL AND categoria != ''")->fetchAll(PDO::FETCH_COLUMN);
    $categorias_all = array_unique(array_merge($categorias_pagos, $categorias_ingresos, $categorias_gastos));
    sort($categorias_all);
    
} catch (Exception $e) {
    $error = $e->getMessage();
    error_log("Error in movimientos_financieros.php: " . $e->getMessage());
}

$pageTitle = 'Movimientos Financieros';
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
                <h1 class="text-3xl font-bold text-gray-900">
                    <i class="fas fa-list text-blue-600 mr-2"></i><?php echo $pageTitle; ?>
                </h1>
                <p class="text-gray-600 mt-2">Historial detallado de todos los movimientos financieros</p>
            </div>
            <a href="modulo_financiero.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded inline-flex items-center">
                <i class="fas fa-arrow-left mr-2"></i>
                Volver al Dashboard
            </a>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700">Error: <?php echo htmlspecialchars($error); ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Filters -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-filter mr-2"></i>Filtros de Búsqueda
            </h2>
            <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-<?php echo Auth::isSuperadmin() ? '4' : '3'; ?> gap-4">
                <?php if (Auth::isSuperadmin()): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sucursal</label>
                    <select name="sucursal_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Todas las sucursales</option>
                        <?php foreach ($sucursales as $suc): ?>
                        <option value="<?php echo $suc['id']; ?>" <?php echo ($sucursal_id == $suc['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($suc['nombre']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Inicio</label>
                    <input type="date" name="fecha_inicio" value="<?php echo htmlspecialchars($fecha_inicio); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Fin</label>
                    <input type="date" name="fecha_fin" value="<?php echo htmlspecialchars($fecha_fin); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Movimiento</label>
                    <select name="tipo" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Todos</option>
                        <option value="pago" <?php echo $tipo === 'pago' ? 'selected' : ''; ?>>Pagos</option>
                        <option value="ingreso" <?php echo $tipo === 'ingreso' ? 'selected' : ''; ?>>Ingresos Extra</option>
                        <option value="gasto" <?php echo $tipo === 'gasto' ? 'selected' : ''; ?>>Gastos</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Buscar en Concepto</label>
                    <input type="text" name="buscar" value="<?php echo htmlspecialchars($buscar); ?>"
                           placeholder="Buscar por concepto..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Categoría/Método</label>
                    <input type="text" name="categoria" value="<?php echo htmlspecialchars($categoria); ?>"
                           placeholder="Filtrar por categoría..."
                           list="categorias-list"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <datalist id="categorias-list">
                        <?php foreach ($categorias_all as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
                
                <div class="flex items-end space-x-2">
                    <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        <i class="fas fa-search mr-2"></i>Buscar
                    </button>
                    <a href="movimientos_financieros.php" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded text-center">
                        <i class="fas fa-times mr-2"></i>Limpiar
                    </a>
                </div>
            </form>
        </div>
        
        <!-- Summary Stats -->
        <?php if (!empty($movimientos)): ?>
        <?php
            $total_ingresos = 0;
            $total_egresos = 0;
            foreach ($movimientos as $mov) {
                if ($mov['monto'] > 0) {
                    $total_ingresos += $mov['monto'];
                } else {
                    $total_egresos += abs($mov['monto']);
                }
            }
            $balance = $total_ingresos - $total_egresos;
        ?>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Ingresos</p>
                        <p class="text-2xl font-bold text-green-600">$<?php echo number_format($total_ingresos, 2); ?></p>
                    </div>
                    <div class="bg-green-100 rounded-full p-3">
                        <i class="fas fa-arrow-up text-green-600 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Egresos</p>
                        <p class="text-2xl font-bold text-red-600">$<?php echo number_format($total_egresos, 2); ?></p>
                    </div>
                    <div class="bg-red-100 rounded-full p-3">
                        <i class="fas fa-arrow-down text-red-600 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Balance</p>
                        <p class="text-2xl font-bold <?php echo $balance >= 0 ? 'text-blue-600' : 'text-red-600'; ?>">
                            $<?php echo number_format($balance, 2); ?>
                        </p>
                    </div>
                    <div class="<?php echo $balance >= 0 ? 'bg-blue-100' : 'bg-red-100'; ?> rounded-full p-3">
                        <i class="fas fa-balance-scale <?php echo $balance >= 0 ? 'text-blue-600' : 'text-red-600'; ?> text-xl"></i>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Movements Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900">
                        Resultados de Búsqueda
                    </h3>
                    <span class="text-sm text-gray-600">
                        <?php echo number_format($total_movimientos); ?> movimientos encontrados
                    </span>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                            <?php if (Auth::isSuperadmin()): ?>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sucursal</th>
                            <?php endif; ?>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Concepto</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoría/Método</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Monto</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($movimientos)): ?>
                            <tr>
                                <td colspan="<?php echo Auth::isSuperadmin() ? '6' : '5'; ?>" class="px-6 py-8 text-center text-gray-500">
                                    <i class="fas fa-inbox text-4xl text-gray-300 mb-3"></i>
                                    <p>No se encontraron movimientos con los filtros aplicados</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($movimientos as $mov): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo date('d/m/Y H:i', strtotime($mov['fecha'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs rounded-full capitalize font-medium
                                        <?php 
                                        $colors = [
                                            'pago' => 'bg-blue-100 text-blue-800',
                                            'ingreso' => 'bg-green-100 text-green-800',
                                            'gasto' => 'bg-red-100 text-red-800'
                                        ];
                                        echo $colors[$mov['tipo']] ?? 'bg-gray-100 text-gray-800';
                                        ?>">
                                        <?php echo htmlspecialchars($mov['tipo']); ?>
                                    </span>
                                </td>
                                <?php if (Auth::isSuperadmin()): ?>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    <?php echo htmlspecialchars($mov['sucursal_nombre'] ?? 'N/A'); ?>
                                </td>
                                <?php endif; ?>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <?php echo htmlspecialchars($mov['concepto']); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 capitalize">
                                    <?php echo htmlspecialchars($mov['descripcion']); ?>
                                </td>
                                <td class="px-6 py-4 text-sm font-semibold text-right whitespace-nowrap
                                    <?php echo $mov['monto'] >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                    <?php echo $mov['monto'] >= 0 ? '+' : ''; ?>$<?php echo number_format(abs($mov['monto']), 2); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="bg-gray-50 px-6 py-4 flex items-center justify-between border-t">
                <div class="text-sm text-gray-700">
                    Mostrando <?php echo $offset + 1; ?> a <?php echo min($offset + $perPage, $total_movimientos); ?> de <?php echo number_format($total_movimientos); ?> movimientos
                </div>
                <div class="flex space-x-2">
                    <?php if ($page > 1): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                       class="px-4 py-2 border rounded-lg hover:bg-gray-100">
                        <i class="fas fa-chevron-left mr-1"></i>Anterior
                    </a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                       class="px-4 py-2 border rounded-lg <?php echo $i === (int)$page ? 'bg-blue-600 text-white' : 'hover:bg-gray-100'; ?>">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                       class="px-4 py-2 border rounded-lg hover:bg-gray-100">
                        Siguiente<i class="fas fa-chevron-right ml-1"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
