@extends('layouts.app')
@section('page_title', 'Hutang Toko/Supplier')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('debts.index', ['type' => $type ?? 'receivable']) }}" class="btn btn-light border shadow-sm d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; border-radius: 50%;">
            <i class="ph-bold ph-arrow-left"></i>
        </a>
        <div>
            <h2 class="mb-1">{{ $store->name }}</h2>
            <p class="text-muted mb-0">
                @if(($type ?? 'receivable') === 'payable')
                    Hutang Kita ke Toko - Tagihan Belum Lunas
                @else
                    Piutang Pelanggan - Tagihan Belum Lunas
                @endif
            </p>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">ID Nota</th>
                                <th>Tanggal</th>
                                <th>Total Hutang</th>
                                <th>{{ __('Paid Amount') }}</th>
                                <th>Sisa</th>
                                <th>{{ __('Status') }}</th>
                                <th class="text-end pe-4">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($store->debts as $debt)
                                @php
                                    $remaining = $debt->amount - $debt->paid_amount;
                                @endphp
                                <tr>
                                    <td class="ps-4">
                                        <a href="{{ route('admin.receipts.show', $debt->receipt_id) }}" class="fw-medium text-primary text-decoration-none">
                                            #{{ $debt->receipt_id }}
                                        </a>
                                    </td>
                                    <td>{{ $debt->created_at->format('d M Y') }}</td>
                                    <td>Rp {{ number_format($debt->amount, 0, ',', '.') }}</td>
                                    <td class="text-success">Rp {{ number_format($debt->paid_amount, 0, ',', '.') }}</td>
                                    <td class="fw-bold text-danger">Rp {{ number_format($remaining, 0, ',', '.') }}</td>
                                    <td>
                                        @if($debt->status == 'lunas')
                                            <span class="badge bg-success">Lunas</span>
                                        @elseif($debt->status == 'partial')
                                            <span class="badge bg-warning text-dark">Sebagian</span>
                                        @else
                                            <span class="badge bg-danger">Belum Lunas</span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-4">
                                        @if($remaining > 0)
                                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#payModal{{ $debt->id }}">
                                                Bayar Sekarang
                                            </button>
                                        @else
                                            <button class="btn btn-sm btn-light border text-muted" disabled>Lunas Penuh</button>
                                        @endif
                                    </td>
                                </tr>

                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">Tidak ada hutang tercatat untuk toko ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@foreach($store->debts as $debt)
    @php
        $remaining = $debt->amount - $debt->paid_amount;
    @endphp
    <!-- Payment Modal for this Debt -->
    @if($remaining > 0)
    <div class="modal fade" id="payModal{{ $debt->id }}" tabindex="-1" aria-labelledby="payModalLabel{{ $debt->id }}" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom-0 pb-0 pt-4 px-4">
                    <h5 class="modal-title fw-bold" id="payModalLabel{{ $debt->id }}">Tambah Pembayaran (Nota #{{ $debt->receipt_id }})</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('debts.pay', $debt->id) }}" method="POST">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="alert alert-light border mb-4">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Total Hutang:</span>
                                <span class="fw-medium">Rp {{ number_format($debt->amount, 0, ',', '.') }}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Sisa Saldo:</span>
                                <span class="fw-bold text-danger">Rp {{ number_format($remaining, 0, ',', '.') }}</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-medium">Jumlah Pembayaran <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light fw-bold">Rp</span>
                                <input type="number" step="0.01" class="form-control" name="amount_paid" max="{{ $remaining }}" value="{{ $remaining }}" required>
                            </div>
                            <div class="form-text">Tidak bisa melebihi sisa saldo.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-medium">{{ __('Payment Tanggal') }}<span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="payment_date" value="{{ date('Y-m-d') }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-medium">{{ __('Payment Metode') }}</label>
                            <select class="form-select" name="payment_method">
                                <option value="Cash">Tunai</option>
                                <option value="Transfer Bank">Transfer Bank</option>
                                <option value="E-Wallet">E-Wallet</option>
                                <option value="Other">Lainnya</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary px-4"><i class="ph-bold ph-check-circle me-1"></i> Kirim Pembayaran</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
@endforeach

<div class="row">
    <div class="col-12">
        <h4 class="mb-3 mt-4">Histori Pembayaran Terkini</h4>
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Tanggal</th>
                                <th>Untuk Nota</th>
                                <th>Jumlah Dibayar</th>
                                <th>Metode</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                // Collect all payments for all debts of this store, sorted by date
                                $allPayments = collect();
                                foreach($store->debts as $d) {
                                    foreach($d->payments as $p) {
                                        $p->receipt_id = $d->receipt_id;
                                        $allPayments->push($p);
                                    }
                                }
                                $allPayments = $allPayments->sortByDesc('payment_date');
                            @endphp
                            
                            @forelse($allPayments as $payment)
                                <tr>
                                    <td class="ps-4">{{ $payment->payment_date->format('d M Y') }}</td>
                                    <td>
                                        <a href="{{ route('admin.receipts.show', $payment->receipt_id) }}" class="text-decoration-none">
                                            <span class="text-muted">#{{ $payment->receipt_id }}</span>
                                        </a>
                                    </td>
                                    <td class="fw-bold text-success">Rp {{ number_format($payment->amount_paid, 0, ',', '.') }}</td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><i class="ph-fill ph-wallet"></i> {{ $payment->payment_method ?? 'Tidak Diketahui' }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">Belum ada pembayaran yang dilakukan ke toko ini.</td>
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
