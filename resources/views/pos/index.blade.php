<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>POS - Neo ERP</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
        body {
            background: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .pos-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .pos-header h1 {
            margin: 0;
            font-size: 24px;
        }

        .pos-header .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .session-info {
            text-align: right;
            font-size: 14px;
        }

        .session-info small {
            display: block;
            opacity: 0.9;
        }

        .btn-logout-pos {
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid white;
            color: white;
            padding: 8px 20px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-logout-pos:hover {
            background: white;
            color: #667eea;
        }

        .pos-content {
            padding: 40px;
            text-align: center;
        }

        .coming-soon {
            max-width: 600px;
            margin: 0 auto;
            padding: 60px 40px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .coming-soon i {
            font-size: 100px;
            color: #667eea;
            margin-bottom: 30px;
        }

        .coming-soon h2 {
            color: #2c3e50;
            margin-bottom: 20px;
        }

        .coming-soon p {
            color: #7f8c8d;
            font-size: 18px;
            margin-bottom: 30px;
        }

        .status-badge {
            display: inline-block;
            background: #27ae60;
            color: white;
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 14px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="pos-header">
        <h1><i class="bi bi-shop"></i> NEO ERP - Punto de Venta</h1>
        <div class="user-info">
            <div class="session-info">
                <strong>{{ $posUser->name }}</strong>
                <small>Sesión iniciada: {{ $posSession->opened_at->format('H:i') }}</small>
                <small>Duración: <span id="session-duration">{{ $posSession->formatted_duration }}</span></small>
            </div>
            <button class="btn-logout-pos" onclick="logoutPos()">
                <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
            </button>
        </div>
    </div>

    <div class="pos-content">
        <div class="coming-soon">
            <i class="bi bi-tools"></i>
            <h2>Interfaz POS en Construcción</h2>
            <p>
                La autenticación del POS está funcionando correctamente.
                La interfaz completa de ventas se implementará en la Fase 4.
            </p>
            <div class="status-badge">
                <i class="bi bi-check-circle"></i> Fase 3 Completada
            </div>
            <hr style="margin: 40px 0;">
            <div style="text-align: left;">
                <h5 style="color: #2c3e50; margin-bottom: 15px;">✅ Implementado:</h5>
                <ul style="color: #7f8c8d; list-style: none; padding: 0;">
                    <li style="margin-bottom: 10px;"><i class="bi bi-check-circle-fill text-success"></i> Login con PIN</li>
                    <li style="margin-bottom: 10px;"><i class="bi bi-check-circle-fill text-success"></i> Autenticación 2FA (PIN + RFID)</li>
                    <li style="margin-bottom: 10px;"><i class="bi bi-check-circle-fill text-success"></i> Gestión de sesiones</li>
                    <li style="margin-bottom: 10px;"><i class="bi bi-check-circle-fill text-success"></i> Timeout automático (10 min)</li>
                    <li style="margin-bottom: 10px;"><i class="bi bi-check-circle-fill text-success"></i> Middleware de seguridad</li>
                </ul>
                <h5 style="color: #2c3e50; margin: 30px 0 15px 0;">⏳ Próxima Fase:</h5>
                <ul style="color: #7f8c8d; list-style: none; padding: 0;">
                    <li style="margin-bottom: 10px;"><i class="bi bi-hourglass-split text-warning"></i> Grid de servicios/productos</li>
                    <li style="margin-bottom: 10px;"><i class="bi bi-hourglass-split text-warning"></i> Carrito de compra</li>
                    <li style="margin-bottom: 10px;"><i class="bi bi-hourglass-split text-warning"></i> Cálculo de totales</li>
                    <li style="margin-bottom: 10px;"><i class="bi bi-hourglass-split text-warning"></i> Métodos de pago</li>
                    <li style="margin-bottom: 10px;"><i class="bi bi-hourglass-split text-warning"></i> Impresión de ticket</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <script>
        // Configurar CSRF token para AJAX
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Cerrar sesión POS
        function logoutPos() {
            if (confirm('¿Está seguro que desea cerrar la sesión del POS?')) {
                $.ajax({
                    url: '{{ route('pos.logout') }}',
                    method: 'POST',
                    success: function(response) {
                        if (response.success) {
                            window.location.href = response.redirect;
                        }
                    },
                    error: function() {
                        alert('Error al cerrar sesión');
                    }
                });
            }
        }

        // Verificar sesión cada 30 segundos (detectar timeout)
        setInterval(function() {
            $.ajax({
                url: '{{ route('pos.check.session') }}',
                method: 'POST',
                success: function(response) {
                    if (!response.active) {
                        if (response.expired) {
                            alert('Su sesión ha expirado por inactividad');
                        }
                        window.location.href = '{{ route('pos.login') }}';
                    } else {
                        // Actualizar duración de sesión
                        $('#session-duration').text(response.duration);
                    }
                },
                error: function() {
                    console.error('Error al verificar sesión');
                }
            });
        }, 30000); // Cada 30 segundos
    </script>
</body>
</html>
