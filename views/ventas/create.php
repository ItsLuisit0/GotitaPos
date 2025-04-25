<?php
// Título de la página
$pageTitle = 'Nueva Venta';

// Incluir la configuración
require_once '../../config/config.php';

// Verificar autenticación
if (!isAuthenticated()) {
    redirect('/views/auth/login.php');
}

// Obtener datos necesarios para el formulario
// Cajas disponibles (abiertas)
$cajasQuery = "SELECT id, nombre FROM caja WHERE estaAbierta = 1 ORDER BY nombre";
$cajas = fetchAll($cajasQuery);

// Clientes
$clientesQuery = "SELECT id, nombre, apellido FROM cliente ORDER BY nombre, apellido";
$clientes = fetchAll($clientesQuery);

// Productos con stock disponible
$productosQuery = "SELECT p.id, p.nombre, p.descripcion, p.precioUnitario, i.cantidad as stockDisponible, c.nombre as categoria
                  FROM producto p
                  JOIN inventario i ON p.id = i.producto_id
                  JOIN categoria c ON p.categoria_id = c.id
                  WHERE i.cantidad > 0
                  ORDER BY c.nombre, p.nombre";
$productos = fetchAll($productosQuery);

// Agrupar productos por categoría para la visualización
$productosPorCategoria = [];
foreach ($productos as $producto) {
    $categoria = $producto['categoria'];
    if (!isset($productosPorCategoria[$categoria])) {
        $productosPorCategoria[$categoria] = [];
    }
    $productosPorCategoria[$categoria][] = $producto;
}

// Variables para mensajes
$error = '';
$success = false;
$ventaId = 0;

// Incluir el encabezado
require_once '../components/header.php';
?>

<!-- Cabecera de la página -->
<div class="flex justify-between items-center mb-6">
    <h2 class="text-xl font-semibold text-gray-800">Registrar Nueva Venta</h2>
    <a href="<?php echo BASE_URL; ?>/views/ventas/index.php" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg inline-flex items-center">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
        </svg>
        Volver
    </a>
</div>

<!-- Mensajes -->
<?php if ($error): ?>
<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
    <div class="flex">
        <div class="flex-shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
            </svg>
        </div>
        <div class="ml-3">
            <p class="text-sm"><?php echo $error; ?></p>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
    <div class="flex">
        <div class="flex-shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <div class="ml-3">
            <p class="text-sm">Venta registrada correctamente. 
                <a href="<?php echo BASE_URL; ?>/views/ventas/view.php?id=<?php echo $ventaId; ?>" class="font-medium underline">Ver detalles</a>
            </p>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Formulario de venta -->
