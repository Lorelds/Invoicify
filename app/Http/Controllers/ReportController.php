<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use App\Models\Product;
use App\Models\Debt;
use App\Models\DebtPayment;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\ReportExport;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    /**
     * Show reports page with filter form.
     */
    public function index()
    {
        return view('reports.index');
    }

    /**
     * Generate and download report based on filters.
     */
    public function generate(Request $request)
    {
        $request->validate([
            'period' => 'required|in:weekly,monthly,yearly,custom',
            'format' => 'required|in:pdf,excel',
            'date'   => 'nullable|date',
            'date_week' => 'nullable|string',
            'date_month' => 'nullable|string',
            'date_year' => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
        ]);

        // Determine date range
        $dates = $this->getDateRange($request->period, $request);
        $startDate = $dates['start'];
        $endDate = $dates['end'];
        $periodLabel = $dates['label'];

        // Gather report data
        $data = $this->gatherReportData($startDate, $endDate, $periodLabel);

        // Export
        $filename = 'Laporan_' . str_replace([' ', '/', ':'], '_', $periodLabel) . '_' . now()->format('Ymd_His');

        if ($request->format === 'pdf') {
            return $this->exportPdf($data, $filename);
        }

        return $this->exportExcel($data, $filename);
    }

    private function getDateRange(string $period, Request $request): array
    {
        $baseDate = Carbon::now();

        if ($period === 'weekly' && $request->filled('date_week')) {
            // week format: "2024-W12"
            $parts = explode('-W', $request->date_week);
            if (count($parts) === 2) {
                $baseDate = Carbon::now()->setISODate($parts[0], $parts[1]);
            }
        } elseif ($period === 'monthly' && $request->filled('date_month')) {
            $baseDate = Carbon::parse($request->date_month . '-01');
        } elseif ($period === 'yearly' && $request->filled('date_year')) {
            $baseDate = Carbon::parse($request->date_year . '-01-01');
        } elseif ($request->filled('date')) {
            $baseDate = Carbon::parse($request->date);
        }

        switch ($period) {
            case 'weekly':
                $start = $baseDate->copy()->startOfWeek();
                $end = $baseDate->copy()->endOfWeek();
                $label = 'Minggu ' . $start->format('d M') . ' - ' . $end->format('d M Y');
                break;
            case 'monthly':
                $start = $baseDate->copy()->startOfMonth();
                $end = $baseDate->copy()->endOfMonth();
                $label = 'Bulan ' . $baseDate->translatedFormat('F Y');
                break;
            case 'yearly':
                $start = $baseDate->copy()->startOfYear();
                $end = $baseDate->copy()->endOfYear();
                $label = 'Tahun ' . $baseDate->format('Y');
                break;
            case 'custom':
                $start = Carbon::parse($request->start_date)->startOfDay();
                $end = Carbon::parse($request->end_date)->endOfDay();
                $label = $start->format('d M Y') . ' - ' . $end->format('d M Y');
                break;
            default:
                $start = Carbon::now()->startOfMonth();
                $end = Carbon::now()->endOfMonth();
                $label = 'Bulan ' . Carbon::now()->translatedFormat('F Y');
        }

        return ['start' => $start, 'end' => $end, 'label' => $label];
    }

    /**
     * Gather all report data for the given date range.
     */
    private function gatherReportData(Carbon $startDate, Carbon $endDate, string $periodLabel): array
    {
        // Company name (use nullsafe operator)
        $companyName = auth()->user()->company?->name ?? 'My Company';

        // --- SALES SUMMARY ---
        $totalSales = Receipt::where('status', 'validated')
            ->where('type', 'penjualan')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->sum('total_amount');

        $totalSalesPaid = Receipt::where('status', 'validated')
            ->where('type', 'penjualan')
            ->where('payment_status', 'lunas')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->sum('total_amount');

        // --- PURCHASES SUMMARY ---
        $totalPurchases = Receipt::where('status', 'validated')
            ->where('type', 'pembelian')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->sum('total_amount');

        $totalPurchasesPaid = Receipt::where('status', 'validated')
            ->where('type', 'pembelian')
            ->where('payment_status', 'lunas')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->sum('total_amount');

        // --- RECEIPTS LIST ---
        $receipts = Receipt::with('store')
            ->where('status', 'validated')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->orderBy('transaction_date', 'desc')
            ->get();

        $salesReceipts = $receipts->where('type', 'penjualan');
        $purchaseReceipts = $receipts->where('type', 'pembelian');

        // --- DEBTS ---
        $totalDebtCreated = Debt::whereBetween('created_at', [$startDate, $endDate])->sum('amount');
        $totalDebtPaid = DebtPayment::whereBetween('payment_date', [$startDate, $endDate])->sum('amount_paid');
        $outstandingDebts = Debt::where('status', 'hutang')->get();
        $totalOutstanding = $outstandingDebts->sum(fn($d) => $d->amount - $d->paid_amount);

        // --- PAYMENTS ---
        $payments = DebtPayment::with('debt.store')
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->orderBy('payment_date', 'desc')
            ->get();

        // --- PRODUCTS SNAPSHOT ---
        $products = Product::orderBy('name')->get();
        $totalInventoryValue = $products->sum(fn($p) => $p->buy_price * $p->stock);
        $lowStockProducts = $products->where('stock', '<=', 5)->where('stock', '>', 0);
        $outOfStockProducts = $products->where('stock', '==', 0);

        return [
            'companyName'        => $companyName,
            'periodLabel'        => $periodLabel,
            'startDate'          => $startDate,
            'endDate'            => $endDate,
            'generatedAt'        => now()->format('d M Y H:i'),
            // Sales
            'totalSales'         => $totalSales,
            'totalSalesPaid'     => $totalSalesPaid,
            'salesReceipts'      => $salesReceipts,
            'salesCount'         => $salesReceipts->count(),
            // Purchases
            'totalPurchases'     => $totalPurchases,
            'totalPurchasesPaid' => $totalPurchasesPaid,
            'purchaseReceipts'   => $purchaseReceipts,
            'purchasesCount'     => $purchaseReceipts->count(),
            // Debts
            'totalDebtCreated'   => $totalDebtCreated,
            'totalDebtPaid'      => $totalDebtPaid,
            'totalOutstanding'   => $totalOutstanding,
            // Payments
            'payments'           => $payments,
            // Products
            'products'           => $products,
            'totalProducts'      => $products->count(),
            'totalInventoryValue'=> $totalInventoryValue,
            'lowStockProducts'   => $lowStockProducts,
            'outOfStockProducts' => $outOfStockProducts,
        ];
    }

    /**
     * Export as PDF.
     */
    private function exportPdf(array $data, string $filename)
    {
        $pdf = Pdf::loadView('reports.pdf', $data)
            ->setPaper('a4', 'portrait');

        return $pdf->download($filename . '.pdf');
    }

    /**
     * Export as Excel.
     */
    private function exportExcel(array $data, string $filename)
    {
        return Excel::download(new ReportExport($data), $filename . '.xlsx');
    }
}
