@extends('layouts.app')

@section('title', 'Usuarios - Neo ERP')
@section('page-title', 'Gestion de Usuarios')

@section('content')
<div class="card">
    <div class="card-body">
        <!-- Toolbar -->
        <div id="toolbar" style="padding:5px;">
            @can('users.create')
            <a href="javascript:void(0)" class="easyui-linkbutton" iconCls="icon-add" plain="true" onclick="newUser()">Nuevo Usuario</a>
            @endcan
            @can('users.edit')
            <a href="javascript:void(0)" class="easyui-linkbutton" iconCls="icon-edit" plain="true" onclick="editUser()">Editar</a>
            @endcan
            @can('users.delete')
            <a href="javascript:void(0)" class="easyui-linkbutton" iconCls="icon-remove" plain="true" onclick="deleteUser()">Eliminar</a>
            @endcan
            @canany(['pos.use', 'users.edit'])
            <span class="toolbar-separator"></span>
            <a href="javascript:void(0)" class="easyui-linkbutton" iconCls="icon-lock" plain="true" onclick="openPosConfig()">Config. POS</a>
            @endcanany
            <span class="ms-3">
                <input id="searchbox" class="easyui-searchbox" style="width:250px"
                    data-options="searcher:doSearch,prompt:'Buscar usuario...'">
            </span>
        </div>

        <!-- DataGrid -->
        <table id="dg-users" class="easyui-datagrid" style="width:100%;height:700px"
            data-options="
                url:'{{ route('users.data') }}',
                method:'get',
                toolbar:'#toolbar',
                pagination:true,
                pageSize:20,
                pageList:[10,20,50,100],
                rownumbers:true,
                singleSelect:true,
                fitColumns:true,
                sortName:'id',
                sortOrder:'desc',
                remoteSort:true
            ">
            <thead>
                <tr>
                    <th data-options="field:'id',width:60,sortable:true">ID</th>
                    <th data-options="field:'name',width:150,sortable:true">Nombre</th>
                    <th data-options="field:'email',width:200,sortable:true">Email</th>
                    <th data-options="field:'tenant',width:150">Empresa</th>
                    <th data-options="field:'roles',width:150">Roles</th>
                    <th data-options="field:'pos_enabled',width:80,align:'center',formatter:formatPosStatus">POS</th>
                    <th data-options="field:'is_active',width:80,align:'center',formatter:formatStatus">Estado</th>
                    <th data-options="field:'created_at',width:130">Creado</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<!-- Dialog para crear/editar usuario -->
<div id="dlg-user" class="easyui-dialog" style="width:500px;padding:20px" closed="true" buttons="#dlg-buttons">
    <form id="fm-user" method="post">
        <input type="hidden" name="id" id="user-id">
        <div class="mb-3">
            <label class="form-label">Nombre <span class="text-danger">*</span></label>
            <input class="easyui-textbox" name="name" id="user-name" style="width:100%" data-options="required:true">
        </div>
        <div class="mb-3">
            <label class="form-label">Email <span class="text-danger">*</span></label>
            <input class="easyui-textbox" name="email" id="user-email" style="width:100%" data-options="required:true,validType:'email'">
        </div>
        <div class="mb-3">
            <label class="form-label">Contrasena <span id="password-required" class="text-danger">*</span></label>
            <input class="easyui-textbox" name="password" id="user-password" type="password" style="width:100%">
            <small class="text-muted" id="password-help" style="display:none">Dejar vacio para mantener la contrasena actual</small>
        </div>
        <div class="mb-3">
            <label class="form-label">Roles</label>
            <select class="easyui-combobox" name="roles[]" id="user-roles" style="width:100%"
                data-options="
                    url:'{{ route('roles.list') }}',
                    method:'get',
                    valueField:'id',
                    textField:'name',
                    multiple:true,
                    panelHeight:'auto'
                ">
            </select>
        </div>
        <div class="mb-3">
            <input type="checkbox" name="is_active" id="user-active" value="1" checked>
            <label for="user-active">Usuario Activo</label>
        </div>
    </form>
