@extends('layouts.appAdmin')

@section('title', 'Estadísticas del Apartamento')

@section('content')
<div class="container-fluid">
    <!-- Header de la sección -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="font-titulo mb-2">
                <i class="fas fa-chart-line me-2"></i>Estadísticas del Apartamento
            </h1>
            <p class="text-secondary mb-0">Análisis detallado del rendimiento y métricas de {{ $apartamento->titulo ?? $apartamento->nombre }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('apartamentos.admin.show', $apartamento->id) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Volver al Apartamento
            </a>
            <a href="{{ route('apartamentos.admin.edit', $apartamento->id) }}" class="btn btn-primary">
                <i class="fas fa-edit me-2"></i>Editar
            </a>
        </div>
    </div>

    <!-- Métricas Principales -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm text-center">
                <div class="card-body">
                    <div class="metric-icon bg-primary mb-3">
                        <i class="fas fa-calendar-check text-white fa-2x"></i>
                    </div>
                    <h3 class="text-primary mb-1">{{ number_format($totalReservas) }}</h3>
                    <p class="text-muted mb-0">Total Reservas</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm text-center">
                <div class="card-body">
                    <div class="metric-icon bg-success mb-3">
                        <i class="fas fa-euro-sign text-white fa-2x"></i>
                    </div>
                    <h3 class="text-success mb-1">{{ number_format($totalIngresos, 2) }}€</h3>
                    <p class="text-muted mb-0">Total Ingresos</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm text-center">
                <div class="card-body">
                    <div class="metric-icon bg-info mb-3">
                        <i class="fas fa-chart-bar text-white fa-2x"></i>
                    </div>
                    <h3 class="text-info mb-1">{{ number_format($precioPromedio, 2) }}€</h3>
                    <p class="text-muted mb-0">Precio Promedio</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm text-center">
                <div class="card-body">
                    <div class="metric-icon bg-warning mb-3">
                        <i class="fas fa-images text-white fa-2x"></i>
                    </div>
                    <h3 class="text-warning mb-1">{{ $totalFotos }}</h3>
                    <p class="text-muted mb-0">Total Fotos</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Gráfico de Reservas Mensuales -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-area me-2"></i>Reservas por Mes (Últimos 12 Meses)
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="reservasMensualesChart" height="100"></canvas>
                </div>
            </div>
        </div>

        <!-- Estados de Reservas -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-pie-chart me-2"></i>Estados de Reservas
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="estadosReservasChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Ocupación por Día de la Semana -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-week me-2"></i>Ocupación por Día de la Semana
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="ocupacionSemanalChart" height="200"></canvas>
                </div>
            </div>
        </div>

        <!-- Estadísticas por Temporada -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-sun me-2"></i>Estadísticas por Temporada
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="text-center">
                                <h4 class="text-success">{{ $estadisticasTemporada['alta']['reservas'] }}</h4>
                                <p class="text-muted mb-1">Temporada Alta</p>
                                <small class="text-success">{{ number_format($estadisticasTemporada['alta']['ingresos'], 2) }}€</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <h4 class="text-info">{{ $estadisticasTemporada['baja']['reservas'] }}</h4>
                                <p class="text-muted mb-1">Temporada Baja</p>
                                <small class="text-info">{{ number_format($estadisticasTemporada['baja']['ingresos'], 2) }}€</small>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-6">
                            <p class="mb-1"><strong>Promedio Alta:</strong></p>
                            <h5 class="text-success">{{ number_format($estadisticasTemporada['alta']['promedio'], 2) }}€</h5>
                        </div>
                        <div class="col-6">
                            <p class="mb-1"><strong>Promedio Baja:</strong></p>
                            <h5 class="text-info">{{ number_format($estadisticasTemporada['baja']['promedio'], 2) }}€</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Clientes -->
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-trophy me-2"></i>Top 10 Clientes
                    </h5>
                </div>
                <div class="card-body">
                    @if(count($topClientes) > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th><i class="fas fa-user me-2"></i>Cliente</th>
                                        <th><i class="fas fa-calendar-check me-2"></i>Total Reservas</th>
                                        <th><i class="fas fa-euro-sign me-2"></i>Total Gastado</th>
                                        <th><i class="fas fa-clock me-2"></i>Última Reserva</th>
                                        <th><i class="fas fa-chart-line me-2"></i>Valor Promedio</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($topClientes as $cliente)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-circle me-3">
                                                        <span class="avatar-text">{{ substr($cliente['nombre'], 0, 1) }}</span>
                                                    </div>
                                                    <div>
                                                        <strong>{{ $cliente['nombre'] }}</strong>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary">{{ $cliente['total_reservas'] }}</span>
                                            </td>
                                            <td>
                                                <strong class="text-success">{{ number_format($cliente['total_gastado'], 2) }}€</strong>
                                            </td>
                                            <td>
                                                @if($cliente['ultima_reserva'])
                                                    {{ \Carbon\Carbon::parse($cliente['ultima_reserva'])->format('d/m/Y') }}
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td>
                                                {{ number_format($cliente['total_gastado'] / $cliente['total_reservas'], 2) }}€
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0">No hay datos de clientes disponibles.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Resumen de Tarifas -->
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-tags me-2"></i>Resumen de Tarifas
                    </h5>
                </div>
                <div class="card-body">
                    @if($apartamento->tarifas && count($apartamento->tarifas) > 0)
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="text-center">
                                    <h4 class="text-primary">{{ count($apartamento->tarifas) }}</h4>
                                    <p class="text-muted mb-1">Total Tarifas</p>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="text-center">
                                    <h4 class="text-success">{{ $apartamento->tarifas->where('activo', true)->count() }}</h4>
                                    <p class="text-muted mb-1">Tarifas Activas</p>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="text-center">
                                    <h4 class="text-info">{{ number_format($apartamento->tarifas->avg('precio'), 2) }}€</h4>
                                    <p class="text-muted mb-1">Precio Promedio</p>
                                </div>
                            </div>
                        </div>
                    @else
                        <p class="text-muted mb-0">No hay tarifas configuradas para este apartamento.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos específicos para la página de estadísticas */
.metric-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}

.avatar-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #007AFF 0%, #0056CC 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 16px;
}

