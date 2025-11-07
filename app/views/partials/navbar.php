<nav class="bg-white shadow-lg relative" x-data="{ sidebarOpen: false, accountOpen: false }">
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
                     class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50"
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
        
        <!-- Sidebar Menu (Mobile - Only 4 main items) -->
        <div class="py-4">
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
        </div>
        
        <!-- Sidebar Footer -->
        <div class="absolute bottom-0 left-0 right-0 p-4 bg-gray-50 border-t">
            <p class="text-xs text-center text-gray-500">
                © <?php echo date('Y'); ?> <?php echo APP_NAME; ?>
            </p>
        </div>
    </div>
    
    <!-- Desktop Sidebar (Always visible) -->
    <div class="hidden lg:block fixed left-0 top-16 h-[calc(100vh-4rem)] w-64 bg-white shadow-lg overflow-y-auto z-30">
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
            
            <a href="usuarios.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                <i class="fas fa-user-shield w-5 mr-3"></i>
                <span class="font-medium">Usuarios</span>
            </a>
            
            <a href="importar_datos.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                <i class="fas fa-file-import w-5 mr-3"></i>
                <span class="font-medium">Importar Datos</span>
            </a>
            
            <a href="auditoria.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                <i class="fas fa-clipboard-list w-5 mr-3"></i>
                <span class="font-medium">Auditoría</span>
            </a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Add padding to main content for desktop sidebar -->
    <style>
        @media (min-width: 1024px) {
            body > div.container {
                margin-left: 16rem; /* 256px (w-64) */
            }
        }
    </style>
</nav>
