@extends('layouts.app')
@section('page_title', 'Histori Nota')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('dashboard') }}" class="btn btn-light border shadow-sm d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; border-radius: 50%;">
            <i class="ph-bold ph-arrow-left"></i>
        </a>
        <div>
            <h2 class="mb-1">Histori Nota</h2>
            <p class="text-muted mb-0">Lihat semua nota yang dipindai dan status saat ini.</p>
        </div>
    </div>
    <div>
        <a href="{{ route('admin.receipts.upload.form') }}" class="btn btn-primary px-4 fw-medium">
            <i class="ph-bold ph-plus me-1"></i> Unggah Nota Baru
        </a>
    </div>
</div>

<div class="d-flex gap-2 mb-4">
    <a href="{{ route('admin.receipts.index', array_merge(request()->query(), ['type' => ''])) }}" class="btn {{ request('type') == '' ? 'btn-dark' : 'btn-outline-dark bg-white' }} px-4 rounded-pill fw-medium shadow-sm">
        Semua Nota
    </a>
    <a href="{{ route('admin.receipts.index', array_merge(request()->query(), ['type' => 'pembelian'])) }}" class="btn {{ request('type') == 'pembelian' ? 'btn-primary' : 'btn-outline-primary bg-white' }} px-4 rounded-pill fw-medium shadow-sm">
        <i class="ph-bold ph-download-simple me-1"></i> Pembelian
    </a>
    <a href="{{ route('admin.receipts.index', array_merge(request()->query(), ['type' => 'penjualan'])) }}" class="btn {{ request('type') == 'penjualan' ? 'btn-success' : 'btn-outline-success bg-white' }} px-4 rounded-pill fw-medium shadow-sm">
        <i class="ph-bold ph-upload-simple me-1"></i> Penjualan
    </a>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form action="{{ route('admin.receipts.index') }}" method="GET" class="row g-3 align-items-end">
            <!-- Hidden input to preserve type filter when searching -->
            @if(request('type'))
                <input type="hidden" name="type" value="{{ request('type') }}">
            @endif
            
            <div class="col-md-6">
                <label class="form-label text-muted small mb-1">Cari Nota</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="ph ph-magnifying-glass"></i></span>
                    <input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="Cari berdasarkan ID, Status, atau Nama Toko...">
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label text-muted small mb-1">Urutkan</label>
                <select name="sort" class="form-select">
                    <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Terbaru</option>
                    <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Terlama</option>
                    <option value="highest_amount" {{ request('sort') == 'highest_amount' ? 'selected' : '' }}>Jumlah Tertinggi</option>
                    <option value="lowest_amount" {{ request('sort') == 'lowest_amount' ? 'selected' : '' }}>Jumlah Terendah</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="ph-bold ph-funnel me-1"></i> Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">ID Nota</th>
                        <th>Tanggal</th>
                        <th>Toko / Supplier</th>
                        <th>Tipe</th>
                        <th>Total Harga</th>
                        <th>Status Pembayaran</th>
                        <th>Status Validasi</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($receipts as $receipt)
                        <tr>
                            <td class="ps-4 fw-medium text-primary">#{{ $receipt->receipt_number ?? str_pad($receipt->id, 5, '0', STR_PAD_LEFT) }}</td>
                            <td>{{ $receipt->transaction_date ? \Carbon\Carbon::parse($receipt->transaction_date)->format('d M Y') : 'Unknown' }}</td>
                            <td>
                                @if($receipt->store)
                                    {{ $receipt->store->name }}
                                @else
                                    {{ $receipt->store_name ?? 'Unknown' }}
                                @endif
                            </td>
                            <td>
                                @if($receipt->type == 'pembelian')
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25"><i class="ph-bold ph-download-simple me-1"></i> Pembelian</span>
                                @else
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25"><i class="ph-bold ph-upload-simple me-1"></i> Penjualan</span>
                                @endif
                            </td>
                            <td class="fw-medium">Rp {{ number_format($receipt->total_amount, 0, ',', '.') }}</td>
                            <td>
                                @if($receipt->payment_status == 'lunas')
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1"><i class="ph-fill ph-check-circle me-1"></i> Lunas</span>
                                @else
                                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2 py-1"><i class="ph-fill ph-clock me-1"></i> Belum Lunas (Hutang)</span>
                                @endif
                            </td>
                            <td>
                                @if($receipt->status == 'validated')
                                    <span class="badge bg-primary">Tervalidasi</span>
                                @else
                                    <span class="badge bg-secondary">Menunggu Validasi</span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                @if($receipt->status == 'pending')
                                    <div class="btn-group">
                                        <a href="{{ route('admin.receipts.validate', $receipt->id) }}" class="btn btn-sm btn-outline-warning">
                                            <i class="ph-bold ph-check-square-offset"></i> Validasi
                                        </a>
                                        @if(auth()->check() && auth()->user()->role === 'super_admin')
                                        <form action="{{ route('admin.receipts.destroy', $receipt->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus nota yang belum divalidasi ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light border text-danger" title="Hapus Nota">
                                                <i class="ph-bold ph-trash"></i>
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                @else
                                    <div class="btn-group">
                                        <a href="{{ route('admin.receipts.show', $receipt->id) }}" class="btn btn-sm btn-light border text-primary">
                                            <i class="ph-bold ph-eye"></i> Lihat Detail
                                        </a>
                                        @if(auth()->check() && auth()->user()->role === 'super_admin')
                                        <form action="{{ route('admin.receipts.destroy', $receipt->id) }}" method="POST" class="d-inline" onsubmit="return confirm('PERINGATAN: Menghapus nota ini akan mengurangi barang dari inventaris dan membatalkan hutang toko! Apakah Anda yakin?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light border text-danger" title="Hapus Nota">
                                                <i class="ph-bold ph-trash"></i>
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="ph-fill ph-receipt fs-1 mb-2 text-light"></i>
                                <h5>Tidak Ada Nota Ditemukan</h5>
                                <p>Anda belum mengunggah nota apapun.</p>
                                <a href="{{ route('admin.receipts.upload.form') }}" class="btn btn-sm btn-primary mt-2">Unggah Sekarang</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($receipts->hasPages())
    <div class="card-footer bg-white border-top-0 pt-4">
        {{ $receipts->links() }}
    </div>
    @endif
</div>
@endsection
