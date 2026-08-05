@extends('layouts.app')
@section('page_title', 'Validate OCR Data')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('admin.receipts.index') }}" class="btn btn-light border shadow-sm d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; border-radius: 50%;">
            <i class="ph-bold ph-arrow-left"></i>
        </a>
        <div>
            <h2 class="mb-1">{{ __('Tinjau Hasil OCR') }}</h2>
            <p class="text-muted mb-0">{{ __('Harap periksa dan koreksi data yang diekstrak sebelum menyimpan ke inventaris.') }}</p>
        </div>
    </div>
</div>

<div class="row">
        <!-- Image Preview Column -->
        <div class="col-lg-5 mb-4 mb-lg-0">
            <div class="card border-0 shadow-sm h-100 sticky-top" style="top: 90px; z-index: 10;">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4">
                    <h5 class="fw-semibold text-dark"><i class="ph ph-image me-2"></i> {{ __('Gambar Nota') }}</h5>
                </div>
                <div class="card-body p-4 text-center">
                    <div class="border rounded p-2 bg-light d-flex justify-content-center align-items-center" style="min-height: 400px; max-height: 600px; overflow: hidden;">
                        @if($receipt->image_path)
                            <img src="{{ Storage::url($receipt->image_path) }}" alt="Receipt" class="img-fluid rounded" style="object-fit: contain; max-height: 100%; width: auto;">
                        @else
                            <div class="text-center text-muted w-100 px-4">
                                <i class="ph-fill ph-keyboard" style="font-size: 5rem; opacity: 0.5;"></i>
                                <h4 class="mt-3 fw-medium">Input Manual</h4>
                                <p class="mb-4">Tidak ada gambar nota yang diunggah.</p>
                                
                                <form action="{{ route('admin.receipts.uploadImage', $receipt->id) }}" method="POST" enctype="multipart/form-data" class="mt-4 p-3 border rounded bg-white shadow-sm">
                                    @csrf
                                    <label class="form-label small fw-bold text-start w-100">Tambahkan Gambar (Opsional)</label>
                                    <input type="file" name="receipt_image" class="form-control form-control-sm mb-2" accept="image/jpeg,image/png,image/jpg" required>
                                    <button type="submit" class="btn btn-sm btn-primary w-100">
                                        <i class="ph-bold ph-upload-simple me-1"></i> Unggah Gambar
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Validation Form Column -->
        <div class="col-lg-7">
            <form action="{{ route('admin.receipts.validateSubmit', $receipt->id) }}" method="POST">
                @csrf
                
                @if ($errors->any())
                    <div class="alert alert-danger mb-4 border-0 shadow-sm">
                        <div class="d-flex align-items-center mb-2">
                            <i class="ph-fill ph-warning-circle fs-4 me-2"></i>
                            <h6 class="mb-0 fw-bold">Please fix the following errors:</h6>
                        </div>
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            <!-- General Info -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4">
                    <h5 class="fw-semibold text-dark"><i class="ph ph-receipt me-2"></i> {{ __('Informasi Umum') }}</h5>
                </div>
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">{{ __('Toko/Supplier') }} <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="ph ph-storefront"></i></span>
                                <select class="form-select @error('store_id') is-invalid @enderror" id="store_id" name="store_id" required>
                                    <option value="" disabled {{ !$receipt->store_id ? 'selected' : '' }}>-- Select Store/Vendor --</option>
                                    @foreach($stores as $store)
                                        <option value="{{ $store->id }}" {{ (old('store_id', $receipt->store_id) == $store->id) ? 'selected' : '' }}>
                                            {{ $store->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-text mt-2">
                                <a href="#" data-bs-toggle="modal" data-bs-target="#addVendorModal" class="text-decoration-none"><i class="ph-bold ph-plus"></i> Add new vendor</a>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="receipt_number" class="form-label fw-medium">{{ __('Nomor Nota') }}</label>
                            <input type="text" class="form-control" id="receipt_number" name="receipt_number" value="{{ old('receipt_number', $receipt->receipt_number ?? ($parsedData['receipt_number'] ?? '')) }}">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="transaction_date" class="form-label fw-medium">{{ __('Tanggal Transaksi') }} <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="transaction_date" name="transaction_date" value="{{ old('transaction_date', $receipt->transaction_date ? \Carbon\Carbon::parse($receipt->transaction_date)->format('Y-m-d') : date('Y-m-d')) }}" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="total_amount" class="form-label fw-medium">{{ __('Total Harga') }} <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light fw-bold">Rp</span>
                                <input type="number" step="0.01" class="form-control" id="total_amount" name="total_amount" value="{{ old('total_amount', $receipt->total_amount) }}" required>
                            </div>
                            <div class="form-text mt-1"><i class="ph ph-magic-wand"></i> {{ __('Akan diperbarui otomatis berdasarkan subtotal barang.') }}</div>
                        </div>
                        <div class="col-md-6 mb-3 d-flex align-items-end">
                            <!-- Removed USD to IDR Button -->
                        </div>
                    </div>
                    <div class="row border-top pt-3 mt-1 bg-light bg-opacity-50 rounded">
                        <div class="col-md-4 mb-3">
                            <label for="payment_status" class="form-label fw-medium">{{ __('Status Pembayaran') }} <span class="text-danger">*</span></label>
                            <select class="form-select border-primary" id="payment_status" name="payment_status" required>
                                <option value="lunas" {{ $receipt->payment_status === 'lunas' ? 'selected' : '' }}>{{ __('Lunas') }}</option>
                                <option value="hutang" {{ $receipt->payment_status === 'hutang' ? 'selected' : '' }}>{{ __('Belum Lunas (Hutang)') }}</option>
                                <option value="partial" {{ $receipt->payment_status === 'partial' ? 'selected' : '' }}>{{ __('Dibayar Sebagian (DP)') }}</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3" id="amount_paid_container" style="display: none;">
                            <label for="amount_paid" class="form-label fw-medium">Jumlah DP / Dibayar <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-white fw-bold border-warning text-warning">Rp</span>
                                <input type="number" step="0.01" class="form-control border-warning" id="amount_paid" name="amount_paid" value="{{ old('amount_paid') }}" placeholder="0">
                            </div>
                        </div>

                        <div class="col-md-4 mb-3" id="payment_method_container" style="display: none;">
                            <label for="payment_method" class="form-label fw-medium text-success"><i class="ph-fill ph-check-circle me-1"></i> {{ __('Metode Pembayaran') }}</label>
                            <select class="form-select border-success" id="payment_method" name="payment_method">
                                <option value="Cash">Cash</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="E-Wallet">E-Wallet</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Items Validation -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4 d-flex justify-content-between align-items-center">
                    <h5 class="fw-semibold text-dark mb-0"><i class="ph ph-list-numbers me-2"></i> {{ __('Barang yang Diekstrak') }}</h5>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addItemBtn">
                        <i class="ph-bold ph-plus"></i> {{ __('Tambah Barang') }}
                    </button>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle" id="itemsTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 25%">{{ __('Nama Barang') }}</th>
                                    <th style="width: 15%">{{ __('Kategori') }} <small class="text-muted fw-normal">({{ __('Opsional') }})</small></th>
                                    <th style="width: 12%">{{ __('Kuantitas') }}</th>
                                    <th style="width: 13%">M/Kg <small class="text-muted fw-normal">({{ __('Opsional') }})</small></th>
                                    <th style="width: 15%">{{ __('Harga Satuan') }}</th>
                                    <th style="width: 15%">{{ __('Subtotal') }}</th>
                                    <th style="width: 5%"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $items = old('items', $parsedData['items'] ?? []);
                                @endphp
                                
                                @forelse($items as $index => $item)
                                    <tr class="item-row">
                                        <td>
                                            <input type="text" class="form-control" name="items[{{ $index }}][name]" value="{{ old('items.'.$index.'.name', $item['name'] ?? '') }}" list="productList" style="min-width: 150px;" required>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control" name="items[{{ $index }}][category]" list="categoryList" placeholder="e.g. Paku" value="{{ old('items.'.$index.'.category', $item['category'] ?? '') }}" style="min-width: 120px;">
                                        </td>
                                        <td>
                                            <input type="number" step="1" min="1" class="form-control qty-input px-2" name="items[{{ $index }}][quantity]" value="{{ old('items.'.$index.'.quantity', $item['quantity'] ?? 1) }}" style="min-width: 80px;" required>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" min="0.01" class="form-control measure-input px-2" name="items[{{ $index }}][measure]" value="{{ old('items.'.$index.'.measure', $item['measure'] ?? 1) }}" style="min-width: 80px;">
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" class="form-control price-input px-2" name="items[{{ $index }}][unit_price]" value="{{ old('items.'.$index.'.unit_price', $item['unit_price'] ?? 0) }}" style="min-width: 120px;" required>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control subtotal-input bg-light" value="{{ (float)($item['quantity'] ?? 1) * (float)($item['measure'] ?? 1) * (float)($item['unit_price'] ?? 0) }}" style="min-width: 120px;" readonly tabindex="-1">
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-light text-danger remove-item-btn"><i class="ph-bold ph-trash"></i></button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr id="noItemsRow">
                                        <td colspan="7" class="text-center text-muted py-3">
                                            {{ __('Barang tidak ditemukan. Mulai dengan menambahkan barang baru secara manual atau scan nota.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="alert alert-info mt-3 border-0 d-flex align-items-center">
                        <i class="ph-fill ph-info fs-4 me-2"></i>
                        <div>{{ __('Barang akan dicocokkan otomatis dengan barang yang sudah ada atau dibuat sebagai barang baru.') }}</div>
                    </div>
                </div>
            </div>
            
            <div class="d-flex justify-content-end gap-3 mb-5">
                <a href="{{ route('admin.receipts.index') }}" class="btn btn-light border px-4 py-2">{{ __('Batal') }}</a>
                <button type="submit" name="action" value="draft" class="btn btn-outline-primary px-4 py-2 fw-semibold" formnovalidate>
                    <i class="ph-bold ph-floppy-disk me-1"></i> {{ __('Simpan Draft') }}
                </button>
                <button type="submit" name="action" value="confirm" class="btn btn-success px-5 py-2 fw-semibold">
                    <i class="ph-bold ph-check-circle me-1"></i> {{ __('Konfirmasi & Simpan ke Inventaris') }}
                </button>
            </div>
            </form>
        </div>
    </div>

<template id="itemRowTemplate">
    <tr class="item-row">
        <td><input type="text" class="form-control" name="items[__INDEX__][name]" list="productList" style="min-width: 150px;" required></td>
        <td><input type="text" class="form-control" name="items[__INDEX__][category]" list="categoryList" placeholder="e.g. Paku" style="min-width: 120px;"></td>
        <td><input type="number" step="1" min="1" class="form-control qty-input px-2" name="items[__INDEX__][quantity]" value="1" style="min-width: 80px;" required></td>
        <td><input type="number" step="0.01" min="0.01" class="form-control measure-input px-2" name="items[__INDEX__][measure]" value="1" style="min-width: 80px;"></td>
        <td><input type="number" step="0.01" class="form-control price-input px-2" name="items[__INDEX__][unit_price]" value="0" style="min-width: 120px;" required></td>
        <td><input type="number" class="form-control subtotal-input bg-light" value="0" style="min-width: 120px;" readonly tabindex="-1"></td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-light text-danger remove-item-btn"><i class="ph-bold ph-trash"></i></button>
        </td>
    </tr>
</template>
<datalist id="categoryList">
    @foreach($categories as $cat)
        <option value="{{ $cat }}">
    @endforeach
</datalist>

<datalist id="productList">
    @foreach($productNames as $name)
        <option value="{{ $name }}">
    @endforeach
</datalist>

<!-- Add Vendor Modal -->
<div class="modal fade" id="addVendorModal" tabindex="-1" aria-labelledby="addVendorModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="addVendorForm" onsubmit="window.submitVendorForm(event)">
        <div class="modal-header">
          <h5 class="modal-title" id="addVendorModalLabel">Add New Vendor</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <div id="vendorAlert" class="alert alert-danger d-none"></div>
            <div class="mb-3">
                <label for="vendor_name" class="form-label fw-medium">Vendor Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="vendor_name" name="name" required>
            </div>
            <div class="mb-3">
                <label for="vendor_phone" class="form-label fw-medium">Phone Number</label>
                <input type="text" class="form-control" id="vendor_phone" name="phone">
            </div>
            <div class="mb-3">
                <label for="vendor_address" class="form-label fw-medium">Address</label>
                <textarea class="form-control" id="vendor_address" name="address" rows="2"></textarea>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="saveVendorBtn">Save Vendor</button>
        </div>
      </form>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script>
    (function() {
        let itemIndex = document.querySelectorAll('.item-row').length;
        const totalAmountInput = document.getElementById('total_amount');
        
        function updateSubtotalsAndTotal() {
            let overallTotal = 0;
            document.querySelectorAll('.item-row').forEach(row => {
                const qty = parseFloat(row.querySelector('.qty-input').value) || 0;
                const measureInput = row.querySelector('.measure-input').value;
                const measure = measureInput === '' ? 1 : (parseFloat(measureInput) || 1);
                const price = parseFloat(row.querySelector('.price-input').value) || 0;
                const subtotal = qty * price * measure;
                
                row.querySelector('.subtotal-input').value = subtotal.toFixed(2);
                overallTotal += subtotal;
            });
            
            totalAmountInput.value = overallTotal.toFixed(2);
        }

        // Listen for input changes to dynamically update subtotal
        document.getElementById('itemsTable').addEventListener('input', function(e) {
            if (e.target.classList.contains('qty-input') || e.target.classList.contains('price-input') || e.target.classList.contains('measure-input')) {
                updateSubtotalsAndTotal();
            }
        });

        // Add new item row
        document.getElementById('addItemBtn').addEventListener('click', function() {
            const noItemsRow = document.getElementById('noItemsRow');
            if (noItemsRow) noItemsRow.remove();
            
            const template = document.getElementById('itemRowTemplate').innerHTML;
            const newRowHtml = template.replace(/__INDEX__/g, itemIndex++);
            
            const tbody = document.querySelector('#itemsTable tbody');
            tbody.insertAdjacentHTML('beforeend', newRowHtml);
        });
        
        // Remove item row (event delegation)
        document.getElementById('itemsTable').addEventListener('click', function(e) {
            if (e.target.closest('.remove-item-btn')) {
                const row = e.target.closest('tr');
                row.remove();
                
                updateSubtotalsAndTotal();
                
                // If no rows left, add the empty message back
                if (document.querySelectorAll('.item-row').length === 0) {
                    const tbody = document.querySelector('#itemsTable tbody');
                    tbody.innerHTML = '<tr id="noItemsRow"><td colspan="7" class="text-center text-muted py-3">No items. Click "Add Missing Item" to add one.</td></tr>';
                }
            }
        });
        
        // Tab shortcut to add new row
        document.getElementById('itemsTable').addEventListener('keydown', function(e) {
            // Check if Tab key was pressed (without Shift)
            if (e.key === 'Tab' && !e.shiftKey) {
                // If the focused element is the remove button (last focusable element in row)
                const isRemoveBtn = e.target.closest('.remove-item-btn');
                
                if (isRemoveBtn) {
                    const currentRow = e.target.closest('tr');
                    const allRows = document.querySelectorAll('.item-row');
                    const isLastRow = currentRow === allRows[allRows.length - 1];
                    
                    // If it's the last row, add a new row automatically
                    if (isLastRow) {
                        e.preventDefault(); // Prevent default tabbing behavior (which goes to cancel btn)
                        document.getElementById('addItemBtn').click(); // Add new row
                        
                        // Focus the first input (item name) of the newly added row
                        setTimeout(() => {
                            const newRows = document.querySelectorAll('.item-row');
                            const newlyAddedRow = newRows[newRows.length - 1];
                            const firstInput = newlyAddedRow.querySelector('input[type="text"]');
                            if (firstInput) firstInput.focus();
                        }, 50);
                    }
                }
            }
        });

        // Initial calculation to ensure subtotals match if manually edited earlier
        updateSubtotalsAndTotal();
        // Payment Status Logic
        const paymentStatus = document.getElementById('payment_status');
        const amountPaidContainer = document.getElementById('amount_paid_container');
        const paymentMethodContainer = document.getElementById('payment_method_container');
        const amountPaidInput = document.getElementById('amount_paid');
        const paymentMethodInput = document.getElementById('payment_method');

        function updatePaymentFields() {
            if (paymentStatus.value === 'lunas') {
                amountPaidContainer.style.display = 'none';
                paymentMethodContainer.style.display = 'block';
                amountPaidInput.required = false;
                paymentMethodInput.required = true;
            } else if (paymentStatus.value === 'partial') {
                amountPaidContainer.style.display = 'block';
                paymentMethodContainer.style.display = 'block';
                amountPaidInput.required = true;
                paymentMethodInput.required = true;
            } else { // hutang
                amountPaidContainer.style.display = 'none';
                paymentMethodContainer.style.display = 'none';
                amountPaidInput.required = false;
                paymentMethodInput.required = false;
            }
        }

        paymentStatus.addEventListener('change', updatePaymentFields);
        // Initial setup
        updatePaymentFields();
    })();

    window.submitVendorForm = function(e) {
        e.preventDefault();
        
        const form = document.getElementById('addVendorForm');
        const btn = document.getElementById('saveVendorBtn');
        const alertBox = document.getElementById('vendorAlert');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';
        alertBox.classList.add('d-none');
        
        const formData = new FormData(form);
        
        fetch('{{ route('stores.store') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(response => response.json().then(data => ({ status: response.status, body: data })))
        .then(res => {
            if (res.status === 422) {
                let errorMsg = '';
                for (let field in res.body.errors) {
                    errorMsg += res.body.errors[field][0] + '<br>';
                }
                alertBox.innerHTML = errorMsg;
                alertBox.classList.remove('d-none');
            } else if (res.status === 200 || res.status === 201) {
                const store = res.body.store;
                
                const select = document.getElementById('store_id');
                const option = new Option(store.name, store.id, true, true);
                select.add(option);
                
                // Hide modal reliably
                const modalEl = document.getElementById('addVendorModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) {
                    modal.hide();
                } else {
                    modalEl.classList.remove('show');
                    modalEl.style.display = 'none';
                    document.body.classList.remove('modal-open');
                }
                
                setTimeout(() => {
                    document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                }, 300);
                
                form.reset();
            } else {
                alertBox.innerHTML = 'An unexpected error occurred.';
                alertBox.classList.remove('d-none');
            }
        })
        .catch(error => {
            alertBox.innerHTML = 'Network error. Please try again.';
            alertBox.classList.remove('d-none');
            console.error('Error:', error);
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = 'Save Vendor';
        });
    };
</script>
@endpush