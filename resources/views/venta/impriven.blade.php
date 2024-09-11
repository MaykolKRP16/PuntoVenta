<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante de Pago</title>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        .comprobante-header {
            text-align: center;
        }

        .comprobante-header h1 {
            margin: 0;
        }

        .comprobante-header img {
            width: 100px;
            height: auto;
        }

        .comprobante-info,
        .comprobante-tabla {
            width: 100%;
            margin-bottom: 20px;
        }

        .comprobante-info td {
            padding: 5px;
        }

        .comprobante-tabla th,
        .comprobante-tabla td {
            padding: 10px;
            border: 1px solid #000;
        }

        @media (max-width: 575px) {
            #hide-group {
                display: none;
            }
        }

        @media (min-width: 576px) {
            #icon-form {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="container-fluid px-4">
        <!-- Encabezado -->
        <div class="comprobante-header">
            <img src="logo.png" alt="Logo de la empresa">
            <h1>Comprobante de Pago</h1>
            <p>Dirección de la empresa</p>
            <p>Teléfono: (123) 456-7890 | Email: info@empresa.com</p>
        </div>

        <!-- Información del comprobante -->
        <table class="comprobante-info">
            <tr>
                <td><strong>Cliente:</strong></td>
                <td>{{$venta->cliente->persona->razon_social}}</td>
                <td><strong>Fecha:</strong></td>
                <td>{{ \Carbon\Carbon::parse($venta->fecha_hora)->format('d-m-Y') }}</td>
            </tr>
            <tr>
                <td><strong>Número de comprobante:</strong></td>
                <td>{{$venta->numero_comprobante}}</td>
                <td><strong>Vendedor:</strong></td>
                <td>{{$venta->user->name}}</td>
            </tr>
            <tr>
                <td><strong>Impuesto:</strong></td>
                <td>{{$venta->impuesto}}%</td>
                <td><strong>Hora:</strong></td>
                <td>{{ \Carbon\Carbon::parse($venta->fecha_hora)->format('H:i') }}</td>
            </tr>
        </table>

        <!-- Tabla de detalles de productos -->
        <table class="comprobante-tabla" cellspacing="0" cellpadding="0">
            <thead class="bg-primary text-white">
                <tr>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Precio Unitario</th>
                    <th>Descuento</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($venta->productos as $item)
                <tr>
                    <td>{{$item->nombre}}</td>
                    <td>{{$item->pivot->cantidad}}</td>
                    <td>{{$item->pivot->precio_venta}}</td>
                    <td>{{$item->pivot->descuento}}</td>
                    <td class="td-subtotal">{{($item->pivot->cantidad * $item->pivot->precio_venta) - ($item->pivot->descuento)}}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="4">Total antes de impuestos:</th>
                    <th>{{ number_format($subtotal, 2) }}</th>
                </tr>
                <tr>
                    <th colspan="4">Impuesto:</th>
                    <th>{{ number_format($impuesto, 2) }}</th>
                </tr>
                <tr>
                    <th colspan="4">Total a pagar:</th>
                    <th>{{ number_format($total, 2) }}</th>
                </tr>
            </tfoot>
        </table>
    </div>

</body>

</html>