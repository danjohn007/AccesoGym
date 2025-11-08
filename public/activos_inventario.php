<?php
require_once 'bootstrap.php';
Auth::requireRole(['superadmin', 'admin']);

$db = Database::getInstance();
$conn = $db->getConnection();

$user = Auth::user();
$sucursalId = Auth::isSuperadmin() ? ($_GET['sucursal_id'] ?? null) : Auth::sucursalId();

// Get filters
$tipo = $_GET['tipo'] ?? '';
$estado = $_GET['estado'] ?? '';

// Build query
$where = ["1=1"];
$params = [];

if ($sucursalId) {
    $where[] = "sucursal_id = ?";
    $params[] = $sucursalId;
}

if ($tipo) {
    $where[] = "tipo = ?";
    $params[] = $tipo;
}

if ($estado) {
    $where[] = "estado = ?";
    $params[] = $estado;
}

$whereClause = implode(' AND ', $where);

// Get assets
$stmt = $conn->prepare("SELECT a.*, s.nombre as sucursal_nombre 
                       FROM activos_inventario a
                       LEFT JOIN sucursales s ON a.sucursal_id = s.id
                       WHERE $whereClause
                       ORDER BY a.created_at DESC");
$stmt->execute($params);
$activos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Load branches for SuperAdmin
$sucursales = [];
if (Auth::isSuperadmin()) {
    $sucursales = $conn->query("SELECT id, nombre FROM sucursales WHERE activo=1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
}

$pageTitle = 'Activos e Inventario';
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
                    <i class="fas fa-boxes text-blue-600 mr-2"></i><?php echo $pageTitle; ?>
                </h1>
                <p class="text-gray-600 mt-2">Gestión de equipos, mobiliario e inventario por sucursal</p>
            </div>
            <a href="activo_form.php" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold">
                <i class="fas fa-plus mr-2"></i>Nuevo Activo
            </a>
        </div>
        
        <!-- Filters -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <?php if (Auth::isSuperadmin()): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sucursal</label>
                    <select name="sucursal_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Todas</option>
                        <?php foreach ($sucursales as $suc): ?>
                            <option value="<?php echo $suc['id']; ?>" <?php echo $sucursalId == $suc['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($suc['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tipo</label>
                    <select name="tipo" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Todos</option>
                        <option value="equipo" <?php echo $tipo === 'equipo' ? 'selected' : ''; ?>>Equipo</option>
                        <option value="mobiliario" <?php echo $tipo === 'mobiliario' ? 'selected' : ''; ?>>Mobiliario</option>
                        <option value="electronico" <?php echo $tipo === 'electronico' ? 'selected' : ''; ?>>Electrónico</option>
                        <option value="consumible" <?php echo $tipo === 'consumible' ? 'selected' : ''; ?>>Consumible</option>
                        <option value="otro" <?php echo $tipo === 'otro' ? 'selected' : ''; ?>>Otro</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                    <select name="estado" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Todos</option>
                        <option value="excelente" <?php echo $estado === 'excelente' ? 'selected' : ''; ?>>Excelente</option>
                        <option value="bueno" <?php echo $estado === 'bueno' ? 'selected' : ''; ?>>Bueno</option>
                        <option value="regular" <?php echo $estado === 'regular' ? 'selected' : ''; ?>>Regular</option>
                        <option value="malo" <?php echo $estado === 'malo' ? 'selected' : ''; ?>>Malo</option>
                        <option value="fuera_servicio" <?php echo $estado === 'fuera_servicio' ? 'selected' : ''; ?>>Fuera de Servicio</option>
                    </select>
                </div>
                
                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        <i class="fas fa-filter mr-2"></i>Filtrar
                    </button>
                    <a href="activos_inventario.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </form>
        </div>
        
        <!-- Assets Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if (empty($activos)): ?>
                <div class="col-span-full bg-white rounded-lg shadow p-12 text-center">
                    <i class="fas fa-boxes text-gray-300 text-6xl mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">No hay activos registrados</h3>
                    <p class="text-gray-500 mb-4">Comienza agregando tu primer activo o equipo</p>
                    <a href="activo_form.php" class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold">
                        <i class="fas fa-plus mr-2"></i>Agregar Activo
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($activos as $activo): ?>
                    <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow">
                        <?php if ($activo['foto']): ?>
                            <img src="<?php echo APP_URL . '/uploads/activos/' . htmlspecialchars($activo['foto']); ?>" 
                                 alt="<?php echo htmlspecialchars($activo['nombre']); ?>" 
                                 class="w-full h-48 object-cover rounded-t-lg">
                        <?php else: ?>
                            <div class="w-full h-48 bg-gray-200 flex items-center justify-center rounded-t-lg">
                                <i class="fas fa-box text-gray-400 text-6xl"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="p-4">
                            <div class="flex items-start justify-between mb-2">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($activo['nombre']); ?></h3>
                                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars($activo['codigo']); ?></p>
                                </div>
                                <span class="px-2 py-1 text-xs rounded-full capitalize
                                    <?php 
                                    echo $activo['estado'] === 'excelente' ? 'bg-green-100 text-green-800' : 
                                         ($activo['estado'] === 'bueno' ? 'bg-blue-100 text-blue-800' : 
                                         ($activo['estado'] === 'regular' ? 'bg-yellow-100 text-yellow-800' : 
                                         ($activo['estado'] === 'malo' ? 'bg-orange-100 text-orange-800' : 'bg-red-100 text-red-800')));
                                    ?>">
                                    <?php echo str_replace('_', ' ', $activo['estado']); ?>
                                </span>
                            </div>
                            
                            <div class="space-y-1 text-sm text-gray-600 mb-3">
                                <p><i class="fas fa-tag w-4"></i> <span class="capitalize"><?php echo htmlspecialchars($activo['tipo']); ?></span></p>
                                <p><i class="fas fa-building w-4"></i> <?php echo htmlspecialchars($activo['sucursal_nombre']); ?></p>
                                <?php if ($activo['ubicacion']): ?>
                                <p><i class="fas fa-map-marker-alt w-4"></i> <?php echo htmlspecialchars($activo['ubicacion']); ?></p>
                                <?php endif; ?>
                                <?php if ($activo['cantidad'] > 1): ?>
                                <p><i class="fas fa-layer-group w-4"></i> Cantidad: <?php echo $activo['cantidad']; ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="flex justify-between items-center pt-3 border-t">
                                <a href="activo_detalle.php?id=<?php echo $activo['id']; ?>" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                    Ver Detalle <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                                <a href="activo_form.php?id=<?php echo $activo['id']; ?>" class="text-gray-600 hover:text-gray-800">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
