@extends('layouts.app')

@section('title', 'Configuración')
@section('page-title', 'Configuración del Sistema')

@section('content')
<div class="row">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist">
                    <button class="nav-link active text-start" id="company-tab" data-bs-toggle="pill" data-bs-target="#company" type="button">
                        <i class="bi bi-building me-2"></i> Datos de Empresa
                    </button>
                    <button class="nav-link text-start" id="numbering-tab" data-bs-toggle="pill" data-bs-target="#numbering" type="button">
                        <i class="bi bi-123 me-2"></i> Numeración
                    </button>
                    <button class="nav-link text-start" id="preferences-tab" data-bs-toggle="pill" data-bs-target="#preferences" type="button">
                        <i class="bi bi-sliders me-2"></i> Preferencias
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-9">
        <div class="card">
            <div class="card-body">
                <form id="settingsForm">
                    @csrf
                    <div class="tab-content" id="v-pills-tabContent">
                        <!-- Datos de Empresa -->
                        <div class="tab-pane fade show active" id="company" role="tabpanel">
                            <h5 class="mb-4">Datos de la Empresa</h5>
                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label class="form-label">Nombre de la Empresa <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="company_name" value="{{ $settings['company_name'] }}" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">RUC</label>
                                    <input type="text" class="form-control" name="company_ruc" value="{{ $settings['company_ruc'] }}">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Dirección</label>
                                <textarea class="form-control" name="company_address" rows="2">{{ $settings['company_address'] }}</textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Teléfono</label>
                                    <input type="text" class="form-control" name="company_phone" value="{{ $settings['company_phone'] }}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="company_email" value="{{ $settings['company_email'] }}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Ciudad</label>
                                    <input type="text" class="form-control" name="company_city" value="{{ $settings['company_city'] }}">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">País</label>
                                <input type="text" class="form-control" name="company_country" value="{{ $settings['company_country'] }}">
                            </div>
                        </div>

                        <!-- Numeración -->
                        <div class="tab-pane fade" id="numbering" role="tabpanel">
                            <h5 class="mb-4">Configuración de Numeración</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Prefijo de Facturas <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="invoice_prefix" value="{{ $settings['invoice_prefix'] }}" required>
                                    <small class="text-muted">Ejemplo: V-, FAC-, etc.</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Próximo Número</label>
                                    <input type="number" class="form-control" value="{{ $settings['invoice_next_number'] }}" readonly>
                                    <small class="text-muted">Se genera automáticamente</small>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Prefijo de Compras <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="purchase_prefix" value="{{ $settings['purchase_prefix'] }}" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Próximo Número</label>
                                    <input type="number" class="form-control" value="{{ $settings['purchase_next_number'] }}" readonly>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Prefijo de Gastos <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="expense_prefix" value="{{ $settings['expense_prefix'] }}" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Próximo Número</label>
                                    <input type="number" class="form-control" value="{{ $settings['expense_next_number'] }}" readonly>
                                </div>
                            </div>
                        </div>

                        <!-- Preferencias -->
                        <div class="tab-pane fade" id="preferences" role="tabpanel">
                            <h5 class="mb-4">Preferencias Generales</h5>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">IVA por Defecto <span class="text-danger">*</span></label>
                                    <select class="form-select" name="default_tax_rate" required>
                                        <option value="10" {{ $settings['default_tax_rate'] == '10' ? 'selected' : '' }}>10%</option>
                                        <option value="5" {{ $settings['default_tax_rate'] == '5' ? 'selected' : '' }}>5%</option>
                                        <option value="0" {{ $settings['default_tax_rate'] == '0' ? 'selected' : '' }}>Exento</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Símbolo de Moneda <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="currency_symbol" value="{{ $settings['currency_symbol'] }}" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Código de Moneda <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="currency_code" value="{{ $settings['currency_code'] }}" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Formato de Fecha <span class="text-danger">*</span></label>
                                    <select class="form-select" name="date_format" required>
                                        <option value="d/m/Y" {{ $settings['date_format'] == 'd/m/Y' ? 'selected' : '' }}>DD/MM/YYYY</option>
                                        <option value="m/d/Y" {{ $settings['date_format'] == 'm/d/Y' ? 'selected' : '' }}>MM/DD/YYYY</option>
                                        <option value="Y-m-d" {{ $settings['date_format'] == 'Y-m-d' ? 'selected' : '' }}>YYYY-MM-DD</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Zona Horaria <span class="text-danger">*</span></label>
                                    <select class="form-select" name="timezone" required>
                                        <option value="America/Asuncion" {{ $settings['timezone'] == 'America/Asuncion' ? 'selected' : '' }}>America/Asuncion</option>
                                        <option value="America/Buenos_Aires" {{ $settings['timezone'] == 'America/Buenos_Aires' ? 'selected' : '' }}>America/Buenos_Aires</option>
                                        <option value="America/Sao_Paulo" {{ $settings['timezone'] == 'America/Sao_Paulo' ? 'selected' : '' }}>America/Sao_Paulo</option>
                                        <option value="America/Santiago" {{ $settings['timezone'] == 'America/Santiago' ? 'selected' : '' }}>America/Santiago</option>
                                        <option value="America/Lima" {{ $settings['timezone'] == 'America/Lima' ? 'selected' : '' }}>America/Lima</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>
                    <div class="text-end">
                        <button type="button" class="btn btn-primary" onclick="saveSettings()">
                            <i class="bi bi-save"></i> Guardar Configuración
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function saveSettings() {
    var formData = {};
    $('#settingsForm').serializeArray().forEach(function(item) {
        formData[item.name] = item.value;
    });

    $.ajax({
        url: '{{ route('settings.update') }}',
        method: 'POST',
        data: formData,
        success: function(response) {
            $.messager.show({
                title: 'Éxito',
                msg: response.message,
                timeout: 3000,
                showType: 'slide'
            });
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
</script>
@endpush
