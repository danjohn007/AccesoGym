<?php
require_once 'bootstrap.php';
Auth::requireRole('admin');

$db = Database::getInstance();
$conn = $db->getConnection();

// Date range filter
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-t');
$sucursal_id = Auth::isSuperadmin() ? ($_GET['sucursal_id'] ?? null) : $user['sucursal_id'];

// Calculate financials
try {
    // Ingresos (Payments)
    $stmt = $conn->prepare("SELECT SUM(monto) as total FROM pagos 
                           WHERE fecha_pago BETWEEN ? AND ? AND estado='completado'
                           " . ($sucursal_id ? "AND sucursal_id = ?" : ""));
    $params = [$fecha_inicio . ' 00:00:00', $fecha_fin . ' 23:59:59'];
    if ($sucursal_id) $params[] = $sucursal_id;
    $stmt->execute($params);
    $ingresos = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
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
    
    // Expenses by category
    $stmt = $conn->prepare("SELECT categoria, SUM(monto) as total, COUNT(*) as cantidad 
                           FROM gastos WHERE fecha_gasto BETWEEN ? AND ?
                           " . ($sucursal_id ? "AND sucursal_id = ?" : "") . "
                           GROUP BY categoria");
    $stmt->execute($params);
    $gastosPorCategoria = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
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
            <h1 class="text-3xl font-bold text-gray-900">
                <i class="fas fa-chart-line text-blue-600 mr-2"></i><?php echo $pageTitle; ?>
            </h1>
            <p class="text-gray-600 mt-2">Resumen financiero y análisis de ingresos/gastos</p>
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
                        $stmt = $conn->query("SELECT id, nombre FROM sucursales WHERE activo=1 ORDER BY nombre");
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
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Ingresos por Método de Pago</h3>
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
    </script>
</body>
</html>
