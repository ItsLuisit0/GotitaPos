<?php
// Incluir configuración
require_once '../../config/config.php';

// Si ya está autenticado, redirigir al dashboard
if (isAuthenticated()) {
    redirect('/views/dashboard/index.php');
}

// Procesar el formulario de login
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    
    if (empty($username)) {
        $error = 'Por favor, ingresa tu nombre de usuario';
    } else {
        // Consultar si el usuario existe (puede ser administrador o empleado)
        $adminQuery = "SELECT id, nombre, username FROM administrador WHERE username = ?";
        $admin = fetchOne($adminQuery, [$username], "s");
        
        $employeeQuery = "SELECT id, nombre, username FROM empleado WHERE username = ? AND activo = 1";
        $employee = fetchOne($employeeQuery, [$username], "s");
        
        if ($admin) {
            // Iniciar sesión como administrador
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['user_name'] = $admin['nombre'];
            $_SESSION['username'] = $admin['username'];
            $_SESSION['user_type'] = 'admin';
            
            redirect('/views/dashboard/index.php');
        } elseif ($employee) {
            // Iniciar sesión como empleado
            $_SESSION['user_id'] = $employee['id'];
            $_SESSION['user_name'] = $employee['nombre'];
            $_SESSION['username'] = $employee['username'];
            $_SESSION['user_type'] = 'employee';
            
            redirect('/views/dashboard/index.php');
        } else {
            $error = 'Usuario no encontrado';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - <?php echo APP_NAME; ?></title>
    <!-- Google Fonts - Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        poppins: ['Poppins', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            300: '#7dd3fc',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md">
        <div class="bg-white shadow-lg rounded-lg px-8 pt-6 pb-8 mb-4">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-primary-600"><?php echo APP_NAME; ?></h1>
                <p class="text-gray-600 mt-1">Sistema de Punto de Venta</p>
            </div>
            
            <form method="POST" action="">
                <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><?php echo $error; ?></span>
                </div>
                <?php endif; ?>
                
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="username">
                        Usuario
                    </label>
                    <input 
                        class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-primary-400" 
                        id="username" 
                        name="username" 
                        type="text" 
                        placeholder="Ingresa tu nombre de usuario"
                        value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                    >
                </div>
                
                <div class="flex items-center justify-between">
                    <button 
                        class="bg-primary-600 hover:bg-primary-700 text-white font-bold py-3 px-6 rounded focus:outline-none focus:shadow-outline w-full" 
                        type="submit"
                    >
                        Iniciar Sesión
                    </button>
                </div>
            </form>
            
            <div class="mt-6 text-center text-sm">
                <span class="text-gray-600">
                    © <?php echo date('Y'); ?> <?php echo APP_NAME; ?>
                </span>
            </div>
        </div>
    </div>
</body>
</html> 