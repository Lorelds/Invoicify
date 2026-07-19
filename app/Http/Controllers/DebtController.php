<?php

namespace App\Http\Controllers;

use App\Models\Debt;
use App\Models\DebtPayment;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DebtController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $type = $request->input('type', 'receivable'); // default to receivable (Piutang Pelanggan)
        $receiptType = $type === 'payable' ? 'pembelian' : 'penjualan';

        // Group debts by store. We get stores that have active debts of the requested type.
        $query = Store::whereHas('debts', function($q) use ($receiptType) {
            $q->whereIn('status', ['hutang', 'partial'])
              ->whereHas('receipt', function($q2) use ($receiptType) {
                  $q2->where('type', $receiptType);
              });
        });

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        $storesWithDebts = $query->with(['debts' => function($query) use ($receiptType) {
            $query->whereIn('status', ['hutang', 'partial'])
                  ->whereHas('receipt', function($q) use ($receiptType) {
                      $q->where('type', $receiptType);
                  })
                  ->orderBy('created_at', 'desc');
        }])->paginate(10)->withQueryString();

        return view('debts.index', compact('storesWithDebts', 'search', 'type'));
    }

    public function showStore(Request $request, $storeId)
    {
        $type = $request->input('type', 'receivable');
        $receiptType = $type === 'payable' ? 'pembelian' : 'penjualan';

        $store = Store::with(['debts' => function($query) use ($receiptType) {
            $query->whereIn('status', ['hutang', 'partial'])
                  ->whereHas('receipt', function($q) use ($receiptType) {
                      $q->where('type', $receiptType);
                  })
                  ->orderBy('created_at', 'desc');
        }, 'debts.receipt', 'debts.payments' => function($query) {
            $query->orderBy('payment_date', 'desc');
        }])->findOrFail($storeId);

        return view('debts.show', compact('store', 'type'));
    }

    public function pay(Request $request, $debtId)
    {
        $request->validate([
            'amount_paid' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'nullable|string',
            'reference_number' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $debt = Debt::findOrFail($debtId);
            
            // Calculate remaining balance (round to 2 decimals for floating point safety)
            $remainingBalance = round($debt->amount - $debt->paid_amount, 2);
            $amountPaid = round($request->amount_paid, 2);
            
            // Don't allow overpayment
            if ($amountPaid > $remainingBalance) {
                return redirect()->back()
                    ->withErrors(['amount_paid' => 'Payment amount cannot exceed the remaining balance of Rp ' . number_format($remainingBalance, 2, ',', '.')])
                    ->withInput();
            }

            // Create Payment Record
            DebtPayment::create([
                'debt_id' => $debt->id,
                'amount_paid' => $amountPaid,
                'payment_date' => $request->payment_date,
                'payment_method' => $request->payment_method,
                'reference_number' => $request->reference_number,
                'notes' => $request->notes,
            ]);

            // Update Debt Balance and Status
            $debt->paid_amount += $amountPaid;
            
            if (round($debt->paid_amount, 2) >= round($debt->amount, 2)) {
                $debt->status = 'lunas';
                if ($debt->receipt) {
                    $debt->receipt->update(['payment_status' => 'lunas']);
                }
            } else {
                $debt->status = 'partial';
                if ($debt->receipt) {
                    $debt->receipt->update(['payment_status' => 'partial']);
                }
            }
            
            $debt->save();

            DB::commit();

            return redirect()->back()->with('success', __('Payment recorded successfully!'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error recording payment: ' . $e->getMessage());
        }
    }
}