.avatar-text {
    line-height: 1;
}

.card {
    border: none;
    border-radius: 12px;
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    border-radius: 12px 12px 0 0 !important;
}

/* Responsive */
@media (max-width: 768px) {
    .metric-icon {
        width: 60px;
        height: 60px;
    }
    
    .metric-icon i {
        font-size: 1.5rem !important;
    }
    
    .d-flex.justify-content-between {
        flex-direction: column;
        gap: 16px;
        align-items: stretch !important;
    }
    
    .d-flex.gap-2 {
        flex-direction: column;
        gap: 12px !important;
    }
    
    .btn {
        width: 100%;
    }
}

@media (max-width: 576px) {
    .card-body {
        padding: 16px;
    }
    
    .table-responsive {
        font-size: 14px;
    }
}
</style>

@endsection

@section('scripts')
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Colores para los gráficos
    const colors = {
        primary: '#007AFF',
        success: '#34C759',
        info: '#5AC8FA',
        warning: '#FF9500',
        danger: '#FF3B30',
        secondary: '#8E8E93'
    };

    // Gráfico de Reservas Mensuales
    const reservasCtx = document.getElementById('reservasMensualesChart').getContext('2d');
    new Chart(reservasCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode(array_keys($estadisticasMensuales)) !!},
            datasets: [{
                label: 'Reservas',
                data: {!! json_encode(array_column($estadisticasMensuales, 'reservas')) !!},
                borderColor: colors.primary,
                backgroundColor: colors.primary + '20',
                tension: 0.4,
                fill: true
            }, {
                label: 'Ingresos (€)',
                data: {!! json_encode(array_column($estadisticasMensuales, 'ingresos')) !!},
                borderColor: colors.success,
                backgroundColor: colors.success + '20',
                tension: 0.4,
                fill: false,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Número de Reservas'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Ingresos (€)'
                    },
                    grid: {
                        drawOnChartArea: false,
                    },
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                }
            }
        }
    });

    // Gráfico de Estados de Reservas
    const estadosCtx = document.getElementById('estadosReservasChart').getContext('2d');
    new Chart(estadosCtx, {
        type: 'doughnut',
        data: {
            labels: {!! json_encode(array_keys($estadosReservas)) !!},
            datasets: [{
                data: {!! json_encode(array_values($estadosReservas)) !!},
                backgroundColor: [
                    colors.primary,
                    colors.success,
                    colors.info,
                    colors.warning,
                    colors.danger,
                    colors.secondary
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
        }
    });

    // Gráfico de Ocupación Semanal
    const ocupacionCtx = document.getElementById('ocupacionSemanalChart').getContext('2d');
    new Chart(ocupacionCtx, {
        type: 'bar',
        data: {
            labels: {!! json_encode(array_keys($ocupacionSemanal)) !!},
            datasets: [{
                label: 'Reservas',
                data: {!! json_encode(array_column($ocupacionSemanal, 'reservas')) !!},
                backgroundColor: colors.primary,
                borderColor: colors.primary,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Número de Reservas'
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
@endsection

