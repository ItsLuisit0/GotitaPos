<?php
// Incluir la configuración
require_once '../../config/config.php';

// Verificar autenticación
if (!isAuthenticated()) {
    redirect('/views/auth/login.php');
}

// Verificar que se ha proporcionado un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect('/views/ventas/index.php');
}

$id = (int) $_GET['id'];

// Obtener los datos de la venta
$sql = "SELECT v.*, c.nombre as cliente_nombre, c.apellido as cliente_apellido, c.telefono as cliente_telefono, 
               c.direccion as cliente_direccion, ca.nombre as caja_nombre, e.nombre as empleado_nombre, 
               e.apellido as empleado_apellido
        FROM venta v
        JOIN cliente c ON v.cliente_id = c.id
        JOIN caja ca ON v.caja_id = ca.id
        LEFT JOIN ticket t ON v.id = t.venta_id
        LEFT JOIN empleado e ON t.empleado_id = e.id
        WHERE v.id = ?";
$venta = fetchOne($sql, [$id], "i");

// Si la venta no existe, redirigir
if (!$venta) {
    redirect('/views/ventas/index.php');
}

// Obtener los detalles de la venta
$sqlDetalles = "SELECT dv.*, p.nombre as producto_nombre, p.descripcion as producto_descripcion
                FROM detalleventa dv
                JOIN producto p ON dv.producto_id = p.id
                WHERE dv.venta_id = ?
                ORDER BY dv.id";
$detalles = fetchAll($sqlDetalles, [$id], "i");

// Obtener configuración del sistema
$configQuery = "SELECT * FROM configuracionsistema LIMIT 1";
$config = fetchOne($configQuery);

// Formato para imprimir
header('Content-Type: text/html');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket #<?php echo $venta['id']; ?> - <?php echo APP_NAME; ?></title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            width: 80mm;
            margin: 0 auto;
            padding: 5mm;
        }
        
        .ticket-header {
            text-align: center;
            margin-bottom: 10px;
        }
        
        .ticket-header h1 {
            font-size: 18px;
            margin: 0;
        }
        
        .ticket-header p {
            margin: 5px 0;
        }
        
        .ticket-info {
            margin-bottom: 10px;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 5px 0;
        }
        
        .ticket-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        
        .ticket-table th {
            text-align: left;
            border-bottom: 1px solid #000;
        }
        
        .ticket-table td, .ticket-table th {
            padding: 3px 0;
        }
        
        .ticket-total {
            text-align: right;
            border-top: 1px dashed #000;
            padding-top: 5px;
        }
        
        .ticket-footer {
            text-align: center;
            margin-top: 10px;
            font-size: 10px;
        }
        
        @media print {
            body {
                width: 80mm;
                padding: 0;
                margin: 0;
            }
            
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="ticket-content">
        <!-- Encabezado del ticket -->
        <div class="ticket-header">
            <h1><?php echo $config ? htmlspecialchars($config['nombreNegocio']) : APP_NAME; ?></h1>
            <p><?php echo $config ? htmlspecialchars($config['direccion'] . ', ' . $config['ciudad'] . ', ' . $config['estado']) : ''; ?></p>
            <p>Tel: 555-123-4567</p>
        </div>
        
        <!-- Información de la venta -->
        <div class="ticket-info">
            <p><strong>Ticket #:</strong> <?php echo $venta['id']; ?></p>
            <p><strong>Fecha:</strong> <?php echo date('d/m/Y H:i', strtotime($venta['fecha'])); ?></p>
            <p><strong>Cliente:</strong> <?php echo htmlspecialchars($venta['cliente_nombre'] . ' ' . $venta['cliente_apellido']); ?></p>
            <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($venta['cliente_telefono']); ?></p>
            <p><strong>Caja:</strong> <?php echo htmlspecialchars($venta['caja_nombre']); ?></p>
            <p><strong>Atendido por:</strong> <?php echo htmlspecialchars($venta['empleado_nombre'] . ' ' . $venta['empleado_apellido']); ?></p>
        </div>
        
        <!-- Detalle de productos -->
        <table class="ticket-table">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Cant.</th>
                    <th>P.U.</th>
                    <th>Subt.</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($detalles as $detalle): ?>
                <tr>
                    <td><?php echo htmlspecialchars($detalle['producto_nombre']); ?></td>
                    <td><?php echo $detalle['cantidad']; ?></td>
                    <td>$<?php echo number_format($detalle['precioUnitario'], 2); ?></td>
                    <td>$<?php echo number_format($detalle['subtotal'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Totales -->
        <div class="ticket-total">
            <p><strong>Subtotal:</strong> $<?php echo number_format($venta['montoTotal'] / 1.16, 2); ?></p>
            <p><strong>IVA (16%):</strong> $<?php echo number_format($venta['montoTotal'] - ($venta['montoTotal'] / 1.16), 2); ?></p>
            <p style="font-size: 14px;"><strong>Total:</strong> $<?php echo number_format($venta['montoTotal'], 2); ?></p>
        </div>
        
        <!-- Pie del ticket -->
        <div class="ticket-footer">
            <p>¡Gracias por su compra!</p>
            <p><?php echo date('Y'); ?> &copy; <?php echo $config ? htmlspecialchars($config['nombreNegocio']) : APP_NAME; ?></p>
        </div>
    </div>
    
    <!-- Botones (no se imprimen) -->
    <div class="no-print" style="text-align: center; margin-top: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; background-color: #0284c7; color: white; border: none; border-radius: 5px; cursor: pointer;">
            Imprimir Ticket
        </button>
        <button onclick="window.location.href='<?php echo BASE_URL; ?>/views/ventas/view.php?id=<?php echo $venta['id']; ?>'" style="padding: 10px 20px; background-color: #6b7280; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">
            Volver
        </button>
    </div>
    
    <script>
        // Imprimir automáticamente al cargar
        window.onload = function() {
            // Esperar a que se carguen las fuentes
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html> 