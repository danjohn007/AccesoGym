<?php
require_once 'bootstrap.php';
Auth::requireAuth();

require_once __DIR__ . '/../app/models/Socio.php';
require_once __DIR__ . '/../app/models/TipoMembresia.php';
require_once __DIR__ . '/../app/models/Sucursal.php';

$socioModel = new Socio();
$tipoMembresiaModel = new TipoMembresia();
$sucursalModel = new Sucursal();

$user = Auth::user();
$sucursalId = Auth::isSuperadmin() ? null : Auth::sucursalId();

// Get all members
$socios = $socioModel->getAllWithMembresia($sucursalId);
$tiposMembresia = $tipoMembresiaModel->getActive();
$sucursales = Auth::isSuperadmin() ? $sucursalModel->getActive() : [];

$pageTitle = 'Socios';
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
                <h1 class="text-3xl font-bold text-gray-900">Gestión de Socios</h1>
                <p class="text-gray-600">Administra los miembros del gimnasio</p>
            </div>
            <a href="socio_form.php" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded inline-flex items-center">
                <i class="fas fa-plus mr-2"></i>
                Nuevo Socio
            </a>
        </div>
        
        <!-- Filters -->
        <div class="bg-white rounded-lg shadow p-6 mb-6" x-data="{ filtersOpen: false }">
            <button @click="filtersOpen = !filtersOpen" class="flex items-center text-gray-700 font-medium">
                <i class="fas fa-filter mr-2"></i>
                Filtros
                <i class="fas fa-chevron-down ml-2 text-sm" :class="{ 'rotate-180': filtersOpen }"></i>
            </button>
            
            <div x-show="filtersOpen" class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-4" style="display: none;">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
                    <input type="text" id="searchInput" placeholder="Nombre, código, teléfono..." 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                    <select id="estadoFilter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Todos</option>
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                        <option value="suspendido">Suspendido</option>
                        <option value="vencido">Vencido</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Membresía</label>
                    <select id="membresiaFilter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Todas</option>
                        <?php foreach ($tiposMembresia as $tipo): ?>
                            <option value="<?php echo $tipo['id']; ?>"><?php echo htmlspecialchars($tipo['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="flex items-end">
                    <button onclick="clearFilters()" class="w-full bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
                        Limpiar Filtros
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Members Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200" id="sociosTable">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Foto</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Teléfono</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Membresía</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vencimiento</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($socios)): ?>
                            <tr>
                                <td colspan="8" class="px-6 py-4 text-center text-gray-500">No hay socios registrados</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($socios as $socio): ?>
                                <tr class="socio-row" 
                                    data-nombre="<?php echo htmlspecialchars(strtolower($socio['nombre'] . ' ' . $socio['apellido'])); ?>"
                                    data-codigo="<?php echo htmlspecialchars(strtolower($socio['codigo'])); ?>"
                                    data-telefono="<?php echo htmlspecialchars($socio['telefono']); ?>"
                                    data-estado="<?php echo $socio['estado']; ?>"
                                    data-membresia="<?php echo $socio['tipo_membresia_id']; ?>">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($socio['foto']): ?>
                                            <img src="<?php echo '/uploads/photos/' . htmlspecialchars($socio['foto']); ?>" 
                                                 alt="Foto" class="h-10 w-10 rounded-full object-cover">
                                        <?php else: ?>
                                            <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                                <i class="fas fa-user text-gray-400"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($socio['codigo']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($socio['nombre'] . ' ' . $socio['apellido']); ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <?php echo htmlspecialchars($socio['email'] ?? 'Sin email'); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo formatPhone($socio['telefono']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($socio['tipo_membresia_nombre']): ?>
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full" 
                                                  style="background-color: <?php echo $socio['membresia_color']; ?>20; color: <?php echo $socio['membresia_color']; ?>">
                                                <?php echo htmlspecialchars($socio['tipo_membresia_nombre']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-gray-500 text-sm">Sin membresía</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo $socio['fecha_vencimiento'] ? formatDate($socio['fecha_vencimiento']) : '-'; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php echo statusBadge($socio['estado']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="socio_detalle.php?id=<?php echo $socio['id']; ?>" 
                                           class="text-blue-600 hover:text-blue-900 mr-3" title="Ver detalle">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="socio_form.php?id=<?php echo $socio['id']; ?>" 
                                           class="text-green-600 hover:text-green-900 mr-3" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="acceso_manual.php?socio_id=<?php echo $socio['id']; ?>" 
                                           class="text-purple-600 hover:text-purple-900" title="Acceso manual">
                                            <i class="fas fa-door-open"></i>
                                        </a>
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
        // Filter functionality
        const searchInput = document.getElementById('searchInput');
        const estadoFilter = document.getElementById('estadoFilter');
        const membresiaFilter = document.getElementById('membresiaFilter');
        const rows = document.querySelectorAll('.socio-row');
        
        function filterTable() {
            const searchTerm = searchInput.value.toLowerCase();
            const estadoValue = estadoFilter.value;
            const membresiaValue = membresiaFilter.value;
            
            rows.forEach(row => {
                const nombre = row.dataset.nombre;
                const codigo = row.dataset.codigo;
                const telefono = row.dataset.telefono;
                const estado = row.dataset.estado;
                const membresia = row.dataset.membresia;
                
                const matchesSearch = !searchTerm || 
                    nombre.includes(searchTerm) || 
                    codigo.includes(searchTerm) || 
                    telefono.includes(searchTerm);
                
                const matchesEstado = !estadoValue || estado === estadoValue;
                const matchesMembresia = !membresiaValue || membresia === membresiaValue;
                
                if (matchesSearch && matchesEstado && matchesMembresia) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
        
        function clearFilters() {
            searchInput.value = '';
            estadoFilter.value = '';
            membresiaFilter.value = '';
            filterTable();
        }
        
        searchInput.addEventListener('input', filterTable);
        estadoFilter.addEventListener('change', filterTable);
        membresiaFilter.addEventListener('change', filterTable);
    </script>
</body>
</html>
