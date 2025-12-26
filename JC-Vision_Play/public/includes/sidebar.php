<div class="flex h-screen overflow-hidden">
    <!-- Sidebar -->
    <aside class="w-64 bg-white border-r border-gray-200 hidden md:flex flex-col">
        <div class="p-6 flex items-center justify-center border-b border-gray-100">
            <img src="/logo-costao.png" alt="Logo" class="h-10">
        </div>
        
        <nav class="flex-1 overflow-y-auto py-4">
            <ul class="space-y-1 px-2">
                <li>
                    <a href="/dashboard.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-md group <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'bg-blue-50 text-blue-600' : ''; ?>">
                        <i class="fas fa-home w-5 h-5 mr-3 <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-500'; ?>"></i>
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="/screens.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-md group <?php echo basename($_SERVER['PHP_SELF']) == 'screens.php' ? 'bg-blue-50 text-blue-600' : ''; ?>">
                        <i class="fas fa-desktop w-5 h-5 mr-3 <?php echo basename($_SERVER['PHP_SELF']) == 'screens.php' ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-500'; ?>"></i>
                        Telas
                    </a>
                </li>
                <li>
                    <a href="/playlists.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-md group <?php echo basename($_SERVER['PHP_SELF']) == 'playlists.php' ? 'bg-blue-50 text-blue-600' : ''; ?>">
                        <i class="fas fa-list w-5 h-5 mr-3 <?php echo basename($_SERVER['PHP_SELF']) == 'playlists.php' ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-500'; ?>"></i>
                        Playlists
                    </a>
                </li>
                <li>
                    <a href="/media.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-md group <?php echo basename($_SERVER['PHP_SELF']) == 'media.php' ? 'bg-blue-50 text-blue-600' : ''; ?>">
                        <i class="fas fa-photo-video w-5 h-5 mr-3 <?php echo basename($_SERVER['PHP_SELF']) == 'media.php' ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-500'; ?>"></i>
                        Mídia
                    </a>
                </li>
                <li>
                    <a href="/logs.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-md group <?php echo basename($_SERVER['PHP_SELF']) == 'logs.php' ? 'bg-blue-50 text-blue-600' : ''; ?>">
                        <i class="fas fa-history w-5 h-5 mr-3 <?php echo basename($_SERVER['PHP_SELF']) == 'logs.php' ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-500'; ?>"></i>
                        Logs
                    </a>
                </li>
            </ul>
        </nav>

        <div class="p-4 border-t border-gray-100">
            <div class="flex items-center">
                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold">
                    <?php echo isset($_SESSION['user_name']) ? strtoupper(substr($_SESSION['user_name'], 0, 1)) : 'U'; ?>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-700"><?php echo isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Usuário'; ?></p>
                    <a href="/logout.php" class="text-xs text-red-500 hover:text-red-700">Sair</a>
                </div>
            </div>
        </div>
    </aside>

    <!-- Mobile Header -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="md:hidden bg-white border-b border-gray-200 p-4 flex items-center justify-between">
            <img src="/logo-costao.png" alt="Logo" class="h-8">
            <button id="mobile-menu-btn" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-bars text-xl"></i>
            </button>
        </header>

        <!-- Main Content -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
