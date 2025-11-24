<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Neo ERP')</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- jEasyUI CSS -->
    <link rel="stylesheet" type="text/css" href="https://www.jeasyui.com/easyui/themes/default/easyui.css">
    <link rel="stylesheet" type="text/css" href="https://www.jeasyui.com/easyui/themes/icon.css">
    
    <style>
        :root {
            --sidebar-width: 250px;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: #2c3e50;
            transition: all 0.3s;
            z-index: 1000;
        }
        
        .sidebar .brand {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar .brand h4 {
            color: #fff;
            margin: 0;
        }
        
        .sidebar .nav-menu {
            padding: 0;
            margin: 0;
            list-style: none;
        }
        
        .sidebar .nav-menu li a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .sidebar .nav-menu li a:hover,
        .sidebar .nav-menu li a.active {
            background: rgba(255,255,255,0.1);
            color: #fff;
        }
        
        .sidebar .nav-menu li a i {
            margin-right: 10px;
            font-size: 1.1rem;
        }

        .sidebar .nav-menu .submenu {
            list-style: none;
            padding-left: 0;
            margin: 0;
            display: none;
        }

        .sidebar .nav-menu .submenu.show {
            display: block;
        }

        .sidebar .nav-menu .submenu li a {
            padding-left: 50px;
            font-size: 0.9rem;
        }

        .sidebar .nav-menu .has-submenu > a::after {
            content: '\F282';
            font-family: 'bootstrap-icons';
            float: right;
            transition: transform 0.3s;
        }

        .sidebar .nav-menu .has-submenu.open > a::after {
            transform: rotate(90deg);
        }
        
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            background: #f8f9fa;
        }
        
        .top-navbar {
            background: #fff;
            padding: 15px 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .content-wrapper {
            padding: 20px;
        }
        
        .card {
            border: none;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }
        
        /* jEasyUI customizations */
        .datagrid-header td,
        .datagrid-body td {
            font-size: 13px;
        }
        
        .panel-title {
            font-weight: 600;
        }
    </style>
    
    @stack('styles')
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="brand">
            <h4><i class="bi bi-box-seam"></i> Neo ERP</h4>
        </div>
        <ul class="nav-menu">
            <li>
                <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="{{ route('customers.index') }}" class="{{ request()->routeIs('customers.*') ? 'active' : '' }}">
                    <i class="bi bi-people"></i> Clientes
                </a>
            </li>
            <li>
                <a href="{{ route('suppliers.index') }}" class="{{ request()->routeIs('suppliers.*') ? 'active' : '' }}">
                    <i class="bi bi-truck"></i> Proveedores
                </a>
            </li>
            <li class="has-submenu {{ request()->routeIs('products.*', 'categories.*', 'inventory-adjustments.*') ? 'open' : '' }}">
                <a href="javascript:void(0)" class="{{ request()->routeIs('products.*', 'categories.*', 'inventory-adjustments.*') ? 'active' : '' }}">
                    <i class="bi bi-box"></i> Inventario
                </a>
                <ul class="submenu {{ request()->routeIs('products.*', 'categories.*', 'inventory-adjustments.*') ? 'show' : '' }}">
                    <li>
                        <a href="{{ route('products.index') }}" class="{{ request()->routeIs('products.*') ? 'active' : '' }}">
                            <i class="bi bi-box-seam"></i> Productos
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('categories.index') }}" class="{{ request()->routeIs('categories.*') ? 'active' : '' }}">
                            <i class="bi bi-tags"></i> Categorías
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('inventory-adjustments.index') }}" class="{{ request()->routeIs('inventory-adjustments.*') ? 'active' : '' }}">
                            <i class="bi bi-sliders"></i> Ajustes
                        </a>
                    </li>
                </ul>
            </li>
            <li class="has-submenu {{ request()->routeIs('sales.*') ? 'open' : '' }}">
                <a href="javascript:void(0)" class="{{ request()->routeIs('sales.*') ? 'active' : '' }}">
                    <i class="bi bi-cart-check"></i> Ventas
                </a>
                <ul class="submenu {{ request()->routeIs('sales.*') ? 'show' : '' }}">
                    <li>
                        <a href="{{ route('sales.create') }}" class="{{ request()->routeIs('sales.create') ? 'active' : '' }}">
                            <i class="bi bi-plus-circle"></i> Nueva Factura
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('sales.index') }}" class="{{ request()->routeIs('sales.index') ? 'active' : '' }}">
                            <i class="bi bi-list-ul"></i> Listado
                        </a>
                    </li>
                </ul>
            </li>
            <li class="has-submenu {{ request()->routeIs('purchases.*') ? 'open' : '' }}">
                <a href="javascript:void(0)" class="{{ request()->routeIs('purchases.*') ? 'active' : '' }}">
                    <i class="bi bi-bag"></i> Compras
                </a>
                <ul class="submenu {{ request()->routeIs('purchases.*') ? 'show' : '' }}">
                    <li>
                        <a href="{{ route('purchases.create') }}" class="{{ request()->routeIs('purchases.create') ? 'active' : '' }}">
                            <i class="bi bi-plus-circle"></i> Nueva Compra
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('purchases.index') }}" class="{{ request()->routeIs('purchases.index') ? 'active' : '' }}">
                            <i class="bi bi-list-ul"></i> Listado
                        </a>
                    </li>
                </ul>
            </li>
            <li class="has-submenu {{ request()->routeIs('expenses.*', 'expense-categories.*') ? 'open' : '' }}">
                <a href="javascript:void(0)" class="{{ request()->routeIs('expenses.*', 'expense-categories.*') ? 'active' : '' }}">
                    <i class="bi bi-cash-stack"></i> Gastos
                </a>
                <ul class="submenu {{ request()->routeIs('expenses.*', 'expense-categories.*') ? 'show' : '' }}">
                    <li>
                        <a href="{{ route('expenses.create') }}" class="{{ request()->routeIs('expenses.create') ? 'active' : '' }}">
                            <i class="bi bi-plus-circle"></i> Nuevo Gasto
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('expenses.index') }}" class="{{ request()->routeIs('expenses.index') ? 'active' : '' }}">
                            <i class="bi bi-list-ul"></i> Listado
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('expense-categories.index') }}" class="{{ request()->routeIs('expense-categories.*') ? 'active' : '' }}">
                            <i class="bi bi-tags"></i> Categorías
                        </a>
                    </li>
                </ul>
            </li>
            <li class="has-submenu {{ request()->routeIs('reports.*') ? 'open' : '' }}">
                <a href="javascript:void(0)" class="{{ request()->routeIs('reports.*') ? 'active' : '' }}">
                    <i class="bi bi-file-bar-graph"></i> Reportes
                </a>
                <ul class="submenu {{ request()->routeIs('reports.*') ? 'show' : '' }}">
                    <li>
                        <a href="{{ route('reports.index') }}" class="{{ request()->routeIs('reports.index') ? 'active' : '' }}">
                            <i class="bi bi-grid"></i> Centro de Reportes
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('reports.sales') }}" class="{{ request()->routeIs('reports.sales') ? 'active' : '' }}">
                            <i class="bi bi-cart-check"></i> Ventas
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('reports.purchases') }}" class="{{ request()->routeIs('reports.purchases') ? 'active' : '' }}">
                            <i class="bi bi-bag"></i> Compras
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('reports.expenses') }}" class="{{ request()->routeIs('reports.expenses') ? 'active' : '' }}">
                            <i class="bi bi-cash-stack"></i> Gastos
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('reports.inventory') }}" class="{{ request()->routeIs('reports.inventory') ? 'active' : '' }}">
                            <i class="bi bi-box-seam"></i> Inventario
                        </a>
                    </li>
                </ul>
            </li>
            <li>
                <a href="{{ route('account-receivables.index') }}" class="{{ request()->routeIs('account-receivables.*') ? 'active' : '' }}">
                    <i class="bi bi-wallet2"></i> Cuentas por Cobrar
                </a>
            </li>
            <li>
                <a href="{{ route('account-payables.index') }}" class="{{ request()->routeIs('account-payables.*') ? 'active' : '' }}">
                    <i class="bi bi-credit-card"></i> Cuentas por Pagar
                </a>
            </li>
            <li>
                <a href="{{ route('cash-registers.current') }}" class="{{ request()->routeIs('cash-registers.*') ? 'active' : '' }}">
                    <i class="bi bi-cash-coin"></i> Caja
                </a>
            </li>
            <li class="has-submenu {{ request()->routeIs('bank-accounts.*', 'bank-transactions.*', 'checks.*') ? 'open' : '' }}">
                <a href="javascript:void(0)" class="{{ request()->routeIs('bank-accounts.*', 'bank-transactions.*', 'checks.*') ? 'active' : '' }}">
                    <i class="bi bi-bank"></i> Bancos
                </a>
                <ul class="submenu {{ request()->routeIs('bank-accounts.*', 'bank-transactions.*', 'checks.*') ? 'show' : '' }}">
                    <li>
                        <a href="{{ route('bank-accounts.index') }}" class="{{ request()->routeIs('bank-accounts.*') ? 'active' : '' }}">
                            <i class="bi bi-credit-card-2-front"></i> Cuentas Bancarias
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('bank-transactions.index') }}" class="{{ request()->routeIs('bank-transactions.*') ? 'active' : '' }}">
                            <i class="bi bi-arrow-left-right"></i> Movimientos
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('checks.index') }}" class="{{ request()->routeIs('checks.*') ? 'active' : '' }}">
                            <i class="bi bi-receipt"></i> Cheques
                        </a>
                    </li>
                </ul>
            </li>
            <li>
                <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') ? 'active' : '' }}">
                    <i class="bi bi-person-badge"></i> Usuarios
                </a>
            </li>
            <li>
                <a href="{{ route('roles.index') }}" class="{{ request()->routeIs('roles.*') ? 'active' : '' }}">
                    <i class="bi bi-shield-lock"></i> Roles
                </a>
            </li>
            <li>
                <a href="{{ route('settings.index') }}" class="{{ request()->routeIs('settings.*') ? 'active' : '' }}">
                    <i class="bi bi-gear"></i> Configuraci&oacute;n
                </a>
            </li>
        </ul>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <div class="top-navbar">
            <div>
                <h5 class="mb-0">@yield('page-title', 'Dashboard')</h5>
            </div>
            <div class="d-flex align-items-center">
                <span class="me-3">{{ auth()->user()->name }}</span>
                <div class="dropdown">
                    <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i>Perfil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesi&oacute;n
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Content -->
        <div class="content-wrapper">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            
            @yield('content')
        </div>
    </div>
    
    <!-- jQuery (required by jEasyUI) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jEasyUI JS -->
    <script type="text/javascript" src="https://www.jeasyui.com/easyui/jquery.easyui.min.js"></script>
    <script type="text/javascript" src="https://www.jeasyui.com/easyui/locale/easyui-lang-es.js"></script>
    
    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Toggle submenu
        $(document).ready(function() {
            $('.has-submenu > a').click(function(e) {
                e.preventDefault();
                var $parent = $(this).parent();
                $parent.toggleClass('open');
                $parent.find('.submenu').toggleClass('show');
            });
        });
    </script>
    
    @stack('scripts')
</body>
</html>
