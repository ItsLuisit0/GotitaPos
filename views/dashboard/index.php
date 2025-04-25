<?php
// Título de la página
$pageTitle = 'Dashboard';

// Incluir la configuración
require_once '../../config/config.php';

// Verificar autenticación
if (!isAuthenticated()) {
    redirect('/views/auth/login.php');
}

// Incluir el encabezado
require_once '../components/header.php';

// Obtener datos para el dashboard

// 1. Total de ventas del día
$ventasDiariasQuery = "SELECT COALESCE(SUM(montoTotal), 0) as total FROM venta 
                      WHERE DATE(fecha) = CURDATE() AND estadoVenta = 'Completada'";
$ventasDiarias = fetchOne($ventasDiariasQuery);
$totalVentasDiarias = $ventasDiarias ? $ventasDiarias['total'] : 0;

// 2. Total de ventas de la semana
$ventasSemanalesQuery = "SELECT COALESCE(SUM(montoTotal), 0) as total FROM venta 
                        WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
                        AND estadoVenta = 'Completada'";
$ventasSemanales = fetchOne($ventasSemanalesQuery);
$totalVentasSemanales = $ventasSemanales ? $ventasSemanales['total'] : 0;

// 3. Total de clientes
$totalClientesQuery = "SELECT COUNT(*) as total FROM cliente";
$totalClientes = fetchOne($totalClientesQuery)['total'];

// 4. Productos con bajo stock
$productosBajoStockQuery = "SELECT COUNT(*) as total FROM producto WHERE stockDisponible < 10";
$productosBajoStock = fetchOne($productosBajoStockQuery)['total'];

// 5. Productos más vendidos
$productosVendidosQuery = "SELECT p.nombre, SUM(dv.cantidad) as cantidad 
                          FROM detalleventa dv
                          JOIN producto p ON dv.producto_id = p.id
                          JOIN venta v ON dv.venta_id = v.id
                          WHERE v.estadoVenta = 'Completada'
                          GROUP BY p.id
                          ORDER BY cantidad DESC
                          LIMIT 5";
$productosVendidos = fetchAll($productosVendidosQuery);

// 6. Ventas por día de la semana actual
$ventasPorDiaQuery = "SELECT 
                      DATE(fecha) as fecha,
                      SUM(montoTotal) as total
                      FROM venta
                      WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                      AND estadoVenta = 'Completada'
                      GROUP BY DATE(fecha)
                      ORDER BY fecha";
$ventasPorDia = fetchAll($ventasPorDiaQuery);

// Formatear datos para las gráficas
$etiquetasVentasDiarias = [];
$datosVentasDiarias = [];

foreach ($ventasPorDia as $venta) {
    $fecha = date('d/m', strtotime($venta['fecha']));
    $etiquetasVentasDiarias[] = $fecha;
    $datosVentasDiarias[] = $venta['total'];
}

// Formatear datos para gráfica de productos más vendidos
$etiquetasProductos = [];
$datosProductos = [];

foreach ($productosVendidos as $producto) {
    $etiquetasProductos[] = $producto['nombre'];
    $datosProductos[] = $producto['cantidad'];
}

// JSON para las gráficas
$etiquetasVentasDiariasJSON = json_encode($etiquetasVentasDiarias);
$datosVentasDiariasJSON = json_encode($datosVentasDiarias);
$etiquetasProductosJSON = json_encode($etiquetasProductos);
$datosProductosJSON = json_encode($datosProductos);
?>

