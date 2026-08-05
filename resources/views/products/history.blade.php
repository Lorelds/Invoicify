@extends('layouts.app')
@section('page_title', __('Stock History'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">{{ __('Stock History') }}</h2>
        <p class="text-muted mb-0">{{ __('Track all inventory changes, manual adjustments, and receipt entries.') }}</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('products.index') }}" class="btn btn-light border">
            <i class="ph-bold ph-arrow-left me-1"></i> {{ __('Back to Products') }}
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">{{ __('Date & Time') }}</th>
                        <th>{{ __('Product') }}</th>
                        <th>{{ __('Type') }}</th>
                        <th>{{ __('Change') }}</th>
                        <th>{{ __('Balance') }}</th>
                        <th>{{ __('Reference') }}</th>
                        <th>{{ __('User') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movements as $movement)
                        <tr>
                            <td class="ps-4 text-nowrap">
                                <div class="fw-medium">{{ $movement->created_at->format('d M Y') }}</div>
                                <div class="small text-muted">{{ $movement->created_at->format('H:i') }}</div>
                            </td>
                            <td>
                                @if($movement->product)
                                    <a href="{{ route('products.show', $movement->product->id) }}" class="fw-medium text-decoration-none">
                                        {{ $movement->product->name }}
                                    </a>
                                @else
                                    <span class="text-muted">{{ __('Unknown Product') }}</span>
                                @endif
                            </td>
                            <td>
                                @if(in_array($movement->type, ['in', 'pembelian']))
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle">
                                        <i class="ph-bold ph-arrow-down-left me-1"></i> Stock In
                                    </span>
                                @elseif(in_array($movement->type, ['out', 'penjualan']))
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle">
                                        <i class="ph-bold ph-arrow-up-right me-1"></i> Stock Out
                                    </span>
                                @else
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle">
                                        <i class="ph-bold ph-pencil-simple me-1"></i> Adjustment
                                    </span>
                                @endif
                            </td>
                            <td>
                                <span class="fw-bold {{ $movement->quantity > 0 ? 'text-success' : ($movement->quantity < 0 ? 'text-danger' : 'text-muted') }}">
                                    {{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}
                                </span>
                            </td>
                            <td>
                                <span class="fw-semibold">{{ $movement->balance }}</span>
                            </td>
                            <td>
                                @if($movement->receipt_id && $movement->receipt)
                                    <a href="{{ route('admin.receipts.show', $movement->receipt_id) }}" class="text-decoration-none text-primary">
                                        {{ $movement->receipt->receipt_number ?? 'Receipt #' . $movement->receipt_id }}
                                    </a>
                                    @if($movement->receipt->store)
                                        <div class="small text-muted text-truncate" style="max-width: 150px;">
                                            {{ $movement->receipt->store->name }}
                                        </div>
                                    @endif
                                @else
                                    <span class="text-muted small"><i class="ph-fill ph-hand-pointing me-1"></i> {{ __('Manual Edit') }}</span>
                                    @if($movement->notes)
                                        <div class="small text-muted text-truncate" style="max-width: 150px;" title="{{ $movement->notes }}">
                                            {{ $movement->notes }}
                                        </div>
                                    @endif
                                @endif
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if($movement->user)
                                        <img src="https://ui-avatars.com/api/?name={{ urlencode($movement->user->name) }}&background=random&color=fff&size=24" class="rounded-circle" alt="">
                                        <span class="small">{{ $movement->user->name }}</span>
                                    @else
                                        <span class="text-muted small">System</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="ph-fill ph-clock-counter-clockwise fs-1 d-block mb-2 text-light"></i>
                                {{ __('No stock movements found.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($movements->hasPages())
            <div class="px-4 py-3 border-top">
                {{ $movements->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
</div>
@endsection