</div>
<div id="dlg-buttons">
    <a href="javascript:void(0)" class="easyui-linkbutton c6" iconCls="icon-ok" onclick="saveUser()" style="width:90px">Guardar</a>
    <a href="javascript:void(0)" class="easyui-linkbutton" iconCls="icon-cancel" onclick="$('#dlg-user').dialog('close')" style="width:90px">Cancelar</a>
</div>

<!-- Dialog para configuración POS -->
<div id="dlg-pos-config" class="easyui-dialog" style="width:550px;padding:20px" closed="true" buttons="#dlg-pos-buttons">
    <form id="fm-pos-config" method="post">
        <input type="hidden" name="pos_user_id" id="pos-user-id">

        <div class="alert alert-info" style="font-size: 14px;">
            <i class="bi bi-info-circle"></i> Configure el acceso al Punto de Venta para este usuario.
        </div>

        <div class="mb-3">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="pos-enabled" name="pos_enabled" onchange="togglePosFields()">
                <label class="form-check-label" for="pos-enabled">
                    <strong>Habilitar acceso al POS</strong>
                </label>
            </div>
            <small class="text-muted">Permite que el usuario pueda usar el punto de venta</small>
        </div>

        <div id="pos-fields" style="display:none;">
            <hr>

            <div class="mb-3">
                <label class="form-label"><i class="bi bi-key"></i> PIN del POS</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="pos-pin" name="pin" maxlength="6" pattern="[0-9]*" placeholder="4-6 dígitos">
                    <input type="text" class="form-control" id="pos-pin-confirm" name="pin_confirmation" maxlength="6" pattern="[0-9]*" placeholder="Confirmar PIN">
                    <button class="btn btn-outline-danger" type="button" onclick="removePinConfirm()" id="btn-remove-pin" style="display:none">
                        <i class="bi bi-trash"></i> Eliminar PIN
                    </button>
                </div>
                <small class="text-muted">
                    <span id="pin-status-new">Ingrese un PIN numérico de 4-6 dígitos</span>
                    <span id="pin-status-exists" style="display:none" class="text-success">
                        <i class="bi bi-check-circle"></i> PIN configurado. Dejar vacío para mantener el actual.
                    </span>
                </small>
            </div>

            <div class="mb-3">
                <label class="form-label"><i class="bi bi-percent"></i> Porcentaje de Comisión</label>
                <div class="input-group">
                    <input type="number" class="form-control" id="pos-commission" name="commission_percentage" min="0" max="100" step="0.01" placeholder="0.00">
                    <span class="input-group-text">%</span>
                </div>
                <small class="text-muted">Comisión por ventas realizadas (0-100%)</small>
            </div>

            <div class="mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="pos-require-rfid" name="pos_require_rfid" onchange="toggleRfidField()">
                    <label class="form-check-label" for="pos-require-rfid">
                        <strong>Requerir RFID (2FA)</strong>
                    </label>
                </div>
                <small class="text-muted">Requiere tarjeta RFID además del PIN para mayor seguridad</small>
            </div>

            <div class="mb-3" id="rfid-field" style="display:none;">
                <label class="form-label"><i class="bi bi-credit-card"></i> Código RFID</label>
                <input type="text" class="form-control" id="pos-rfid-code" name="rfid_code" placeholder="Código de la tarjeta RFID">
                <small class="text-muted">Código único de la tarjeta RFID del usuario</small>
            </div>
        </div>
    </form>
</div>
<div id="dlg-pos-buttons">
    <a href="javascript:void(0)" class="easyui-linkbutton c6" iconCls="icon-ok" onclick="savePosConfig()" style="width:90px">Guardar</a>
    <a href="javascript:void(0)" class="easyui-linkbutton" iconCls="icon-cancel" onclick="$('#dlg-pos-config').dialog('close')" style="width:90px">Cancelar</a>
</div>
@endsection

@push('scripts')
<script>
var editingId = null;
var posUserId = null;
var userHasPin = false;

function formatStatus(value) {
    if (value) {
        return '<span class="badge bg-success">Activo</span>';
    } else {
        return '<span class="badge bg-danger">Inactivo</span>';
    }
}

