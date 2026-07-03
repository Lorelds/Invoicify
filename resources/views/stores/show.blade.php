@extends('layouts.app')
@section('page_title', 'Store Details')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('stores.index') }}" class="btn btn-light border shadow-sm d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; border-radius: 50%;">
            <i class="ph-bold ph-arrow-left"></i>
        </a>
        <div>
            <h2 class="mb-1">{{ $store->name }}</h2>
            <p class="text-muted mb-0">Detailed information about this vendor and their transactions.</p>
        </div>
    </div>
    <div>
        <a href="{{ route('debts.showStore', $store->id) }}" class="btn btn-outline-primary px-4 fw-medium">
            <i class="ph-bold ph-money me-1"></i> View Debts
        </a>
        @if(auth()->check() && auth()->user()->role === 'super_admin')
        <a href="{{ route('stores.edit', $store->id) }}" class="btn btn-primary px-4 fw-medium ms-2">
            <i class="ph-bold ph-pencil-simple me-1"></i>{{ __('Edit Store') }}</a>
        @endif
    </div>
</div>

<div class="row">
    <!-- Store Details Column -->
    <div class="col-lg-4 mb-4 mb-lg-0">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4">
                <h5 class="fw-semibold text-dark"><i class="ph ph-storefront me-2"></i> Store Information</h5>
            </div>
            <div class="card-body p-4">
                <div class="mb-4">
                    <div class="text-muted small mb-1">{{ __('Store Name') }}</div>
                    <div class="fw-semibold fs-5 text-dark">{{ $store->name }}</div>
                </div>
                <div class="mb-4">
                    <div class="text-muted small mb-1">Address</div>
                    <div class="fw-medium text-dark">{{ $store->address ?? 'No address provided' }}</div>
                </div>
                <div class="mb-4">
                    <div class="text-muted small mb-1">Phone Number</div>
                    <div class="fw-medium text-dark">
                        @if($store->phone)
                            <a href="tel:{{ $store->phone }}" class="text-decoration-none">{{ $store->phone }}</a>
                        @else
                            -
                        @endif
                    </div>
                </div>
                <div class="mt-4 pt-4 border-top">
                    <div class="text-muted small mb-1">{{ __('Total Receipts') }}</div>
                    <div class="fw-bold fs-3 text-primary">{{ $store->receipts()->count() }}</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Receipts Column -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4">
                <h5 class="fw-semibold text-dark"><i class="ph ph-receipt me-2"></i> Transaction History</h5>
            </div>
            <div class="card-body p-0 mt-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Receipt ID</th>
                                <th>Date</th>
                                <th>{{ __('Status') }}</th>
                                <th class="text-end pe-4">{{ __('Total Amount') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($store->receipts()->orderBy('created_at', 'desc')->get() as $receipt)
                                <tr style="cursor: pointer;" onclick="window.location='{{ route('admin.receipts.show', $receipt->id) }}'">
                                    <td class="ps-4 fw-medium text-primary">#{{ $receipt->receipt_number ?? str_pad($receipt->id, 5, '0', STR_PAD_LEFT) }}</td>
                                    <td>{{ $receipt->transaction_date ? \Carbon\Carbon::parse($receipt->transaction_date)->format('d M Y') : 'Unknown' }}</td>
                                    <td>
                                        @if($receipt->status == 'pending')
                                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2 py-1">Pending</span>
                                        @else
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">Validated</span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-4 fw-medium">Rp {{ number_format($receipt->total_amount, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        <i class="ph-fill ph-receipt fs-1 text-light mb-2"></i>
                                        <p class="mb-0">No transactions recorded for this store.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
