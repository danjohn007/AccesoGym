<?php
require_once 'bootstrap.php';
Auth::requireAuth();

require_once __DIR__ . '/../app/models/Socio.php';
require_once __DIR__ . '/../app/models/Acceso.php';
require_once __DIR__ . '/../app/models/Pago.php';
require_once __DIR__ . '/../app/models/DispositivoShelly.php';

$socioModel = new Socio();
$accesoModel = new Acceso();
$pagoModel = new Pago();
$dispositivoModel = new DispositivoShelly();

$user = Auth::user();
$sucursalId = Auth::isSuperadmin() ? null : Auth::sucursalId();

// Get statistics
$totalSocios = $socioModel->getActiveCount($sucursalId);
$accesosHoy = $accesoModel->getTodayCount($sucursalId);
$ingresosHoy = $pagoModel->getTodayIncome($sucursalId);

// Add ingresos_extra and subtract gastos for today's income
$db = Database::getInstance();
$conn = $db->getConnection();
$today = date('Y-m-d');

// Get additional income from ingresos_extra
$sql = "SELECT SUM(monto) as total FROM ingresos_extra WHERE DATE(fecha_ingreso) = ?";
$params = [$today];
if ($sucursalId) {
    $sql .= " AND sucursal_id = ?";
    $params[] = $sucursalId;
}
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$ingresosExtra = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Get expenses from gastos
$sql = "SELECT SUM(monto) as total FROM gastos WHERE DATE(fecha_gasto) = ?";
$params = [$today];
if ($sucursalId) {
    $sql .= " AND sucursal_id = ?";
    $params[] = $sucursalId;
}
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$gastosHoy = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Calculate total income (payments + extra income - expenses)
$ingresosHoy = $ingresosHoy + $ingresosExtra - $gastosHoy;

$dispositivosOnline = $dispositivoModel->getOnlineCount($sucursalId);

// Get recent access logs
$recentAccess = $accesoModel->getAccessLog(['sucursal_id' => $sucursalId], 10);

// Get members expiring soon
$sociosVenciendo = $socioModel->getExpiringSoon(7, $sucursalId);

// Get access statistics for chart (last 7 days)
$accessStats = $accesoModel->getStats($sucursalId, 7);

$pageTitle = 'Dashboard';
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
            <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
            <p class="text-gray-600">Bienvenido, <?php echo htmlspecialchars($user['nombre']); ?></p>
        </div>
        
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Active Members -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                        <i class="fas fa-users text-white text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Socios Activos</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $totalSocios; ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Today's Access -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                        <i class="fas fa-door-open text-white text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Accesos Hoy</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $accesosHoy; ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Today's Income -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                        <i class="fas fa-dollar-sign text-white text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Ingresos Hoy</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo formatMoney($ingresosHoy); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Devices Online -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-purple-500 rounded-md p-3">
                        <i class="fas fa-wifi text-white text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Dispositivos Online</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $dispositivosOnline; ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Access Chart -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Accesos (Últimos 7 días)</h2>
                <canvas id="accessChart"></canvas>
            </div>
            
            <!-- Expiring Memberships -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Membresías por Vencer</h2>
                <div class="overflow-y-auto" style="max-height: 300px;">
                    <?php if (empty($sociosVenciendo)): ?>
                        <p class="text-gray-500 text-center py-4">No hay membresías por vencer próximamente</p>
                    <?php else: ?>
                        <ul class="divide-y divide-gray-200">
                            <?php foreach ($sociosVenciendo as $socio): ?>
                                <li class="py-3">
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <p class="font-medium text-gray-900"><?php echo htmlspecialchars($socio['nombre'] . ' ' . $socio['apellido']); ?></p>
                                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($socio['tipo_membresia_nombre']); ?></p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-medium text-red-600">Vence: <?php echo formatDate($socio['fecha_vencimiento']); ?></p>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Recent Access Log -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Accesos Recientes</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Socio</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dispositivo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha/Hora</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($recentAccess)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">No hay registros de acceso</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentAccess as $acceso): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($acceso['socio_nombre']); ?>
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
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo formatDateTime($acceso['fecha_hora']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
        // Access Chart
        const ctx = document.getElementById('accessChart').getContext('2d');
        const accessData = <?php echo json_encode($accessStats); ?>;
        
        const labels = accessData.map(item => {
            const date = new Date(item.fecha);
            return date.toLocaleDateString('es-MX', { day: '2-digit', month: 'short' });
        });
        
        const permitidos = accessData.map(item => item.permitidos);
        const denegados = accessData.map(item => item.denegados);
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Permitidos',
                    data: permitidos,
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    tension: 0.4
                }, {
                    label: 'Denegados',
                    data: denegados,
                    borderColor: 'rgb(239, 68, 68)',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
