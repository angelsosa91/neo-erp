<?php

namespace App\Observers;

use App\Models\BankTransaction;
use App\Services\AccountingIntegrationService;
use Illuminate\Support\Facades\Log;

class BankTransactionObserver
{
    protected $accountingService;

    public function __construct(AccountingIntegrationService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    /**
     * Handle the BankTransaction "created" event.
     */
    public function created(BankTransaction $transaction): void
    {
        // Solo generar asiento si la transacción está completada
        // y no tiene ya un asiento asociado
        if ($transaction->status === 'completed' && !$transaction->journal_entry_id) {
            $this->generateJournalEntry($transaction);
        }
    }

    /**
     * Handle the BankTransaction "updated" event.
     */
    public function updated(BankTransaction $transaction): void
    {
        // Si el estado cambió a 'completed' y no tiene asiento, generarlo
        if ($transaction->isDirty('status') && $transaction->status === 'completed' && !$transaction->journal_entry_id) {
            $this->generateJournalEntry($transaction);
        }

        // Si el estado cambió a 'cancelled' y tiene asiento, anularlo
        if ($transaction->isDirty('status') && $transaction->status === 'cancelled' && $transaction->journal_entry_id) {
            $this->reverseJournalEntry($transaction);
        }
    }

    /**
     * Handle the BankTransaction "deleting" event.
     */
    public function deleting(BankTransaction $transaction): void
    {
        // Si tiene asiento contable asociado, anularlo antes de eliminar
        if ($transaction->journal_entry_id) {
            $this->reverseJournalEntry($transaction);
        }
    }

    /**
     * Generar asiento contable para la transacción
     */
    protected function generateJournalEntry(BankTransaction $transaction): void
    {
        try {
            // No generar asiento para transferencias entrantes
            // ya que se generan con la transferencia saliente
            if ($transaction->type === 'transfer_in') {
                return;
            }

            $journalEntry = $this->accountingService->createBankTransactionJournalEntry($transaction);

            Log::info('Asiento contable generado para transacción bancaria', [
                'transaction_id' => $transaction->id,
                'journal_entry_id' => $journalEntry->id,
                'type' => $transaction->type,
                'amount' => $transaction->amount,
            ]);
        } catch (\Exception $e) {
            Log::error('Error al generar asiento contable para transacción bancaria', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // No lanzar la excepción para no bloquear la creación de la transacción
            // El error queda registrado en los logs para revisión
        }
    }

    /**
     * Anular asiento contable de la transacción
     */
    protected function reverseJournalEntry(BankTransaction $transaction): void
    {
        try {
            if ($transaction->journalEntry) {
                $this->accountingService->reverseJournalEntry($transaction->journalEntry);

                Log::info('Asiento contable anulado para transacción bancaria', [
                    'transaction_id' => $transaction->id,
                    'journal_entry_id' => $transaction->journal_entry_id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error al anular asiento contable de transacción bancaria', [
                'transaction_id' => $transaction->id,
                'journal_entry_id' => $transaction->journal_entry_id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
