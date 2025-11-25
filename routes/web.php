<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\InventoryAdjustmentController;
use App\Http\Controllers\AccountReceivableController;
use App\Http\Controllers\AccountPayableController;
use App\Http\Controllers\CashRegisterController;
use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\BankTransactionController;
use App\Http\Controllers\BankController;
use App\Http\Controllers\CheckController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

// Rutas publicas
Route::get('/', function () {
    return redirect('/login');
});

// Rutas de autenticacion
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Rutas protegidas
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Usuarios
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/data', [UserController::class, 'data'])->name('users.data');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::post('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    
    // Roles
    Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
    Route::get('/roles/data', [RoleController::class, 'data'])->name('roles.data');
    Route::get('/roles/list', [RoleController::class, 'list'])->name('roles.list');
    Route::get('/roles/permissions', [RoleController::class, 'permissions'])->name('roles.permissions');
    Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
    Route::get('/roles/{role}', [RoleController::class, 'show'])->name('roles.show');
    Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
    
    // Clientes
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/data', [CustomerController::class, 'data'])->name('customers.data');
    Route::get('/customers/list', [CustomerController::class, 'list'])->name('customers.list');
    Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
    Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');
    
    // Proveedores
    Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
    Route::get('/suppliers/data', [SupplierController::class, 'data'])->name('suppliers.data');
    Route::get('/suppliers/list', [SupplierController::class, 'list'])->name('suppliers.list');
    Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
    Route::get('/suppliers/{supplier}', [SupplierController::class, 'show'])->name('suppliers.show');
    
    // Categorías
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('/categories/data', [CategoryController::class, 'data'])->name('categories.data');
    Route::get('/categories/list', [CategoryController::class, 'list'])->name('categories.list');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::get('/categories/{category}', [CategoryController::class, 'show'])->name('categories.show');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
    
    // Productos
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/data', [ProductController::class, 'data'])->name('products.data');
    Route::get('/products/list', [ProductController::class, 'list'])->name('products.list');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
    Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');

    // Ventas
    Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
    Route::get('/sales/data', [SaleController::class, 'data'])->name('sales.data');
    Route::get('/sales/create', [SaleController::class, 'create'])->name('sales.create');
    Route::post('/sales', [SaleController::class, 'store'])->name('sales.store');
    Route::get('/sales/{sale}', [SaleController::class, 'show'])->name('sales.show');
    Route::get('/sales/{sale}/detail', [SaleController::class, 'detail'])->name('sales.detail');
    Route::post('/sales/{sale}/confirm', [SaleController::class, 'confirm'])->name('sales.confirm');
    Route::post('/sales/{sale}/cancel', [SaleController::class, 'cancel'])->name('sales.cancel');
    Route::delete('/sales/{sale}', [SaleController::class, 'destroy'])->name('sales.destroy');

    // Compras
    Route::get('/purchases', [PurchaseController::class, 'index'])->name('purchases.index');
    Route::get('/purchases/data', [PurchaseController::class, 'data'])->name('purchases.data');
    Route::get('/purchases/create', [PurchaseController::class, 'create'])->name('purchases.create');
    Route::post('/purchases', [PurchaseController::class, 'store'])->name('purchases.store');
    Route::get('/purchases/{purchase}', [PurchaseController::class, 'show'])->name('purchases.show');
    Route::get('/purchases/{purchase}/detail', [PurchaseController::class, 'detail'])->name('purchases.detail');
    Route::post('/purchases/{purchase}/confirm', [PurchaseController::class, 'confirm'])->name('purchases.confirm');
    Route::post('/purchases/{purchase}/cancel', [PurchaseController::class, 'cancel'])->name('purchases.cancel');
    Route::delete('/purchases/{purchase}', [PurchaseController::class, 'destroy'])->name('purchases.destroy');

    // Categorías de Gastos
    Route::get('/expense-categories', [ExpenseCategoryController::class, 'index'])->name('expense-categories.index');
    Route::get('/expense-categories/data', [ExpenseCategoryController::class, 'data'])->name('expense-categories.data');
    Route::get('/expense-categories/list', [ExpenseCategoryController::class, 'list'])->name('expense-categories.list');
    Route::post('/expense-categories', [ExpenseCategoryController::class, 'store'])->name('expense-categories.store');
    Route::get('/expense-categories/{expenseCategory}', [ExpenseCategoryController::class, 'show'])->name('expense-categories.show');
    Route::put('/expense-categories/{expenseCategory}', [ExpenseCategoryController::class, 'update'])->name('expense-categories.update');
    Route::delete('/expense-categories/{expenseCategory}', [ExpenseCategoryController::class, 'destroy'])->name('expense-categories.destroy');

    // Gastos
    Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
    Route::get('/expenses/data', [ExpenseController::class, 'data'])->name('expenses.data');
    Route::get('/expenses/create', [ExpenseController::class, 'create'])->name('expenses.create');
    Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
    Route::get('/expenses/{expense}', [ExpenseController::class, 'show'])->name('expenses.show');
    Route::get('/expenses/{expense}/edit', [ExpenseController::class, 'edit'])->name('expenses.edit');
    Route::put('/expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
    Route::post('/expenses/{expense}/pay', [ExpenseController::class, 'pay'])->name('expenses.pay');
    Route::post('/expenses/{expense}/cancel', [ExpenseController::class, 'cancel'])->name('expenses.cancel');
    Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');

    // Reportes
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
    Route::get('/reports/purchases', [ReportController::class, 'purchases'])->name('reports.purchases');
    Route::get('/reports/expenses', [ReportController::class, 'expenses'])->name('reports.expenses');
    Route::get('/reports/inventory', [ReportController::class, 'inventory'])->name('reports.inventory');
    Route::get('/reports/summary', [ReportController::class, 'summary'])->name('reports.summary');

    // Configuración
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');

    // Ajustes de Inventario
    Route::get('/inventory-adjustments', [InventoryAdjustmentController::class, 'index'])->name('inventory-adjustments.index');
    Route::get('/inventory-adjustments/data', [InventoryAdjustmentController::class, 'data'])->name('inventory-adjustments.data');
    Route::get('/inventory-adjustments/create', [InventoryAdjustmentController::class, 'create'])->name('inventory-adjustments.create');
    Route::post('/inventory-adjustments', [InventoryAdjustmentController::class, 'store'])->name('inventory-adjustments.store');
    Route::get('/inventory-adjustments/{inventoryAdjustment}', [InventoryAdjustmentController::class, 'show'])->name('inventory-adjustments.show');
    Route::post('/inventory-adjustments/{inventoryAdjustment}/confirm', [InventoryAdjustmentController::class, 'confirm'])->name('inventory-adjustments.confirm');
    Route::post('/inventory-adjustments/{inventoryAdjustment}/cancel', [InventoryAdjustmentController::class, 'cancel'])->name('inventory-adjustments.cancel');
    Route::delete('/inventory-adjustments/{inventoryAdjustment}', [InventoryAdjustmentController::class, 'destroy'])->name('inventory-adjustments.destroy');

    // Cuentas por Cobrar
    Route::get('/account-receivables', [AccountReceivableController::class, 'index'])->name('account-receivables.index');
    Route::get('/account-receivables/data', [AccountReceivableController::class, 'data'])->name('account-receivables.data');
    Route::get('/account-receivables/by-customer', [AccountReceivableController::class, 'byCustomer'])->name('account-receivables.by-customer');
    Route::get('/account-receivables/create', [AccountReceivableController::class, 'create'])->name('account-receivables.create');
    Route::post('/account-receivables', [AccountReceivableController::class, 'store'])->name('account-receivables.store');
    Route::get('/account-receivables/{accountReceivable}', [AccountReceivableController::class, 'show'])->name('account-receivables.show');
    Route::post('/account-receivables/{accountReceivable}/add-payment', [AccountReceivableController::class, 'addPayment'])->name('account-receivables.add-payment');
    Route::post('/account-receivables/{accountReceivable}/cancel', [AccountReceivableController::class, 'cancel'])->name('account-receivables.cancel');
    Route::delete('/account-receivables/{accountReceivable}', [AccountReceivableController::class, 'destroy'])->name('account-receivables.destroy');

    // Cuentas por Pagar
    Route::get('/account-payables', [AccountPayableController::class, 'index'])->name('account-payables.index');
    Route::get('/account-payables/data', [AccountPayableController::class, 'data'])->name('account-payables.data');
    Route::get('/account-payables/by-supplier', [AccountPayableController::class, 'bySupplier'])->name('account-payables.by-supplier');
    Route::get('/account-payables/create', [AccountPayableController::class, 'create'])->name('account-payables.create');
    Route::post('/account-payables', [AccountPayableController::class, 'store'])->name('account-payables.store');
    Route::get('/account-payables/{accountPayable}', [AccountPayableController::class, 'show'])->name('account-payables.show');
    Route::post('/account-payables/{accountPayable}/add-payment', [AccountPayableController::class, 'addPayment'])->name('account-payables.add-payment');
    Route::post('/account-payables/{accountPayable}/cancel', [AccountPayableController::class, 'cancel'])->name('account-payables.cancel');
    Route::delete('/account-payables/{accountPayable}', [AccountPayableController::class, 'destroy'])->name('account-payables.destroy');

    // Caja
    Route::get('/cash-registers', [CashRegisterController::class, 'index'])->name('cash-registers.index');
    Route::get('/cash-registers/data', [CashRegisterController::class, 'data'])->name('cash-registers.data');
    Route::get('/cash-registers/current', [CashRegisterController::class, 'current'])->name('cash-registers.current');
    Route::post('/cash-registers/open', [CashRegisterController::class, 'open'])->name('cash-registers.open');
    Route::get('/cash-registers/{cashRegister}', [CashRegisterController::class, 'show'])->name('cash-registers.show');
    Route::post('/cash-registers/{cashRegister}/add-movement', [CashRegisterController::class, 'addMovement'])->name('cash-registers.add-movement');
    Route::post('/cash-registers/{cashRegister}/close', [CashRegisterController::class, 'close'])->name('cash-registers.close');

    // Bancos - Catálogo de Bancos
    Route::get('/banks', [BankController::class, 'index'])->name('banks.index');
    Route::get('/banks/list', [BankController::class, 'list'])->name('banks.list');
    Route::get('/banks/active', [BankController::class, 'getActive'])->name('banks.active');
    Route::post('/banks', [BankController::class, 'store'])->name('banks.store');
    Route::get('/banks/{bank}', [BankController::class, 'show'])->name('banks.show');
    Route::put('/banks/{bank}', [BankController::class, 'update'])->name('banks.update');
    Route::delete('/banks/{bank}', [BankController::class, 'destroy'])->name('banks.destroy');

    // Bancos - Cuentas Bancarias
    Route::get('/bank-accounts', [BankAccountController::class, 'index'])->name('bank-accounts.index');
    Route::get('/bank-accounts/data', [BankAccountController::class, 'data'])->name('bank-accounts.data');
    Route::get('/bank-accounts/list', [BankAccountController::class, 'list'])->name('bank-accounts.list');
    Route::post('/bank-accounts', [BankAccountController::class, 'store'])->name('bank-accounts.store');
    Route::get('/bank-accounts/{bankAccount}', [BankAccountController::class, 'show'])->name('bank-accounts.show');
    Route::put('/bank-accounts/{bankAccount}', [BankAccountController::class, 'update'])->name('bank-accounts.update');
    Route::post('/bank-accounts/{bankAccount}/toggle-status', [BankAccountController::class, 'toggleStatus'])->name('bank-accounts.toggle-status');
    Route::post('/bank-accounts/{bankAccount}/set-default', [BankAccountController::class, 'setDefault'])->name('bank-accounts.set-default');
    Route::get('/bank-accounts/{bankAccount}/reconciliation', [BankAccountController::class, 'reconciliation'])->name('bank-accounts.reconciliation');
    Route::post('/bank-accounts/{bankAccount}/reconcile', [BankAccountController::class, 'reconcile'])->name('bank-accounts.reconcile');

    // Bancos - Transacciones
    Route::get('/bank-transactions', [BankTransactionController::class, 'index'])->name('bank-transactions.index');
    Route::get('/bank-transactions/data', [BankTransactionController::class, 'data'])->name('bank-transactions.data');
    Route::get('/bank-transactions/create', [BankTransactionController::class, 'create'])->name('bank-transactions.create');
    Route::post('/bank-transactions', [BankTransactionController::class, 'store'])->name('bank-transactions.store');
    Route::post('/bank-transactions/transfer', [BankTransactionController::class, 'transfer'])->name('bank-transactions.transfer');
    Route::post('/bank-transactions/cash-deposit', [BankTransactionController::class, 'cashDeposit'])->name('bank-transactions.cash-deposit');
    Route::post('/bank-transactions/cash-withdrawal', [BankTransactionController::class, 'cashWithdrawal'])->name('bank-transactions.cash-withdrawal');
    Route::get('/bank-transactions/{bankTransaction}', [BankTransactionController::class, 'show'])->name('bank-transactions.show');
    Route::post('/bank-transactions/{bankTransaction}/cancel', [BankTransactionController::class, 'cancel'])->name('bank-transactions.cancel');

    // Bancos - Cheques
    Route::get('/checks', [CheckController::class, 'index'])->name('checks.index');
    Route::get('/checks/data', [CheckController::class, 'data'])->name('checks.data');
    Route::get('/checks/create', [CheckController::class, 'create'])->name('checks.create');
    Route::post('/checks', [CheckController::class, 'store'])->name('checks.store');
    Route::get('/checks/{check}', [CheckController::class, 'show'])->name('checks.show');
    Route::post('/checks/{check}/deposit', [CheckController::class, 'depositCheck'])->name('checks.deposit');
    Route::post('/checks/{check}/cash', [CheckController::class, 'cashCheck'])->name('checks.cash');
    Route::post('/checks/{check}/bounce', [CheckController::class, 'bounceCheck'])->name('checks.bounce');
    Route::post('/checks/{check}/cancel', [CheckController::class, 'cancel'])->name('checks.cancel');
});
