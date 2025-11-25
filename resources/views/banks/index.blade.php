@extends('layouts.app')

@section('title', 'Bancos')
@section('page-title', 'Bancos')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2>Bancos</h2>
        </div>
        <div class="col-md-6 text-end">
            <button type="button" class="btn btn-primary" onclick="newBank()">
                <i class="bi bi-plus-circle"></i> Nuevo Banco
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3">
                    <input type="text" class="form-control" id="search-name" placeholder="Nombre">
                </div>
                <div class="col-md-3">
                    <input type="text" class="form-control" id="search-short-name" placeholder="Nombre corto">
                </div>
                <div class="col-md-2">
                    <input type="text" class="form-control" id="search-country" placeholder="País">
                </div>
                <div class="col-md-2">
                    <select class="form-select" id="search-active">
                        <option value="">Todos los estados</option>
                        <option value="1">Activos</option>
                        <option value="0">Inactivos</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-secondary w-100" onclick="searchBanks()">
                        <i class="bi bi-search"></i> Buscar
                    </button>
                </div>
            </div>

            <table id="banks-grid"></table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="bankModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bankModalLabel">Nuevo Banco</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="bankForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" id="bank-id" name="id">

                    <div class="mb-3">
                        <label for="bank-name" class="form-label">Nombre oficial <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="bank-name" name="name" required>
                    </div>

                    <div class="mb-3">
                        <label for="bank-short-name" class="form-label">Nombre corto <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="bank-short-name" name="short_name" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="bank-code" class="form-label">Código SET/BCP</label>
                                <input type="text" class="form-control" id="bank-code" name="code">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="bank-swift-code" class="form-label">Código SWIFT/BIC</label>
                                <input type="text" class="form-control" id="bank-swift-code" name="swift_code">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="bank-country" class="form-label">País <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="bank-country" name="country" value="Paraguay" required>
                    </div>

                    <div class="mb-3">
                        <label for="bank-logo" class="form-label">Logo</label>
                        <input type="file" class="form-control" id="bank-logo" name="logo" accept="image/*">
                        <div id="current-logo" class="mt-2" style="display: none;">
                            <img id="logo-preview" src="" alt="Logo actual" style="max-height: 100px;">
                        </div>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="bank-is-active" name="is_active" checked>
                        <label class="form-check-label" for="bank-is-active">Activo</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(function() {
    $('#banks-grid').datagrid({
        url: '{{ route("banks.list") }}',
        method: 'get',
        rownumbers: true,
        singleSelect: true,
        pagination: true,
        pageSize: 20,
        pageList: [10, 20, 50, 100],
        toolbar: '#toolbar',
        columns: [[
            {field: 'id', title: 'ID', width: 50, align: 'center'},
            {field: 'name', title: 'Nombre', width: 200},
            {field: 'short_name', title: 'Nombre Corto', width: 100},
            {field: 'code', title: 'Código', width: 80, align: 'center'},
            {field: 'swift_code', title: 'SWIFT', width: 100, align: 'center'},
            {field: 'country', title: 'País', width: 100},
            {field: 'is_active', title: 'Estado', width: 80, align: 'center',
                formatter: function(value, row) {
                    return value ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-secondary">Inactivo</span>';
                }
            },
            {field: 'action', title: 'Acciones', width: 150, align: 'center',
                formatter: function(value, row) {
                    return `
                        <button class="btn btn-sm btn-info" onclick="editBank(${row.id})">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteBank(${row.id})">
                            <i class="bi bi-trash"></i>
                        </button>
                    `;
                }
            }
        ]]
    });
});

function searchBanks() {
    $('#banks-grid').datagrid('load', {
        name: $('#search-name').val(),
        short_name: $('#search-short-name').val(),
        country: $('#search-country').val(),
        is_active: $('#search-active').val()
    });
}

function newBank() {
    $('#bankForm')[0].reset();
    $('#bank-id').val('');
    $('#current-logo').hide();
    $('#bankModalLabel').text('Nuevo Banco');
    $('#bankModal').modal('show');
}

function editBank(id) {
    $.get('{{ url("banks") }}/' + id, function(data) {
        $('#bank-id').val(data.id);
        $('#bank-name').val(data.name);
        $('#bank-short-name').val(data.short_name);
        $('#bank-code').val(data.code);
        $('#bank-swift-code').val(data.swift_code);
        $('#bank-country').val(data.country);
        $('#bank-is-active').prop('checked', data.is_active);

        if (data.logo) {
            $('#logo-preview').attr('src', '{{ asset("storage") }}/' + data.logo);
            $('#current-logo').show();
        } else {
            $('#current-logo').hide();
        }

        $('#bankModalLabel').text('Editar Banco');
        $('#bankModal').modal('show');
    });
}

function deleteBank(id) {
    if (confirm('¿Está seguro de eliminar este banco?')) {
        $.ajax({
            url: '{{ url("banks") }}/' + id,
            type: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    $.messager.show({
                        title: 'Éxito',
                        msg: response.message,
                        timeout: 3000
                    });
                    $('#banks-grid').datagrid('reload');
                } else {
                    $.messager.alert('Error', response.message, 'error');
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                $.messager.alert('Error', response.message || 'Error al eliminar el banco', 'error');
            }
        });
    }
}

$('#bankForm').on('submit', function(e) {
    e.preventDefault();

    const id = $('#bank-id').val();
    const url = id ? '{{ url("banks") }}/' + id : '{{ route("banks.store") }}';
    const method = id ? 'POST' : 'POST';

    const formData = new FormData(this);
    if (id) {
        formData.append('_method', 'PUT');
    }

    $.ajax({
        url: url,
        type: method,
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                $('#bankModal').modal('hide');
                $.messager.show({
                    title: 'Éxito',
                    msg: response.message,
                    timeout: 3000
                });
                $('#banks-grid').datagrid('reload');
            }
        },
        error: function(xhr) {
            const errors = xhr.responseJSON.errors;
            let errorMsg = 'Error al guardar el banco';
            if (errors) {
                errorMsg = Object.values(errors).flat().join('<br>');
            }
            $.messager.alert('Error', errorMsg, 'error');
        }
    });
});

$('#search-name, #search-short-name, #search-country').on('keypress', function(e) {
    if (e.which === 13) {
        searchBanks();
    }
});
</script>
@endsection
