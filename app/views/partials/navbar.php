<nav class="bg-white shadow-lg" x-data="{ mobileMenuOpen: false }">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center py-4">
            <!-- Logo -->
            <div class="flex items-center">
                <i class="fas fa-dumbbell text-2xl text-blue-600 mr-2"></i>
                <span class="text-xl font-bold text-gray-800"><?php echo APP_NAME; ?></span>
            </div>
            
            <!-- Desktop Menu -->
            <div class="hidden md:flex items-center space-x-4">
                <a href="dashboard.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                    <i class="fas fa-home mr-1"></i> Dashboard
                </a>
                
                <a href="socios.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                    <i class="fas fa-users mr-1"></i> Socios
                </a>
                
                <a href="accesos.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                    <i class="fas fa-door-open mr-1"></i> Accesos
                </a>
                
                <a href="pagos.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                    <i class="fas fa-dollar-sign mr-1"></i> Pagos
                </a>
                
                <?php if (Auth::isAdmin()): ?>
                <a href="dispositivos.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                    <i class="fas fa-wifi mr-1"></i> Dispositivos
                </a>
                
                <a href="reportes.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                    <i class="fas fa-chart-bar mr-1"></i> Reportes
                </a>
                <?php endif; ?>
                
                <?php if (Auth::isSuperadmin()): ?>
                <a href="configuracion.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                    <i class="fas fa-cog mr-1"></i> Configuración
                </a>
                <?php endif; ?>
                
                <!-- User Menu -->
                <div class="relative" x-data="{ userMenuOpen: false }">
                    <button @click="userMenuOpen = !userMenuOpen" class="flex items-center text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-user-circle mr-1"></i>
                        <span><?php echo htmlspecialchars($user['nombre'] ?? 'Usuario'); ?></span>
                        <i class="fas fa-chevron-down ml-1 text-xs"></i>
                    </button>
                    
                    <div x-show="userMenuOpen" @click.away="userMenuOpen = false" 
                         class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10"
                         style="display: none;">
                        <div class="px-4 py-2 border-b">
                            <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['nombre'] ?? ''); ?></p>
                            <p class="text-xs text-gray-500"><?php echo htmlspecialchars($user['email'] ?? ''); ?></p>
                            <p class="text-xs text-gray-500 capitalize"><?php echo htmlspecialchars($user['rol'] ?? ''); ?></p>
                        </div>
                        <a href="perfil.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-user mr-2"></i> Mi Perfil
                        </a>
                        <a href="logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                            <i class="fas fa-sign-out-alt mr-2"></i> Cerrar Sesión
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Mobile menu button -->
            <div class="md:hidden">
                <button @click="mobileMenuOpen = !mobileMenuOpen" class="text-gray-700 hover:text-blue-600">
                    <i class="fas fa-bars text-2xl"></i>
                </button>
            </div>
        </div>
        
        <!-- Mobile Menu -->
        <div x-show="mobileMenuOpen" class="md:hidden pb-4" style="display: none;">
            <a href="dashboard.php" class="block text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                <i class="fas fa-home mr-1"></i> Dashboard
            </a>
            <a href="socios.php" class="block text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                <i class="fas fa-users mr-1"></i> Socios
            </a>
            <a href="accesos.php" class="block text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                <i class="fas fa-door-open mr-1"></i> Accesos
            </a>
            <a href="pagos.php" class="block text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                <i class="fas fa-dollar-sign mr-1"></i> Pagos
            </a>
            <?php if (Auth::isAdmin()): ?>
            <a href="dispositivos.php" class="block text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                <i class="fas fa-wifi mr-1"></i> Dispositivos
            </a>
            <a href="reportes.php" class="block text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                <i class="fas fa-chart-bar mr-1"></i> Reportes
            </a>
            <?php endif; ?>
            <?php if (Auth::isSuperadmin()): ?>
            <a href="configuracion.php" class="block text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                <i class="fas fa-cog mr-1"></i> Configuración
            </a>
            <?php endif; ?>
            <a href="perfil.php" class="block text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                <i class="fas fa-user mr-2"></i> Mi Perfil
            </a>
            <a href="logout.php" class="block text-red-600 hover:text-red-700 px-3 py-2 rounded-md text-sm font-medium">
                <i class="fas fa-sign-out-alt mr-2"></i> Cerrar Sesión
            </a>
        </div>
    </div>
</nav>
