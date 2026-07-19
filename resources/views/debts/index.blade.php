@extends('layouts.app')
@section('page_title', 'Ringkasan Hutang')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('dashboard') }}" class="btn btn-light border shadow-sm d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; border-radius: 50%;">
            <i class="ph-bold ph-arrow-left"></i>
        </a>
        <div>
            <h2 class="mb-1">Hutang Aktif</h2>
            <p class="text-muted mb-0">Ringkasan semua sisa hutang dikelompokkan berdasarkan toko/supplier.</p>
        </div>
    </div>
    <form action="{{ route('debts.index') }}" method="GET" class="d-flex">
        <input type="hidden" name="type" value="{{ $type ?? 'receivable' }}">
        <div class="input-group">
            <span class="input-group-text bg-white border-end-0"><i class="ph-bold ph-magnifying-glass text-muted"></i></span>
            <input type="text" class="form-control border-start-0 ps-0" name="search" value="{{ $search ?? '' }}" placeholder="Cari nama toko/pelanggan..." style="min-width: 250px;">
            <button class="btn btn-primary" type="submit">Cari</button>
        </div>
    </form>
</div>

<!-- Simple Toggle -->
<div class="d-flex gap-2 mb-4">
    <a href="{{ route('debts.index', ['type' => 'receivable']) }}" class="btn d-flex align-items-center gap-2 px-4 py-2 {{ ($type ?? 'receivable') === 'receivable' ? 'btn-primary shadow-sm' : 'btn-light border text-muted' }}" style="border-radius: 8px;">
        <i class="ph-fill ph-hand-coins fs-5"></i>
        <span class="fw-semibold">Piutang Pelanggan</span>
    </a>
    <a href="{{ route('debts.index', ['type' => 'payable']) }}" class="btn d-flex align-items-center gap-2 px-4 py-2 {{ ($type ?? 'receivable') === 'payable' ? 'btn-danger shadow-sm' : 'btn-light border text-muted' }}" style="border-radius: 8px;">
        <i class="ph-fill ph-storefront fs-5"></i>
        <span class="fw-semibold">Hutang Toko</span>
    </a>
</div>

<div class="row">
    @forelse($storesWithDebts as $store)
        @php
            $totalDebt = $store->debts->sum('amount');
            $totalPaid = $store->debts->sum('paid_amount');
            $remaining = $totalDebt - $totalPaid;
            $percentage = $totalDebt > 0 ? ($totalPaid / $totalDebt) * 100 : 0;
            
            // Count active debts (hutang or partial)
            $activeDebtsCount = $store->debts->whereIn('status', ['hutang', 'partial'])->count();
        @endphp

        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="p-2 bg-light rounded text-primary">
                                <i class="ph-fill ph-storefront fs-4"></i>
                            </div>
                            <div>
                                <h5 class="mb-0">{{ $store->name }}</h5>
                                <span class="text-muted small">{{ $activeDebtsCount }} Nota Aktif</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">Total Hutang</span>
                            <span class="fw-medium">Rp {{ number_format($totalDebt, 0, ',', '.') }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">Total Dibayar</span>
                            <span class="fw-medium text-success">Rp {{ number_format($totalPaid, 0, ',', '.') }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-3 border-top pt-1 mt-1">
                            <span class="fw-semibold">Sisa Saldo</span>
                            <span class="fw-bold text-danger">Rp {{ number_format($remaining, 0, ',', '.') }}</span>
                        </div>
                        
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $percentage }}%" aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                    
                    <div class="mt-4 pt-3 border-top">
                        <a href="{{ route('debts.showStore', ['store' => $store->id, 'type' => $type ?? 'receivable']) }}" class="btn btn-light w-100 text-primary fw-medium border">
                            Kelola Hutang Toko <i class="ph-bold ph-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-5 text-center">
                    <i class="ph-fill ph-check-circle text-success mb-3" style="font-size: 4rem;"></i>
                    <h4 class="mb-2">Tidak Ada Hutang Ditemukan</h4>
                    <p class="text-muted">Saat ini Anda tidak memiliki hutang yang tercatat pada toko manapun. Semua nota inventaris Anda telah lunas!</p>
                    <a href="{{ route('admin.receipts.index') }}" class="btn btn-primary mt-3">Unggah Nota Baru</a>
                </div>
            </div>
        </div>
    @endforelse
</div>
@if($storesWithDebts->hasPages())
<div class="card mt-4 border-0 shadow-sm">
    <div class="card-body">
        {{ $storesWithDebts->links() }}
    </div>
</div>
@endif
@endsection
