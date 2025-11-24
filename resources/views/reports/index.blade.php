@extends('layouts.app')

@section('title', 'Reportes')
@section('page-title', 'Centro de Reportes')

@section('content')
<div class="row">
    <div class="col-md-3 mb-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="bi bi-cart-check text-primary" style="font-size: 3rem;"></i>
                <h5 class="card-title mt-3">Reporte de Ventas</h5>
                <p class="card-text text-muted">Análisis de ventas por período, cliente y estado</p>
                <a href="{{ route('reports.sales') }}" class="btn btn-primary">
                    <i class="bi bi-file-earmark-text"></i> Ver Reporte
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="bi bi-bag text-success" style="font-size: 3rem;"></i>
                <h5 class="card-title mt-3">Reporte de Compras</h5>
                <p class="card-text text-muted">Análisis de compras por período y proveedor</p>
                <a href="{{ route('reports.purchases') }}" class="btn btn-success">
                    <i class="bi bi-file-earmark-text"></i> Ver Reporte
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="bi bi-cash-stack text-warning" style="font-size: 3rem;"></i>
                <h5 class="card-title mt-3">Reporte de Gastos</h5>
                <p class="card-text text-muted">Análisis de gastos por categoría y período</p>
                <a href="{{ route('reports.expenses') }}" class="btn btn-warning">
                    <i class="bi bi-file-earmark-text"></i> Ver Reporte
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="bi bi-box-seam text-info" style="font-size: 3rem;"></i>
                <h5 class="card-title mt-3">Reporte de Inventario</h5>
                <p class="card-text text-muted">Stock actual, productos bajos y valorización</p>
                <a href="{{ route('reports.inventory') }}" class="btn btn-info">
                    <i class="bi bi-file-earmark-text"></i> Ver Reporte
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
