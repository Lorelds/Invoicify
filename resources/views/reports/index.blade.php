@extends('layouts.app')
@section('page_title', 'Reports')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('dashboard') }}" class="btn btn-light border shadow-sm d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; border-radius: 50%;">
            <i class="ph-bold ph-arrow-left"></i>
        </a>
        <div>
            <h2 class="mb-1">{{ __('Reports & Export') }}</h2>
            <p class="text-muted mb-0">{{ __('Generate weekly, monthly, or yearly reports as PDF or Excel.') }}</p>
        </div>
    </div>
</div>

<div class="row">
    <!-- Left: Report Configuration -->
    <div class="col-lg-5 mb-4">
        <form action="{{ route('reports.generate') }}" method="POST" id="reportForm">
            @csrf

            <!-- Period Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h5 class="fw-semibold mb-4"><i class="ph ph-calendar-dots text-primary me-2"></i>{{ __('Report Period') }}</h5>

                    <!-- Period Type Buttons -->
                    <div class="btn-group w-100 mb-4" role="group" id="periodSelector">
                        <input type="radio" class="btn-check" name="period" id="periodWeekly" value="weekly" autocomplete="off" checked>
                        <label class="btn btn-outline-primary" for="periodWeekly">
                            <i class="ph ph-calendar-check me-1"></i> {{ __('Weekly') }}
                        </label>

                        <input type="radio" class="btn-check" name="period" id="periodMonthly" value="monthly" autocomplete="off">
                        <label class="btn btn-outline-primary" for="periodMonthly">
                            <i class="ph ph-calendar me-1"></i> {{ __('Monthly') }}
                        </label>

                        <input type="radio" class="btn-check" name="period" id="periodYearly" value="yearly" autocomplete="off">
                        <label class="btn btn-outline-primary" for="periodYearly">
                            <i class="ph ph-calendar-blank me-1"></i> {{ __('Yearly') }}
                        </label>

                        <input type="radio" class="btn-check" name="period" id="periodCustom" value="custom" autocomplete="off">
                        <label class="btn btn-outline-primary" for="periodCustom">
                            <i class="ph ph-sliders me-1"></i> {{ __('Custom') }}
                        </label>
                    </div>

                    <!-- Standard Date Picker -->
                    <div id="standardDateContainer">
                        <label for="date" class="form-label fw-medium text-muted small">{{ __('Select Date') }}</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="ph ph-calendar text-muted"></i></span>
                            <input type="date" class="form-control border-start-0 ps-0" id="date" name="date" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="form-text mt-2" id="dateHelpText">
                            <i class="ph ph-info"></i> <span id="dateHelpContent">The report will cover the week containing this date.</span>
                        </div>
                    </div>

                    <!-- Custom Date Range Picker -->
                    <div id="customDateContainer" style="display: none;">
                        <div class="row g-3">
                            <div class="col-6">
                                <label for="start_date" class="form-label fw-medium text-muted small">{{ __('Start Date') }}</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="{{ date('Y-m-01') }}">
                            </div>
                            <div class="col-6">
                                <label for="end_date" class="form-label fw-medium text-muted small">{{ __('End Date') }}</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="{{ date('Y-m-d') }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Format Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h5 class="fw-semibold mb-4"><i class="ph ph-file-arrow-down text-primary me-2"></i>{{ __('Export Format') }}</h5>

                    <div class="row g-3">
                        <div class="col-6">
                            <input type="radio" class="btn-check" name="format" id="formatPdf" value="pdf" autocomplete="off" checked>
                            <label class="btn btn-outline-danger w-100 py-3 d-flex flex-column align-items-center gap-2" for="formatPdf">
                                <i class="ph-bold ph-file-pdf fs-2"></i>
                                <span class="fw-semibold">PDF</span>
                                <small class="text-muted">{{ __('Best for printing') }}</small>
                            </label>
                        </div>
                        <div class="col-6">
                            <input type="radio" class="btn-check" name="format" id="formatExcel" value="excel" autocomplete="off">
                            <label class="btn btn-outline-success w-100 py-3 d-flex flex-column align-items-center gap-2" for="formatExcel">
                                <i class="ph-bold ph-file-xls fs-2"></i>
                                <span class="fw-semibold">Excel</span>
                                <small class="text-muted">{{ __('Best for analysis') }}</small>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Generate Button -->
            <button type="submit" class="btn btn-primary btn-lg w-100 py-3 fw-semibold shadow-sm" id="generateBtn">
                <i class="ph-bold ph-download-simple me-2"></i> {{ __('Generate & Download Report') }}
            </button>
        </form>
    </div>

    <!-- Right: Report Contents Preview -->
    <div class="col-lg-7 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h5 class="fw-semibold mb-4"><i class="ph ph-list-checks text-primary me-2"></i>{{ __('Report Contents') }}</h5>

                <div class="row g-3">
                    <!-- Sales -->
                    <div class="col-md-6">
                        <div class="border rounded-3 p-3 h-100">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <div class="p-2 bg-success bg-opacity-10 rounded-2">
                                    <i class="ph-fill ph-chart-line-up text-success fs-5"></i>
                                </div>
                                <h6 class="mb-0 fw-semibold">{{ __('Sales Summary') }}</h6>
                            </div>
                            <ul class="list-unstyled text-muted small mb-0 ps-1">
                                <li class="mb-1"><i class="ph ph-check-circle text-success me-1"></i> {{ __('Total sales & transactions') }}</li>
                                <li class="mb-1"><i class="ph ph-check-circle text-success me-1"></i> {{ __('Paid vs unpaid breakdown') }}</li>
                                <li><i class="ph ph-check-circle text-success me-1"></i> {{ __('Receipt detail list') }}</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Purchases -->
                    <div class="col-md-6">
                        <div class="border rounded-3 p-3 h-100">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <div class="p-2 bg-warning bg-opacity-10 rounded-2">
                                    <i class="ph-fill ph-shopping-cart text-warning fs-5"></i>
                                </div>
                                <h6 class="mb-0 fw-semibold">{{ __('Purchases Summary') }}</h6>
                            </div>
                            <ul class="list-unstyled text-muted small mb-0 ps-1">
                                <li class="mb-1"><i class="ph ph-check-circle text-success me-1"></i> {{ __('Total purchases') }}</li>
                                <li class="mb-1"><i class="ph ph-check-circle text-success me-1"></i> {{ __('Vendor/store breakdown') }}</li>
                                <li><i class="ph ph-check-circle text-success me-1"></i> {{ __('Purchase receipt details') }}</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Debts -->
                    <div class="col-md-6">
                        <div class="border rounded-3 p-3 h-100">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <div class="p-2 bg-danger bg-opacity-10 rounded-2">
                                    <i class="ph-fill ph-money text-danger fs-5"></i>
                                </div>
                                <h6 class="mb-0 fw-semibold">{{ __('Debts & Payments') }}</h6>
                            </div>
                            <ul class="list-unstyled text-muted small mb-0 ps-1">
                                <li class="mb-1"><i class="ph ph-check-circle text-success me-1"></i> {{ __('New debts created') }}</li>
                                <li class="mb-1"><i class="ph ph-check-circle text-success me-1"></i> {{ __('Payments made') }}</li>
                                <li><i class="ph ph-check-circle text-success me-1"></i> {{ __('Outstanding balance') }}</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Products -->
                    <div class="col-md-6">
                        <div class="border rounded-3 p-3 h-100">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <div class="p-2 bg-primary bg-opacity-10 rounded-2">
                                    <i class="ph-fill ph-package text-primary fs-5"></i>
                                </div>
                                <h6 class="mb-0 fw-semibold">{{ __('Inventory Snapshot') }}</h6>
                            </div>
                            <ul class="list-unstyled text-muted small mb-0 ps-1">
                                <li class="mb-1"><i class="ph ph-check-circle text-success me-1"></i> {{ __('All products & stock levels') }}</li>
                                <li class="mb-1"><i class="ph ph-check-circle text-success me-1"></i> {{ __('Total inventory value') }}</li>
                                <li><i class="ph ph-check-circle text-success me-1"></i> {{ __('Low & out-of-stock alerts') }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats Preview -->
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-body p-4">
                <h5 class="fw-semibold mb-3"><i class="ph ph-lightning text-warning me-2"></i>{{ __('Quick Facts') }}</h5>
                <div class="row g-3">
                    <div class="col-4">
                        <div class="text-center p-3 bg-light rounded-3">
                            <div class="fs-3 fw-bold text-primary">{{ \App\Models\Receipt::where('status', 'validated')->count() }}</div>
                            <small class="text-muted">{{ __('Total Receipts') }}</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="text-center p-3 bg-light rounded-3">
                            <div class="fs-3 fw-bold text-success">{{ \App\Models\Product::count() }}</div>
                            <small class="text-muted">{{ __('Products') }}</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="text-center p-3 bg-light rounded-3">
                            <div class="fs-3 fw-bold text-danger">{{ \App\Models\Debt::where('status', 'hutang')->count() }}</div>
                            <small class="text-muted">{{ __('Active Debts') }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
    const periodRadios = document.querySelectorAll('input[name="period"]');
    const standardDateContainer = document.getElementById('standardDateContainer');
    const customDateContainer = document.getElementById('customDateContainer');
    const dateHelpContent = document.getElementById('dateHelpContent');
    const generateBtn = document.getElementById('generateBtn');
    const reportForm = document.getElementById('reportForm');

    const helpTexts = {
        weekly: 'The report will cover the week containing this date.',
        monthly: 'The report will cover the entire month of this date.',
        yearly: 'The report will cover the entire year of this date.',
    };

    periodRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'custom') {
                standardDateContainer.style.display = 'none';
                customDateContainer.style.display = 'block';
            } else {
                standardDateContainer.style.display = 'block';
                customDateContainer.style.display = 'none';
                dateHelpContent.textContent = helpTexts[this.value] || '';
            }
        });
    });

    // Loading state on submit
    reportForm.addEventListener('submit', function() {
        generateBtn.disabled = true;
        generateBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span> Generating report...';
        
        // Re-enable after 10 seconds (download should complete by then)
        setTimeout(() => {
            generateBtn.disabled = false;
            generateBtn.innerHTML = '<i class="ph-bold ph-download-simple me-2"></i> Generate & Download Report';
        }, 10000);
    });
})();
</script>
@endpush
