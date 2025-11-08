<!-- Custom Styles from Configuration -->
<link rel="stylesheet" href="<?php echo APP_URL; ?>/custom_styles.php">

<nav class="bg-white shadow-lg fixed top-0 left-0 right-0 z-50" x-data="{ sidebarOpen: false, accountOpen: false }">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center py-4">
            <!-- Logo -->
            <div class="flex items-center">
                <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden text-gray-700 hover:text-blue-600 focus:outline-none mr-3">
                    <i class="fas fa-bars text-2xl"></i>
                </button>
                <i class="fas fa-dumbbell text-2xl text-blue-600 mr-2"></i>
                <span class="text-xl font-bold text-gray-800"><?php echo APP_NAME; ?></span>
            </div>
            
            <!-- Global Search (Desktop & Mobile) -->
            <div class="flex-1 mx-4 hidden md:block" x-data="{ searchOpen: false, searchQuery: '', searchResults: [] }">
                <div class="relative max-w-lg mx-auto">
                    <input type="text" 
                           x-model="searchQuery"
                           @input.debounce.500ms="if(searchQuery.length >= 2) { fetch('<?php echo APP_URL; ?>/buscar_socios.php?q=' + encodeURIComponent(searchQuery)).then(r => r.json()).then(data => searchResults = data); } else { searchResults = []; }"
                           @focus="searchOpen = true"
                           @click.outside="searchOpen = false"
                           placeholder="Buscar socio por nombre, código, email o teléfono..."
                           class="w-full px-4 py-2 pl-10 pr-4 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    
                    <!-- Search Results Dropdown -->
                    <div x-show="searchOpen && searchResults.length > 0" 
                         @click.outside="searchOpen = false"
                         class="absolute top-full left-0 right-0 mt-2 bg-white rounded-lg shadow-lg border border-gray-200 max-h-96 overflow-y-auto z-[60]"
                         style="display: none;">
                        <template x-for="result in searchResults" :key="result.id">
                            <a :href="'socio_detalle.php?id=' + result.id" 
                               class="block px-4 py-3 hover:bg-gray-50 border-b border-gray-100 last:border-b-0">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 mr-3">
                                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-semibold" x-text="result.nombre.charAt(0)"></div>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900" x-text="result.nombre + ' ' + result.apellido"></p>
                                        <p class="text-xs text-gray-500">
                                            <span x-text="result.codigo"></span> • 
                                            <span x-text="result.email"></span> • 
                                            <span x-text="result.telefono"></span>
                                        </p>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                              :class="{
                                                  'bg-green-100 text-green-800': result.estado === 'activo',
                                                  'bg-red-100 text-red-800': result.estado === 'vencido',
                                                  'bg-gray-100 text-gray-800': result.estado === 'inactivo',
                                                  'bg-yellow-100 text-yellow-800': result.estado === 'suspendido'
                                              }"
                                              x-text="result.estado"></span>
                                    </div>
                                </div>
                            </a>
                        </template>
                    </div>
                </div>
            </div>
            
            <!-- Account Dropdown (Desktop) -->
            <div class="relative">
                <button @click="accountOpen = !accountOpen" 
                        class="flex items-center space-x-2 text-gray-700 hover:text-blue-600 focus:outline-none">
                    <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white text-sm">
                        <i class="fas fa-user"></i>
                    </div>
                    <span class="hidden md:inline text-sm font-medium"><?php echo htmlspecialchars($user['nombre'] ?? 'Usuario'); ?></span>
                    <i class="fas fa-chevron-down text-xs"></i>
                </button>
                
                <!-- Dropdown Menu -->
                <div x-show="accountOpen" 
                     @click.away="accountOpen = false"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-40"
                     style="display: none;">
                    <div class="px-4 py-2 border-b border-gray-200">
                        <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['nombre'] ?? 'Usuario'); ?></p>
                        <p class="text-xs text-gray-500 capitalize"><?php echo htmlspecialchars($user['rol'] ?? ''); ?></p>
                    </div>
                    
                    <a href="perfil.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50">
                        <i class="fas fa-user w-4 mr-2"></i>Mi Perfil
                    </a>
                    
                    <?php if (Auth::isSuperadmin()): ?>
                    <a href="configuracion.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50">
                        <i class="fas fa-cog w-4 mr-2"></i>Configuración
                    </a>
                    
                    <a href="sucursales.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50">
                        <i class="fas fa-building w-4 mr-2"></i>Sucursales
                    </a>
                    <?php endif; ?>
                    
                    <div class="border-t border-gray-200 mt-1"></div>
                    
                    <a href="logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                        <i class="fas fa-sign-out-alt w-4 mr-2"></i>Cerrar Sesión
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Sidebar Overlay (Mobile only) -->
    <div x-show="sidebarOpen" 
         @click="sidebarOpen = false"
         class="lg:hidden fixed inset-0 bg-black bg-opacity-50 z-40"
         style="display: none;">
    </div>
    
    <!-- Sidebar -->
    <div x-show="sidebarOpen"
         class="lg:hidden fixed top-0 left-0 h-full w-80 bg-white shadow-2xl z-50 overflow-y-auto"
         style="display: none;">
        
        <!-- Sidebar Header (Mobile) -->
        <div class="bg-blue-600 text-white p-6">
            <div class="flex justify-between items-start mb-4">
                <div class="flex items-center">
                    <i class="fas fa-dumbbell text-3xl mr-3"></i>
                    <span class="text-2xl font-bold"><?php echo APP_NAME; ?></span>
                </div>
                <button @click="sidebarOpen = false" class="text-white hover:text-gray-200 focus:outline-none">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            
            <!-- User Info -->
            <div class="border-t border-blue-500 pt-4">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center text-xl">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="ml-3 flex-1">
                        <p class="font-semibold text-sm"><?php echo htmlspecialchars($user['nombre'] ?? 'Usuario'); ?></p>
                        <p class="text-xs text-blue-200"><?php echo htmlspecialchars($user['email'] ?? ''); ?></p>
                        <span class="inline-block mt-1 px-2 py-0.5 bg-blue-500 text-xs rounded-full capitalize">
                            <?php echo htmlspecialchars($user['rol'] ?? ''); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sidebar Menu (Mobile - All items) -->
        <div class="py-4 pb-24">
            <!-- Main Section -->
            <div class="px-4 mb-2">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Menú Principal</p>
            </div>
            
            <a href="dashboard.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                <i class="fas fa-home w-5 mr-3"></i>
                <span class="font-medium">Dashboard</span>
            </a>
            
            <a href="socios.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                <i class="fas fa-users w-5 mr-3"></i>
                <span class="font-medium">Socios</span>
            </a>
            
            <a href="accesos.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                <i class="fas fa-door-open w-5 mr-3"></i>
                <span class="font-medium">Accesos</span>
            </a>
            
            <a href="pagos.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                <i class="fas fa-dollar-sign w-5 mr-3"></i>
                <span class="font-medium">Pagos</span>
            </a>
            
            <?php if (Auth::isAdmin()): ?>
            <!-- Admin Section -->
            <div class="px-4 mt-4 mb-2">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Administración</p>
            </div>
            
            <a href="dispositivos.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                <i class="fas fa-wifi w-5 mr-3"></i>
                <span class="font-medium">Dispositivos</span>
            </a>
            
            <a href="reportes.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                <i class="fas fa-chart-bar w-5 mr-3"></i>
                <span class="font-medium">Reportes</span>
            </a>
            
            <a href="membresias.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                <i class="fas fa-id-card w-5 mr-3"></i>
                <span class="font-medium">Membresías</span>
            </a>
            
            <a href="modulo_financiero.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                <i class="fas fa-chart-line w-5 mr-3"></i>
                <span class="font-medium">Módulo Financiero</span>
            </a>
            
            <a href="registro_ingreso.php" class="flex items-center px-8 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                <i class="fas fa-plus-circle w-4 mr-3 ml-2 text-green-600"></i>
                <span>Registrar Ingreso</span>
            </a>
            
            <a href="registro_egreso.php" class="flex items-center px-8 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                <i class="fas fa-minus-circle w-4 mr-3 ml-2 text-red-600"></i>
                <span>Registrar Egreso</span>
            </a>
            
            <a href="categorias_financieras.php" class="flex items-center px-8 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                <i class="fas fa-tags w-4 mr-3 ml-2"></i>
                <span>Categorías</span>
            </a>
            
            
            <a href="activos_inventario.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                <i class="fas fa-boxes w-5 mr-3"></i>
                <span class="font-medium">Activos e Inventario</span>
            </a>
            <a href="usuarios.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                <i class="fas fa-user-shield w-5 mr-3"></i>
                <span class="font-medium">Usuarios</span>
            </a>
            
            <a href="importar_datos.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                <i class="fas fa-file-import w-5 mr-3"></i>
                <span class="font-medium">Importar Datos</span>
            </a>
            <?php endif; ?>
            
            <?php if (Auth::isSuperadmin()): ?>
            <a href="auditoria.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                <i class="fas fa-clipboard-list w-5 mr-3"></i>
                <span class="font-medium">Auditoría</span>
            </a>
            <?php endif; ?>
            
            <!-- Account Section (from top right) -->
            <div class="px-4 mt-4 mb-2 border-t pt-4">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Cuenta</p>
            </div>
            
            <a href="perfil.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                <i class="fas fa-user w-5 mr-3"></i>
                <span class="font-medium">Mi Perfil</span>
            </a>
            
            <?php if (Auth::isSuperadmin()): ?>
            <a href="configuracion.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                <i class="fas fa-cog w-5 mr-3"></i>
                <span class="font-medium">Configuración</span>
            </a>
            
            <a href="sucursales.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                <i class="fas fa-building w-5 mr-3"></i>
                <span class="font-medium">Sucursales</span>
            </a>
            <?php endif; ?>
            
            <a href="logout.php" class="flex items-center px-6 py-3 text-red-600 hover:bg-red-50 transition-colors">
                <i class="fas fa-sign-out-alt w-5 mr-3"></i>
                <span class="font-medium">Cerrar Sesión</span>
            </a>
        </div>
        
        <!-- Sidebar Footer -->
        <div class="absolute bottom-0 left-0 right-0 p-4 bg-gray-50 border-t">
            <p class="text-xs text-center text-gray-500">
                © <?php echo date('Y'); ?> <?php echo APP_NAME; ?>
            </p>
        </div>
    </div>
    
    <!-- Desktop Sidebar (Always visible) -->
    <div class="hidden lg:block fixed left-0 top-[4.5rem] h-[calc(100vh-4.5rem)] w-64 bg-white shadow-lg overflow-y-auto z-30">
        <!-- Sidebar Menu -->
        <div class="py-4">
            <!-- Main Section -->
            <div class="px-4 mb-2">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Menú Principal</p>
            </div>
            
            <a href="dashboard.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                <i class="fas fa-home w-5 mr-3"></i>
                <span class="font-medium">Dashboard</span>
            </a>
            
            <a href="socios.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                <i class="fas fa-users w-5 mr-3"></i>
                <span class="font-medium">Socios</span>
            </a>
            
            <a href="accesos.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                <i class="fas fa-door-open w-5 mr-3"></i>
                <span class="font-medium">Accesos</span>
            </a>
            
            <a href="pagos.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                <i class="fas fa-dollar-sign w-5 mr-3"></i>
                <span class="font-medium">Pagos</span>
            </a>
            
            <?php if (Auth::isAdmin()): ?>
            <!-- Admin Section -->
            <div class="px-4 mt-4 mb-2">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Administración</p>
            </div>
            
            <a href="dispositivos.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                <i class="fas fa-wifi w-5 mr-3"></i>
                <span class="font-medium">Dispositivos</span>
            </a>
            
            <a href="reportes.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                <i class="fas fa-chart-bar w-5 mr-3"></i>
                <span class="font-medium">Reportes</span>
            </a>
            
            <a href="membresias.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                <i class="fas fa-id-card w-5 mr-3"></i>
                <span class="font-medium">Membresías</span>
            </a>
            
            <a href="modulo_financiero.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                <i class="fas fa-chart-line w-5 mr-3"></i>
                <span class="font-medium">Módulo Financiero</span>
            </a>
            
            <a href="registro_ingreso.php" class="flex items-center px-8 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                <i class="fas fa-plus-circle w-4 mr-3 ml-2 text-green-600"></i>
                <span>Registrar Ingreso</span>
            </a>
            
            <a href="registro_egreso.php" class="flex items-center px-8 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                <i class="fas fa-minus-circle w-4 mr-3 ml-2 text-red-600"></i>
                <span>Registrar Egreso</span>
            </a>
            
            <a href="categorias_financieras.php" class="flex items-center px-8 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                <i class="fas fa-tags w-4 mr-3 ml-2"></i>
                <span>Categorías</span>
            </a>
            
            <a href="activos_inventario.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                <i class="fas fa-boxes w-5 mr-3"></i>
                <span class="font-medium">Activos e Inventario</span>
            </a>
            
            <a href="usuarios.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                <i class="fas fa-user-shield w-5 mr-3"></i>
                <span class="font-medium">Usuarios</span>
            </a>
            
            <a href="importar_datos.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                <i class="fas fa-file-import w-5 mr-3"></i>
                <span class="font-medium">Importar Datos</span>
            </a>
            <?php endif; ?>
            
            <?php if (Auth::isSuperadmin()): ?>
            <a href="auditoria.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                <i class="fas fa-clipboard-list w-5 mr-3"></i>
                <span class="font-medium">Auditoría</span>
            </a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Add padding to main content for desktop sidebar and fixed navbar -->
    <style>
        /* Fixed navbar: Add top padding to body content */
        body {
            padding-top: 4.5rem; /* Height of fixed navbar */
        }
        
        /* Desktop sidebar: Add left margin to main content */
        @media (min-width: 1024px) {
            body > div.container,
            body > .container {
                margin-left: 16rem; /* 256px (w-64) */
            }
        }
    </style>
</nav>
