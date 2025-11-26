<?php

namespace App\Services;

use App\Models\AccountingSetting;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Sale;
use App\Models\Purchase;
use Illuminate\Support\Facades\DB;

class AccountingIntegrationService
{
    /**
     * Crear asiento contable para una venta
     */
    public function createSaleJournalEntry(Sale $sale): JournalEntry
    {
        $tenantId = $sale->tenant_id;

        // Verificar que las cuentas necesarias estén configuradas
        $this->validateSaleAccounts($tenantId, $sale);

        // Determinar la cuenta de débito según el método de pago
        $debitAccountId = $this->getDebitAccountForSale($tenantId, $sale);

        // Obtener cuenta de ingresos
        $creditAccountId = AccountingSetting::getValue($tenantId, 'sales_income');

        // Obtener cuenta de IVA ventas si hay IVA
        $taxAccountId = null;
        if ($sale->total_iva > 0) {
            $taxAccountId = AccountingSetting::getValue($tenantId, 'sales_tax');
        }

        // Crear el asiento contable
        DB::beginTransaction();
        try {
            $journalEntry = JournalEntry::create([
                'tenant_id' => $tenantId,
                'entry_number' => JournalEntry::generateEntryNumber($tenantId),
                'entry_date' => $sale->sale_date,
                'reference' => $sale->sale_number,
                'description' => $this->getSaleDescription($sale),
                'status' => 'posted',
                'created_by' => $sale->user_id,
            ]);

            // Calcular subtotal sin IVA
            $subtotalWithoutTax = $sale->subtotal_exento + $sale->subtotal_5 + $sale->subtotal_10;

            // Línea de débito: Caja/Banco/Cuentas por Cobrar
            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $debitAccountId,
                'description' => $this->getSaleDescription($sale),
                'debit' => $sale->total,
                'credit' => 0,
            ]);

