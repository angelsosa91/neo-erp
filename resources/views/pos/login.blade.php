<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>POS Login - Neo ERP</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
        :root {
            --primary-color: #2c3e50;
            --accent-color: #3498db;
            --success-color: #27ae60;
            --error-color: #e74c3c;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow: hidden;
        }

        .pos-login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 40px;
            width: 450px;
            max-width: 95vw;
        }

        .pos-logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .pos-logo h1 {
            color: var(--primary-color);
            font-size: 32px;
            font-weight: 700;
            margin: 0;
        }

        .pos-logo p {
            color: #7f8c8d;
            margin: 5px 0 0 0;
            font-size: 14px;
        }

        .user-info {
            text-align: center;
            margin-bottom: 25px;
        }

        .user-info h5 {
            color: var(--primary-color);
            font-weight: 600;
            margin: 0;
        }

        .user-info small {
            color: #95a5a6;
        }

        .pin-display {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 30px;
        }

        .pin-dot {
            width: 50px;
            height: 50px;
            border: 3px solid #e0e0e0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .pin-dot.filled {
            background: var(--accent-color);
            border-color: var(--accent-color);
        }

        .pin-dot.filled::after {
            content: '●';
            color: white;
            font-size: 24px;
        }

        .pin-dot.error {
            animation: shake 0.5s;
            border-color: var(--error-color);
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }

        .numpad {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .numpad-btn {
            height: 70px;
            font-size: 28px;
            font-weight: 600;
            border: 2px solid #e0e0e0;
            background: white;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s;
            user-select: none;
            -webkit-tap-highlight-color: transparent;
        }

        .numpad-btn:hover {
            background: #f8f9fa;
            border-color: var(--accent-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .numpad-btn:active {
            transform: translateY(0);
            box-shadow: none;
        }

        .numpad-btn-special {
            background: #f8f9fa;
            color: var(--primary-color);
            font-size: 20px;
        }

        .numpad-btn-ok {
            background: var(--success-color);
            color: white;
            border-color: var(--success-color);
        }

        .numpad-btn-ok:hover {
            background: #229954;
            border-color: #229954;
        }

        .alert-custom {
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
        }

        .logout-link {
            text-align: center;
            margin-top: 20px;
        }

        .logout-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 14px;
        }

        .logout-link a:hover {
            text-decoration: underline;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .loading.active {
            display: block;
        }

        .spinner-border {
            width: 3rem;
            height: 3rem;
        }
    </style>
</head>
<body>
    <div class="pos-login-container">
        <div class="pos-logo">
            <h1><i class="bi bi-shop"></i> NEO ERP</h1>
            <p>Punto de Venta</p>
        </div>

        <div class="user-info">
            <h5>Ingrese su PIN</h5>
            <small>Cualquier vendedor habilitado puede acceder</small>
        </div>

        <div id="alert-container"></div>

        <div id="pin-input-area">
            <div class="pin-display">
                <div class="pin-dot" data-index="0"></div>
                <div class="pin-dot" data-index="1"></div>
                <div class="pin-dot" data-index="2"></div>
                <div class="pin-dot" data-index="3"></div>
            </div>

            <div class="numpad">
                <button class="numpad-btn" data-number="1">1</button>
                <button class="numpad-btn" data-number="2">2</button>
                <button class="numpad-btn" data-number="3">3</button>
                <button class="numpad-btn" data-number="4">4</button>
                <button class="numpad-btn" data-number="5">5</button>
                <button class="numpad-btn" data-number="6">6</button>
                <button class="numpad-btn" data-number="7">7</button>
                <button class="numpad-btn" data-number="8">8</button>
                <button class="numpad-btn" data-number="9">9</button>
                <button class="numpad-btn numpad-btn-special" id="btn-clear">
                    <i class="bi bi-arrow-left"></i>
                </button>
                <button class="numpad-btn" data-number="0">0</button>
                <button class="numpad-btn numpad-btn-ok" id="btn-ok">
                    <i class="bi bi-check-lg"></i>
                </button>
            </div>
        </div>

        <div class="loading" id="loading">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2">Verificando...</p>
        </div>

        <div class="logout-link">
            <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                <i class="bi bi-box-arrow-left"></i> Salir del sistema
            </a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                @csrf
            </form>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <script>
        let pin = '';
        const MAX_PIN_LENGTH = 6;
        const MIN_PIN_LENGTH = 4;

        // Configurar CSRF token para AJAX
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Click en botones numéricos
        $('.numpad-btn[data-number]').click(function() {
            if (pin.length < MAX_PIN_LENGTH) {
                pin += $(this).data('number');
                updatePinDisplay();

                // Vibrar si está disponible
                if (navigator.vibrate) {
                    navigator.vibrate(10);
                }
            }
        });

        // Botón clear (borrar)
        $('#btn-clear').click(function() {
            pin = pin.slice(0, -1);
            updatePinDisplay();
        });

        // Botón OK (enviar)
        $('#btn-ok').click(function() {
            if (pin.length >= MIN_PIN_LENGTH) {
                submitPin();
            } else {
                showAlert('Ingrese al menos ' + MIN_PIN_LENGTH + ' dígitos', 'warning');
            }
        });

        // Soporte para teclado físico
        $(document).keydown(function(e) {
            if (e.key >= '0' && e.key <= '9') {
                if (pin.length < MAX_PIN_LENGTH) {
                    pin += e.key;
                    updatePinDisplay();
                }
            } else if (e.key === 'Backspace') {
                pin = pin.slice(0, -1);
                updatePinDisplay();
            } else if (e.key === 'Enter') {
                if (pin.length >= MIN_PIN_LENGTH) {
                    submitPin();
                }
            }
        });

        // Actualizar visualización del PIN
        function updatePinDisplay() {
            $('.pin-dot').each(function(index) {
                if (index < pin.length) {
                    $(this).addClass('filled');
                } else {
                    $(this).removeClass('filled');
                }
                $(this).removeClass('error');
            });
        }

        // Enviar PIN al servidor
        function submitPin() {
            showLoading(true);

            $.ajax({
                url: '{{ route('pos.login.post') }}',
                method: 'POST',
                data: { pin: pin },
                success: function(response) {
                    if (response.success) {
                        if (response.requires_rfid) {
                            // Redirigir a verificación RFID
                            window.location.href = '{{ route('pos.rfid.verify.view') }}';
                        } else {
                            // Redirigir al POS
                            showAlert(response.message, 'success');
                            setTimeout(function() {
                                window.location.href = response.redirect;
                            }, 500);
                        }
                    } else {
                        showLoading(false);
                        showAlert(response.message, 'danger');
                        pinError();
                    }
                },
                error: function(xhr) {
                    showLoading(false);
                    const message = xhr.responseJSON?.message || 'Error al autenticar';
                    showAlert(message, 'danger');
                    pinError();
                }
            });
        }

        // Mostrar error en PIN
        function pinError() {
            $('.pin-dot').addClass('error');
            pin = '';

            setTimeout(function() {
                updatePinDisplay();
            }, 500);

            // Vibrar si está disponible
            if (navigator.vibrate) {
                navigator.vibrate([100, 50, 100]);
            }
        }

        // Mostrar/ocultar loading
        function showLoading(show) {
            if (show) {
                $('#pin-input-area').hide();
                $('#loading').addClass('active');
            } else {
                $('#pin-input-area').show();
                $('#loading').removeClass('active');
            }
        }

        // Mostrar alerta
        function showAlert(message, type) {
            const alertHtml = `
                <div class="alert alert-${type} alert-custom" role="alert">
                    ${message}
                </div>
            `;
            $('#alert-container').html(alertHtml);

            setTimeout(function() {
                $('#alert-container').html('');
            }, 5000);
        }

        // Mensaje de sesión si existe
        @if(session('error'))
            showAlert('{{ session('error') }}', 'warning');
        @endif
    </script>
</body>
</html>
