<?php
require_once 'bootstrap.php';
Auth::requireRole(['superadmin', 'admin']);

$db = Database::getInstance();
$conn = $db->getConnection();
$user = Auth::user();

// Date range filter
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-t');
$sucursal_id = Auth::isSuperadmin() ? ($_GET['sucursal_id'] ?? null) : $user['sucursal_id'];

// Calculate financials
try {
    // Ingresos (Payments + Extra Income)
    $stmt = $conn->prepare("SELECT SUM(monto) as total FROM pagos 
                           WHERE fecha_pago BETWEEN ? AND ? AND estado='completado'
                           " . ($sucursal_id ? "AND sucursal_id = ?" : ""));
    $params = [$fecha_inicio . ' 00:00:00', $fecha_fin . ' 23:59:59'];
    if ($sucursal_id) $params[] = $sucursal_id;
    $stmt->execute($params);
    $ingresos_pagos = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Add extra income
    $stmt = $conn->prepare("SELECT SUM(monto) as total FROM ingresos_extra 
                           WHERE fecha_ingreso BETWEEN ? AND ?
                           " . ($sucursal_id ? "AND sucursal_id = ?" : ""));
    $stmt->execute($params);
    $ingresos_extra = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    $ingresos = $ingresos_pagos + $ingresos_extra;
    
    // Gastos (Expenses)
    $stmt = $conn->prepare("SELECT SUM(monto) as total FROM gastos 
                           WHERE fecha_gasto BETWEEN ? AND ?
                           " . ($sucursal_id ? "AND sucursal_id = ?" : ""));
    $stmt->execute($params);
    $gastos = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Balance
    $balance = $ingresos - $gastos;
    
    // Payments by method
    $stmt = $conn->prepare("SELECT metodo_pago, SUM(monto) as total, COUNT(*) as cantidad 
                           FROM pagos WHERE fecha_pago BETWEEN ? AND ? AND estado='completado'
                           " . ($sucursal_id ? "AND sucursal_id = ?" : "") . "
                           GROUP BY metodo_pago");
    $stmt->execute($params);
    $pagosPorMetodo = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add extra income by category to the chart
    $stmt = $conn->prepare("SELECT categoria as metodo_pago, SUM(monto) as total, COUNT(*) as cantidad 
                           FROM ingresos_extra WHERE fecha_ingreso BETWEEN ? AND ?
                           " . ($sucursal_id ? "AND sucursal_id = ?" : "") . "
                           GROUP BY categoria");
    $stmt->execute($params);
    $ingresosPorCategoria = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Merge both arrays
    $pagosPorMetodo = array_merge($pagosPorMetodo, $ingresosPorCategoria);
    
    // Expenses by category
    $stmt = $conn->prepare("SELECT categoria, SUM(monto) as total, COUNT(*) as cantidad 
                           FROM gastos WHERE fecha_gasto BETWEEN ? AND ?
                           " . ($sucursal_id ? "AND sucursal_id = ?" : "") . "
                           GROUP BY categoria");
    $stmt->execute($params);
    $gastosPorCategoria = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get latest movements (payments, ingresos_extra, gastos) with pagination
    $movimientos_page = $_GET['mov_page'] ?? 1;
    $movimientos_per_page = 20;
    $movimientos_offset = ($movimientos_page - 1) * $movimientos_per_page;
    
    // Combine all movements
    $movimientos_sql = "
        (SELECT 'pago' as tipo, fecha_pago as fecha, monto, metodo_pago as descripcion, 
                CONCAT('Pago de ', s.nombre, ' ', s.apellido) as concepto, sucursal_id
         FROM pagos p
         LEFT JOIN socios s ON p.socio_id = s.id
         WHERE estado='completado'
         " . ($sucursal_id ? "AND p.sucursal_id = {$sucursal_id}" : "") . ")
        UNION ALL
        (SELECT 'ingreso' as tipo, fecha_ingreso as fecha, monto, categoria as descripcion, 
                concepto, sucursal_id
         FROM ingresos_extra
         WHERE 1=1
         " . ($sucursal_id ? "AND sucursal_id = {$sucursal_id}" : "") . ")
        UNION ALL
        (SELECT 'gasto' as tipo, fecha_gasto as fecha, -monto as monto, categoria as descripcion, 
                concepto, sucursal_id
         FROM gastos
         WHERE 1=1
         " . ($sucursal_id ? "AND sucursal_id = {$sucursal_id}" : "") . ")
        ORDER BY fecha DESC
        LIMIT {$movimientos_per_page} OFFSET {$movimientos_offset}
    ";
    
    $stmt = $conn->query($movimientos_sql);
    $ultimos_movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Count total movements
    $count_sql = "
        SELECT COUNT(*) as total FROM (
            (SELECT id FROM pagos WHERE estado='completado' " . ($sucursal_id ? "AND sucursal_id = {$sucursal_id}" : "") . ")
            UNION ALL
            (SELECT id FROM ingresos_extra WHERE 1=1 " . ($sucursal_id ? "AND sucursal_id = {$sucursal_id}" : "") . ")
            UNION ALL
            (SELECT id FROM gastos WHERE 1=1 " . ($sucursal_id ? "AND sucursal_id = {$sucursal_id}" : "") . ")
        ) as all_movements
    ";
    $stmt = $conn->query($count_sql);
    $total_movimientos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_movimientos_pages = ceil($total_movimientos / $movimientos_per_page);
    
} catch (Exception $e) {
    $error = $e->getMessage();
}

$pageTitle = 'Módulo Financiero';
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100">
    <?php include __DIR__ . '/../app/views/partials/navbar.php'; ?>
    
    <div class="container mx-auto px-4 py-8">
        <div class="mb-8">
            <div class="flex justify-between items-center mb-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">
                        <i class="fas fa-chart-line text-blue-600 mr-2"></i><?php echo $pageTitle; ?>
                    </h1>
                    <p class="text-gray-600 mt-2">Resumen financiero y análisis de ingresos/gastos</p>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <a href="registro_ingreso.php" class="bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg shadow p-4 hover:from-green-600 hover:to-green-700 transition">
                    <div class="flex items-center">
                        <i class="fas fa-plus-circle text-3xl mr-4"></i>
                        <div>
                            <h3 class="text-lg font-semibold">Registrar Ingreso</h3>
                            <p class="text-sm text-green-100">Agregar movimiento de ingreso</p>
                        </div>
                    </div>
                </a>
                
                <a href="registro_egreso.php" class="bg-gradient-to-r from-red-500 to-red-600 text-white rounded-lg shadow p-4 hover:from-red-600 hover:to-red-700 transition">
                    <div class="flex items-center">
                        <i class="fas fa-minus-circle text-3xl mr-4"></i>
                        <div>
                            <h3 class="text-lg font-semibold">Registrar Egreso</h3>
                            <p class="text-sm text-red-100">Agregar movimiento de egreso</p>
                        </div>
                    </div>
                </a>
                
                <a href="movimientos_financieros.php" class="bg-gradient-to-r from-purple-500 to-purple-600 text-white rounded-lg shadow p-4 hover:from-purple-600 hover:to-purple-700 transition">
                    <div class="flex items-center">
                        <i class="fas fa-list-alt text-3xl mr-4"></i>
                        <div>
                            <h3 class="text-lg font-semibold">Ver Movimientos</h3>
                            <p class="text-sm text-purple-100">Historial con filtros avanzados</p>
                        </div>
                    </div>
                </a>
                
                <a href="categorias_financieras.php" class="bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg shadow p-4 hover:from-blue-600 hover:to-blue-700 transition">
                    <div class="flex items-center">
                        <i class="fas fa-tags text-3xl mr-4"></i>
                        <div>
                            <h3 class="text-lg font-semibold">Categorías</h3>
                            <p class="text-sm text-blue-100">Gestionar categorías financieras</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-<?php echo Auth::isSuperadmin() ? '4' : '3'; ?> gap-4">
                <?php if (Auth::isSuperadmin()): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sucursal</label>
                    <select name="sucursal_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Todas las sucursales</option>
                        <?php
                        $stmt = $conn->prepare("SELECT id, nombre FROM sucursales WHERE activo=1 ORDER BY nombre");
                        $stmt->execute();
                        $sucursales = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($sucursales as $suc):
                        ?>
                        <option value="<?php echo $suc['id']; ?>" <?php echo ($sucursal_id == $suc['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($suc['nombre']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
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
        
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Ingresos Totales</p>
                        <p class="text-3xl font-bold text-green-600 mt-2">$<?php echo number_format($ingresos, 2); ?></p>
                    </div>
                    <div class="bg-green-100 p-4 rounded-full">
                        <i class="fas fa-arrow-up text-2xl text-green-600"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Gastos Totales</p>
                        <p class="text-3xl font-bold text-red-600 mt-2">$<?php echo number_format($gastos, 2); ?></p>
                    </div>
                    <div class="bg-red-100 p-4 rounded-full">
                        <i class="fas fa-arrow-down text-2xl text-red-600"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Balance</p>
                        <p class="text-3xl font-bold <?php echo $balance >= 0 ? 'text-blue-600' : 'text-red-600'; ?> mt-2">
                            $<?php echo number_format($balance, 2); ?>
                        </p>
                    </div>
                    <div class="bg-blue-100 p-4 rounded-full">
                        <i class="fas fa-balance-scale text-2xl text-blue-600"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Charts -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Ingresos por Método/Categoría</h3>
                <canvas id="pagosPorMetodoChart"></canvas>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Gastos por Categoría</h3>
                <canvas id="gastosPorCategoriaChart"></canvas>
            </div>
        </div>
        
        <!-- Tables -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b">
                    <h3 class="text-lg font-semibold text-gray-900">Detalle de Ingresos</h3>
                </div>
                <div class="p-6">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b">
                                <th class="text-left py-2 text-sm font-medium text-gray-600">Método</th>
                                <th class="text-right py-2 text-sm font-medium text-gray-600">Cantidad</th>
                                <th class="text-right py-2 text-sm font-medium text-gray-600">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pagosPorMetodo as $pago): ?>
                            <tr class="border-b">
                                <td class="py-2 text-sm capitalize"><?php echo htmlspecialchars($pago['metodo_pago']); ?></td>
                                <td class="py-2 text-sm text-right"><?php echo $pago['cantidad']; ?></td>
                                <td class="py-2 text-sm text-right font-semibold">$<?php echo number_format($pago['total'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b">
                    <h3 class="text-lg font-semibold text-gray-900">Detalle de Gastos</h3>
                </div>
                <div class="p-6">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b">
                                <th class="text-left py-2 text-sm font-medium text-gray-600">Categoría</th>
                                <th class="text-right py-2 text-sm font-medium text-gray-600">Cantidad</th>
                                <th class="text-right py-2 text-sm font-medium text-gray-600">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($gastosPorCategoria as $gasto): ?>
                            <tr class="border-b">
                                <td class="py-2 text-sm capitalize"><?php echo htmlspecialchars($gasto['categoria']); ?></td>
                                <td class="py-2 text-sm text-right"><?php echo $gasto['cantidad']; ?></td>
                                <td class="py-2 text-sm text-right font-semibold">$<?php echo number_format($gasto['total'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Latest Movements Section -->
        <div class="mt-8 bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">
                        <i class="fas fa-list mr-2"></i>Últimos Movimientos
                    </h3>
                    <p class="text-sm text-gray-600 mt-1">Historial completo de pagos, ingresos y egresos</p>
                </div>
                <a href="movimientos_financieros.php" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded inline-flex items-center transition">
                    <i class="fas fa-search mr-2"></i>Ver todos los movimientos
                </a>
            </div>
            
            <!-- Movement Filters -->
            <div id="movimientos" class="px-6 py-4 bg-gray-50 border-b">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Buscar</label>
                        <input type="text" id="movimientoSearch" placeholder="Buscar concepto..." 
                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Tipo</label>
                        <select id="movimientoTipo" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Todos</option>
                            <option value="pago">Pago</option>
                            <option value="ingreso">Ingreso</option>
                            <option value="gasto">Gasto</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Categoría</label>
                        <input type="text" id="movimientoCategoria" placeholder="Filtrar categoría..." 
                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div class="flex items-end">
                        <button onclick="clearMovimientoFilters()" class="w-full px-4 py-2 text-sm bg-gray-500 hover:bg-gray-600 text-white rounded-md">
                            <i class="fas fa-times mr-1"></i>Limpiar
                        </button>
                    </div>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Concepto</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Categoría</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Monto</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($ultimos_movimientos)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                    No hay movimientos registrados
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($ultimos_movimientos as $mov): ?>
                            <tr class="hover:bg-gray-50 movimiento-row"
                                data-tipo="<?php echo htmlspecialchars($mov['tipo']); ?>"
                                data-concepto="<?php echo htmlspecialchars(strtolower($mov['concepto'])); ?>"
                                data-categoria="<?php echo htmlspecialchars(strtolower($mov['descripcion'])); ?>">
                                <td class="px-6 py-4 text-sm text-gray-900 whitespace-nowrap">
                                    <?php echo date('d/m/Y H:i', strtotime($mov['fecha'])); ?>
                                </td>
                                <td class="px-6 py-4">
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
            <?php if ($total_movimientos_pages > 1): ?>
            <div class="bg-gray-50 px-6 py-4 flex items-center justify-between border-t">
                <div class="text-sm text-gray-700">
                    Mostrando <?php echo $movimientos_offset + 1; ?> a <?php echo min($movimientos_offset + $movimientos_per_page, $total_movimientos); ?> de <?php echo $total_movimientos; ?> movimientos
                </div>
                <div class="flex space-x-2">
                    <?php if ($movimientos_page > 1): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['mov_page' => $movimientos_page - 1])); ?>" 
                       class="px-4 py-2 border rounded-lg hover:bg-gray-100">Anterior</a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $movimientos_page - 2); $i <= min($total_movimientos_pages, $movimientos_page + 2); $i++): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['mov_page' => $i])); ?>" 
                       class="px-4 py-2 border rounded-lg <?php echo $i === (int)$movimientos_page ? 'bg-blue-600 text-white' : 'hover:bg-gray-100'; ?>">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>
                    
                    <?php if ($movimientos_page < $total_movimientos_pages): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['mov_page' => $movimientos_page + 1])); ?>" 
                       class="px-4 py-2 border rounded-lg hover:bg-gray-100">Siguiente</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Pagos por Método Chart
        const pagosPorMetodoCtx = document.getElementById('pagosPorMetodoChart').getContext('2d');
        new Chart(pagosPorMetodoCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_column($pagosPorMetodo, 'metodo_pago')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($pagosPorMetodo, 'total')); ?>,
                    backgroundColor: ['#3B82F6', '#10B981', '#F59E0B', '#8B5CF6', '#EF4444', '#06B6D4']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
        
        // Gastos por Categoría Chart
        const gastosPorCategoriaCtx = document.getElementById('gastosPorCategoriaChart').getContext('2d');
        new Chart(gastosPorCategoriaCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($gastosPorCategoria, 'categoria')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($gastosPorCategoria, 'total')); ?>,
                    backgroundColor: ['#EF4444', '#F59E0B', '#10B981', '#3B82F6', '#8B5CF6', '#EC4899']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
        
        // Movement filtering functionality
        const movimientoSearch = document.getElementById('movimientoSearch');
        const movimientoTipo = document.getElementById('movimientoTipo');
        const movimientoCategoria = document.getElementById('movimientoCategoria');
        const movimientoRows = document.querySelectorAll('.movimiento-row');
        
        function filterMovimientos() {
            const searchTerm = movimientoSearch.value.toLowerCase();
            const tipoValue = movimientoTipo.value;
            const categoriaValue = movimientoCategoria.value.toLowerCase();
            
            movimientoRows.forEach(row => {
                const tipo = row.dataset.tipo;
                const concepto = row.dataset.concepto;
                const categoria = row.dataset.categoria;
                
                const matchesSearch = !searchTerm || concepto.includes(searchTerm);
                const matchesTipo = !tipoValue || tipo === tipoValue;
                const matchesCategoria = !categoriaValue || categoria.includes(categoriaValue);
                
                if (matchesSearch && matchesTipo && matchesCategoria) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
        
        function clearMovimientoFilters() {
            movimientoSearch.value = '';
            movimientoTipo.value = '';
            movimientoCategoria.value = '';
            filterMovimientos();
        }
        
        movimientoSearch.addEventListener('input', filterMovimientos);
        movimientoTipo.addEventListener('change', filterMovimientos);
        movimientoCategoria.addEventListener('input', filterMovimientos);
    </script>
</body>
</html>
