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
                            <label for="store_id" class="form-label fw-medium">Store / Vendor <span class="text-muted fw-normal">(Optional - You can set this later per receipt)</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="ph ph-storefront"></i></span>
                                <select class="form-select @error('store_id') is-invalid @enderror" id="store_id" name="store_id">
                                    <option value="" selected>-- Select Store/Vendor (Optional) --</option>
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
                        
                        <div class="drag-drop-zone position-relative" id="dropZone" style="cursor: pointer; overflow: hidden;">
                            <div id="dropDefaultText" style="pointer-events: none;">
                                <i class="ph-fill ph-images icon"></i>
                                <h5 class="mb-2">Click to upload or drag and drop</h5>
                                <p class="text-muted small mb-0">PNG, JPG or JPEG (You can select multiple files)</p>
                            </div>
                            
                            <div id="imagePreviewContainer" class="d-flex flex-wrap justify-content-center mt-3" style="display:none; gap: 10px; pointer-events: none;"></div>
                            
                            <p id="fileName" class="text-primary fw-medium mt-2 mb-0" style="display:none; pointer-events: none;"></p>
                            
                            <input class="form-control @error('receipt_images') is-invalid @enderror @error('receipt_images.*') is-invalid @enderror" type="file" id="receipt_image" name="receipt_images[]" accept="image/jpeg,image/png,image/jpg" multiple style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; z-index: 10;" onchange="window.showFileName(this)">
                        </div>
                        @error('receipt_images')
                            <div class="text-danger mt-1 small text-center">{{ $message }}</div>
                        @enderror
                        @error('receipt_images.*')
                            <div class="text-danger mt-1 small text-center">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="d-flex justify-content-center gap-3 mt-5">
                        <button type="submit" name="action" value="manual" class="btn btn-outline-primary btn-lg px-4" onclick="submitActionValue = 'manual'" formnovalidate>
                            <i class="ph-bold ph-keyboard me-2"></i> Input Manual
                        </button>
                        <button type="submit" name="action" value="scan" class="btn btn-primary btn-lg px-5" id="submitBtn" onclick="submitActionValue = 'scan'">
                            <i class="ph-bold ph-scan me-2"></i> Scan Receipt
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
(function() {
    var storeSelect;
    var submitActionValue = 'scan';
    
    function initUploadPage() {
        // Initialize TomSelect
        if (document.getElementById("store_id") && !document.getElementById("store_id").tomselect) {
            storeSelect = new TomSelect("#store_id", { create: false, sortField: { field: "text", direction: "asc" } });
        }
        
        var dropZone = document.getElementById('dropZone');
        var fileInput = document.getElementById('receipt_image');
        var uploadForm = document.getElementById('uploadForm');
        var addVendorForm = document.getElementById('addVendorForm');
        
        // Define the preview function
        var handleFileChange = function(input) {
            try {
                var fileNameElement = document.getElementById('fileName');
                var previewContainer = document.getElementById('imagePreviewContainer');
                var defaultText = document.getElementById('dropDefaultText');
                var dropZoneEl = document.getElementById('dropZone');
                
                if (!fileNameElement || !previewContainer || !defaultText) return;
                
                if (input.files && input.files.length > 0) {
                    previewContainer.innerHTML = '';
                    
                    for (var i = 0; i < input.files.length; i++) {
                        var file = input.files[i];
                        var fileType = file.type || '';
                        if (fileType.indexOf('image/') === 0 || file.name.match(/\.(jpg|jpeg|png)$/i)) {
                            var reader = new FileReader();
                            reader.onload = function(e) {
                                var img = document.createElement('img');
                                img.src = e.target.result;
                                img.style.height = '120px';
                                img.style.width = '120px';
                                img.style.objectFit = 'cover';
                                img.className = 'rounded border shadow-sm z-3 position-relative';
                                img.style.pointerEvents = 'none'; // Ensure clicks pass through to input
                                previewContainer.appendChild(img);
                            }
                            reader.readAsDataURL(file);
                        }
                    }
                    
                    if (input.files.length === 1) {
                        fileNameElement.innerHTML = "<i class='ph-bold ph-check-circle me-1'></i> Selected: " + input.files[0].name;
                    } else {
                        fileNameElement.innerHTML = "<i class='ph-bold ph-check-circle me-1'></i> Selected: " + input.files.length + " files";
                    }
                    
                    defaultText.style.display = 'none';
                    fileNameElement.style.display = 'block';
                    previewContainer.style.display = 'flex';
                    if(dropZoneEl) {
                        dropZoneEl.style.borderColor = 'var(--primary-color)';
                        dropZoneEl.style.backgroundColor = '#f0f9ff';
                    }
                } else {
                    defaultText.style.display = 'block';
                    fileNameElement.style.display = 'none';
                    previewContainer.style.display = 'none';
                    previewContainer.innerHTML = '';
                    if(dropZoneEl) {
                        dropZoneEl.style.borderColor = 'var(--border-color)';
                        dropZoneEl.style.backgroundColor = '#f8fafc';
                    }
                }
            } catch (error) {
                console.error("Error showing file preview:", error);
                var fName = document.getElementById('fileName');
                if(fName) {
                    fName.textContent = "Files selected";
                    fName.style.display = 'block';
                }
            }
        };

        // Attach event listener directly to input
        if (fileInput) {
            // Remove inline handlers that might conflict
            fileInput.removeAttribute('onchange');
            fileInput.removeAttribute('oninput');
            
            // Clean up any old listeners if this is a re-init
            var newFileInput = fileInput.cloneNode(true);
            fileInput.parentNode.replaceChild(newFileInput, fileInput);
            fileInput = newFileInput;
            
            fileInput.addEventListener('change', function() {
                handleFileChange(this);
            });
        }
        
        function preventDefaults(e) { e.preventDefault(); e.stopPropagation(); }
        function highlight(e) { if(dropZone){ dropZone.style.borderColor = 'var(--primary-color)'; dropZone.style.backgroundColor = '#e0f2fe'; } }
        function unhighlight(e) { if(dropZone){ dropZone.style.borderColor = 'var(--border-color)'; dropZone.style.backgroundColor = '#f8fafc'; } }
        function handleDrop(e) {
            var dt = e.dataTransfer;
            if(fileInput && dt.files) {
                fileInput.files = dt.files;
                // Dispatch change event manually so handleFileChange triggers
                fileInput.dispatchEvent(new Event('change'));
            }
        }
        
        if (dropZone) {
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(function(eventName) { dropZone.addEventListener(eventName, preventDefaults, false); });
            ['dragenter', 'dragover'].forEach(function(eventName) { dropZone.addEventListener(eventName, highlight, false); });
            ['dragleave', 'drop'].forEach(function(eventName) { dropZone.addEventListener(eventName, unhighlight, false); });
            dropZone.addEventListener('drop', handleDrop, false);
        }
        
        // Handle Submit Form
        if (uploadForm) {
            uploadForm.onsubmit = null;
            uploadForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                // Determine which button was clicked based on submitActionValue
                const action = window.submitActionValue || 'scan';
                const files = fileInput ? fileInput.files : [];
                
                if (action === 'scan' && (!files || files.length === 0)) {
                    alert('Please select at least one receipt image to upload.');
                    return;
                }

                const typeInput = document.querySelector('input[name="type"]:checked');
                if (!typeInput) {
                    alert('Please select a receipt type.');
                    return;
                }

                const loadingOverlay = document.getElementById('loadingOverlay');
                const loadingText = loadingOverlay.querySelector('h3');
                loadingOverlay.style.display = 'flex';
                
                if (action === 'manual') {
                    loadingText.innerHTML = `📝 Menyiapkan form input manual...`;
                }
                
                const btn = document.getElementById('submitBtn');
                if (btn) {
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Processing...';
                    btn.disabled = true;
                }

                const storeId = document.getElementById('store_id') ? document.getElementById('store_id').value : '';
                const type = typeInput.value;
                let lastReceiptId = null;
                let hasErrors = false;
                
                if (action === 'manual' && (!files || files.length === 0)) {
                    const formData = new FormData();
                    formData.append('type', type);
                    formData.append('action', 'manual');
                    if (storeId) {
                        formData.append('store_id', storeId);
                    }
                    formData.append('_token', '{{ csrf_token() }}');
                    
                    try {
                        const response = await fetch('{{ route('admin.receipts.upload') }}', {
                            method: 'POST',
                            headers: { 'Accept': 'application/json' },
                            body: formData
                        });
                        const data = await response.json();
                        if (data.receipt_id) lastReceiptId = data.receipt_id;
                    } catch (err) {
                        hasErrors = true;
                    }
                } else {
                    for (let i = 0; i < files.length; i++) {
                        if (action === 'scan') {
                            if (files.length > 1) {
                                loadingText.innerHTML = `🤖 AI is scanning your receipt (${i + 1} of ${files.length})...`;
                            } else {
                                loadingText.innerHTML = `🤖 AI is scanning your receipt...`;
                            }
                        }

                        const formData = new FormData();
                        formData.append('receipt_images[]', files[i]);
                        formData.append('type', type);
                        formData.append('action', action);
                        if (storeId) {
                            formData.append('store_id', storeId);
                        }
                        formData.append('_token', '{{ csrf_token() }}');

                        try {
                            const response = await fetch('{{ route('admin.receipts.upload') }}', {
                                method: 'POST',
                                headers: { 'Accept': 'application/json' },
                                body: formData
                            });
                            
                            const data = await response.json();
                            
                            if (response.status === 422) {
                                hasErrors = true;
                                console.error('Validation error:', data.errors);
                                break;
                            }
                            
                            if (data.receipt_id) {
                                lastReceiptId = data.receipt_id;
                            }
                            if (data.api_errors && data.api_errors.length > 0) {
                                hasErrors = true;
                            }
                        } catch (err) {
                            console.error(err);
                            hasErrors = true;
                        }
                    }
                }

                if (files.length <= 1 && lastReceiptId && !hasErrors) {
                    window.location.href = '/admin/receipts/' + lastReceiptId + '/validate';
                } else {
                    window.location.href = '{{ route('admin.receipts.index') }}';
                }
            });
        }
        
        // Setup action tracking for buttons
        const btnManual = document.querySelector('button[value="manual"]');
        const btnScan = document.querySelector('button[value="scan"]');
        if (btnManual) {
            btnManual.onclick = function() { window.submitActionValue = 'manual'; };
        }
        if (btnScan) {
            btnScan.onclick = function() { window.submitActionValue = 'scan'; };
        }

        // Handle Add Vendor Form
        if (addVendorForm) {
            addVendorForm.removeAttribute('onsubmit');
            // Remove old listeners
            var newVendorForm = addVendorForm.cloneNode(true);
            addVendorForm.parentNode.replaceChild(newVendorForm, addVendorForm);
            addVendorForm = newVendorForm;
            
            addVendorForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const form = this;
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
                            if(select) {
                                const option = new Option(store.name, store.id, true, true);
                                select.add(option);
                            }
                        }
                        
                        const modalEl = document.getElementById('addVendorModal');
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) modal.hide();
                        else {
                            modalEl.classList.remove('show');
                            modalEl.style.display = 'none';
                        }
                        
                        setTimeout(() => {
                            document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
                            document.body.classList.remove('modal-open');
                            document.body.style.overflow = '';
                            document.body.style.paddingRight = '';
                        }, 300);
                        
                        form.reset();
                        
                        const pageAlerts = document.getElementById('pageAlerts');
                        if(pageAlerts) {
                            pageAlerts.innerHTML = `
                                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
                                    <i class="ph-fill ph-check-circle fs-5 me-2"></i> Vendor <strong>${store.name}</strong> added successfully!
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            `;
                        }
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
            });
        }
    }
    
    // Bind the initialization to Turbo events and normal load
    document.addEventListener("turbo:load", initUploadPage);
    
    // If not using turbo or for first paint
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initUploadPage);
    } else {
        initUploadPage();
    }
})();
</script>
@endpush