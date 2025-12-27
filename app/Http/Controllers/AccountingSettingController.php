<?php

namespace App\Http\Controllers;

use App\Models\AccountingSetting;
use App\Models\AccountChart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountingSettingController extends Controller
{
    public function index()
    {
        $settings = AccountingSetting::where('tenant_id', Auth::user()->tenant_id)
            ->with('account')
            ->get()
            ->keyBy('key');

        $availableKeys = AccountingSetting::getAvailableKeys();

        return view('accounting.settings.index', compact('settings', 'availableKeys'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'settings' => 'required|array',
            'settings.*' => 'nullable|exists:account_chart,id',
        ]);

        $tenantId = Auth::user()->tenant_id;
        $availableKeys = AccountingSetting::getAvailableKeys();

        // Validar tipos de cuenta antes de guardar
        foreach ($request->settings as $key => $accountId) {
            if ($accountId && array_key_exists($key, $availableKeys)) {
                $account = AccountChart::where('id', $accountId)
                    ->where('tenant_id', $tenantId)
                    ->first();

                if (!$account) {
                    return response()->json([
                        'success' => false,
                        'message' => "La cuenta seleccionada para {$availableKeys[$key]} no es válida.",
                    ], 422);
                }

                // Validar tipo de cuenta según la configuración
                $validationError = $this->validateAccountType($key, $account, $availableKeys[$key]);
                if ($validationError) {
                    return response()->json([
                        'success' => false,
                        'message' => $validationError,
                    ], 422);
                }
            }
        }

        // Si todas las validaciones pasan, guardar la configuración
        foreach ($request->settings as $key => $accountId) {
            if (array_key_exists($key, $availableKeys)) {
                if ($accountId) {
                    AccountingSetting::setValue(
                        $tenantId,
                        $key,
                        $accountId,
                        $availableKeys[$key]
                    );
                } else {
                    // Si no hay account_id, eliminar la configuración
                    AccountingSetting::where('tenant_id', $tenantId)
                        ->where('key', $key)
                        ->delete();
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Configuración contable guardada exitosamente',
        ]);
    }

    /**
     * Validar que el tipo de cuenta sea apropiado para la configuración
     */
    private function validateAccountType($key, $account, $description)
    {
        $accountTypeRules = [
            // Ingresos
            'sales_income' => ['income'],
            'sales_discount' => ['expense', 'income'], // Puede ser contra-ingreso
            'financial_income' => ['income'],

            // Gastos
            'purchases_expense' => ['expense'],
            'purchases_discount' => ['income', 'expense'], // Puede ser contra-gasto
            'expenses_default' => ['expense'],
            'financial_expenses' => ['expense'],

            // Activos (Caja, Bancos, Cuentas por Cobrar, Inventario)
            'cash' => ['asset'],
            'bank_default' => ['asset'],
            'bank_deposits_default' => ['asset'],
            'bank_withdrawals_default' => ['asset'],
            'accounts_receivable' => ['asset'],
            'inventory' => ['asset'],

            // Pasivos
            'accounts_payable' => ['liability'],

            // Impuestos (pueden ser activos o pasivos)
            'sales_tax' => ['liability', 'asset'],
            'purchases_tax' => ['asset', 'liability'],
        ];

        if (!isset($accountTypeRules[$key])) {
            return null; // No hay regla de validación para esta clave
        }

        $allowedTypes = $accountTypeRules[$key];

        if (!in_array($account->account_type, $allowedTypes)) {
            $typesSpanish = [
                'asset' => 'Activo',
                'liability' => 'Pasivo',
                'equity' => 'Patrimonio',
                'income' => 'Ingreso',
                'expense' => 'Gasto',
            ];

            $allowedTypesText = implode(' o ', array_map(function($type) use ($typesSpanish) {
                return $typesSpanish[$type] ?? $type;
            }, $allowedTypes));

            $currentType = $typesSpanish[$account->account_type] ?? $account->account_type;

            return "La cuenta '{$account->name}' es de tipo {$currentType}, pero para '{$description}' debe ser de tipo {$allowedTypesText}.";
        }

        return null; // Validación exitosa
    }
}
