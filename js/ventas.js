// --- CONFIGURACIÓN DE VARIABLES ---
let carrito = [];
const tablaCarrito = document.getElementById('carrito-body');
const displayTotal = document.getElementById('total-monto');
const resBusqueda = document.getElementById('res-busqueda');

// Cargar nombre del vendedor desde la sesión
document.getElementById('user-name').innerText = sessionStorage.getItem('usuario_nombre') || 'Vendedor';

/**
 * 1. BUSCAR PRODUCTOS (AJAX)
 */
async function buscarProducto(query) {
    if (query.length < 2) {
        resBusqueda.style.display = 'none';
        return;
    }

    try {
        const response = await fetch(`api/buscar_productos.php?q=${query}`);
        const productos = await response.json();
        
        if (productos.length > 0) {
            resBusqueda.innerHTML = productos.map(p => `
                <div class="res-item" onclick='agregarAlCarrito(${JSON.stringify(p)})'>
                    <span>${p.nombre}</span>
                    <strong>S/ ${p.precio_base} | Stock: ${p.stock}</strong>
                </div>
            `).join('');
            resBusqueda.style.display = 'block';
        } else {
            resBusqueda.style.display = 'none';
        }
    } catch (error) {
        console.error("Error buscando productos:", error);
    }
}

/**
 * 2. AGREGAR AL CARRITO
 */
function agregarAlCarrito(producto) {
    const existe = carrito.find(item => item.id === producto.id);

    if (existe) {
        if (existe.cantidad < producto.stock) {
            existe.cantidad++;
        } else {
            alert("Stock máximo alcanzado");
        }
    } else {
        carrito.push({
            id: producto.id,
            nombre: producto.nombre,
            precio_base: parseFloat(producto.precio_base),
            stock: producto.stock,
            cantidad: 1,
            porcentaje: 0
        });
    }

    document.getElementById('input-busqueda').value = '';
    resBusqueda.style.display = 'none';
    renderCarrito();
}

/**
 * 3. RENDERIZAR TABLA
 */
function renderCarrito() {
    tablaCarrito.innerHTML = '';
    let totalGeneral = 0;

    carrito.forEach((item, index) => {
        const precioUnitarioFinal = item.precio_base * (1 + (item.porcentaje / 100));
        const subtotal = precioUnitarioFinal * item.cantidad;
        totalGeneral += subtotal;

        tablaCarrito.innerHTML += `
            <tr>
                <td>${item.nombre}</td>
                <td>
                    <input type="number" value="${item.cantidad}" min="1" max="${item.stock}" 
                    style="width:60px" onchange="updateItem(${index}, 'cantidad', this.value)">
                </td>
                <td>S/ ${item.precio_base.toFixed(2)}</td>
                <td>
                    <input type="number" value="${item.porcentaje}" min="0" 
                    style="width:60px" onchange="updateItem(${index}, 'porcentaje', this.value)"> %
                </td>
                <td>S/ ${subtotal.toFixed(2)}</td>
                <td>
                    <button onclick="removeItem(${index})" style="color:red; cursor:pointer; border:none; background:none;">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    displayTotal.innerText = totalGeneral.toFixed(2);
}

function updateItem(index, campo, valor) {
    carrito[index][campo] = parseFloat(valor);
    renderCarrito();
}

function removeItem(index) {
    carrito.splice(index, 1);
    renderCarrito();
}

/**
 * 4. FINALIZAR VENTA Y ABRIR BOLETA
 */
async function finalizarVenta() {
    const cliNombre = document.getElementById('cli-nombre').value;
    const cliDni = document.getElementById('cli-dni').value;

    if (carrito.length === 0) return alert("El carrito está vacío");
    if (!cliNombre || !cliDni) return alert("Complete los datos del cliente");

    const ventaData = {
        cliente: {
            nombre: cliNombre,
            dni: cliDni,
            direccion: document.getElementById('cli-dir').value || 'Tacna'
        },
        carrito: carrito, // Aquí van los productos seleccionados
        totalFinal: parseFloat(displayTotal.innerText),
        fecha: new Date().toLocaleString()
    };

    try {
        const resp = await fetch('api/guardar_venta.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(ventaData)
        });

        const result = await resp.json();

        if (result.success) {
            ventaData.numeroBoleta = result.boleta; // El número que viene de la DB

            // 1. Guardamos todo en la memoria del navegador
            localStorage.setItem('ultimaVenta', JSON.stringify(ventaData));

            // 2. Abrimos la ventana emergente
            const ancho = 450;
            const alto = 700;
            const x = (window.screen.width / 2) - (ancho / 2);
            const y = (window.screen.height / 2) - (alto / 2);
            
            window.open('imprimir_boleta.html', 'Boleta', 
                `width=${ancho},height=${alto},left=${x},top=${y},scrollbars=yes`);

            // 3. Limpiamos el carrito actual
            carrito = [];
            renderCarrito();
            alert("Venta procesada con éxito");
        }
    } catch (e) {
        console.error(e);
        alert("Error al procesar la venta");
    }
}

async function generarVenta() {
    // 1. Validaciones previas
    const clienteNombre = document.getElementById('cli-nombre').value.trim();
    const clienteDni = document.getElementById('cli-dni').value.trim();
    const clienteDir = document.getElementById('cli-dir').value.trim() || 'Tacna';
    const metodoPago = document.getElementById('metodo-pago').value; // efectivo o transferencia

    if (carrito.length === 0) {
        alert("El carrito está vacío. Agregue productos primero.");
        return;
    }

    if (!clienteNombre || !clienteDni) {
        alert("Por favor, ingrese los datos del cliente.");
        return;
    }

    // 2. Preparar los datos para enviar a la base de datos
    const ventaData = {
        cliente: {
            nombre: clienteNombre,
            dni: clienteDni,
            direccion: clienteDir
        },
        metodo_pago: metodoPago,
        carrito: carrito, // Tus productos seleccionados
        totalFinal: parseFloat(document.getElementById('total-pagar').innerText.replace('S/ ', ''))
    };

    try {
        // 3. ENVIAR A LA BASE DE DATOS (api/guardar_venta.php)
        const response = await fetch('api/guardar_venta.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(ventaData)
        });

        const result = await response.json();

        if (result.success) {
            // 4. PREPARAR DATOS PARA LA BOLETA
            // Agregamos el número de boleta que nos devolvió la base de datos
            ventaData.numeroBoleta = result.numero_boleta;
            ventaData.fecha = new Date().toLocaleString();

            // 5. GUARDAR EN LOCALSTORAGE PARA QUE LA BOLETA LOS LEA
            localStorage.setItem('ultimaVenta', JSON.stringify(ventaData));

            // 6. ABRIR LA VENTANA EMERGENTE AL INSTANTE
            const ancho = 450;
            const alto = 700;
            const x = (window.screen.width / 2) - (ancho / 2);
            const y = (window.screen.height / 2) - (alto / 2);

            window.open('imprimir_boleta.html', 'BoletaRayo', 
                `width=${ancho},height=${alto},left=${x},top=${y},scrollbars=yes`);

            // 7. LIMPIAR TODO PARA LA SIGUIENTE VENTA
            alert("¡Venta realizada con éxito!");
            limpiarFormularioVenta(); // Una función tuya que vacíe el carrito y campos
            
        } else {
            alert("Error al guardar la venta: " + result.message);
        }

    } catch (error) {
        console.error("Error crítico:", error);
        alert("No se pudo conectar con el servidor para guardar la venta.");
    }
}