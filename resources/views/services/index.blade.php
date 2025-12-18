@extends('layouts.app')

@section('title', 'Servicios - Neo ERP')
@section('page-title', 'Gestión de Servicios')

@section('content')
<div class="card">
    <div class="card-body">
        <!-- Toolbar -->
        <div id="toolbar" style="padding:5px;">
            @can('services.create')
            <a href="javascript:void(0)" class="easyui-linkbutton" iconCls="icon-add" plain="true" onclick="newService()">Nuevo Servicio</a>
            @endcan
            @can('services.edit')
            <a href="javascript:void(0)" class="easyui-linkbutton" iconCls="icon-edit" plain="true" onclick="editService()">Editar</a>
            @endcan
            @can('services.delete')
            <a href="javascript:void(0)" class="easyui-linkbutton" iconCls="icon-remove" plain="true" onclick="deleteService()">Eliminar</a>
            @endcan
            <span class="ms-3">
                <input id="searchbox" class="easyui-searchbox" style="width:250px"
                    data-options="searcher:doSearch,prompt:'Buscar servicio...'">
            </span>
        </div>

        <!-- DataGrid -->
        <table id="dg-services" class="easyui-datagrid" style="width:100%;height:700px"
            data-options="
                url:'{{ route('services.data') }}',
                method:'get',
                toolbar:'#toolbar',
                pagination:true,
                pageSize:20,
                pageList:[10,20,50,100],
                rownumbers:true,
                singleSelect:true,
                fitColumns:true,
                sortName:'sort_order',
                sortOrder:'asc',
                remoteSort:true
            ">
            <thead>
                <tr>
                    <th data-options="field:'id',width:60,sortable:true">ID</th>
                    <th data-options="field:'code',width:100,sortable:true">Código</th>
                    <th data-options="field:'name',width:200,sortable:true">Nombre</th>
                    <th data-options="field:'category_name',width:120">Categoría</th>
                    <th data-options="field:'price',width:100,align:'right'">Precio</th>
                    <th data-options="field:'duration',width:80,align:'center'">Duración</th>
                    <th data-options="field:'tax_rate',width:60,align:'center'">IVA</th>
                    <th data-options="field:'commission',width:80,align:'center'">Comisión</th>
                    <th data-options="field:'is_active',width:80,align:'center',formatter:formatStatus">Estado</th>
                    <th data-options="field:'created_at',width:100">Creado</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<!-- Dialog para crear/editar servicio -->
<div id="dlg-service" class="easyui-dialog" style="width:600px;padding:20px" closed="true" buttons="#dlg-buttons">
    <form id="fm-service" method="post">
        <input type="hidden" name="id" id="service-id">

        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Código <span class="text-danger">*</span></label>
                <input class="easyui-textbox" name="code" id="service-code" style="width:100%" data-options="required:true">
            </div>
            <div class="col-md-8 mb-3">
                <label class="form-label">Nombre <span class="text-danger">*</span></label>
                <input class="easyui-textbox" name="name" id="service-name" style="width:100%" data-options="required:true">
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Categoría</label>
                <select class="easyui-combobox" name="category_id" id="service-category" style="width:100%"
                    data-options="
                        url:'{{ route('categories.list') }}',
                        method:'get',
                        valueField:'id',
                        textField:'name',
                        panelHeight:'auto'
                    ">
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Duración (minutos)</label>
                <input class="easyui-numberbox" name="duration_minutes" id="service-duration" style="width:100%"
                    data-options="min:0,precision:0">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Descripción</label>
            <textarea class="form-control" name="description" id="service-description" rows="2"></textarea>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Precio <span class="text-danger">*</span></label>
                <input class="easyui-numberbox" name="price" id="service-price" style="width:100%"
                    data-options="required:true,min:0,precision:0,groupSeparator:'.',decimalSeparator:','">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">IVA <span class="text-danger">*</span></label>
                <select class="easyui-combobox" name="tax_rate" id="service-tax" style="width:100%"
                    data-options="required:true,panelHeight:'auto',editable:false">
                    <option value="0">Exento (0%)</option>
                    <option value="5">5%</option>
                    <option value="10" selected>10%</option>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Comisión (%)</label>
                <input class="easyui-numberbox" name="commission_percentage" id="service-commission" style="width:100%"
                    data-options="min:0,max:100,precision:2">
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Color (Hex)</label>
                <input type="color" class="form-control form-control-color" name="color" id="service-color" value="#3498db">
                <small class="text-muted">Color del botón en POS</small>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Icono (Bootstrap Icons)</label>
                <input class="easyui-textbox" name="icon" id="service-icon" style="width:100%" data-options="prompt:'bi-scissors'">
                <small class="text-muted">Ejemplo: bi-scissors, bi-cut</small>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Orden de visualización</label>
                <input class="easyui-numberbox" name="sort_order" id="service-sort" style="width:100%"
                    data-options="min:0,precision:0,value:0">
                <small class="text-muted">Menor número = aparece primero</small>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Estado</label><br>
                <input type="checkbox" name="is_active" id="service-active" checked>
                <label for="service-active">Servicio Activo</label>
            </div>
        </div>
    </form>