            // Línea de crédito: Ingresos por Ventas (sin IVA)
            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $creditAccountId,
                'description' => 'Venta ' . $sale->sale_number,
                'debit' => 0,
                'credit' => $subtotalWithoutTax,
            ]);

            // Si hay IVA, agregar línea de crédito para IVA
            if ($sale->total_iva > 0 && $taxAccountId) {
                JournalEntryLine::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $taxAccountId,
                    'description' => 'IVA Ventas ' . $sale->sale_number,
                    'debit' => 0,
                    'credit' => $sale->total_iva,
                ]);
            }

            // Actualizar saldos de cuentas
            $journalEntry->updateAccountBalances();

            // Vincular el asiento a la venta
            $sale->journal_entry_id = $journalEntry->id;
            $sale->save();

            DB::commit();

            return $journalEntry;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Reversar asiento contable de una venta anulada
     */
    public function reverseSaleJournalEntry(Sale $sale): ?JournalEntry
    {
        if (!$sale->journal_entry_id) {
            return null;
        }

        $originalEntry = JournalEntry::find($sale->journal_entry_id);
        if (!$originalEntry) {
            return null;
        }

        DB::beginTransaction();
        try {
            // Crear asiento de reversa
            $reversalEntry = JournalEntry::create([
                'tenant_id' => $sale->tenant_id,
                'entry_number' => JournalEntry::generateEntryNumber($sale->tenant_id),
                'entry_date' => now()->toDateString(),
                'reference' => $sale->sale_number . ' (Anulación)',
                'description' => 'Anulación de venta ' . $sale->sale_number,
                'status' => 'posted',
                'created_by' => auth()->id(),
            ]);

            // Crear líneas inversas (intercambiar débito y crédito)
            foreach ($originalEntry->lines as $line) {
                JournalEntryLine::create([
                    'journal_entry_id' => $reversalEntry->id,
                    'account_id' => $line->account_id,
                    'description' => 'Reversa: ' . $line->description,
                    'debit' => $line->credit,  // Invertir
                    'credit' => $line->debit,  // Invertir
                ]);
            }

            // Actualizar saldos de cuentas
            $reversalEntry->updateAccountBalances();

            DB::commit();

            return $reversalEntry;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Validar que las cuentas necesarias estén configuradas
     */
    private function validateSaleAccounts(int $tenantId, Sale $sale): void
    {
        $errors = [];

        // Validar cuenta de ingresos
        if (!AccountingSetting::getValue($tenantId, 'sales_income')) {
            $errors[] = 'Cuenta de Ingresos por Ventas';
        }

        // Validar cuenta según método de pago
        if ($sale->payment_type === 'credit') {
            if (!AccountingSetting::getValue($tenantId, 'accounts_receivable')) {
                $errors[] = 'Cuenta de Cuentas por Cobrar';
            }
        } else {
            // Venta al contado
            if ($sale->payment_method === 'cash') {
                if (!AccountingSetting::getValue($tenantId, 'cash')) {
                    $errors[] = 'Cuenta de Caja';
                }
            } elseif (in_array($sale->payment_method, ['card', 'transfer'])) {
                if (!AccountingSetting::getValue($tenantId, 'bank_default')) {
                    $errors[] = 'Cuenta de Banco por Defecto';
                }
            }
        }

        // Validar cuenta de IVA si hay IVA
        if ($sale->total_iva > 0) {
            if (!AccountingSetting::getValue($tenantId, 'sales_tax')) {
                $errors[] = 'Cuenta de IVA Ventas';
            }
        }

        if (!empty($errors)) {
            throw new \Exception(
                'Debe configurar las siguientes cuentas contables antes de confirmar la venta: ' .
                implode(', ', $errors) . '. ' .
                'Vaya a Contabilidad > Configuración Contable para configurarlas.'
            );
        }
    }

    /**
     * Obtener la cuenta de débito según el tipo y método de pago
     */
    private function getDebitAccountForSale(int $tenantId, Sale $sale): int
    {
        // Si es venta a crédito, usar cuentas por cobrar
        if ($sale->payment_type === 'credit') {
            return AccountingSetting::getValue($tenantId, 'accounts_receivable');
        }

        // Si es venta al contado, determinar según método de pago
        if ($sale->payment_method === 'cash') {
            return AccountingSetting::getValue($tenantId, 'cash');
        } elseif (in_array($sale->payment_method, ['card', 'transfer'])) {
            return AccountingSetting::getValue($tenantId, 'bank_default');
        }

        // Por defecto, usar caja
        return AccountingSetting::getValue($tenantId, 'cash');
    }

    /**
     * Obtener descripción del asiento de venta
     */
    private function getSaleDescription(Sale $sale): string
    {
        $description = 'Venta ' . $sale->sale_number;

        if ($sale->customer) {
            $description .= ' - ' . $sale->customer->name;
        }

        return $description;
    }

    /**
     * Crear asiento contable para una compra
     */
    public function createPurchaseJournalEntry(Purchase $purchase): JournalEntry
    {
        $tenantId = $purchase->tenant_id;

        // Verificar que las cuentas necesarias estén configuradas
        $this->validatePurchaseAccounts($tenantId, $purchase);

        // Determinar la cuenta de crédito según el método de pago
        $creditAccountId = $this->getCreditAccountForPurchase($tenantId, $purchase);

        // Obtener cuenta de gastos/costo de ventas
        $debitAccountId = AccountingSetting::getValue($tenantId, 'purchases_expense');

        // Obtener cuenta de IVA compras si hay IVA
        $taxAccountId = null;
        if ($purchase->total_iva > 0) {
            $taxAccountId = AccountingSetting::getValue($tenantId, 'purchases_tax');
        }

        // Crear el asiento contable
        DB::beginTransaction();
        try {
            $journalEntry = JournalEntry::create([
                'tenant_id' => $tenantId,
                'entry_number' => JournalEntry::generateEntryNumber($tenantId),
                'entry_date' => $purchase->purchase_date,
                'reference' => $purchase->purchase_number,
                'description' => $this->getPurchaseDescription($purchase),
                'status' => 'posted',
                'created_by' => $purchase->user_id,
            ]);

            // Calcular subtotal sin IVA
            $subtotalWithoutTax = $purchase->subtotal_exento + $purchase->subtotal_5 + $purchase->subtotal_10;

            // Línea de débito: Costo de Ventas / Compras (sin IVA)
            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $debitAccountId,
                'description' => 'Compra ' . $purchase->purchase_number,
                'debit' => $subtotalWithoutTax,
                'credit' => 0,
            ]);

            // Si hay IVA, agregar línea de débito para IVA Crédito Fiscal
            if ($purchase->total_iva > 0 && $taxAccountId) {
                JournalEntryLine::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $taxAccountId,
                    'description' => 'IVA Compras ' . $purchase->purchase_number,
                    'debit' => $purchase->total_iva,
                    'credit' => 0,
                ]);
            }

            // Línea de crédito: Caja/Banco/Cuentas por Pagar
            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $creditAccountId,
                'description' => $this->getPurchaseDescription($purchase),
                'debit' => 0,
                'credit' => $purchase->total,
            ]);

            // Actualizar saldos de cuentas
            $journalEntry->updateAccountBalances();

            // Vincular el asiento a la compra
            $purchase->journal_entry_id = $journalEntry->id;
            $purchase->save();

            DB::commit();

            return $journalEntry;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Reversar asiento contable de una compra anulada
     */
    public function reversePurchaseJournalEntry(Purchase $purchase): ?JournalEntry
    {
        if (!$purchase->journal_entry_id) {
            return null;
        }

        $originalEntry = JournalEntry::find($purchase->journal_entry_id);
        if (!$originalEntry) {
            return null;
        }

        DB::beginTransaction();
        try {
            // Crear asiento de reversa
            $reversalEntry = JournalEntry::create([
                'tenant_id' => $purchase->tenant_id,
                'entry_number' => JournalEntry::generateEntryNumber($purchase->tenant_id),
                'entry_date' => now()->toDateString(),
                'reference' => $purchase->purchase_number . ' (Anulación)',
                'description' => 'Anulación de compra ' . $purchase->purchase_number,
                'status' => 'posted',
                'created_by' => auth()->id(),
            ]);

            // Crear líneas inversas (intercambiar débito y crédito)
            foreach ($originalEntry->lines as $line) {
                JournalEntryLine::create([
                    'journal_entry_id' => $reversalEntry->id,
                    'account_id' => $line->account_id,
                    'description' => 'Reversa: ' . $line->description,
                    'debit' => $line->credit,  // Invertir
                    'credit' => $line->debit,  // Invertir
                ]);
            }

            // Actualizar saldos de cuentas
            $reversalEntry->updateAccountBalances();

            DB::commit();

            return $reversalEntry;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Validar que las cuentas necesarias para compras estén configuradas
     */
    private function validatePurchaseAccounts(int $tenantId, Purchase $purchase): void
    {
        $errors = [];

        // Validar cuenta de gastos/costo de ventas
        if (!AccountingSetting::getValue($tenantId, 'purchases_expense')) {
            $errors[] = 'Cuenta de Compras / Costo de Ventas';
        }

        // Validar cuenta según método de pago
        if ($purchase->payment_type === 'credit') {
            if (!AccountingSetting::getValue($tenantId, 'accounts_payable')) {
                $errors[] = 'Cuenta de Cuentas por Pagar';
            }
        } else {
            // Compra al contado
            if ($purchase->payment_method === 'cash') {
                if (!AccountingSetting::getValue($tenantId, 'cash')) {
                    $errors[] = 'Cuenta de Caja';
                }
            } elseif (in_array($purchase->payment_method, ['card', 'transfer'])) {
                if (!AccountingSetting::getValue($tenantId, 'bank_default')) {
                    $errors[] = 'Cuenta de Banco por Defecto';
                }
            }
        }

        // Validar cuenta de IVA si hay IVA
        if ($purchase->total_iva > 0) {
            if (!AccountingSetting::getValue($tenantId, 'purchases_tax')) {
                $errors[] = 'Cuenta de IVA Compras';
            }
        }

        if (!empty($errors)) {
            throw new \Exception(
                'Debe configurar las siguientes cuentas contables antes de confirmar la compra: ' .
                implode(', ', $errors) . '. ' .
                'Vaya a Contabilidad > Configuración Contable para configurarlas.'
            );
        }
    }

    /**
     * Obtener la cuenta de crédito según el tipo y método de pago
     */
    private function getCreditAccountForPurchase(int $tenantId, Purchase $purchase): int
    {
        // Si es compra a crédito, usar cuentas por pagar
        if ($purchase->payment_type === 'credit') {
            return AccountingSetting::getValue($tenantId, 'accounts_payable');
        }

        // Si es compra al contado, determinar según método de pago
        if ($purchase->payment_method === 'cash') {
            return AccountingSetting::getValue($tenantId, 'cash');
        } elseif (in_array($purchase->payment_method, ['card', 'transfer'])) {
            return AccountingSetting::getValue($tenantId, 'bank_default');
        }

        // Por defecto, usar caja
        return AccountingSetting::getValue($tenantId, 'cash');
    }

    /**
     * Obtener descripción del asiento de compra
     */
    private function getPurchaseDescription(Purchase $purchase): string
    {
        $description = 'Compra ' . $purchase->purchase_number;

        if ($purchase->supplier) {
            $description .= ' - ' . $purchase->supplier->name;
        }

        return $description;
    }
}
