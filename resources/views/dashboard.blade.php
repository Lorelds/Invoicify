@extends('layouts.app')
@section('page_title', __('Dashboard Overview'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">{{ __('Dashboard Overview') }}</h2>
        <p class="text-muted mb-0">{{ __('Welcome back') }}, {{ auth()->user()->name }}. {{ __("Here's what's happening today.") }}</p>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12 col-sm-6 col-xl">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-primary bg-opacity-10 text-primary rounded p-2 me-3">
                        <i class="ph-bold ph-currency-circle-dollar fs-4"></i>
                    </div>
                    <h6 class="text-muted mb-0">{{ __('Total Penjualan') }}</h6>
                </div>
                <h3 class="mb-1">Rp {{ number_format($total_sales, 0, ',', '.') }}</h3>
                <p class="text-success small mb-0"><i class="ph-bold ph-trend-up"></i> {{ __('Hanya Nota Lunas') }}</p>
            </div>
        </div>
    </div>
    
    <div class="col-12 col-sm-6 col-xl">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-danger bg-opacity-10 text-danger rounded p-2 me-3">
                        <i class="ph-bold ph-hand-coins fs-4"></i>
                    </div>
                    <h6 class="text-muted mb-0">{{ __('Hutang Toko') }}</h6>
                </div>
                <h3 class="mb-1">Rp {{ number_format($hutang_toko, 0, ',', '.') }}</h3>
                <p class="text-danger small mb-0"><i class="ph-bold ph-warning"></i> {{ __('Hutang Kita') }}</p>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-warning bg-opacity-10 text-warning rounded p-2 me-3">
                        <i class="ph-bold ph-wallet fs-4"></i>
                    </div>
                    <h6 class="text-muted mb-0">{{ __('Piutang') }}</h6>
                </div>
                <h3 class="mb-1">Rp {{ number_format($piutang_pelanggan, 0, ',', '.') }}</h3>
                <p class="text-warning small mb-0"><i class="ph-bold ph-clock"></i> {{ __('Pelanggan Berhutang') }}</p>
            </div>
        </div>
    </div>
    
    <div class="col-12 col-sm-6 col-xl">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-success bg-opacity-10 text-success rounded p-2 me-3">
                        <i class="ph-bold ph-package fs-4"></i>
                    </div>
                    <h6 class="text-muted mb-0">{{ __('Barang') }}</h6>
                </div>
                <h3 class="mb-1">{{ number_format($total_products, 0, ',', '.') }}</h3>
                <p class="text-muted small mb-0">{{ __('Barang Unik') }}</p>
            </div>
        </div>
    </div>
    
    <div class="col-12 col-sm-6 col-xl">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-info bg-opacity-10 text-info rounded p-2 me-3">
                        <i class="ph-bold ph-receipt fs-4"></i>
                    </div>
                    <h6 class="text-muted mb-0">{{ __('Nota') }}</h6>
                </div>
                <h3 class="mb-1">{{ number_format($total_receipts, 0, ',', '.') }}</h3>
                <p class="text-muted small mb-0">{{ __('Nota Tervalidasi') }}</p>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Chart -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-4 pb-0">
                <h5 class="mb-0">Sales Overview (Last 6 Months)</h5>
            </div>
            <div class="card-body">
                <div style="position: relative; height: 300px; width: 100%;">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Receipts -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Receipts</h5>
                <a href="{{ route('admin.receipts.index') }}" class="btn btn-sm btn-light border text-primary">View All</a>
            </div>
            <div class="card-body">
                @forelse($recent_receipts as $receipt)
                    <div class="d-flex align-items-center justify-content-between mb-3 pb-3 border-bottom border-light last-border-none">
                        <div>
                            <p class="fw-medium mb-1">
                                <a href="{{ route('admin.receipts.show', $receipt->id) }}" class="text-dark text-decoration-none hover-primary">
                                    #{{ $receipt->receipt_number ?? str_pad($receipt->id, 5, '0', STR_PAD_LEFT) }} - {{ $receipt->store->name ?? $receipt->store_name ?? 'Unknown Store' }}
                                </a>
                            </p>
                            <p class="text-muted small mb-0">{{ \Carbon\Carbon::parse($receipt->transaction_date)->format('d M Y') }}</p>
                        </div>
                        <div class="text-end">
                            <p class="fw-medium text-success mb-1">Rp {{ number_format($receipt->total_amount, 0, ',', '.') }}</p>
                            @if($receipt->payment_status == 'lunas')
                                <span class="badge bg-success bg-opacity-10 text-success px-2 py-1"><i class="ph-fill ph-check-circle me-1"></i> Paid</span>
                            @else
                                <span class="badge bg-warning bg-opacity-10 text-warning px-2 py-1"><i class="ph-fill ph-clock me-1"></i> Debt</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4 text-muted">
                        <i class="ph-fill ph-receipt fs-2 mb-2 text-light"></i>
                        <p class="mb-0">No receipts yet.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    (function() {
        const ctx = document.getElementById('salesChart').getContext('2d');
        const rawData = @json($chart_data);
        
        const labels = rawData.map(item => item.month);
        const data = rawData.map(item => item.sales);
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Sales (Rp)',
                    data: data,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(context.parsed.y);
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value, index, values) {
                                if(value >= 1000000) {
                                    return 'Rp ' + (value / 1000000) + 'M';
                                } else if(value >= 1000) {
                                    return 'Rp ' + (value / 1000) + 'k';
                                }
                                return 'Rp ' + value;
                            }
                        }
                    }
                }
            }
        });
    })();
</script>
<style>
    .last-border-none:last-child {
        border-bottom: none !important;
        margin-bottom: 0 !important;
        padding-bottom: 0 !important;
    }
    .hover-primary:hover {
        color: var(--bs-primary) !important;
    }
</style>
@endpush
