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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow: hidden;
            background: #f8f9fa;
        }

        /* Header */
        .pos-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            height: 70px;
        }

        .pos-header h1 {
            margin: 0;
            font-size: 22px;
            font-weight: 600;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .session-info {
            text-align: right;
            font-size: 13px;
        }

        .session-info strong {
            display: block;
        }

        .session-info small {
            display: block;
            opacity: 0.9;
        }

        .btn-logout-pos {
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid white;
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
        }

        .btn-logout-pos:hover {
            background: white;
            color: #667eea;
        }

        .btn-change-vendor {
            background: rgba(255, 255, 255, 0.15);
            border: 2px solid rgba(255, 255, 255, 0.6);
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
        }

        .btn-change-vendor:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: white;
        }

        /* Main Layout */
        .pos-container {
            display: flex;
            height: calc(100vh - 70px);
        }

        /* Left Panel - Services Grid */
        .services-panel {
            flex: 1;
            background: #fff;
            padding: 20px;
            overflow-y: auto;
        }

        .search-box {
            margin-bottom: 20px;
        }

        .search-box input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
        }

        .search-box input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .service-card {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            min-height: 140px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }

        .service-card .icon {
            font-size: 40px;
            margin-bottom: 10px;
        }

        .service-card .name {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 8px;
            color: #2c3e50;
        }

        .service-card .price {
            font-size: 18px;
            font-weight: 700;
            color: #27ae60;
        }

        .service-card .duration {
            font-size: 11px;
            color: #7f8c8d;
            margin-top: 5px;
        }

        /* Right Panel - Cart */
        .cart-panel {
            width: 400px;
            background: #f8f9fa;
            border-left: 2px solid #e0e0e0;
            display: flex;
            flex-direction: column;
        }

        .cart-header {
            background: #2c3e50;
            color: white;
            padding: 15px 20px;
            font-size: 18px;
            font-weight: 600;
        }

        .cart-items {
            flex: 1;
            overflow-y: auto;
            padding: 15px;
        }

        .cart-item {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .cart-item-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 10px;
        }

        .cart-item-name {
            font-weight: 600;
            color: #2c3e50;
            flex: 1;
            font-size: 14px;
        }

        .cart-item-remove {
            color: #e74c3c;
            cursor: pointer;
            font-size: 18px;
            padding: 0 5px;
        }

        .cart-item-remove:hover {
            color: #c0392b;
        }

        .cart-item-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .quantity-btn {
            background: #667eea;
            color: white;
            border: none;
            width: 28px;
            height: 28px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .quantity-btn:hover {
            background: #5568d3;
        }

        .quantity-value {
            font-weight: 600;
            min-width: 30px;
            text-align: center;
        }

        .cart-item-price {
            font-weight: 700;
            color: #27ae60;
            font-size: 16px;
        }

        .empty-cart {
            text-align: center;
            padding: 40px 20px;
            color: #95a5a6;
        }

        .empty-cart i {
            font-size: 60px;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        /* Cart Summary */
        .cart-summary {
            background: white;
            border-top: 2px solid #e0e0e0;
            padding: 20px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .summary-row.total {
            font-size: 24px;
            font-weight: 700;
            color: #2c3e50;
            padding-top: 15px;
            border-top: 2px solid #e0e0e0;
            margin-top: 15px;
        }

        .summary-label {
            color: #7f8c8d;
        }

        .summary-value {
            font-weight: 600;
        }

        .btn-checkout {
            width: 100%;
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
            color: white;
            border: none;
            padding: 15px;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 15px;
            transition: all 0.3s;
        }

        .btn-checkout:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
        }

        .btn-checkout:disabled {
            background: #bdc3c7;
            cursor: not-allowed;
            transform: none;
        }

        .btn-clear-cart {
            width: 100%;
            background: #e74c3c;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            margin-top: 10px;
            transition: all 0.3s;
        }

        .btn-clear-cart:hover {
            background: #c0392b;
        }

        /* Loading State */
        .loading {
            text-align: center;
            padding: 40px;
            color: #95a5a6;
        }

        .loading i {
            font-size: 48px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* Payment Methods */
        .payment-method {
            border: 3px solid #e0e0e0;
            border-radius: 12px;
            padding: 25px 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: white;
        }

        .payment-method:hover {
            border-color: #667eea;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .payment-method.selected {
            border-color: #27ae60;
            background: #e8f8f5;
        }

        .payment-method i {
            font-size: 40px;
            color: #667eea;
            display: block;
            margin-bottom: 10px;
        }

        .payment-method.selected i {
            color: #27ae60;
        }

        .payment-method span {
            font-weight: 600;
            color: #2c3e50;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="pos-header">
        <h1><i class="bi bi-shop"></i> Punto de Venta</h1>
        <div class="user-info">
            <div class="session-info">
                <strong>{{ $posUser->name }}</strong>
                <small>Sesión: {{ $posSession->opened_at->format('H:i') }}</small>
                <small>Duración: <span id="session-duration">{{ $posSession->formatted_duration }}</span></small>
            </div>
            <button class="btn-change-vendor" onclick="changeVendor()">
                <i class="bi bi-arrow-left-right"></i> Cambiar Vendedor
            </button>
            <button class="btn-logout-pos" onclick="logoutPos()">
                <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
            </button>
        </div>
    </div>

    <!-- Main Container -->
    <div class="pos-container">
        <!-- Left Panel - Services -->
        <div class="services-panel">
            <div class="search-box">
                <input
                    type="text"
                    id="service-search"
                    placeholder="Buscar servicio por nombre o código..."
                    autocomplete="off"
                >
            </div>

            <!-- Services Grid -->
            <div id="services-grid" class="services-grid">
                <div class="loading">
                    <i class="bi bi-arrow-repeat"></i>
                    <p>Cargando servicios...</p>
                </div>
            </div>
        </div>

        <!-- Right Panel - Cart -->
        <div class="cart-panel">
            <div class="cart-header">
                <i class="bi bi-cart3"></i> Carrito de Compra
            </div>

            <div class="cart-items" id="cart-items">
                <div class="empty-cart">
                    <i class="bi bi-cart-x"></i>
                    <p>El carrito está vacío</p>
                    <small>Agregue servicios desde el panel izquierdo</small>
                </div>
            </div>

            <div class="cart-summary">
                <div class="summary-row">
                    <span class="summary-label">Subtotal (sin IVA):</span>
                    <span class="summary-value" id="cart-subtotal">₲ 0</span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">IVA (5% + 10%):</span>
                    <span class="summary-value" id="cart-tax">₲ 0</span>
                </div>
                <div class="summary-row total">
                    <span>Total:</span>
                    <span id="cart-total">₲ 0</span>
                </div>

                <button class="btn-checkout" id="btn-checkout" disabled onclick="openCheckout()">
                    <i class="bi bi-credit-card"></i> Procesar Pago
                </button>
                <button class="btn-clear-cart" onclick="clearCart()">
                    <i class="bi bi-trash"></i> Limpiar Carrito
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de Checkout -->
    <div class="modal fade" id="checkoutModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-gradient-primary text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <h5 class="modal-title"><i class="bi bi-credit-card"></i> Procesar Pago</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Resumen de la venta -->
                    <div class="card mb-3">
                        <div class="card-body">
                            <h6 class="card-title">Resumen de la venta</h6>
                            <div class="row">
                                <div class="col-6">
                                    <small class="text-muted">Subtotal (sin IVA):</small>
                                    <div class="fw-bold" id="checkout-subtotal">₲ 0</div>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">IVA:</small>
                                    <div class="fw-bold" id="checkout-tax">₲ 0</div>
                                </div>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Total a Cobrar:</h5>
                                <h3 class="mb-0 text-success" id="checkout-total">₲ 0</h3>
                            </div>
                        </div>
                    </div>

                    <!-- Métodos de Pago -->
                    <h6>Método de Pago</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <div class="payment-method" data-method="efectivo" onclick="selectPaymentMethod('efectivo')">
                                <i class="bi bi-cash-coin"></i>
                                <span>Efectivo</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="payment-method" data-method="tarjeta" onclick="selectPaymentMethod('tarjeta')">
                                <i class="bi bi-credit-card-2-front"></i>
                                <span>Tarjeta</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="payment-method" data-method="transferencia" onclick="selectPaymentMethod('transferencia')">
                                <i class="bi bi-bank"></i>
                                <span>Transferencia</span>
                            </div>
                        </div>
                    </div>

                    <!-- Campo de monto recibido (solo para efectivo) -->
                    <div id="cash-received-section" style="display: none;" class="mb-3">
                        <label class="form-label">Monto Recibido</label>
                        <input type="text" class="form-control form-control-lg" id="cash-received" placeholder="₲ 0">
                        <div id="change-display" class="mt-2" style="display: none;">
                            <div class="alert alert-info">
                                <strong>Cambio a devolver:</strong> <span id="change-amount" class="fs-5">₲ 0</span>
                            </div>
                        </div>
                    </div>

                    <!-- Referencia (opcional) -->
                    <div class="mb-3">
                        <label class="form-label">Referencia/Nota (opcional)</label>
                        <input type="text" class="form-control" id="payment-reference" placeholder="Número de transacción, cheque, etc.">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success btn-lg" id="btn-confirm-payment" onclick="processSale()">
                        <i class="bi bi-check-circle"></i> Confirmar Venta
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <script>
        // Variables globales
        let allServices = [];
        let cart = [];
        let selectedPaymentMethod = null;
        let checkoutModal = null;

        // Configurar CSRF token
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Cargar servicios al iniciar
        $(document).ready(function() {
            loadServices();
            // Inicializar modal de checkout
            checkoutModal = new bootstrap.Modal(document.getElementById('checkoutModal'));
        });

        // Cargar items (servicios + productos) desde API
        function loadServices() {
            $.ajax({
                url: '{{ route('pos.items') }}',
                method: 'GET',
                success: function(response) {
                    allServices = response;
                    renderServices(allServices);
                },
                error: function() {
                    $('#services-grid').html(
                        '<div class="loading">' +
                        '<i class="bi bi-exclamation-triangle text-danger"></i>' +
                        '<p>Error al cargar items</p>' +
                        '</div>'
                    );
                }
            });
        }

        // Renderizar servicios en el grid
        function renderServices(services) {
            if (services.length === 0) {
                $('#services-grid').html(
                    '<div class="loading">' +
                    '<i class="bi bi-inbox"></i>' +
                    '<p>No hay servicios o productos disponibles</p>' +
                    '</div>'
                );
                return;
            }

            let html = '';
            services.forEach(service => {
                const isProduct = service.type === 'product';
                const color = service.color || (isProduct ? '#3498db' : '#667eea');
                const icon = service.icon || (isProduct ? 'bi-box-seam' : 'bi-star-fill');
                const duration = service.formatted_duration || '';
                const stockInfo = isProduct && service.stock ? `Stock: ${service.stock}` : '';

                html += `
                    <div class="service-card"
                         style="border-color: ${color};"
                         onclick="addToCart(${service.id})">
                        <div>
                            <div class="icon" style="color: ${color};">
                                <i class="bi ${icon}"></i>
                            </div>
                            <div class="name">${service.name}</div>
                            ${isProduct ? '<small class="text-muted">Producto</small>' : ''}
                        </div>
                        <div>
                            <div class="price">₲ ${formatNumber(service.price)}</div>
                            ${duration ? `<div class="duration">${duration}</div>` : ''}
                            ${stockInfo ? `<small class="text-muted">${stockInfo}</small>` : ''}
                        </div>
                    </div>
                `;
            });

            $('#services-grid').html(html);
        }

        // Buscar servicios
        $('#service-search').on('input', function() {
            const search = $(this).val().toLowerCase();

            if (search === '') {
                renderServices(allServices);
                return;
            }

            const filtered = allServices.filter(service =>
                service.name.toLowerCase().includes(search) ||
                service.code.toLowerCase().includes(search) ||
                (service.description && service.description.toLowerCase().includes(search))
            );

            renderServices(filtered);
        });

        // Agregar item (servicio o producto) al carrito
        function addToCart(serviceId) {
            const service = allServices.find(s => s.id === serviceId);
            if (!service) return;

            // Verificar si ya está en el carrito
            const existingItem = cart.find(item => item.id === serviceId && item.type === service.type);

            if (existingItem) {
                existingItem.quantity++;
            } else {
                cart.push({
                    id: service.id,
                    type: service.type,
                    name: service.name,
                    price: parseFloat(service.price),
                    tax_rate: parseInt(service.tax_rate),
                    stock: service.stock || null,
                    quantity: 1
                });
            }

            renderCart();
        }

        // Renderizar carrito
        function renderCart() {
            if (cart.length === 0) {
                $('#cart-items').html(`
                    <div class="empty-cart">
                        <i class="bi bi-cart-x"></i>
                        <p>El carrito está vacío</p>
                        <small>Agregue servicios desde el panel izquierdo</small>
                    </div>
                `);
                $('#btn-checkout').prop('disabled', true);
                updateSummary();
                return;
            }

            let html = '';
            cart.forEach((item, index) => {
                const itemTotal = item.price * item.quantity;
                html += `
                    <div class="cart-item">
                        <div class="cart-item-header">
                            <div class="cart-item-name">${item.name}</div>
                            <div class="cart-item-remove" onclick="removeFromCart(${index})">
                                <i class="bi bi-x-circle-fill"></i>
                            </div>
                        </div>
                        <div class="cart-item-details">
                            <div class="quantity-controls">
                                <button class="quantity-btn" onclick="decreaseQuantity(${index})">
                                    <i class="bi bi-dash"></i>
                                </button>
                                <span class="quantity-value">${item.quantity}</span>
                                <button class="quantity-btn" onclick="increaseQuantity(${index})">
                                    <i class="bi bi-plus"></i>
                                </button>
                            </div>
                            <div class="cart-item-price">₲ ${formatNumber(itemTotal)}</div>
                        </div>
                    </div>
                `;
            });

            $('#cart-items').html(html);
            $('#btn-checkout').prop('disabled', false);
            updateSummary();
        }

        // Aumentar cantidad
        function increaseQuantity(index) {
            cart[index].quantity++;
            renderCart();
        }

        // Disminuir cantidad
        function decreaseQuantity(index) {
            if (cart[index].quantity > 1) {
                cart[index].quantity--;
                renderCart();
            }
        }

        // Eliminar del carrito
        function removeFromCart(index) {
            cart.splice(index, 1);
            renderCart();
        }

        // Limpiar carrito
        function clearCart() {
            if (cart.length === 0) return;

            if (confirm('¿Está seguro que desea vaciar el carrito?')) {
                cart = [];
                renderCart();
            }
        }

        // Actualizar resumen (totales)
        function updateSummary() {
            let subtotal = 0;
            let totalTax = 0;

            cart.forEach(item => {
                const itemTotal = item.price * item.quantity;

                // Calcular IVA incluido en el precio (fórmula paraguaya)
                if (item.tax_rate > 0) {
                    const tax = itemTotal * item.tax_rate / (100 + item.tax_rate);
                    totalTax += tax;
                    subtotal += (itemTotal - tax);
                } else {
                    subtotal += itemTotal;
                }
            });

            const total = subtotal + totalTax;

            $('#cart-subtotal').text('₲ ' + formatNumber(subtotal));
            $('#cart-tax').text('₲ ' + formatNumber(totalTax));
            $('#cart-total').text('₲ ' + formatNumber(total));
        }

        // Formatear números con separador de miles
        function formatNumber(num) {
            return Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }

        // Abrir checkout
        function openCheckout() {
            if (cart.length === 0) return;

            // Calcular totales
            let subtotal = 0;
            let totalTax = 0;

            cart.forEach(item => {
                const itemTotal = item.price * item.quantity;
                if (item.tax_rate > 0) {
                    const tax = itemTotal * item.tax_rate / (100 + item.tax_rate);
                    totalTax += tax;
                    subtotal += (itemTotal - tax);
                } else {
                    subtotal += itemTotal;
                }
            });

            const total = subtotal + totalTax;

            // Actualizar resumen en modal
            $('#checkout-subtotal').text('₲ ' + formatNumber(subtotal));
            $('#checkout-tax').text('₲ ' + formatNumber(totalTax));
            $('#checkout-total').text('₲ ' + formatNumber(total));

            // Resetear selección
            selectedPaymentMethod = null;
            $('.payment-method').removeClass('selected');
            $('#cash-received-section').hide();
            $('#change-display').hide();
            $('#cash-received').val('');
            $('#payment-reference').val('');
            $('#btn-confirm-payment').prop('disabled', true);

            // Abrir modal
            checkoutModal.show();
        }

        // Seleccionar método de pago
        function selectPaymentMethod(method) {
            selectedPaymentMethod = method;

            // Actualizar UI
            $('.payment-method').removeClass('selected');
            $(`.payment-method[data-method="${method}"]`).addClass('selected');

            // Mostrar/ocultar campo de efectivo
            if (method === 'efectivo') {
                $('#cash-received-section').show();
                $('#cash-received').focus();
            } else {
                $('#cash-received-section').hide();
                $('#change-display').hide();
                $('#btn-confirm-payment').prop('disabled', false);
            }
        }

        // Calcular cambio (efectivo)
        $('#cash-received').on('input', function() {
            let received = parseFloat($(this).val().replace(/\D/g, '')) || 0;
            let total = 0;

            cart.forEach(item => {
                total += item.price * item.quantity;
            });

            if (received >= total) {
                let change = received - total;
                $('#change-amount').text('₲ ' + formatNumber(change));
                $('#change-display').show();
                $('#btn-confirm-payment').prop('disabled', false);
            } else {
                $('#change-display').hide();
                $('#btn-confirm-payment').prop('disabled', true);
            }
        });

        // Procesar venta
        function processSale() {
            if (!selectedPaymentMethod) {
                alert('Por favor seleccione un método de pago');
                return;
            }

            if (cart.length === 0) {
                alert('El carrito está vacío');
                return;
            }

            // Preparar datos de la venta
            const saleData = {
                items: cart.map(item => ({
                    type: item.type,
                    id: item.id,
                    quantity: item.quantity,
                    unit_price: item.price,
                    tax_rate: item.tax_rate
                })),
                payment_method: selectedPaymentMethod,
                notes: $('#payment-reference').val()
            };

            // Deshabilitar botón
            $('#btn-confirm-payment').prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Procesando...');

            // Enviar venta
            $.ajax({
                url: '{{ route('pos.sales.store') }}',
                method: 'POST',
                data: saleData,
                success: function(response) {
                    if (response.success) {
                        // Cerrar modal
                        checkoutModal.hide();

                        // Mostrar mensaje de éxito
                        alert('Pre-venta creada exitosamente!\n\nNúmero: ' + response.sale.sale_number + '\nTotal: ₲ ' + formatNumber(response.sale.total) + '\n\nEstado: BORRADOR\n\nDebe confirmarse desde el módulo de Ventas para descontar stock.');

                        // Limpiar carrito
                        cart = [];
                        renderCart();

                        // TODO: Abrir modal de impresión de ticket
                    }
                },
                error: function(xhr) {
                    alert('Error al procesar la venta: ' + (xhr.responseJSON?.message || 'Error desconocido'));
                    $('#btn-confirm-payment').prop('disabled', false).html('<i class="bi bi-check-circle"></i> Confirmar Venta');
                }
            });
        }

        // Cambiar de vendedor (logout rápido al POS login)
        function changeVendor() {
            if (cart.length > 0) {
                if (!confirm('Hay items en el carrito que se perderán. ¿Desea continuar?')) {
                    return;
                }
            }

            if (confirm('¿Cambiar de vendedor?\n\nSe cerrará tu sesión y podrás ingresar con otro PIN.')) {
                $.ajax({
                    url: '{{ route('pos.logout') }}',
                    method: 'POST',
                    success: function(response) {
                        if (response.success) {
                            // Redirige al login POS (solo PIN)
                            window.location.href = response.redirect;
                        }
                    },
                    error: function() {
                        alert('Error al cambiar de vendedor');
                    }
                });
            }
        }

        // Cerrar sesión POS
        function logoutPos() {
            if (cart.length > 0) {
                if (!confirm('Hay items en el carrito. ¿Está seguro que desea cerrar la sesión?')) {
                    return;
                }
            }

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

        // Verificar sesión cada 30 segundos
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
                        $('#session-duration').text(response.duration);
                    }
                },
                error: function() {
                    console.error('Error al verificar sesión');
                }
            });
        }, 30000);
    </script>
</body>
</html>
