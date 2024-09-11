@extends('layouts.app')

@section('title', 'Reporte de Ventas')

@push('css')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    /* Estilos personalizados */
    .card {
        margin-top: 30px;
        border-radius: 15px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .chart-container {
        width: 100%;
        height: 400px;
        position: relative;
        margin: 0 auto;
    }

    .form-group label {
        font-weight: bold;
        color: #495057;
    }

    .card-header h4 {
        font-size: 1.5rem;
        margin: 0;
    }

    h1 {
        font-size: 2.5rem;
        font-weight: 700;
    }

    p.text-muted {
        font-size: 1rem;
        color: #6c757d !important;
    }

    .text-center {
        margin-bottom: 20px;
    }
</style>
@endpush

@section('content')

<!-- Contenedor para los dos gráficos -->
<div class="container mt-5">
    <div class="row">
        <div class="col-12">
            <!-- Título general -->
            <h1 class="text-center">Reporte de Ventas</h1>
            <p class="text-center text-muted">Visualiza los gráficos de ventas por mes y por producto en el 2024.</p>
        </div>
    </div>

    <div class="row">
        <!-- Gráfico de Ventas Mensuales -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white text-center">
                    <h4>Ventas Mensuales</h4>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráfico de Ventas por Producto -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white text-center">
                    <h4>Ventas por Producto</h4>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="producto_id">Seleccionar Producto:</label>
                        <select class="form-control" id="producto_id">
                            <option value="">Seleccione un producto</option>
                            @foreach($productos as $producto)
                            <option value="{{ $producto->id }}">{{ $producto->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="chart-container">
                        <canvas id="productSalesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('js')
<script>
    // Función para obtener el nombre del mes
    function getMonthName(monthNumber) {
        const months = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        return months[monthNumber - 1];
    }

    // Cargar gráfico de ventas mensuales
    var salesChart;
    fetch('/api/sales-data')
        .then(response => response.json())
        .then(data => {
            var ctx = document.getElementById('salesChart').getContext('2d');
            salesChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: Object.keys(data).map(key => getMonthName(key)),
                    datasets: [{
                        label: 'Ventas Mensuales',
                        data: Object.values(data),
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });

    // Cargar gráfico de ventas por producto
    var productSalesChart;
    document.getElementById('producto_id').addEventListener('change', function () {
        var producto_id = this.value;

        if (producto_id) {
            fetch(`/api/sales-data-by-product?producto_id=${producto_id}`)
                .then(response => response.json())
                .then(data => {
                    var ctx = document.getElementById('productSalesChart').getContext('2d');

                    if (productSalesChart) {
                        productSalesChart.destroy();
                    }

                    productSalesChart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: Object.keys(data).map(key => getMonthName(key)),
                            datasets: [{
                                label: 'Ventas del Producto',
                                data: Object.values(data),
                                backgroundColor: 'rgba(75, 192, 192, 0.6)',
                                borderColor: 'rgba(75, 192, 192, 1)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                });
        }
    });
</script>
@endpush
