<!DOCTYPE html>
<html lang="en" style="background-color: #f4f6f9;">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f4f6f9">
    <meta name="view-transition" content="same-origin">
    <title>Inventory Tesseract OCR</title>

    <!-- Preload Critical CSS to prevent render-blocking delays -->
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" as="style">
    <link rel="preload" href="{{ asset('css/style.css') }}" as="style">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Phosphor Icons (defer to not block rendering) -->
    <script src="https://unpkg.com/@phosphor-icons/web" defer></script>
    <!-- Bootstrap CSS for grid system & utilities -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom Design System (loads AFTER Bootstrap to override it) -->
    <link href="{{ asset('css/style.css') }}" rel="stylesheet">
    <!-- Critical CSS override (last = highest priority, prevents white flash) -->
    <style>
        @view-transition { navigation: auto; }
        html { background-color: #f4f6f9 !important; }
        body { background-color: #f4f6f9 !important; margin: 0; }
        #app-wrapper { display: flex; min-height: 100vh; }
        .sidebar { width: 260px; background-color: #1e293b !important; flex-shrink: 0; }
        .main-content { flex-grow: 1; background-color: #f4f6f9 !important; }
    </style>
</head>
<body style="background-color: #f4f6f9;">
    <div id="app-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <i class="ph-fill ph-scan"></i>
                <span>OCR Inventory</span>
            </div>
            <nav class="sidebar-menu">
                <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="ph ph-squares-four fs-5"></i> {{ __('Dashboard') }}
                </a>
                <a href="{{ route('admin.receipts.index') }}" class="{{ request()->routeIs('admin.receipts.*') ? 'active' : '' }}">
                    <i class="ph ph-receipt fs-5"></i> {{ __('Receipts') }}
                </a>
                <a href="{{ route('products.index') }}" class="{{ request()->routeIs('products.*') ? 'active' : '' }}">
                    <i class="ph ph-package fs-5"></i> {{ __('Products') }}
                </a>
                <a href="{{ route('stores.index') }}" class="{{ request()->routeIs('stores.*') ? 'active' : '' }}">
                    <i class="ph ph-storefront fs-5"></i> {{ __('Stores') }}
                </a>
                <a href="{{ route('debts.index') }}" class="{{ request()->routeIs('debts.*') ? 'active' : '' }}">
                    <i class="ph ph-money fs-5"></i> {{ __('Active Debts') }}
                </a>
                <a href="{{ route('payments.index') }}" class="{{ request()->routeIs('payments.*') ? 'active' : '' }}">
                    <i class="ph ph-wallet fs-5"></i> {{ __('Payments History') }}
                </a>
                <a href="{{ route('reports.index') }}" class="{{ request()->routeIs('reports.*') ? 'active' : '' }}">
                    <i class="ph ph-chart-bar fs-5"></i> {{ __('Reports') }}
                </a>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <!-- Topbar -->
            <header class="topbar">
                <div class="d-flex align-items-center">
                    <h5 class="mb-0 text-muted fw-normal">@yield('page_title', 'Dashboard')</h5>
                </div>
                <div class="d-flex align-items-center gap-3">
                    
                    <!-- Language Switcher -->
                    <div class="dropdown">
                        <button class="btn btn-light bg-white border shadow-sm dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="border-radius: 8px;">
                            <i class="ph-bold ph-globe"></i>
                            <span class="text-uppercase fw-semibold">{{ session('locale', 'en') }}</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="border-radius: 8px; min-width: 120px;">
                            <li><a class="dropdown-item d-flex align-items-center gap-2 {{ session('locale', 'en') === 'en' ? 'active bg-primary' : '' }}" href="{{ route('lang.switch', 'en') }}">🇬🇧 English</a></li>
                            <li><a class="dropdown-item d-flex align-items-center gap-2 {{ session('locale') === 'id' ? 'active bg-primary' : '' }}" href="{{ route('lang.switch', 'id') }}">🇮🇩 Indonesia</a></li>
                        </ul>
                    </div>

                    <div class="d-flex align-items-center gap-2 ms-2">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name ?? 'User') }}&background=0ea5e9&color=fff" alt="User" class="rounded-circle" width="36" height="36">
                        <span class="fw-medium">{{ auth()->user()->name ?? 'User' }}</span>
                        
                        <form method="POST" action="{{ route('logout') }}" class="ms-3">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="ph-bold ph-sign-out me-1"></i> Logout
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <!-- Content Wrapper -->
            <div class="content-wrapper">
                @if (session('success'))
                    <div class="alert alert-success d-flex align-items-center mb-4 border-0 shadow-sm">
                        <i class="ph-fill ph-check-circle fs-4 me-2"></i>
                        {{ session('success') }}
                    </div>
                @endif
                @if (session('info'))
                    <div class="alert alert-info d-flex align-items-center mb-4 border-0 shadow-sm">
                        <i class="ph-fill ph-info fs-4 me-2"></i>
                        {{ session('info') }}
                    </div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger mb-4 border-0 shadow-sm">
                        <div class="d-flex align-items-center mb-2">
                            <i class="ph-fill ph-warning-circle fs-4 me-2"></i>
                            <strong>Please fix the following errors:</strong>
                        </div>
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>