</div>
<div id="dlg-buttons">
    <a href="javascript:void(0)" class="easyui-linkbutton c6" iconCls="icon-ok" onclick="saveService()" style="width:90px">Guardar</a>
    <a href="javascript:void(0)" class="easyui-linkbutton" iconCls="icon-cancel" onclick="$('#dlg-service').dialog('close')" style="width:90px">Cancelar</a>
</div>
@endsection

@push('scripts')
<script>
var editingId = null;

function formatStatus(value) {
    if (value) {
        return '<span class="badge bg-success">Activo</span>';
    } else {
        return '<span class="badge bg-secondary">Inactivo</span>';
    }
}

function doSearch(value) {
    $('#dg-services').datagrid('load', {
        search: value
    });
}

function newService() {
    editingId = null;
    $('#dlg-service').dialog('open').dialog('setTitle', 'Nuevo Servicio');
    $('#fm-service').form('clear');
    $('#service-active').prop('checked', true);

    // Obtener siguiente código
    $.get('{{ route('services.create') }}', function(html) {
        var parser = new DOMParser();
        var doc = parser.parseFromString(html, 'text/html');
        var nextCode = doc.querySelector('[name="next_code"]')?.value || 'SRV-00001';
        $('#service-code').textbox('setValue', nextCode);
    });
}

function editService() {
    var row = $('#dg-services').datagrid('getSelected');
    if (row) {
        editingId = row.id;
        $('#dlg-service').dialog('open').dialog('setTitle', 'Editar Servicio');

        $.get('{{ url("/services") }}/' + row.id, function(data) {
            $('#service-id').val(data.id);
            $('#service-code').textbox('setValue', data.code);
            $('#service-name').textbox('setValue', data.name);
            $('#service-description').val(data.description);
            $('#service-category').combobox('setValue', data.category_id);
            $('#service-price').numberbox('setValue', data.price);
            $('#service-tax').combobox('setValue', data.tax_rate);
            $('#service-duration').numberbox('setValue', data.duration_minutes);
            $('#service-commission').numberbox('setValue', data.commission_percentage);
            $('#service-color').val(data.color || '#3498db');
            $('#service-icon').textbox('setValue', data.icon);
            $('#service-sort').numberbox('setValue', data.sort_order);
            $('#service-active').prop('checked', data.is_active);
        });
    } else {
        $.messager.alert('Aviso', 'Seleccione un servicio para editar', 'warning');
    }
}

function saveService() {
    if (!$('#fm-service').form('validate')) {
        return;
    }

    var formData = {
        code: $('#service-code').textbox('getValue'),
        name: $('#service-name').textbox('getValue'),
        description: $('#service-description').val(),
        category_id: $('#service-category').combobox('getValue'),
        price: $('#service-price').numberbox('getValue'),
        tax_rate: $('#service-tax').combobox('getValue'),
        duration_minutes: $('#service-duration').numberbox('getValue'),
        commission_percentage: $('#service-commission').numberbox('getValue'),
        color: $('#service-color').val(),
        icon: $('#service-icon').textbox('getValue'),
        sort_order: $('#service-sort').numberbox('getValue'),
        is_active: $('#service-active').is(':checked') ? 1 : 0
    };

    var url = editingId ? '{{ url("/services") }}/' + editingId : '{{ route("services.store") }}';
    var method = editingId ? 'PUT' : 'POST';

    $.ajax({
        url: url,
        method: method,
        data: formData,
        success: function(result) {
            if (result.success) {
                $('#dlg-service').dialog('close');
                $('#dg-services').datagrid('reload');
                $.messager.show({
                    title: 'Éxito',
                    msg: result.message,
                    timeout: 3000,
                    showType: 'slide'
                });
            } else {
                $.messager.alert('Error', result.message, 'error');
            }
        },
        error: function(xhr) {
            var errors = xhr.responseJSON?.errors;
            if (errors) {
                var msg = Object.values(errors).flat().join('<br>');
                $.messager.alert('Error de validación', msg, 'error');
            } else {
                $.messager.alert('Error', xhr.responseJSON?.message || 'Error al guardar', 'error');
            }
        }
    });
}

function deleteService() {
    var row = $('#dg-services').datagrid('getSelected');
    if (row) {
        $.messager.confirm('Confirmar', '¿Está seguro de eliminar el servicio "' + row.name + '"?', function(r) {
            if (r) {
                $.ajax({
                    url: '{{ url("/services") }}/' + row.id,
                    method: 'DELETE',
                    success: function(result) {
                        if (result.success) {
                            $('#dg-services').datagrid('reload');
                            $.messager.show({
                                title: 'Éxito',
                                msg: result.message,
                                timeout: 3000,
                                showType: 'slide'
                            });
                        } else {
                            $.messager.alert('Error', result.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        $.messager.alert('Error', xhr.responseJSON?.message || 'Error al eliminar', 'error');
                    }
                });
            }
        });
    } else {
        $.messager.alert('Aviso', 'Seleccione un servicio para eliminar', 'warning');
    }
}
</script>
@endpush
