<?php
// Verificar si el usuario está autenticado
if (!isAuthenticated()) {
    redirect('/views/auth/login.php');
}

// Obtener los datos del usuario actual
$userType = $_SESSION['user_type'] ?? '';
$userName = $_SESSION['user_name'] ?? '';
$username = $_SESSION['username'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?><?php echo APP_NAME; ?></title>
    
    <!-- Google Fonts - Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS (versión de producción) -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    
    <!-- Vue.js (versión de producción) -->
    <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
    
    <!-- Heroicons -->
    <script src="https://cdn.jsdelivr.net/npm/@heroicons/vue@2.0.16/outline/esm/index.min.js"></script>
    
    <!-- Chart.js para gráficas -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        
        /* Estilos personalizados */
        .sidebar-link {
            display: flex;
            align-items: center;
            color: #6b7280;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
        }
        
        .sidebar-link:hover {
            background-color: #f0f9ff;
            color: #0284c7;
        }
        
        .sidebar-link.active {
            background-color: #f0f9ff;
            color: #0284c7;
            font-weight: 500;
        }
        
        .sidebar-icon {
            width: 1.25rem;
            height: 1.25rem;
            margin-right: 0.75rem;
        }
        
        /* Colores personalizados de primary */
        .bg-primary-50 { background-color: #f0f9ff; }
        .bg-primary-100 { background-color: #e0f2fe; }
        .bg-primary-500 { background-color: #0ea5e9; }
        .bg-primary-600 { background-color: #0284c7; }
        .bg-primary-700 { background-color: #0369a1; }
        
        .text-primary-600 { color: #0284c7; }
        .text-primary-700 { color: #0369a1; }
        .text-primary-800 { color: #075985; }
        
        .hover\:bg-primary-50:hover { background-color: #f0f9ff; }
        .hover\:bg-primary-700:hover { background-color: #0369a1; }
        
        .hover\:text-primary-600:hover { color: #0284c7; }
        .hover\:text-primary-800:hover { color: #075985; }
        
        .focus\:border-primary-500:focus { border-color: #0ea5e9; }
        .focus\:ring-primary-500:focus { --tw-ring-color: #0ea5e9; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Barra de navegación superior -->
    <header class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <span class="text-primary-600 text-xl font-bold"><?php echo APP_NAME; ?></span>
                    </div>
                </div>
                
                <div class="flex items-center">
                    <div class="hidden md:ml-4 md:flex-shrink-0 md:flex md:items-center">
                        <div class="ml-3 relative" x-data="{ open: false }">
                            <div>
                                <button @click="open = !open" class="flex text-sm rounded-full focus:outline-none" id="user-menu-button">
                                    <span class="sr-only">Abrir menú de usuario</span>
                                    <div class="h-8 w-8 rounded-full bg-primary-600 flex items-center justify-center text-white uppercase">
                                        <?php echo substr($userName, 0, 1); ?>
                                    </div>
                                    <span class="ml-2 text-gray-700"><?php echo $userName; ?></span>
                                </button>
                            </div>
                            
                            <div 
                                x-show="open" 
                                @click.away="open = false" 
                                class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 focus:outline-none" 
                                style="display: none;"
                            >
                                <a href="<?php echo BASE_URL; ?>/views/profile/index.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    Mi Perfil
                                </a>
                                <a href="<?php echo BASE_URL; ?>/views/auth/logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    Cerrar Sesión
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
    
    <div class="flex">
        <!-- Menú lateral -->
        <div class="w-64 bg-white shadow-sm h-screen fixed left-0 top-16 overflow-y-auto">
            <nav class="mt-5 px-2 space-y-1">
                <a href="<?php echo BASE_URL; ?>/views/dashboard/index.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' && dirname($_SERVER['PHP_SELF']) == '/pospurificadora/views/dashboard' ? 'active' : ''; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="sidebar-icon">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                    </svg>
                    Dashboard
                </a>
                
                <a href="<?php echo BASE_URL; ?>/views/ventas/index.php" class="sidebar-link <?php echo strpos($_SERVER['PHP_SELF'], '/views/ventas/') !== false ? 'active' : ''; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="sidebar-icon">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />
                    </svg>
                    Ventas
                </a>
                
                <a href="<?php echo BASE_URL; ?>/views/clientes/index.php" class="sidebar-link <?php echo strpos($_SERVER['PHP_SELF'], '/views/clientes/') !== false ? 'active' : ''; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="sidebar-icon">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                    Clientes
                </a>
                
                <a href="<?php echo BASE_URL; ?>/views/productos/index.php" class="sidebar-link <?php echo strpos($_SERVER['PHP_SELF'], '/views/productos/') !== false ? 'active' : ''; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="sidebar-icon">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                    </svg>
                    Productos
                </a>
                
                <a href="<?php echo BASE_URL; ?>/views/inventario/index.php" class="sidebar-link <?php echo strpos($_SERVER['PHP_SELF'], '/views/inventario/') !== false ? 'active' : ''; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="sidebar-icon">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.429 9.75L2.25 12l4.179 2.25m0-4.5l5.571 3 5.571-3m-11.142 0L2.25 7.5 12 2.25l9.75 5.25-4.179 2.25m0 0L21.75 12l-4.179 2.25m0 0l4.179 2.25L12 21.75 2.25 16.5l4.179-2.25m11.142 0l-5.571 3-5.571-3" />
                    </svg>
                    Inventario
                </a>
                
                <a href="<?php echo BASE_URL; ?>/views/cajas/index.php" class="sidebar-link <?php echo strpos($_SERVER['PHP_SELF'], '/views/cajas/') !== false ? 'active' : ''; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="sidebar-icon">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                    </svg>
                    Cajas
                </a>
                
                <?php if ($userType === 'admin'): ?>
                <a href="<?php echo BASE_URL; ?>/views/empleados/index.php" class="sidebar-link <?php echo strpos($_SERVER['PHP_SELF'], '/views/empleados/') !== false ? 'active' : ''; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="sidebar-icon">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                    </svg>
                    Empleados
                </a>
                
                <a href="<?php echo BASE_URL; ?>/views/categorias/index.php" class="sidebar-link <?php echo strpos($_SERVER['PHP_SELF'], '/views/categorias/') !== false ? 'active' : ''; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="sidebar-icon">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" />
                    </svg>
                    Categorías
                </a>
                
                <a href="<?php echo BASE_URL; ?>/views/configuracion/index.php" class="sidebar-link <?php echo strpos($_SERVER['PHP_SELF'], '/views/configuracion/') !== false ? 'active' : ''; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="sidebar-icon">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Configuración
                </a>
                <?php endif; ?>
            </nav>
        </div>
        
        <!-- Contenido principal -->
        <div class="ml-64 flex-1 p-6">
            <div class="max-w-7xl mx-auto">
                <?php if (isset($pageTitle)): ?>
                <h1 class="text-2xl font-semibold text-gray-800 mb-6"><?php echo $pageTitle; ?></h1>
                <?php endif; ?> 