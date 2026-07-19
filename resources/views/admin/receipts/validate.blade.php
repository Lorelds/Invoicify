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

    <div class="row">
        <!-- Image Preview Column -->
        <div class="col-lg-5 mb-4 mb-lg-0">
            <div class="card border-0 shadow-sm h-100 sticky-top" style="top: 90px; z-index: 10;">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4">
                    <h5 class="fw-semibold text-dark"><i class="ph ph-image me-2"></i> {{ __('Gambar Nota') }}</h5>
                </div>
                <div class="card-body p-4 text-center">
                    <div class="border rounded p-2 bg-light d-flex justify-content-center align-items-center" style="min-height: 400px; max-height: 600px; overflow: hidden;">
                        <img src="{{ Storage::url($receipt->image_path) }}" alt="Receipt" class="img-fluid rounded" style="object-fit: contain; max-height: 100%; width: auto;">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Validation Form Column -->
        <div class="col-lg-7">
            <!-- General Info -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4">
                    <h5 class="fw-semibold text-dark"><i class="ph ph-receipt me-2"></i> {{ __('Informasi Umum') }}</h5>
                </div>
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">{{ __('Toko/Supplier') }}</label>
                            <input type="hidden" name="store_id" value="{{ $receipt->store_id }}">
                            <input type="text" class="form-control bg-light" value="{{ $receipt->store->name ?? __('Unknown') }}" readonly>
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
                            <button type="button" class="btn btn-outline-success w-100 mb-4" id="convertUsdBtn">
                                <i class="ph-bold ph-currency-circle-dollar me-1"></i> {{ __('Konversi USD ke IDR') }}
                            </button>
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
                                            <input type="text" class="form-control" name="items[{{ $index }}][name]" value="{{ old('items.'.$index.'.name', $item['name'] ?? '') }}" style="min-width: 150px;" required>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control" name="items[{{ $index }}][category]" value="{{ old('items.'.$index.'.category') }}" list="categoryList" placeholder="e.g. Paku" style="min-width: 120px;">
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
                <button type="submit" class="btn btn-success px-5 py-2 fw-semibold">
                    <i class="ph-bold ph-check-circle me-1"></i> {{ __('Konfirmasi & Simpan ke Inventaris') }}
                </button>
            </div>
        </div>
    </div>
</form>

<template id="itemRowTemplate">
    <tr class="item-row">
        <td><input type="text" class="form-control" name="items[__INDEX__][name]" style="min-width: 150px;" required></td>
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

        // USD to IDR Converter
        const convertUsdBtn = document.getElementById('convertUsdBtn');
        if (convertUsdBtn) {
            convertUsdBtn.addEventListener('click', async function() {
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="ph-bold ph-spinner ph-spin me-1"></i> Fetching Rate...';
                this.disabled = true;

                try {
                    const response = await fetch('https://api.exchangerate-api.com/v4/latest/USD');
                    const data = await response.json();
                    const idrRate = data.rates.IDR;

                    if (!idrRate) throw new Error("IDR rate not found");

                    // Ask for confirmation
                    if (confirm(`Current USD to IDR rate is Rp ${idrRate.toLocaleString('id-ID')}.\n\nDo you want to multiply all unit prices by this rate?`)) {
                        
                        document.querySelectorAll('.item-row').forEach(row => {
                            const priceInput = row.querySelector('.price-input');
                            const currentPrice = parseFloat(priceInput.value) || 0;
                            
                            // Multiply and format to 2 decimal places to avoid floating point issues
                            priceInput.value = (currentPrice * idrRate).toFixed(2);
                        });

                        // Update all subtotals and the grand total
                        updateSubtotalsAndTotal();
                        
                        alert('Successfully converted all prices to IDR!');
                    }
                } catch (error) {
                    console.error('Error fetching exchange rate:', error);
                    alert('Failed to fetch the latest exchange rate. Please check your internet connection.');
                } finally {
                    this.innerHTML = originalText;
                    this.disabled = false;
                }
            });
        }
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
</script>
@endpush