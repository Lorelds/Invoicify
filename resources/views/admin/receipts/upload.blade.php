@extends('layouts.app')
@section('page_title', 'Upload Receipt')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('admin.receipts.index') }}" class="btn btn-light border shadow-sm d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; border-radius: 50%;">
            <i class="ph-bold ph-arrow-left"></i>
        </a>
        <div>
            <h2 class="mb-1">Scan Receipt</h2>
            <p class="text-muted mb-0">Upload a receipt image to extract inventory and pricing data automatically.</p>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-5">
                <div id="pageAlerts"></div>
                <form action="{{ route('admin.receipts.upload') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
                    @csrf
                    
                    <div class="row mb-4">
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-medium d-block">Tipe Nota (Receipt Type) <span class="text-danger">*</span></label>
                            <div class="d-flex gap-3">
                                <div class="form-check form-check-inline p-3 border rounded w-100 me-0 bg-light bg-opacity-50">
                                    <input class="form-check-input ms-1" type="radio" name="type" id="type_pembelian" value="pembelian" required>
                                    <label class="form-check-label fw-medium ms-2" for="type_pembelian">
                                        <i class="ph-bold ph-download-simple text-primary"></i> Nota Pembelian (Barang Masuk)
                                    </label>
                                </div>
                                <div class="form-check form-check-inline p-3 border rounded w-100 me-0 bg-light bg-opacity-50">
                                    <input class="form-check-input ms-1" type="radio" name="type" id="type_penjualan" value="penjualan" required checked>
                                    <label class="form-check-label fw-medium ms-2" for="type_penjualan">
                                        <i class="ph-bold ph-upload-simple text-success"></i> Nota Penjualan (Barang Keluar)
                                    </label>
                                </div>
                            </div>
                            @error('type')
                                <div class="text-danger mt-1 small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-12">
                            <label for="store_id" class="form-label fw-medium">Store / Vendor <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="ph ph-storefront"></i></span>
                                <select class="form-select @error('store_id') is-invalid @enderror" id="store_id" name="store_id" required>
                                    <option value="" selected disabled>-- Select Store/Vendor --</option>
                                    @foreach($stores as $store)
                                        <option value="{{ $store->id }}" {{ old('store_id') == $store->id ? 'selected' : '' }}>
                                            {{ $store->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-text mt-2">
                                <a href="#" data-bs-toggle="modal" data-bs-target="#addVendorModal" class="text-decoration-none"><i class="ph-bold ph-plus"></i> Add new vendor</a>
                            </div>
                            @error('store_id')
                                <div class="text-danger mt-1 small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label fw-medium">Receipt Image <span class="text-danger">*</span></label>
                        
                        <div class="drag-drop-zone position-relative" id="dropZone" onclick="document.getElementById('receipt_image').click()">
                            <i class="ph-fill ph-image icon"></i>
                            <h5 class="mb-2">Click to upload or drag and drop</h5>
                            <p class="text-muted small mb-0">PNG, JPG or JPEG (MAX. 5MB)</p>
                            <p id="fileName" class="text-primary fw-medium mt-3 mb-0" style="display:none;"></p>
                            
                            <input class="form-control d-none @error('receipt_image') is-invalid @enderror" type="file" id="receipt_image" name="receipt_image" accept="image/jpeg,image/png,image/jpg" required onchange="showFileName(this)">
                        </div>
                        @error('receipt_image')
                            <div class="text-danger mt-1 small text-center">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="d-flex justify-content-center mt-5">
                        <button type="submit" class="btn btn-primary btn-lg px-5" id="submitBtn">
                            <i class="ph-bold ph-scan me-2"></i> Start OCR Extraction
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

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

<!-- Loading Overlay -->
<div id="loadingOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(255,255,255,0.9); z-index: 9999; flex-direction: column; justify-content: center; align-items: center;">
    <div class="spinner-border text-primary" style="width: 4rem; height: 4rem; border-width: 0.4rem;" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
    <h3 class="mt-4 text-primary fw-bold">🤖 AI is scanning your receipt...</h3>
    <p class="text-muted fs-5">This usually takes about 5-10 seconds. Please wait.</p>
</div>
@endsection

@push('scripts')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script>
    let storeSelect;
    
    document.addEventListener("DOMContentLoaded", function() {
        storeSelect = new TomSelect("#store_id", {
            create: false,
            sortField: {
                field: "text",
                direction: "asc"
            }
        });
    });

    function showFileName(input) {
        const fileNameElement = document.getElementById('fileName');
        const dropZone = document.getElementById('dropZone');
        
        if (input.files && input.files[0]) {
            fileNameElement.textContent = "Selected: " + input.files[0].name;
            fileNameElement.style.display = 'block';
            dropZone.style.borderColor = 'var(--primary-color)';
            dropZone.style.backgroundColor = '#f0f9ff';
        }
    }
    
    // Simple drag and drop visual feedback
    const dropZone = document.getElementById('dropZone');
    
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
    });
    
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, highlight, false);
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, unhighlight, false);
    });
    
    function highlight(e) {
        dropZone.style.borderColor = 'var(--primary-color)';
        dropZone.style.backgroundColor = '#e0f2fe';
    }
    
    function unhighlight(e) {
        dropZone.style.borderColor = 'var(--border-color)';
        dropZone.style.backgroundColor = '#f8fafc';
    }
    
    dropZone.addEventListener('drop', handleDrop, false);
    
    function handleDrop(e) {
        let dt = e.dataTransfer;
        let files = dt.files;
        document.getElementById('receipt_image').files = files;
        showFileName(document.getElementById('receipt_image'));
    }
    
    document.getElementById('uploadForm').addEventListener('submit', function() {
        // Show loading overlay
        document.getElementById('loadingOverlay').style.display = 'flex';
        
        // Update button
        const btn = document.getElementById('submitBtn');
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Processing OCR...';
        btn.disabled = true;
    });

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
                if (typeof storeSelect !== 'undefined' && storeSelect) {
                    storeSelect.addOption({value: store.id, text: store.name});
                    storeSelect.addItem(store.id);
                } else {
                    const select = document.getElementById('store_id');
                    const option = new Option(store.name, store.id, true, true);
                    select.add(option);
                }
                
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
                
                // Force remove backdrop if it gets stuck
                setTimeout(() => {
                    document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                }, 300);
                
                form.reset();
                
                // Show success notification
                const pageAlerts = document.getElementById('pageAlerts');
                pageAlerts.innerHTML = `
                    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
                        <i class="ph-fill ph-check-circle fs-5 me-2"></i> Vendor <strong>${store.name}</strong> added successfully!
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `;
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