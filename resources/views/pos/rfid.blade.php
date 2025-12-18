<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Verificación RFID - Neo ERP</title>

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

        .rfid-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 40px;
            width: 450px;
            max-width: 95vw;
            text-align: center;
        }

        .rfid-logo {
            margin-bottom: 30px;
        }

        .rfid-logo h1 {
            color: var(--primary-color);
            font-size: 32px;
            font-weight: 700;
            margin: 0;
        }

        .rfid-logo p {
            color: #7f8c8d;
            margin: 5px 0 0 0;
            font-size: 14px;
        }

        .status-icon {
            margin: 30px 0;
        }

        .status-icon i {
            font-size: 80px;
            color: var(--success-color);
        }

        .rfid-icon-container {
            margin: 40px 0;
            position: relative;
        }

        .rfid-icon {
            font-size: 120px;
            color: var(--accent-color);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 1;
                transform: scale(1);
            }
            50% {
                opacity: 0.6;
                transform: scale(1.1);
            }
        }

        .rfid-waves {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .wave {
            position: absolute;
            border: 3px solid var(--accent-color);
            border-radius: 50%;
            animation: wave-animation 2s infinite;
        }

        .wave:nth-child(1) {
            width: 100px;
            height: 100px;
            margin: -50px 0 0 -50px;
        }

        .wave:nth-child(2) {
            width: 140px;
            height: 140px;
            margin: -70px 0 0 -70px;
            animation-delay: 0.3s;
        }

        .wave:nth-child(3) {
            width: 180px;
            height: 180px;
            margin: -90px 0 0 -90px;
            animation-delay: 0.6s;
        }

        @keyframes wave-animation {
            0% {
                opacity: 0.8;
                transform: scale(0.8);
            }
            100% {
                opacity: 0;
                transform: scale(1.2);
            }
        }

        .instruction-text {
            font-size: 18px;
            color: var(--primary-color);
            font-weight: 600;
            margin: 30px 0 10px 0;
        }

        .help-text {
            color: #95a5a6;
            font-size: 14px;
            margin-bottom: 30px;
        }

        .rfid-input-container {
            margin: 30px 0;
        }

        .rfid-input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            text-align: center;
            letter-spacing: 2px;
            transition: all 0.3s;
        }

        .rfid-input:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .btn-cancel {
            width: 100%;
            padding: 15px;
            border: 2px solid #e0e0e0;
            background: white;
            border-radius: 10px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-cancel:hover {
            background: #f8f9fa;
            border-color: var(--error-color);
            color: var(--error-color);
        }

        .alert-custom {
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .loading {
            display: none;
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
    <div class="rfid-container">
        <div class="rfid-logo">
            <h1><i class="bi bi-shield-check"></i> Verificación 2FA</h1>
            <p>Autenticación en dos pasos</p>
        </div>

        <div class="status-icon">
            <i class="bi bi-check-circle-fill"></i>
        </div>

        <p style="color: var(--success-color); font-weight: 600; margin-bottom: 30px;">
            PIN Verificado Correctamente
        </p>

        <div id="alert-container"></div>

        <div id="rfid-waiting-area">
            <div class="rfid-icon-container">
                <div class="rfid-waves">
                    <div class="wave"></div>
                    <div class="wave"></div>
                    <div class="wave"></div>
                </div>
                <i class="bi bi-credit-card-2-back rfid-icon"></i>
            </div>

            <p class="instruction-text">Acerque su tarjeta RFID</p>
            <p class="help-text">El sistema detectará automáticamente su tarjeta</p>

            <div class="rfid-input-container">
                <input
                    type="text"
                    class="rfid-input"
                    id="rfid-code"
                    placeholder="Escaneando..."
                    autocomplete="off"
                    autofocus
                >
                <small class="text-muted">O ingrese el código manualmente</small>
            </div>

            <button class="btn-cancel" onclick="cancelRfid()">
                <i class="bi bi-x-circle"></i> Cancelar
            </button>
        </div>

        <div class="loading" id="loading">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Verificando...</span>
            </div>
            <p class="mt-2">Verificando RFID...</p>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <script>
        let rfidBuffer = '';
        let rfidTimeout = null;

        // Configurar CSRF token para AJAX
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Focus automático en el input
        $('#rfid-code').focus();

        // Detectar entrada de RFID (lectores RFID actúan como teclado)
        $('#rfid-code').on('input', function() {
            clearTimeout(rfidTimeout);

            rfidTimeout = setTimeout(function() {
                const code = $('#rfid-code').val().trim();

                if (code.length > 0) {
                    verifyRfid(code);
                }
            }, 500); // Esperar 500ms después de la última tecla
        });

        // También detectar Enter (muchos lectores RFID envían Enter al final)
        $('#rfid-code').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                const code = $(this).val().trim();
                if (code.length > 0) {
                    verifyRfid(code);
                }
            }
        });

        // Verificar código RFID
        function verifyRfid(code) {
            showLoading(true);

            $.ajax({
                url: '{{ route('pos.rfid.verify') }}',
                method: 'POST',
                data: { rfid_code: code },
                success: function(response) {
                    if (response.success) {
                        showAlert(response.message, 'success');

                        // Vibrar si está disponible
                        if (navigator.vibrate) {
                            navigator.vibrate([100, 50, 100]);
                        }

                        setTimeout(function() {
                            window.location.href = response.redirect;
                        }, 500);
                    } else {
                        showLoading(false);
                        showAlert(response.message, 'danger');
                        $('#rfid-code').val('').focus();

                        // Vibrar error
                        if (navigator.vibrate) {
                            navigator.vibrate([200, 100, 200]);
                        }
                    }
                },
                error: function(xhr) {
                    showLoading(false);
                    const message = xhr.responseJSON?.message || 'Error al verificar RFID';
                    showAlert(message, 'danger');
                    $('#rfid-code').val('').focus();

                    // Vibrar error
                    if (navigator.vibrate) {
                        navigator.vibrate([200, 100, 200]);
                    }
                }
            });
        }

        // Cancelar y volver a login
        function cancelRfid() {
            window.location.href = '{{ route('pos.login') }}';
        }

        // Mostrar/ocultar loading
        function showLoading(show) {
            if (show) {
                $('#rfid-waiting-area').hide();
                $('#loading').addClass('active');
            } else {
                $('#rfid-waiting-area').show();
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
        @if(session('success'))
            showAlert('{{ session('success') }}', 'success');
        @endif
        @if(session('error'))
            showAlert('{{ session('error') }}', 'danger');
        @endif

        // Mantener focus en el input
        setInterval(function() {
            if (!$('#rfid-code').is(':focus') && !$('#loading').hasClass('active')) {
                $('#rfid-code').focus();
            }
        }, 1000);
    </script>
</body>
</html>
