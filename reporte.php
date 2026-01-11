<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Conexión
include 'api/conexion.php';

// Consultas
$res_hoy = mysqli_query($conexion, "SELECT SUM(total_final) as total FROM ventas WHERE DATE(fecha_emision) = CURDATE()");
$total_hoy = ($res_hoy) ? mysqli_fetch_assoc($res_hoy)['total'] : 0;

$ventas = mysqli_query($conexion, "SELECT v.*, c.nombre_completo, c.dni_ruc, c.direccion FROM ventas v JOIN clientes c ON v.id_cliente = c.id ORDER BY v.fecha_emision DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reportes - Ferretería El Rayo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary: #2c3e50; --accent: #e67e22; --bg: #f4f7f6; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg); padding: 20px; }
        .container { max-width: 1000px; margin: auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        h2 { color: var(--primary); border-bottom: 2px solid var(--accent); padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: var(--primary); color: white; padding: 12px; text-align: left; }
        td { padding: 12px; border-bottom: 1px solid #eee; }
        tr:hover { background: #f9f9f9; }
        .btn-print { background: var(--accent); color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="container">
        <h2><i class="fas fa-file-invoice-dollar"></i> Historial de Boletas Emitidas</h2>
        <p>Ventas de hoy: <b>S/ <?php echo number_format($total_hoy ?? 0, 2); ?></b></p>
        
        <table>
            <thead>
                <tr>
                    <th>Número</th>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>Total Pago</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php while($v = mysqli_fetch_assoc($ventas)): ?>
                <tr>
                    <td><?php echo $v['numero_boleta']; ?></td>
                    <td><?php echo $v['fecha_emision']; ?></td>
                    <td><?php echo $v['nombre_completo']; ?></td>
                    <td><b>S/ <?php echo number_format($v['total_final'], 2); ?></b></td>
                    <td>
                        <button class="btn-print" onclick='reimprimirBoleta(<?php echo json_encode($v); ?>)'>
                            <i class="fas fa-print"></i> Ver/Imprimir
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <script>
    async function reimprimirBoleta(venta) {
        try {
            // 1. Buscamos los productos de esta venta en la DB usando el ID de la venta
            const resp = await fetch(`api/obtener_detalle.php?id_venta=${venta.id}`);
            const result = await resp.json();

            if (result.success) {
                // 2. Preparamos los datos con los productos encontrados
                const dataParaBoleta = {
                    numeroBoleta: venta.numero_boleta,
                    fecha: venta.fecha_emision,
                    cliente: {
                        nombre: venta.nombre_completo,
                        dni: venta.dni_ruc,
                        direccion: venta.direccion
                    },
                    totalFinal: parseFloat(venta.total_final),
                    // AQUÍ ESTÁ EL CAMBIO: Ya no es un arreglo vacío
                    carrito: result.productos 
                };

                // 3. Guardamos y abrimos la boleta
                localStorage.setItem('ultimaVenta', JSON.stringify(dataParaBoleta));
                window.open('imprimir_boleta.html', 'Reimpresion', 'width=450,height=700');
            } else {
                alert("No se pudieron obtener los productos: " + result.message);
            }
        } catch (error) {
            console.error(error);
            alert("Error de conexión al recuperar productos.");
        }
    }
    
    </script>
</body>
</html>