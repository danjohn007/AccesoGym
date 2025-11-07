<?php
require_once 'bootstrap.php';
Auth::requireRole(['superadmin', 'admin']);

require_once __DIR__ . '/../app/models/Acceso.php';
require_once __DIR__ . '/../app/models/Pago.php';
require_once __DIR__ . '/../app/models/Socio.php';

$accesoModel = new Acceso();
$pagoModel = new Pago();
$socioModel = new Socio();

$user = Auth::user();
$sucursalId = Auth::isSuperadmin() ? null : Auth::sucursalId();

// Get date range from filters
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01'); // First day of current month
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d'); // Today

// Get statistics for the period
$totalIngresos = $pagoModel->getTotalIncome($sucursalId, $fecha_inicio, $fecha_fin);
$accesoStats = $accesoModel->getStats($sucursalId, 30);
$mostActive = $accesoModel->getMostActive($sucursalId, 30, 10);
$peakHours = $accesoModel->getPeakHours($sucursalId, 30);
$paymentsByMethod = $pagoModel->getByMethod($sucursalId, $fecha_inicio, $fecha_fin);

// Get socios stats
$totalSociosActivos = $socioModel->getActiveCount($sucursalId);
$sociosVenciendo = $socioModel->getExpiringSoon(7, $sucursalId);

$pageTitle = 'Reportes';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-100">
    <?php include __DIR__ . '/../app/views/partials/navbar.php'; ?>
    
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Reportes y Estadísticas</h1>
            <p class="text-gray-600">Análisis del desempeño del gimnasio</p>
        </div>
        
        <!-- Date Filter -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <form method="GET" class="flex gap-4 items-end">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Inicio</label>
                    <input type="date" name="fecha_inicio" value="<?php echo $fecha_inicio; ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Fin</label>
                    <input type="date" name="fecha_fin" value="<?php echo $fecha_fin; ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                    <i class="fas fa-filter mr-2"></i>Filtrar
                </button>
                <a href="reportes.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
                    <i class="fas fa-times"></i>
                </a>
            </form>
        </div>
        
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                        <i class="fas fa-dollar-sign text-white text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Ingresos (Período)</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo formatMoney($totalIngresos); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                        <i class="fas fa-users text-white text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Socios Activos</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $totalSociosActivos; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                        <i class="fas fa-exclamation-triangle text-white text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Por Vencer (7 días)</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo count($sociosVenciendo); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-purple-500 rounded-md p-3">
                        <i class="fas fa-chart-line text-white text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Accesos (30 días)</p>
                        <p class="text-2xl font-bold text-gray-900">
                            <?php 
                            $totalAccesos = array_sum(array_column($accesoStats, 'permitidos'));
                            echo $totalAccesos;
                            ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Access Trend Chart -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Tendencia de Accesos (30 días)</h2>
                <canvas id="accessTrendChart"></canvas>
            </div>
            
            <!-- Peak Hours Chart -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Horas Pico (30 días)</h2>
                <canvas id="peakHoursChart"></canvas>
            </div>
        </div>
        
        <!-- Second Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Payment Methods Chart -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Métodos de Pago (Período seleccionado)</h2>
                <canvas id="paymentMethodsChart"></canvas>
            </div>
            
            <!-- Most Active Members -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Socios Más Activos (30 días)</h2>
                <div class="overflow-y-auto" style="max-height: 300px;">
                    <table class="min-w-full">
                        <thead class="bg-gray-50 sticky top-0">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Socio</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Accesos</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($mostActive as $socio): ?>
                                <tr>
                                    <td class="px-4 py-2 text-sm">
                                        <a href="socio_detalle.php?id=<?php echo $socio['id']; ?>" 
                                           class="text-blue-600 hover:text-blue-900">
                                            <?php echo htmlspecialchars($socio['nombre']); ?>
                                        </a>
                                        <span class="text-gray-500 text-xs ml-1">(<?php echo htmlspecialchars($socio['codigo']); ?>)</span>
                                    </td>
                                    <td class="px-4 py-2 text-sm text-right font-semibold">
                                        <?php echo $socio['total_accesos']; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Export Options -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Exportar Reportes</h2>
            <div class="flex gap-3">
                <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    <i class="fas fa-print mr-2"></i>Imprimir Reporte
                </button>
                <a href="export_excel.php?fecha_inicio=<?php echo $fecha_inicio; ?>&fecha_fin=<?php echo $fecha_fin; ?>" 
                   class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded inline-flex items-center">
                    <i class="fas fa-file-excel mr-2"></i>Exportar a Excel
                </a>
                <a href="export_pdf.php?fecha_inicio=<?php echo $fecha_inicio; ?>&fecha_fin=<?php echo $fecha_fin; ?>" 
                   class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded inline-flex items-center">
                    <i class="fas fa-file-pdf mr-2"></i>Exportar a PDF
                </a>
            </div>
        </div>
    </div>
    
    <script>
        // Access Trend Chart
        const accessTrendCtx = document.getElementById('accessTrendChart').getContext('2d');
        const accessData = <?php echo json_encode($accesoStats); ?>;
        
        new Chart(accessTrendCtx, {
            type: 'line',
            data: {
                labels: accessData.map(item => {
                    const date = new Date(item.fecha);
                    return date.toLocaleDateString('es-MX', { day: '2-digit', month: 'short' });
                }),
                datasets: [{
                    label: 'Accesos Permitidos',
                    data: accessData.map(item => item.permitidos),
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true
            }
        });
        
        // Peak Hours Chart
        const peakHoursCtx = document.getElementById('peakHoursChart').getContext('2d');
        const peakData = <?php echo json_encode($peakHours); ?>;
        
        new Chart(peakHoursCtx, {
            type: 'bar',
            data: {
                labels: peakData.map(item => item.hora + ':00'),
                datasets: [{
                    label: 'Accesos',
                    data: peakData.map(item => item.total),
                    backgroundColor: 'rgba(59, 130, 246, 0.5)',
                    borderColor: 'rgb(59, 130, 246)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        
        // Payment Methods Chart
        const paymentMethodsCtx = document.getElementById('paymentMethodsChart').getContext('2d');
        const paymentData = <?php echo json_encode($paymentsByMethod); ?>;
        
        new Chart(paymentMethodsCtx, {
            type: 'doughnut',
            data: {
                labels: paymentData.map(item => item.metodo_pago.charAt(0).toUpperCase() + item.metodo_pago.slice(1)),
                datasets: [{
                    data: paymentData.map(item => item.total),
                    backgroundColor: [
                        'rgba(59, 130, 246, 0.7)',
                        'rgba(34, 197, 94, 0.7)',
                        'rgba(251, 191, 36, 0.7)',
                        'rgba(239, 68, 68, 0.7)',
                        'rgba(139, 92, 246, 0.7)',
                        'rgba(236, 72, 153, 0.7)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true
            }
        });
    </script>
</body>
</html>