function formatPosStatus(value) {
    if (value) {
        return '<i class="bi bi-check-circle-fill text-success" title="POS habilitado"></i>';
    } else {
        return '<i class="bi bi-x-circle text-muted" title="POS deshabilitado"></i>';
    }
}

function doSearch(value) {
    $('#dg-users').datagrid('load', {
        search: value
    });
}

function newUser() {
    editingId = null;
    $('#dlg-user').dialog('open').dialog('setTitle', 'Nuevo Usuario');
    $('#fm-user').form('clear');
    $('#user-active').prop('checked', true);
    $('#user-password').textbox({required: true});
    $('#password-required').show();
    $('#password-help').hide();
}

function editUser() {
    var row = $('#dg-users').datagrid('getSelected');
    if (row) {
        editingId = row.id;
        $('#dlg-user').dialog('open').dialog('setTitle', 'Editar Usuario');
        $('#user-password').textbox({required: false});
        $('#password-required').hide();
        $('#password-help').show();

        $.get('{{ url("/users") }}/' + row.id, function(data) {
            $('#user-id').val(data.id);
            $('#user-name').textbox('setValue', data.name);
            $('#user-email').textbox('setValue', data.email);
            $('#user-roles').combobox('setValues', data.roles);
            $('#user-active').prop('checked', data.is_active);
        });
    } else {
        $.messager.alert('Aviso', 'Seleccione un usuario para editar', 'warning');
    }
}

