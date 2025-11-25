<?php

namespace App\Http\Controllers;

use App\Models\AccountPayable;
use App\Models\AccountPayablePayment;
use App\Models\CashRegister;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AccountPayableController extends Controller
{
    public function index()
    {
        return view('account-payables.index');
    }

    public function data(Request $request)
    {
        $query = AccountPayable::query();

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('document_number', 'like', '%' . $request->search . '%')
                  ->orWhere('supplier_name', 'like', '%' . $request->search . '%')
                  ->orWhere('purchase_number', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->supplier_id) {
            $query->where('supplier_id', $request->supplier_id);
        }

        $sort = $request->sort ?? 'id';
        $order = $request->order ?? 'desc';
        $query->orderBy($sort, $order);

        $total = $query->count();
        $rows = $query->skip($request->offset ?? 0)
            ->take($request->limit ?? 20)
            ->get();

        return response()->json([
            'total' => $total,
            'rows' => $rows
        ]);
    }

    public function create()
    {
        $documentNumber = AccountPayable::generateDocumentNumber(Auth::user()->tenant_id);
        return view('account-payables.create', compact('documentNumber'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'document_date' => 'required|date',
            'due_date' => 'required|date',
            'supplier_id' => 'required|exists:suppliers,id',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $supplier = Supplier::findOrFail($request->supplier_id);

        $payable = AccountPayable::create([
            'tenant_id' => Auth::user()->tenant_id,
            'document_number' => AccountPayable::generateDocumentNumber(Auth::user()->tenant_id),
            'document_date' => $request->document_date,
            'due_date' => $request->due_date,
            'supplier_id' => $supplier->id,
            'supplier_name' => $supplier->name,
            'description' => $request->description,
            'amount' => $request->amount,
            'paid_amount' => 0,
            'balance' => $request->amount,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cuenta por pagar creada exitosamente',
            'id' => $payable->id
        ]);
    }

    public function show($id)
    {
        $payable = AccountPayable::with(['supplier', 'purchase', 'payments.user'])->findOrFail($id);
        return view('account-payables.detail', compact('payable'));
    }

    public function addPayment(Request $request, $id)
    {
        $request->validate([
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,transfer,check,card,other',
        ]);

        $payable = AccountPayable::findOrFail($id);

        if ($payable->status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Esta cuenta ya está pagada'
            ], 400);
        }

        if ($payable->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Esta cuenta está anulada'
            ], 400);
        }

        if ($request->amount > $payable->balance) {
            return response()->json([
                'success' => false,
                'message' => 'El monto del pago excede el saldo pendiente'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Si el pago es en efectivo, verificar que haya caja abierta y saldo suficiente
            if ($request->payment_method === 'cash') {
                $cashRegister = CashRegister::getOpenRegister(Auth::user()->tenant_id, Auth::id());

                if (!$cashRegister) {
                    throw new \Exception('Debes tener una caja abierta para registrar pagos en efectivo');
                }

                // Validar que haya saldo suficiente en caja
                $cashRegister->calculateExpectedBalance();
                if ($cashRegister->expected_balance < $request->amount) {
                    throw new \Exception('Saldo insuficiente en caja. Disponible: ' . number_format($cashRegister->expected_balance, 0, ',', '.') . ' Gs.');
                }
            }

            $payment = AccountPayablePayment::create([
                'account_payable_id' => $payable->id,
                'payment_number' => AccountPayablePayment::generatePaymentNumber($payable->id),
                'payment_date' => $request->payment_date,
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
                'reference' => $request->reference,
                'notes' => $request->notes,
                'user_id' => Auth::id(),
            ]);

            $payable->updateBalance();

            // Si el pago es en efectivo, registrar en caja
            if ($request->payment_method === 'cash') {
                $cashRegister = CashRegister::getOpenRegister(Auth::user()->tenant_id, Auth::id());

                // Registrar movimiento en caja
                $cashRegister->movements()->create([
                    'type' => 'expense',
                    'concept' => 'payment',
                    'amount' => $request->amount,
                    'description' => 'Pago ' . $payment->payment_number . ' - ' . $payable->supplier_name . ' - ' . $payable->document_number,
                    'reference' => $payment->payment_number,
                    'account_payable_payment_id' => $payment->id,
                ]);

                // Actualizar totales de caja
                $cashRegister->payments += $request->amount;
                $cashRegister->calculateExpectedBalance();
                $cashRegister->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pago registrado exitosamente'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el pago: ' . $e->getMessage()
            ], 500);
        }
    }

    public function cancel($id)
    {
        $payable = AccountPayable::findOrFail($id);

        if ($payable->paid_amount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede anular una cuenta con pagos registrados'
            ], 400);
        }

        $payable->status = 'cancelled';
        $payable->save();

        return response()->json([
            'success' => true,
            'message' => 'Cuenta por pagar anulada exitosamente'
        ]);
    }

    public function destroy($id)
    {
        $payable = AccountPayable::findOrFail($id);

        if ($payable->paid_amount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar una cuenta con pagos registrados'
            ], 400);
        }

        $payable->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cuenta por pagar eliminada exitosamente'
        ]);
    }

    public function bySupplier(Request $request)
    {
        $query = AccountPayable::select('supplier_id', 'supplier_name')
            ->selectRaw('COUNT(*) as total_documents')
            ->selectRaw('SUM(amount) as total_amount')
            ->selectRaw('SUM(paid_amount) as total_paid')
            ->selectRaw('SUM(balance) as total_balance')
            ->where('status', '!=', 'cancelled')
            ->groupBy('supplier_id', 'supplier_name')
            ->having('total_balance', '>', 0)
            ->orderBy('total_balance', 'desc');

        $suppliers = $query->get();

        return view('account-payables.by-supplier', compact('suppliers'));
    }
}
