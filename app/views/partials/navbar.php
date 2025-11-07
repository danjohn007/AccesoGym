<nav class="bg-white shadow-lg relative" x-data="{ sidebarOpen: false }">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center py-4">
            <!-- Logo -->
            <div class="flex items-center">
                <i class="fas fa-dumbbell text-2xl text-blue-600 mr-2"></i>
                <span class="text-xl font-bold text-gray-800"><?php echo APP_NAME; ?></span>
            </div>
            
            <!-- Menu button -->
            <div>
                <button @click="sidebarOpen = true" class="text-gray-700 hover:text-blue-600 focus:outline-none">
                    <i class="fas fa-bars text-2xl"></i>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Sidebar Overlay -->
    <div x-show="sidebarOpen" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="sidebarOpen = false"
         class="fixed inset-0 bg-black bg-opacity-50 z-40"
         style="display: none;">
    </div>
    
    <!-- Sidebar -->
    <div x-show="sidebarOpen"
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="-translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="-translate-x-full"
         class="fixed top-0 left-0 h-full w-80 bg-white shadow-2xl z-50 overflow-y-auto"
         style="display: none;">
        
        <!-- Sidebar Header -->
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
            
            <?php if (Auth::isSuperadmin()): ?>
            <!-- Superadmin Section -->
            <div class="px-4 mt-4 mb-2">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Superadmin</p>
            </div>
            
            <a href="sucursales.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                <i class="fas fa-building w-5 mr-3"></i>
                <span class="font-medium">Sucursales</span>
            </a>
            
            <a href="configuracion.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                <i class="fas fa-cog w-5 mr-3"></i>
                <span class="font-medium">Configuración</span>
            </a>
            <?php endif; ?>
            
            <!-- Account Section -->
            <div class="px-4 mt-4 mb-2">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Cuenta</p>
            </div>
            
            <a href="perfil.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                <i class="fas fa-user w-5 mr-3"></i>
                <span class="font-medium">Mi Perfil</span>
            </a>
            
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
</nav>
