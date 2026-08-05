<!DOCTYPE html>
<html lang="en" style="background-color: #f4f6f9;">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f4f6f9">
    <meta name="view-transition" content="same-origin">
    
    <!-- PWA Meta Tags -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    
    <title>Invoicify</title>

    <!-- Hotwire Turbo for SPA-like navigation (Eliminates white flashes completely) -->
    <script type="module" src="https://cdn.jsdelivr.net/npm/@hotwired/turbo@8.0.4/dist/turbo.es2017-esm.js"></script>

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
                <span>Invoicify</span>
            </div>
            <nav class="sidebar-menu">
                <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="ph ph-squares-four fs-5"></i> {{ __('Dashboard') }}
                </a>
                <a href="{{ route('admin.receipts.index') }}" class="{{ request()->routeIs('admin.receipts.*') ? 'active' : '' }}">
                    <i class="ph ph-receipt fs-5"></i> {{ __('Receipts') }}
                </a>
                <a href="{{ route('products.index') }}" class="{{ request()->routeIs('products.index', 'products.show', 'products.edit') ? 'active' : '' }}">
                    <i class="ph ph-package fs-5"></i> {{ __('Products') }}
                </a>
                <a href="{{ route('products.history') }}" class="{{ request()->routeIs('products.history') ? 'active' : '' }}">
                    <i class="ph ph-clock-counter-clockwise fs-5"></i> {{ __('Stock History') }}
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
                    
                    @if(auth()->user() && auth()->user()->role === 'super_admin')
                    <form method="POST" action="{{ route('backup.download') }}" data-turbo="false" class="m-0">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-primary d-flex align-items-center">
                            <i class="ph-bold ph-database me-1"></i> Backup DB
                        </button>
                    </form>
                    @endif

                    <div class="d-flex align-items-center gap-2 ms-2">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name ?? 'User') }}&background=0ea5e9&color=fff" alt="User" class="rounded-circle" width="36" height="36">
                        <span class="fw-medium">{{ auth()->user()->name ?? 'User' }}</span>
                        
                        <form method="POST" action="{{ route('logout') }}" class="ms-3 m-0">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="ph-bold ph-sign-out me-1"></i> {{ __('Keluar') }}
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
    
    <!-- PWA Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js');
            });
        }
    </script>
    
    @stack('scripts')
</body>
</html>