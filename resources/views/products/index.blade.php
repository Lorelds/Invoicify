@extends('layouts.app')
@section('page_title', 'Inventaris Barang')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('dashboard') }}" class="btn btn-light border shadow-sm d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; border-radius: 50%;">
            <i class="ph-bold ph-arrow-left"></i>
        </a>
        <div>
            <h2 class="mb-1">{{ __('Inventaris Barang') }}</h2>
            <p class="text-muted mb-0">{{ __('Manage all items, pricing, and stock levels.') }}</p>
        </div>
    </div>
    <div>
        <a href="{{ route('products.create') }}" class="btn btn-primary">
            <i class="ph-bold ph-plus"></i>{{ __('Add New Product') }}</a>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form action="{{ route('products.index') }}" method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label text-muted small mb-1">{{ __('Search Products') }}</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="ph ph-magnifying-glass"></i></span>
                    <input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="{{ __('Search by name or SKU...') }}">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label text-muted small mb-1">{{ __('Category') }}</label>
                <select name="category" class="form-select">
                    <option value="">{{ __('All Categories') }}</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label text-muted small mb-1">{{ __('Sort By') }}</label>
                <select name="sort" class="form-select">
                    <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>{{ __('Newest First') }}</option>
                    <option value="name_asc" {{ request('sort') == 'name_asc' ? 'selected' : '' }}>{{ __('Name (A-Z)') }}</option>
                    <option value="stock_high" {{ request('sort') == 'stock_high' ? 'selected' : '' }}>{{ __('Highest Stock') }}</option>
                    <option value="stock_low" {{ request('sort') == 'stock_low' ? 'selected' : '' }}>{{ __('Lowest Stock') }}</option>
                    <option value="price_high" {{ request('sort') == 'price_high' ? 'selected' : '' }}>{{ __('Highest Price') }}</option>
                    <option value="price_low" {{ request('sort') == 'price_low' ? 'selected' : '' }}>{{ __('Lowest Price') }}</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="ph-bold ph-funnel me-1"></i>{{ __('Filter') }}</button>
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
                        <th class="ps-4">{{ __('SKU') }}</th>
                        <th>{{ __('Product Name') }}</th>
                        <th>{{ __('Category') }}</th>
                        <th>{{ __('Buy Price') }}</th>
                        <th>{{ __('Sell Price') }}</th>
                        <th>{{ __('Stock') }}</th>
                        <th class="text-end pe-4">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr>
                            <td class="ps-4">
                                <span class="badge bg-light text-secondary border font-monospace">{{ $product->sku }}</span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    @if($product->image_path)
                                        <div class="bg-light rounded overflow-hidden shadow-sm" style="width: 40px; height: 40px;">
                                            <img src="{{ Storage::url($product->image_path) }}" alt="{{ $product->name }}" class="w-100 h-100 object-fit-cover">
                                        </div>
                                    @else
                                        <div class="p-2 bg-light rounded text-success d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="ph-fill ph-package fs-5"></i>
                                        </div>
                                    @endif
                                    <a href="{{ route('products.show', $product->id) }}" class="fw-medium text-dark text-decoration-none hover-primary">
                                        {{ $product->name }}
                                    </a>
                                </div>
                            </td>
                            <td>
                                @if($product->category)
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border">{{ $product->category }}</span>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                            <td>Rp {{ number_format($product->buy_price, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format($product->sell_price, 0, ',', '.') }}</td>
                            <td>
                                @if($product->stock <= 5 && $product->stock > 0)
                                    <span class="badge bg-warning text-dark"><i class="ph-fill ph-warning"></i> {{ $product->stock }} Low</span>
                                @elseif($product->stock == 0)
                                    <span class="badge bg-danger"><i class="ph-fill ph-x-circle"></i>{{ __('Out of Stock') }}</span>
                                @else
                                    <span class="badge bg-success bg-opacity-25 text-success border border-success"><i class="ph-fill ph-check-circle"></i> {{ $product->stock }} In Stock</span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                <div class="btn-group">
                                    <a href="{{ route('products.show', $product->id) }}" class="btn btn-sm btn-light text-secondary border" title="{{ __('View Details') }}">
                                        <i class="ph-bold ph-eye"></i>
                                    </a>
                                    @if(auth()->check() && auth()->user()->role === 'super_admin')
                                    <a href="{{ route('products.edit', $product->id) }}" class="btn btn-sm btn-light text-primary border" title="{{ __('Edit') }}">
                                        <i class="ph-bold ph-pencil-simple"></i>
                                    </a>
                                    <form action="{{ route('products.destroy', $product->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this product? This action cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light text-danger border" title="{{ __('Delete') }}">
                                            <i class="ph-bold ph-trash"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="ph-fill ph-package fs-1 mb-2"></i>
                                    <p class="mb-0">{{ __('No products found. Start by adding a new product manually or scan a receipt.') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if(method_exists($products, 'hasPages') && $products->hasPages())
        <div class="card-footer bg-white border-top p-3">
            {{ $products->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
