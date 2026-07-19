@extends('layouts.app')
@section('page_title', 'Detail Nota')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('admin.receipts.index') }}" class="btn btn-light border shadow-sm d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; border-radius: 50%;">
            <i class="ph-bold ph-arrow-left"></i>
        </a>
        <div>
            <h2 class="mb-1">Receipt #{{ $receipt->receipt_number ?? str_pad($receipt->id, 5, '0', STR_PAD_LEFT) }}</h2>
            <p class="text-muted mb-0">Detailed view of the scanned receipt and its extracted items.</p>
        </div>
    </div>
    <div>
        @if($receipt->status == 'pending')
            <a href="{{ route('admin.receipts.validate', $receipt->id) }}" class="btn btn-warning px-4 fw-medium">
                <i class="ph-bold ph-check-square-offset me-1"></i> Validasi Now
            </a>
        @endif
    </div>
</div>

<div class="row">
    <!-- Gambar Nota Column -->
    <div class="col-lg-5 mb-4 mb-lg-0">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4">
                <h5 class="fw-semibold text-dark"><i class="ph ph-image me-2"></i> Original Gambar Nota</h5>
            </div>
            <div class="card-body p-4 text-center">
                <div class="border rounded p-2 bg-light d-flex justify-content-center align-items-center" style="min-height: 400px;">
                    <a href="{{ Storage::url($receipt->image_path) }}" target="_blank" title="Click to view full size">
                        <img src="{{ Storage::url($receipt->image_path) }}" alt="Gambar Nota" class="img-fluid rounded" style="object-fit: contain; max-height: 600px; width: auto;">
                    </a>
                </div>
                <div class="mt-3 text-muted small"><i class="ph-fill ph-magnifying-glass-plus"></i> Click image to view full size</div>
            </div>
        </div>
    </div>
    
    <!-- Detail Nota Column -->
    <div class="col-lg-7">
        <!-- Summary Card -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4">
                <h5 class="fw-semibold text-dark"><i class="ph ph-info me-2"></i> Transaction Summary</h5>
            </div>
            <div class="card-body p-4">
                <div class="row mb-4">
                    <div class="col-sm-6 mb-3 mb-sm-0">
                        <div class="text-muted small mb-1">Toko / Supplier</div>
                        <div class="fw-semibold fs-5 text-dark">
                            @if($receipt->store)
                                <a href="{{ route('stores.show', $receipt->store->id) }}" class="text-decoration-none">{{ $receipt->store->name }}</a>
                            @else
                                {{ $receipt->store_name ?? 'Unknown' }}
                            @endif
                        </div>
                    </div>
                    <div class="col-sm-6 text-sm-end">
                        <div class="text-muted small mb-1">Transaction Tanggal</div>
                        <div class="fw-medium text-dark">{{ $receipt->transaction_date ? \Carbon\Carbon::parse($receipt->transaction_date)->format('l, d F Y') : 'Unknown' }}</div>
                    </div>
                </div>
                
                <div class="row bg-light rounded p-3 align-items-center">
                    <div class="col-sm-6 mb-2 mb-sm-0">
                        <div class="text-muted small mb-1">Total Harga</div>
                        <div class="fw-bold fs-4 text-primary">Rp {{ number_format($receipt->total_amount, 0, ',', '.') }}</div>
                    </div>
                    <div class="col-sm-6 text-sm-end">
                        <div class="text-muted small mb-1">Status Pembayaran</div>
                        <div>
                            @if($receipt->payment_status == 'lunas')
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2"><i class="ph-fill ph-check-circle me-1"></i> Lunas</span>
                            @else
                                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-3 py-2"><i class="ph-fill ph-clock me-1"></i> Belum Lunas (Hutang)</span>
                                @if($receipt->debt)
                                    <a href="{{ route('debts.showStore', $receipt->store_id) }}" class="d-block mt-2 small text-decoration-none">View Debt Details <i class="ph-bold ph-arrow-right"></i></a>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>

                <div class="mt-4 border-top pt-3 d-flex justify-content-between small text-muted">
                    <div>Validasid By: <span class="fw-medium text-dark">{{ $receipt->validatedBy->name ?? 'System' }}</span></div>
                    <div>Validasid At: <span class="fw-medium text-dark">{{ $receipt->validated_at ? \Carbon\Carbon::parse($receipt->validated_at)->format('d M Y, H:i') : 'N/A' }}</span></div>
                </div>
            </div>
        </div>
        
        <!-- Extracted Items -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4">
                <h5 class="fw-semibold text-dark"><i class="ph ph-list-numbers me-2"></i> Extracted Items</h5>
            </div>
            <div class="card-body p-0 mt-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Nama Barang</th>
                                <th class="text-center">Jml</th>
                                <th class="text-end">Harga Satuan</th>
                                <th class="text-end pe-4">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($receipt->items as $item)
                                <tr>
                                    <td class="ps-4 fw-medium">{{ $item->product_name }}</td>
                                    <td class="text-center">{{ $item->quantity }}</td>
                                    <td class="text-end text-muted">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                                    <td class="text-end pe-4 fw-medium">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">No items were recorded for this receipt.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($receipt->items->count() > 0)
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="3" class="text-end fw-bold">Grand Total:</td>
                                <td class="text-end pe-4 fw-bold text-primary fs-5">Rp {{ number_format($receipt->total_amount, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
        
    </div>
</div>
@endsection