function saveUser() {
    if (!$('#fm-user').form('validate')) {
        return;
    }

    var formData = {
        name: $('#user-name').textbox('getValue'),
        email: $('#user-email').textbox('getValue'),
        password: $('#user-password').textbox('getValue'),
        roles: $('#user-roles').combobox('getValues'),
        is_active: $('#user-active').is(':checked') ? 1 : 0
    };

    var url = editingId ? '{{ url("/users") }}/' + editingId : '{{ route("users.store") }}';
    var method = editingId ? 'PUT' : 'POST';

    $.ajax({
        url: url,
        method: method,
        data: formData,
        success: function(result) {
            if (result.success) {
                $('#dlg-user').dialog('close');
                $('#dg-users').datagrid('reload');
                $.messager.show({
                    title: 'Exito',
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
                $.messager.alert('Error de validacion', msg, 'error');
            } else {
                $.messager.alert('Error', xhr.responseJSON?.message || 'Error al guardar', 'error');
            }
        }
    });
}

function deleteUser() {
    var row = $('#dg-users').datagrid('getSelected');
    if (row) {
        $.messager.confirm('Confirmar', 'Esta seguro de eliminar el usuario "' + row.name + '"?', function(r) {
            if (r) {
                $.ajax({
                    url: '{{ url("/users") }}/' + row.id,
                    method: 'DELETE',
                    success: function(result) {
                        if (result.success) {
                            $('#dg-users').datagrid('reload');
                            $.messager.show({
                                title: 'Exito',
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
        $.messager.alert('Aviso', 'Seleccione un usuario para eliminar', 'warning');
    }
}

// ==================== POS CONFIGURATION ====================

function openPosConfig() {
    var row = $('#dg-users').datagrid('getSelected');
    if (row) {
        posUserId = row.id;
        $('#dlg-pos-config').dialog('open').dialog('setTitle', 'Configuración POS - ' + row.name);
        $('#fm-pos-config')[0].reset();

        // Cargar configuración actual
        $.get('{{ url("/users") }}/' + row.id + '/pos-config', function(data) {
            $('#pos-user-id').val(data.id);
            $('#pos-enabled').prop('checked', data.pos_enabled);
            $('#pos-require-rfid').prop('checked', data.pos_require_rfid);
            $('#pos-commission').val(data.commission_percentage);
            $('#pos-rfid-code').val(data.rfid_code);

            userHasPin = data.has_pin;
            togglePosFields();
            toggleRfidField();

            if (userHasPin) {
                $('#pin-status-new').hide();
                $('#pin-status-exists').show();
                $('#btn-remove-pin').show();
            } else {
                $('#pin-status-new').show();
                $('#pin-status-exists').hide();
                $('#btn-remove-pin').hide();
            }
        }).fail(function() {
            // Si no existe endpoint, usar valores por defecto
            $('#pos-enabled').prop('checked', false);
            $('#pos-require-rfid').prop('checked', false);
            $('#pos-commission').val('');
            $('#pos-rfid-code').val('');
            userHasPin = false;
            togglePosFields();
        });
    } else {
        $.messager.alert('Aviso', 'Seleccione un usuario para configurar POS', 'warning');
    }
}

function togglePosFields() {
    if ($('#pos-enabled').is(':checked')) {
        $('#pos-fields').slideDown();
    } else {
        $('#pos-fields').slideUp();
    }
}

function toggleRfidField() {
    if ($('#pos-require-rfid').is(':checked')) {
        $('#rfid-field').slideDown();
    } else {
        $('#rfid-field').slideUp();
    }
}

function savePosConfig() {
    var posEnabled = $('#pos-enabled').is(':checked');
    var pin = $('#pos-pin').val();
    var pinConfirm = $('#pos-pin-confirm').val();

    // Validar PIN si se está configurando
    if (posEnabled && pin) {
        if (pin.length < 4 || pin.length > 6) {
            $.messager.alert('Error', 'El PIN debe tener entre 4 y 6 dígitos', 'error');
            return;
        }
        if (!/^[0-9]+$/.test(pin)) {
            $.messager.alert('Error', 'El PIN solo puede contener números', 'error');
            return;
        }
        if (pin !== pinConfirm) {
            $.messager.alert('Error', 'Los PINs no coinciden', 'error');
            return;
        }
    }

    // Primero guardar configuración general
    var configData = {
        pos_enabled: posEnabled ? 1 : 0,
        pos_require_rfid: $('#pos-require-rfid').is(':checked') ? 1 : 0,
        rfid_code: $('#pos-rfid-code').val(),
        commission_percentage: $('#pos-commission').val()
    };

    $.ajax({
        url: '{{ url("/users") }}/' + posUserId + '/pos-config',
        method: 'PUT',
        data: configData,
        success: function(result) {
            // Si hay PIN nuevo, guardarlo
            if (pin) {
                $.ajax({
                    url: '{{ url("/users") }}/' + posUserId + '/pos-pin',
                    method: 'POST',
                    data: {
                        pin: pin,
                        pin_confirmation: pinConfirm
                    },
                    success: function(pinResult) {
                        closePosConfigSuccess();
                    },
                    error: function(xhr) {
                        $.messager.alert('Error', xhr.responseJSON?.message || 'Error al guardar PIN', 'error');
                    }
                });
            } else {
                closePosConfigSuccess();
            }
        },
        error: function(xhr) {
            var errors = xhr.responseJSON?.errors;
            if (errors) {
                var msg = Object.values(errors).flat().join('<br>');
                $.messager.alert('Error de validación', msg, 'error');
            } else {
                $.messager.alert('Error', xhr.responseJSON?.message || 'Error al guardar configuración', 'error');
            }
        }
    });
}

function closePosConfigSuccess() {
    $('#dlg-pos-config').dialog('close');
    $('#dg-users').datagrid('reload');
    $.messager.show({
        title: 'Éxito',
        msg: 'Configuración POS actualizada correctamente',
        timeout: 3000,
        showType: 'slide'
    });
}

function removePinConfirm() {
    $.messager.confirm('Confirmar', '¿Está seguro de eliminar el PIN? El usuario no podrá acceder al POS.', function(r) {
        if (r) {
            $.ajax({
                url: '{{ url("/users") }}/' + posUserId + '/pos-pin',
                method: 'DELETE',
                success: function(result) {
                    userHasPin = false;
                    $('#pos-pin').val('');
                    $('#pos-pin-confirm').val('');
                    $('#pin-status-new').show();
                    $('#pin-status-exists').hide();
                    $('#btn-remove-pin').hide();
                    $.messager.show({
                        title: 'Éxito',
                        msg: result.message,
                        timeout: 3000,
                        showType: 'slide'
                    });
                },
                error: function(xhr) {
                    $.messager.alert('Error', xhr.responseJSON?.message || 'Error al eliminar PIN', 'error');
                }
            });
        }
    });
}
</script>
@endpush