<!-- Tarjetas de estadísticas -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Ventas diarias -->
    <div class="bg-white rounded-lg shadow p-6 flex items-center">
        <div class="rounded-full bg-blue-100 p-3 mr-4">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-primary-600">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
            </svg>
        </div>
        <div>
            <p class="text-gray-500 text-sm">Ventas Hoy</p>
            <p class="text-2xl font-bold text-gray-800">$<?php echo number_format($totalVentasDiarias, 2); ?></p>
        </div>
    </div>
    
    <!-- Ventas semanales -->
    <div class="bg-white rounded-lg shadow p-6 flex items-center">
        <div class="rounded-full bg-green-100 p-3 mr-4">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-green-600">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
            </svg>
        </div>
        <div>
            <p class="text-gray-500 text-sm">Ventas Semanales</p>
            <p class="text-2xl font-bold text-gray-800">$<?php echo number_format($totalVentasSemanales, 2); ?></p>
        </div>
    </div>
    
    <!-- Total de clientes -->
    <div class="bg-white rounded-lg shadow p-6 flex items-center">
        <div class="rounded-full bg-purple-100 p-3 mr-4">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-purple-600">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
            </svg>
        </div>
        <div>
            <p class="text-gray-500 text-sm">Clientes Registrados</p>
            <p class="text-2xl font-bold text-gray-800"><?php echo $totalClientes; ?></p>
        </div>
    </div>
    
    <!-- Productos con bajo stock -->
    <div class="bg-white rounded-lg shadow p-6 flex items-center">
        <div class="rounded-full bg-red-100 p-3 mr-4">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-red-600">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0l-3-3m3 3l3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
            </svg>
        </div>
        <div>
            <p class="text-gray-500 text-sm">Productos Bajo Stock</p>
            <p class="text-2xl font-bold text-gray-800"><?php echo $productosBajoStock; ?></p>
        </div>
    </div>
</div>

<!-- Gráficas -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Gráfica de ventas semanales -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-gray-800 text-lg font-semibold mb-4">Ventas de la semana</h2>
        <div>
            <canvas id="ventasChart" width="400" height="200"></canvas>
        </div>
    </div>
    
    <!-- Gráfica de productos más vendidos -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-gray-800 text-lg font-semibold mb-4">Productos más vendidos</h2>
        <div>
            <canvas id="productosChart" width="400" height="200"></canvas>
        </div>
    </div>
</div>

<!-- Productos más vendidos tabla -->
<div class="bg-white rounded-lg shadow overflow-hidden mb-8">
    <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-gray-800 text-lg font-semibold">Productos más vendidos</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Producto
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Cantidad vendida
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($productosVendidos as $producto): ?>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900"><?php echo $producto['nombre']; ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900"><?php echo $producto['cantidad']; ?> unidades</div>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if (empty($productosVendidos)): ?>
                <tr>
                    <td colspan="2" class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                        No hay datos disponibles
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Scripts para las gráficas -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Gráfica de ventas
        const ventasCtx = document.getElementById('ventasChart').getContext('2d');
        const ventasChart = new Chart(ventasCtx, {
            type: 'line',
            data: {
                labels: <?php echo $etiquetasVentasDiariasJSON ?: '[]'; ?>,
                datasets: [{
                    label: 'Ventas ($)',
                    data: <?php echo $datosVentasDiariasJSON ?: '[]'; ?>,
                    backgroundColor: 'rgba(14, 165, 233, 0.1)',
                    borderColor: 'rgba(14, 165, 233, 1)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true,
                    pointBackgroundColor: 'rgba(14, 165, 233, 1)',
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            drawBorder: false
                        },
                        ticks: {
                            callback: function(value) {
                                return '$' + value;
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
        
        // Gráfica de productos
        const productosCtx = document.getElementById('productosChart').getContext('2d');
        const productosChart = new Chart(productosCtx, {
            type: 'bar',
            data: {
                labels: <?php echo $etiquetasProductosJSON ?: '[]'; ?>,
                datasets: [{
                    label: 'Unidades vendidas',
                    data: <?php echo $datosProductosJSON ?: '[]'; ?>,
                    backgroundColor: [
                        'rgba(14, 165, 233, 0.7)',
                        'rgba(79, 70, 229, 0.7)',
                        'rgba(16, 185, 129, 0.7)',
                        'rgba(245, 158, 11, 0.7)',
                        'rgba(239, 68, 68, 0.7)'
                    ],
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            drawBorder: false
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    });
</script>

<?php
// Incluir el pie de página
require_once '../components/footer.php';
?> 