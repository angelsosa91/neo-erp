<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index');
    }

    /**
     * Reporte de ventas
     */
    public function sales(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $status = $request->get('status', '');
        $customerId = $request->get('customer_id', '');

        $query = Sale::with(['customer', 'user'])
            ->whereBetween('sale_date', [$startDate, $endDate])
            ->when($status, function ($q) use ($status) {
                $q->where('status', $status);
            })
            ->when($customerId, function ($q) use ($customerId) {
                $q->where('customer_id', $customerId);
            })
            ->orderBy('sale_date', 'desc');

        $sales = $query->get();

        $totals = [
            'count' => $sales->count(),
            'subtotal_exento' => $sales->sum('subtotal_exento'),
            'subtotal_5' => $sales->sum('subtotal_5'),
            'iva_5' => $sales->sum('iva_5'),
            'subtotal_10' => $sales->sum('subtotal_10'),
            'iva_10' => $sales->sum('iva_10'),
            'total' => $sales->sum('total'),
        ];

        if ($request->ajax()) {
            return response()->json([
                'sales' => $sales->map(function ($sale) {
                    return [
                        'id' => $sale->id,
                        'sale_number' => $sale->sale_number,
                        'sale_date' => $sale->sale_date->format('d/m/Y'),
                        'customer_name' => $sale->customer->name ?? 'Sin cliente',
                        'subtotal_exento' => $sale->subtotal_exento,
                        'subtotal_5' => $sale->subtotal_5,
                        'iva_5' => $sale->iva_5,
                        'subtotal_10' => $sale->subtotal_10,
                        'iva_10' => $sale->iva_10,
                        'total' => $sale->total,
                        'status' => $sale->status,
                        'user_name' => $sale->user->name ?? '',
                    ];
                }),
                'totals' => $totals,
            ]);
        }

        return view('reports.sales', compact('startDate', 'endDate', 'status', 'customerId'));
    }

    /**
     * Reporte de compras
     */
    public function purchases(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $status = $request->get('status', '');
        $supplierId = $request->get('supplier_id', '');

        $query = Purchase::with(['supplier', 'user'])
            ->whereBetween('purchase_date', [$startDate, $endDate])
            ->when($status, function ($q) use ($status) {
                $q->where('status', $status);
            })
            ->when($supplierId, function ($q) use ($supplierId) {
                $q->where('supplier_id', $supplierId);
            })
            ->orderBy('purchase_date', 'desc');

        $purchases = $query->get();

        $totals = [
            'count' => $purchases->count(),
            'subtotal_exento' => $purchases->sum('subtotal_exento'),
            'subtotal_5' => $purchases->sum('subtotal_5'),
            'iva_5' => $purchases->sum('iva_5'),
            'subtotal_10' => $purchases->sum('subtotal_10'),
            'iva_10' => $purchases->sum('iva_10'),
            'total' => $purchases->sum('total'),
        ];

        if ($request->ajax()) {
            return response()->json([
                'purchases' => $purchases->map(function ($purchase) {
                    return [
                        'id' => $purchase->id,
                        'purchase_number' => $purchase->purchase_number,
                        'purchase_date' => $purchase->purchase_date->format('d/m/Y'),
                        'supplier_name' => $purchase->supplier->name ?? 'Sin proveedor',
                        'invoice_number' => $purchase->invoice_number,
                        'subtotal_exento' => $purchase->subtotal_exento,
                        'subtotal_5' => $purchase->subtotal_5,
                        'iva_5' => $purchase->iva_5,
                        'subtotal_10' => $purchase->subtotal_10,
                        'iva_10' => $purchase->iva_10,
                        'total' => $purchase->total,
                        'status' => $purchase->status,
                        'user_name' => $purchase->user->name ?? '',
                    ];
                }),
                'totals' => $totals,
            ]);
        }

        return view('reports.purchases', compact('startDate', 'endDate', 'status', 'supplierId'));
    }

    /**
     * Reporte de gastos
     */
    public function expenses(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $status = $request->get('status', '');
        $categoryId = $request->get('category_id', '');

        $query = Expense::with(['category', 'supplier', 'user'])
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->when($status, function ($q) use ($status) {
                $q->where('status', $status);
            })
            ->when($categoryId, function ($q) use ($categoryId) {
                $q->where('expense_category_id', $categoryId);
            })
            ->orderBy('expense_date', 'desc');

        $expenses = $query->get();

        $totals = [
            'count' => $expenses->count(),
            'amount' => $expenses->sum('amount'),
            'tax_amount' => $expenses->sum('tax_amount'),
        ];

        // Agrupar por categoría
        $byCategory = $expenses->groupBy('expense_category_id')->map(function ($items, $key) {
            return [
                'category' => $items->first()->category->name ?? 'Sin categoría',
                'count' => $items->count(),
                'amount' => $items->sum('amount'),
            ];
        })->values();

        if ($request->ajax()) {
            return response()->json([
                'expenses' => $expenses->map(function ($expense) {
                    return [
                        'id' => $expense->id,
                        'expense_number' => $expense->expense_number,
                        'expense_date' => $expense->expense_date->format('d/m/Y'),
                        'category_name' => $expense->category->name ?? 'Sin categoría',
                        'description' => $expense->description,
                        'supplier_name' => $expense->supplier->name ?? '-',
                        'amount' => $expense->amount,
                        'tax_amount' => $expense->tax_amount,
                        'status' => $expense->status,
                        'user_name' => $expense->user->name ?? '',
                    ];
                }),
                'totals' => $totals,
                'byCategory' => $byCategory,
            ]);
        }

        return view('reports.expenses', compact('startDate', 'endDate', 'status', 'categoryId'));
    }

    /**
     * Reporte de inventario
     */
    public function inventory(Request $request)
    {
        $categoryId = $request->get('category_id', '');
        $stockFilter = $request->get('stock_filter', ''); // low, zero, all

        $query = Product::with('category')
            ->where('is_active', true)
            ->when($categoryId, function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            })
            ->when($stockFilter === 'low', function ($q) {
                $q->whereRaw('stock <= min_stock AND stock > 0');
            })
            ->when($stockFilter === 'zero', function ($q) {
                $q->where('stock', '<=', 0);
            })
            ->orderBy('name');

        $products = $query->get();

        $totals = [
            'count' => $products->count(),
            'total_stock_value' => $products->sum(function ($product) {
                return $product->stock * $product->purchase_price;
            }),
            'total_sale_value' => $products->sum(function ($product) {
                return $product->stock * $product->sale_price;
            }),
        ];

        if ($request->ajax()) {
            return response()->json([
                'products' => $products->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'code' => $product->code,
                        'name' => $product->name,
                        'category_name' => $product->category->name ?? 'Sin categoría',
                        'stock' => $product->stock,
                        'min_stock' => $product->min_stock,
                        'unit' => $product->unit,
                        'purchase_price' => $product->purchase_price,
                        'sale_price' => $product->sale_price,
                        'stock_value' => $product->stock * $product->purchase_price,
                        'is_low' => $product->stock <= $product->min_stock && $product->stock > 0,
                        'is_zero' => $product->stock <= 0,
                    ];
                }),
                'totals' => $totals,
            ]);
        }

        return view('reports.inventory', compact('categoryId', 'stockFilter'));
    }

    /**
     * Resumen general (para dashboard)
     */
    public function summary(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        // Ventas del período
        $sales = Sale::whereBetween('sale_date', [$startDate, $endDate])
            ->where('status', 'confirmed')
            ->selectRaw('COUNT(*) as count, SUM(total) as total')
            ->first();

        // Compras del período
        $purchases = Purchase::whereBetween('purchase_date', [$startDate, $endDate])
            ->where('status', 'confirmed')
            ->selectRaw('COUNT(*) as count, SUM(total) as total')
            ->first();

        // Gastos del período
        $expenses = Expense::whereBetween('expense_date', [$startDate, $endDate])
            ->where('status', 'paid')
            ->selectRaw('COUNT(*) as count, SUM(amount) as total')
            ->first();

        // Productos con stock bajo
        $lowStock = Product::where('is_active', true)
            ->whereRaw('stock <= min_stock')
            ->count();

        return response()->json([
            'sales' => [
                'count' => $sales->count ?? 0,
                'total' => $sales->total ?? 0,
            ],
            'purchases' => [
                'count' => $purchases->count ?? 0,
                'total' => $purchases->total ?? 0,
            ],
            'expenses' => [
                'count' => $expenses->count ?? 0,
                'total' => $expenses->total ?? 0,
            ],
            'low_stock' => $lowStock,
            'profit' => ($sales->total ?? 0) - ($purchases->total ?? 0) - ($expenses->total ?? 0),
        ]);
    }
}