<div id="app" class="bg-white rounded-lg shadow-md p-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <!-- Cliente y Caja -->
        <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Cliente -->
            <div>
                <label for="cliente_id" class="block text-sm font-medium text-gray-700 mb-1">Cliente <span class="text-red-500">*</span></label>
                <select 
                    v-model="cliente_id"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    required
                >
                    <option value="">Seleccione un cliente</option>
                    <?php foreach ($clientes as $cliente): ?>
                    <option value="<?php echo $cliente['id']; ?>">
                        <?php echo htmlspecialchars($cliente['nombre'] . ' ' . $cliente['apellido']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <p v-if="errors.cliente_id" class="mt-1 text-sm text-red-500">{{ errors.cliente_id }}</p>
            </div>
            
            <!-- Caja -->
            <div>
                <label for="caja_id" class="block text-sm font-medium text-gray-700 mb-1">Caja <span class="text-red-500">*</span></label>
                <select 
                    v-model="caja_id"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    required
                >
                    <option value="">Seleccione una caja</option>
                    <?php foreach ($cajas as $caja): ?>
                    <option value="<?php echo $caja['id']; ?>">
                        <?php echo htmlspecialchars($caja['nombre']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <p v-if="errors.caja_id" class="mt-1 text-sm text-red-500">{{ errors.caja_id }}</p>
            </div>
        </div>
        
        <!-- Totales -->
        <div class="bg-gray-50 p-4 rounded-lg">
            <h3 class="font-medium text-gray-800 mb-4">Resumen</h3>
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span class="text-gray-600">Subtotal:</span>
                    <span class="font-medium">${{ subtotal.toFixed(2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">IVA (16%):</span>
                    <span class="font-medium">${{ iva.toFixed(2) }}</span>
                </div>
                <div class="flex justify-between text-lg text-primary-600 font-bold">
                    <span>Total:</span>
                    <span>${{ total.toFixed(2) }}</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Productos -->
    <div class="mb-6">
        <h3 class="font-medium text-gray-800 mb-4">Seleccionar Productos</h3>
        
        <!-- Lista de productos por categoría -->
        <div class="bg-gray-50 p-4 rounded-lg">
            <div class="mb-4">
                <label for="buscarProducto" class="block text-sm font-medium text-gray-700 mb-1">Buscar producto</label>
                <input 
                    type="text" 
                    v-model="busqueda" 
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    placeholder="Nombre o descripción..."
                >
            </div>
            
            <div class="space-y-4">
                <?php foreach ($productosPorCategoria as $categoria => $productosCategoria): ?>
                <div class="bg-white p-4 rounded-lg shadow-sm">
                    <h4 class="font-medium text-gray-700 mb-2"><?php echo htmlspecialchars($categoria); ?></h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php foreach ($productosCategoria as $producto): ?>
                        <div 
                            v-if="mostrarProducto(<?php echo $producto['id']; ?>, '<?php echo addslashes($producto['nombre']); ?>', '<?php echo addslashes($producto['descripcion']); ?>')"
                            class="border border-gray-200 rounded-lg p-3 hover:bg-gray-50 transition-colors cursor-pointer"
                            @click="agregarProducto({
                                id: <?php echo $producto['id']; ?>,
                                nombre: '<?php echo addslashes($producto['nombre']); ?>',
                                descripcion: '<?php echo addslashes($producto['descripcion']); ?>',
                                precioUnitario: <?php echo $producto['precioUnitario']; ?>,
                                stockDisponible: <?php echo $producto['stockDisponible']; ?>
                            })"
                        >
                            <div class="font-medium text-gray-800"><?php echo htmlspecialchars($producto['nombre']); ?></div>
                            <div class="text-sm text-gray-500 mb-2"><?php echo htmlspecialchars($producto['descripcion']); ?></div>
                            <div class="flex justify-between items-center">
                                <span class="font-medium text-primary-600">${<?php echo number_format($producto['precioUnitario'], 2); ?>}</span>
                                <span class="text-sm text-gray-500">Stock: <?php echo $producto['stockDisponible']; ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- Productos seleccionados -->
    <div class="mb-6">
        <h3 class="font-medium text-gray-800 mb-4">Productos Seleccionados</h3>
        
        <div v-if="productos.length === 0" class="bg-gray-50 p-4 rounded-lg text-center text-gray-500">
            No hay productos seleccionados
        </div>
        
        <div v-else class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Producto
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Precio Unitario
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Cantidad
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Subtotal
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="(producto, index) in productos" :key="producto.id" class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-900">{{ producto.nombre }}</div>
                            <div class="text-sm text-gray-500">{{ producto.descripcion }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">${{ producto.precioUnitario.toFixed(2) }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center space-x-2">
                                <button 
                                    @click="decrementarCantidad(index)" 
                                    class="bg-gray-200 hover:bg-gray-300 text-gray-700 w-8 h-8 rounded-full flex items-center justify-center"
                                    :disabled="producto.cantidad <= 1"
                                    :class="{'opacity-50 cursor-not-allowed': producto.cantidad <= 1}"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15" />
                                    </svg>
                                </button>
                                <input 
                                    type="number" 
                                    v-model="producto.cantidad" 
                                    min="1" 
                                    :max="producto.stockDisponible"
                                    class="w-16 text-center rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                    @change="actualizarProducto(index)"
                                >
                                <button 
                                    @click="incrementarCantidad(index)" 
                                    class="bg-gray-200 hover:bg-gray-300 text-gray-700 w-8 h-8 rounded-full flex items-center justify-center"
                                    :disabled="producto.cantidad >= producto.stockDisponible"
                                    :class="{'opacity-50 cursor-not-allowed': producto.cantidad >= producto.stockDisponible}"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">${{ (producto.cantidad * producto.precioUnitario).toFixed(2) }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <button @click="eliminarProducto(index)" class="text-red-600 hover:text-red-900">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                </svg>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Botones de acción -->
    <div class="flex justify-end space-x-3">
        <button 
            @click="limpiarFormulario" 
            class="bg-gray-200 hover:bg-gray-300 text-gray-700 py-2 px-4 rounded-lg inline-flex items-center"
        >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
            Limpiar
        </button>
        <button 
            @click="guardarVenta" 
            class="bg-primary-600 hover:bg-primary-700 text-white py-2 px-4 rounded-lg inline-flex items-center"
            :disabled="!validarFormulario || cargando"
            :class="{'opacity-50 cursor-not-allowed': !validarFormulario || cargando}"
        >
            <svg v-if="!cargando" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <svg v-else class="animate-spin h-5 w-5 mr-2 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ cargando ? 'Guardando...' : 'Guardar Venta' }}
        </button>
    </div>
</div>

<!-- Script para la aplicación Vue -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const app = Vue.createApp({
        data() {
            return {
                cliente_id: '',
                caja_id: '',
                productos: [],
                busqueda: '',
                errors: {},
                cargando: false
            };
        },
        computed: {
            subtotal() {
                let subtotal = 0;
                this.productos.forEach(producto => {
                    subtotal += producto.precioUnitario * producto.cantidad;
                });
                return subtotal;
            },
            iva() {
                return this.subtotal * 0.16;
            },
            total() {
                return this.subtotal + this.iva;
            },
            validarFormulario() {
                return this.cliente_id && this.caja_id && this.productos.length > 0;
            }
        },
        methods: {
            mostrarProducto(id, nombre, descripcion) {
                if (!this.busqueda) return true;
                const busqueda = this.busqueda.toLowerCase();
                return nombre.toLowerCase().includes(busqueda) || 
                       descripcion.toLowerCase().includes(busqueda);
            },
            agregarProducto(producto) {
                // Verificar si el producto ya está en la lista
                const index = this.productos.findIndex(p => p.id === producto.id);
                
                if (index !== -1) {
                    // Si ya existe, incrementar la cantidad si no excede el stock
                    if (this.productos[index].cantidad < producto.stockDisponible) {
                        this.productos[index].cantidad++;
                    }
                } else {
                    // Si no existe, agregarlo con cantidad 1
                    this.productos.push({
                        ...producto,
                        cantidad: 1
                    });
                }
            },
            actualizarProducto(index) {
                // Asegurar que la cantidad esté dentro de los límites permitidos
                let producto = this.productos[index];
                if (producto.cantidad < 1) {
                    producto.cantidad = 1;
                } else if (producto.cantidad > producto.stockDisponible) {
                    producto.cantidad = producto.stockDisponible;
                }
            },
            eliminarProducto(index) {
                this.productos.splice(index, 1);
            },
            incrementarCantidad(index) {
                let producto = this.productos[index];
                if (producto.cantidad < producto.stockDisponible) {
                    producto.cantidad++;
                }
            },
            decrementarCantidad(index) {
                let producto = this.productos[index];
                if (producto.cantidad > 1) {
                    producto.cantidad--;
                }
            },
            limpiarFormulario() {
                this.cliente_id = '';
                this.caja_id = '';
                this.productos = [];
                this.errors = {};
            },
            validar() {
                this.errors = {};
                let valid = true;
                
                if (!this.cliente_id) {
                    this.errors.cliente_id = 'Debe seleccionar un cliente';
                    valid = false;
                }
                
                if (!this.caja_id) {
                    this.errors.caja_id = 'Debe seleccionar una caja';
                    valid = false;
                }
                
                if (this.productos.length === 0) {
                    alert('Debe agregar al menos un producto');
                    valid = false;
                }
                
                return valid;
            },
            guardarVenta() {
                if (!this.validar()) return;
                
                this.cargando = true;
                
                // Preparar los datos para enviar
                const ventaData = {
                    cliente_id: this.cliente_id,
                    caja_id: this.caja_id,
                    productos: this.productos.map(p => ({
                        id: p.id,
                        cantidad: p.cantidad,
                        precioUnitario: p.precioUnitario
                    }))
                };
                
                // Enviar la solicitud al servidor
                fetch('<?php echo BASE_URL; ?>/api/ventas/create.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(ventaData)
                })
                .then(response => response.json())
                .then(data => {
                    this.cargando = false;
                    
                    if (data.success) {
                        // Redirigir a la vista de la venta
                        window.location.href = `<?php echo BASE_URL; ?>/views/ventas/view.php?id=${data.venta_id}`;
                    } else {
                        // Mostrar error
                        alert(data.message || 'Error al guardar la venta');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    this.cargando = false;
                    alert('Error al procesar la solicitud');
                });
            }
        }
    });
    
    app.mount('#app');
});
</script>

<?php
// Incluir el pie de página
require_once '../components/footer.php';
?> 