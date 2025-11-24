@extends('layouts.app')

@section('title', 'Dashboard - Neo ERP')
@section('page-title', 'Dashboard')

@section('content')
<div class="row">
    <div class="col-md-3 mb-4">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-subtitle mb-2 opacity-75">Ventas del Dia</h6>
                        <h3 class="card-title mb-0">Gs. 0</h3>
                    </div>
                    <i class="bi bi-cart-check fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-subtitle mb-2 opacity-75">Clientes</h6>
                        <h3 class="card-title mb-0">0</h3>
                    </div>
                    <i class="bi bi-people fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card bg-warning text-dark">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-subtitle mb-2 opacity-75">Productos</h6>
                        <h3 class="card-title mb-0">0</h3>
                    </div>
                    <i class="bi bi-box fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-subtitle mb-2 opacity-75">Stock Bajo</h6>
                        <h3 class="card-title mb-0">0</h3>
                    </div>
                    <i class="bi bi-exclamation-triangle fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Ultimas Ventas</h5>
            </div>
            <div class="card-body">
                <table id="dg-ultimas-ventas" class="easyui-datagrid" style="width:100%;height:300px"
                    data-options="
                        singleSelect:true,
                        fitColumns:true,
                        emptyMsg:'No hay ventas registradas'
                    ">
                    <thead>
                        <tr>
                            <th data-options="field:'fecha',width:100">Fecha</th>
                            <th data-options="field:'numero',width:100">Nro. Factura</th>
                            <th data-options="field:'cliente',width:200">Cliente</th>
                            <th data-options="field:'total',width:120,align:'right'">Total</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Accesos Rapidos</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="#" class="btn btn-outline-primary">
                        <i class="bi bi-plus-circle me-2"></i>Nueva Venta
                    </a>
                    <a href="#" class="btn btn-outline-success">
                        <i class="bi bi-person-plus me-2"></i>Nuevo Cliente
                    </a>
                    <a href="#" class="btn btn-outline-warning">
                        <i class="bi bi-box-seam me-2"></i>Nuevo Producto
                    </a>
                    <a href="#" class="btn btn-outline-info">
                        <i class="bi bi-file-bar-graph me-2"></i>Ver Reportes
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(function(){
        // Aqui se cargan los datos de las ultimas ventas
        // $('#dg-ultimas-ventas').datagrid('loadData', data);
    });
</script>
@endpush
