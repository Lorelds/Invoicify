@extends('layouts.app')
@section('page_title', 'Detail Barang')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('products.index') }}" class="btn btn-light border shadow-sm d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; border-radius: 50%;">
            <i class="ph-bold ph-arrow-left"></i>
        </a>
        <div>
            <h2 class="mb-1">Detail Barang</h2>
            <p class="text-muted mb-0">View complete information and history for {{ $product->name }}</p>
        </div>
    </div>
    <div class="d-flex gap-2">
        @if(auth()->check() && auth()->user()->role === 'super_admin')
        <a href="{{ route('products.edit', $product->id) }}" class="btn btn-primary">
            <i class="ph-bold ph-pencil-simple"></i>{{ __('Ubah Barang') }}</a>
        @endif
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-4 mb-md-0">
        <!-- Image & Status Card -->
        <div class="card border-0 shadow-sm overflow-hidden h-100">
            <!-- Product Image Hero -->
            <div class="bg-light d-flex align-items-center justify-content-center position-relative" style="height: 280px;">
                @if($product->image_path)
                    <img src="{{ Storage::url($product->image_path) }}" alt="{{ $product->name }}" class="img-fluid w-100 h-100 object-fit-cover">
                @else
                    <div class="text-center text-muted">
                        <i class="ph-fill ph-image mb-2" style="font-size: 4rem; opacity: 0.5;"></i>
                        <p class="mb-0 small fw-medium">No Image Available</p>
                    </div>
                @endif
                <div class="position-absolute top-0 end-0 p-3">
                    @if($product->stock <= 5 && $product->stock > 0)
                        <span class="badge bg-warning text-dark shadow-sm px-3 py-2 rounded-pill"><i class="ph-fill ph-warning me-1"></i> Low Stock</span>
                    @elseif($product->stock == 0)
                        <span class="badge bg-danger shadow-sm px-3 py-2 rounded-pill"><i class="ph-fill ph-x-circle me-1"></i> Out of Stock</span>
                    @else
                        <span class="badge bg-success shadow-sm px-3 py-2 rounded-pill"><i class="ph-fill ph-check-circle me-1"></i> In Stock</span>
                    @endif
                </div>
            </div>

            <div class="card-body p-4">
                <h4 class="mb-1 fw-bold">{{ $product->name }}</h4>
                <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
                    <p class="text-muted mb-0 font-monospace small"><i class="ph ph-barcode me-1"></i>{{ $product->sku }}</p>
                    @if($product->category)
                        <span class="badge bg-light text-secondary border px-2 py-1">{{ $product->category }}</span>
                    @endif
                </div>
                
                <div class="row g-3 mb-4">
                    <div class="col-6">
                        <div class="p-3 bg-light rounded-3 text-center h-100 border border-light-subtle">
                            <div class="text-muted small fw-medium mb-1"><i class="ph ph-stack me-1"></i>Stock</div>
                            <div class="fs-4 fw-bold {{ $product->stock == 0 ? 'text-danger' : 'text-dark' }}">{{ $product->stock }}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 bg-primary bg-opacity-10 rounded-3 text-center h-100 border border-primary border-opacity-25">
                            <div class="text-primary small fw-medium mb-1"><i class="ph ph-trend-up me-1"></i>Margin</div>
                            <div class="fs-4 fw-bold text-primary">
                                @if($product->buy_price > 0)
                                    {{ number_format((($product->sell_price - $product->buy_price) / $product->buy_price) * 100, 1) }}%
                                @else
                                    100%
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-3 bg-light rounded-3 text-start mb-4">
                    <div class="text-muted small fw-bold mb-3 text-uppercase tracking-wider border-bottom pb-2">Pricing</div>
                    
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted"><i class="ph ph-shopping-cart me-2"></i>{{ __('Buy Price') }}</span>
                        <span class="fw-bold text-danger">Rp {{ number_format($product->buy_price, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted"><i class="ph ph-tag me-2"></i>{{ __('Sell Price') }}</span>
                        <span class="fw-bold text-success fs-5">Rp {{ number_format($product->sell_price, 0, ',', '.') }}</span>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-top">
                    <button type="button" class="btn btn-outline-secondary w-100 fw-medium" data-bs-toggle="modal" data-bs-target="#adjustStockModal">
                        <i class="ph-bold ph-faders me-2"></i> Adjust Stock Manually
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <!-- Price History Graph -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4">
                <h5 class="fw-semibold text-dark"><i class="ph ph-trend-up me-2"></i> Price History</h5>
                <p class="text-muted small mb-0">Track purchase price changes over time based on scanned receipts.</p>
            </div>
            <div class="card-body p-4">
                <div style="position: relative; height: 300px; width: 100%;">
                    <canvas id="priceHistoryChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Stock History Table -->
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4">
                <h5 class="fw-semibold text-dark"><i class="ph ph-clock-counter-clockwise me-2"></i> Stock Movement History</h5>
                <p class="text-muted small mb-0">Record of all stock changes (in and out).</p>
            </div>
            <div class="card-body p-0 mt-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Date</th>
                                <th>Type</th>
                                <th>Qty</th>
                                <th>Balance</th>
                                <th class="pe-4">{{ __('Notes') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stock_movements as $movement)
                                <tr>
                                    <td class="ps-4 text-muted small">{{ $movement->created_at->format('d M Y, H:i') }}</td>
                                    <td>
                                        @if($movement->type == 'in')
                                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1"><i class="ph-bold ph-arrow-down-left me-1"></i> IN</span>
                                        @else
                                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2 py-1"><i class="ph-bold ph-arrow-up-right me-1"></i> OUT</span>
                                        @endif
                                    </td>
                                    <td class="fw-medium {{ $movement->type == 'in' ? 'text-primary' : 'text-warning' }}">
                                        {{ $movement->type == 'in' ? '+' : '-' }}{{ $movement->quantity }}
                                    </td>
                                    <td class="fw-bold">{{ $movement->balance }}</td>
                                    <td class="pe-4 small text-muted">
                                        @if($movement->receipt_id)
                                            <a href="{{ route('admin.receipts.show', $movement->receipt_id) }}" class="text-decoration-none">
                                                {{ $movement->notes }}
                                            </a>
                                        @else
                                            {{ $movement->notes }}
                                        @endif
                                        @if($movement->user)
                                            <div class="mt-1" style="font-size: 0.8em; opacity: 0.8;">
                                                <i class="ph ph-user me-1"></i>By: {{ $movement->user->name }}
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No stock movements recorded yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Adjust Stock Modal -->
<div class="modal fade" id="adjustStockModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-semibold">Adjust Stock Manually</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('products.adjust_stock', $product->id) }}" method="POST">
                @csrf
                <div class="modal-body pb-0 pt-4">
                    <div class="mb-3">
                        <label class="form-label fw-medium">Type of Adjustment <span class="text-danger">*</span></label>
                        <div class="d-flex gap-3">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="type" id="adjust_in" value="in" required>
                                <label class="form-check-label fw-medium text-primary" for="adjust_in">
                                    <i class="ph-bold ph-arrow-down-left"></i> Stock IN (Add)
                                </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="type" id="adjust_out" value="out" required>
                                <label class="form-check-label fw-medium text-warning" for="adjust_out">
                                    <i class="ph-bold ph-arrow-up-right"></i> Stock OUT (Reduce)
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="quantity" class="form-label fw-medium">Quantity <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="quantity" name="quantity" min="0.01" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label fw-medium">Notes / Reason <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="notes" name="notes" placeholder="e.g. Broken item, returns, etc." required>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-2 pb-4 px-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary px-4">Save Adjustment</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    (function() {
        const ctx = document.getElementById('priceHistoryChart').getContext('2d');
        const labels = @json($price_history_dates);
        const data = @json($price_history_values);
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Buy Price (Rp)',
                    data: data,
                    borderColor: '#0ea5e9',
                    backgroundColor: 'rgba(14, 165, 233, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.2,
                    pointBackgroundColor: '#0ea5e9',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
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
                        beginAtZero: false,
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
@endpush
