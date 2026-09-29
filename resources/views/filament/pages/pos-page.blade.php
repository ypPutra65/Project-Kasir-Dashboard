<x-filament-panels::page>
    {{-- =========================================================================
         SCOPED DESIGN SYSTEM: BUDHE LAMONGAN FAST POS TERMINAL
         Self-contained, responsive, dark/light theme aware, zero Tailwind purging bugs
         ========================================================================= --}}
    <style>
        :root {
            --pos-bg-card: #ffffff;
            --pos-bg-subtle: #f8fafc;
            --pos-bg-input: #f1f5f9;
            --pos-border: #e2e8f0;
            --pos-text-primary: #0f172a;
            --pos-text-secondary: #475569;
            --pos-text-muted: #94a3b8;
            --pos-primary: #d97706;
            --pos-primary-hover: #b45309;
            --pos-primary-light: rgba(217, 119, 6, 0.12);
            --pos-success: #16a34a;
            --pos-success-bg: rgba(22, 163, 74, 0.12);
            --pos-danger: #dc2626;
            --pos-danger-bg: rgba(220, 38, 38, 0.12);
            --pos-warning: #d97706;
            --pos-warning-bg: rgba(217, 119, 6, 0.12);
            --pos-info: #2563eb;
            --pos-info-bg: rgba(37, 99, 235, 0.12);
            --pos-shadow: 0 1px 3px rgba(0, 0, 0, 0.08), 0 1px 2px rgba(0, 0, 0, 0.05);
            --pos-shadow-lg: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        }

        .dark, [data-theme="dark"], html.dark, .fi-body.dark {
            --pos-bg-card: #18181b;
            --pos-bg-subtle: #1f1f23;
            --pos-bg-input: #27272a;
            --pos-border: #2e2e33;
            --pos-text-primary: #f8fafc;
            --pos-text-secondary: #cbd5e1;
            --pos-text-muted: #64748b;
            --pos-primary: #f59e0b;
            --pos-primary-hover: #d97706;
            --pos-primary-light: rgba(245, 158, 11, 0.18);
            --pos-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3), 0 2px 4px -2px rgba(0, 0, 0, 0.3);
            --pos-shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.5);
        }

        /* SVG Dimensions Lock */
        .pos-icon-xs { width: 0.875rem !important; height: 0.875rem !important; min-width: 0.875rem !important; max-width: 0.875rem !important; flex-shrink: 0; display: inline-block; }
        .pos-icon-sm { width: 1rem !important; height: 1rem !important; min-width: 1rem !important; max-width: 1rem !important; flex-shrink: 0; display: inline-block; }
        .pos-icon { width: 1.25rem !important; height: 1.25rem !important; min-width: 1.25rem !important; max-width: 1.25rem !important; flex-shrink: 0; display: inline-block; }
        .pos-icon-lg { width: 1.75rem !important; height: 1.75rem !important; min-width: 1.75rem !important; max-width: 1.75rem !important; flex-shrink: 0; display: inline-block; }
        .pos-icon-xl { width: 2.5rem !important; height: 2.5rem !important; min-width: 2.5rem !important; max-width: 2.5rem !important; flex-shrink: 0; display: inline-block; }

        /* POS Root Grid Layout */
        .pos-layout {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.25rem;
            width: 100%;
            margin-top: -0.5rem;
        }
        @media (min-width: 1024px) {
            .pos-layout {
                grid-template-columns: minmax(0, 1.9fr) minmax(360px, 1.1fr);
                align-items: start;
            }
        }

        /* Left Side: Toolbar */
        .pos-toolbar {
            background: var(--pos-bg-card);
            border: 1px solid var(--pos-border);
            border-radius: 1rem;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            box-shadow: var(--pos-shadow);
        }

        .pos-search-box {
            position: relative;
            display: flex;
            align-items: center;
            width: 100%;
        }

        .pos-search-icon {
            position: absolute;
            left: 0.875rem;
            color: var(--pos-text-muted);
            pointer-events: none;
            display: flex;
            align-items: center;
        }

        .pos-search-input {
            width: 100%;
            height: 2.75rem;
            padding: 0 3.25rem 0 2.75rem;
            border-radius: 0.75rem;
            border: 1.5px solid var(--pos-border);
            background: var(--pos-bg-input);
            color: var(--pos-text-primary);
            font-size: 0.875rem;
            font-weight: 500;
            outline: none;
            transition: all 0.2s ease;
        }
        .pos-search-input:focus {
            border-color: var(--pos-primary);
            box-shadow: 0 0 0 3px var(--pos-primary-light);
        }

        .pos-search-kbd {
            position: absolute;
            right: 0.75rem;
            padding: 0.2rem 0.45rem;
            font-size: 0.6875rem;
            font-family: monospace;
            font-weight: 700;
            border-radius: 0.375rem;
            background: var(--pos-bg-card);
            color: var(--pos-text-secondary);
            border: 1px solid var(--pos-border);
            pointer-events: none;
        }

        .pos-cat-tabs {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            overflow-x: auto;
            padding-bottom: 0.25rem;
            scrollbar-width: thin;
        }

        .pos-cat-btn {
            padding: 0.45rem 1rem;
            border-radius: 0.625rem;
            font-size: 0.75rem;
            font-weight: 600;
            border: 1.5px solid transparent;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.15s ease;
            background: var(--pos-bg-input);
            color: var(--pos-text-secondary);
        }
        .pos-cat-btn:hover {
            background: var(--pos-border);
            color: var(--pos-text-primary);
        }
        .pos-cat-btn.active {
            background: var(--pos-primary);
            color: #ffffff;
            border-color: var(--pos-primary);
            font-weight: 700;
            box-shadow: 0 2px 6px rgba(217, 119, 6, 0.3);
        }

        /* Products Grid */
        .pos-grid-menu {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.875rem;
        }
        @media (min-width: 640px) {
            .pos-grid-menu {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }
        @media (min-width: 1280px) {
            .pos-grid-menu {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }

        /* Product Card */
        .pos-card {
            background: var(--pos-bg-card);
            border: 1.5px solid var(--pos-border);
            border-radius: 1rem;
            padding: 0.875rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            cursor: pointer;
            transition: all 0.18s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            user-select: none;
            box-shadow: var(--pos-shadow);
        }
        .pos-card:hover {
            border-color: var(--pos-primary);
            transform: translateY(-2px);
            box-shadow: var(--pos-shadow-lg);
        }
        .pos-card.in-cart {
            border-color: var(--pos-primary);
            background: var(--pos-primary-light);
            box-shadow: 0 0 0 2px var(--pos-primary);
        }
        .pos-card.out-of-stock {
            opacity: 0.55;
            cursor: not-allowed;
            transform: none !important;
            background: var(--pos-bg-input);
        }

        .pos-card-img-wrap {
            width: 100%;
            height: 105px;
            border-radius: 0.75rem;
            overflow: hidden;
            background: var(--pos-bg-input);
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.625rem;
        }

        .pos-card-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.25s ease;
        }
        .pos-card:hover .pos-card-img {
            transform: scale(1.05);
        }

        .pos-qty-badge {
            position: absolute;
            top: 0.375rem;
            right: 0.375rem;
            background: var(--pos-primary);
            color: #ffffff;
            font-size: 0.6875rem;
            font-weight: 800;
            width: 1.4rem;
            height: 1.4rem;
            border-radius: 9999px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.25);
            border: 2px solid var(--pos-bg-card);
        }

        .pos-out-badge {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(2px);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.75rem;
        }
        .pos-out-text {
            background: var(--pos-danger);
            color: #ffffff;
            font-size: 0.6875rem;
            font-weight: 900;
            padding: 0.2rem 0.5rem;
            border-radius: 0.375rem;
            letter-spacing: 0.06em;
        }

        .pos-card-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.25rem;
            margin-bottom: 0.25rem;
        }
        .pos-card-cat {
            font-size: 0.6875rem;
            color: var(--pos-text-muted);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .pos-card-stock {
            font-size: 0.6875rem;
            font-weight: 700;
            padding: 0.1rem 0.35rem;
            border-radius: 0.375rem;
            white-space: nowrap;
        }
        .pos-stock-safe { background: var(--pos-success-bg); color: var(--pos-success); }
        .pos-stock-low { background: var(--pos-warning-bg); color: var(--pos-warning); }
        .pos-stock-empty { background: var(--pos-danger-bg); color: var(--pos-danger); }

        .pos-card-title {
            font-size: 0.8125rem;
            font-weight: 700;
            color: var(--pos-text-primary);
            line-height: 1.25;
            margin: 0.15rem 0 0.4rem 0;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
        }

        .pos-card-price {
            font-size: 0.875rem;
            font-weight: 900;
            color: var(--pos-primary);
            font-family: monospace;
            letter-spacing: -0.01em;
        }

        /* Right Side: Cart Panel */
        .pos-cart-panel {
            background: var(--pos-bg-card);
            border: 1px solid var(--pos-border);
            border-radius: 1rem;
            position: sticky;
            top: 1rem;
            height: calc(100vh - 6.5rem);
            max-height: calc(100vh - 6.5rem);
            display: flex;
            flex-direction: column;
            box-shadow: var(--pos-shadow);
            overflow: hidden;
        }

        .pos-cart-header {
            padding: 0.875rem 1.125rem;
            border-bottom: 1px solid var(--pos-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--pos-bg-subtle);
        }

        .pos-cart-title-box {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .pos-cart-title {
            font-size: 0.875rem;
            font-weight: 700;
            color: var(--pos-text-primary);
        }
        .pos-cart-pill {
            font-size: 0.6875rem;
            font-weight: 700;
            background: var(--pos-primary-light);
            color: var(--pos-primary);
            padding: 0.15rem 0.5rem;
            border-radius: 9999px;
        }

        .pos-cart-clear-btn {
            font-size: 0.6875rem;
            font-weight: 700;
            color: var(--pos-danger);
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 0.2rem 0.4rem;
            border-radius: 0.375rem;
            transition: background 0.15s;
        }
        .pos-cart-clear-btn:hover {
            background: var(--pos-danger-bg);
        }

        .pos-cart-body {
            flex: 1;
            overflow-y: auto;
            padding: 0.75rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            scrollbar-width: thin;
        }

        .pos-cart-empty-box {
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: var(--pos-text-muted);
            padding: 2rem 1rem;
        }
        .pos-cart-empty-icon {
            width: 3.25rem;
            height: 3.25rem;
            border-radius: 9999px;
            background: var(--pos-bg-input);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.75rem;
            color: var(--pos-text-muted);
        }

        .pos-cart-item {
            background: var(--pos-bg-input);
            border: 1px solid var(--pos-border);
            border-radius: 0.75rem;
            padding: 0.625rem;
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }

        .pos-item-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.5rem;
        }
        .pos-item-name {
            font-size: 0.8125rem;
            font-weight: 700;
            color: var(--pos-text-primary);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .pos-item-unit {
            font-size: 0.6875rem;
            color: var(--pos-text-muted);
            font-family: monospace;
        }

        .pos-item-del-btn {
            background: transparent;
            border: none;
            color: var(--pos-text-muted);
            cursor: pointer;
            padding: 0.15rem;
            border-radius: 0.25rem;
            transition: color 0.15s;
            display: flex;
            align-items: center;
        }
        .pos-item-del-btn:hover {
            color: var(--pos-danger);
        }

        .pos-item-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-top: 1px solid var(--pos-border);
            padding-top: 0.35rem;
        }

        .pos-qty-control {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            background: var(--pos-bg-card);
            border: 1px solid var(--pos-border);
            border-radius: 0.5rem;
            padding: 0.1rem;
        }
        .pos-qty-btn {
            width: 1.4rem;
            height: 1.4rem;
            border: none;
            background: transparent;
            border-radius: 0.375rem;
            font-weight: 800;
            font-size: 0.75rem;
            color: var(--pos-text-primary);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.15s;
        }
        .pos-qty-btn:hover {
            background: var(--pos-bg-input);
        }
        .pos-qty-number {
            width: 1.5rem;
            text-align: center;
            font-weight: 700;
            font-size: 0.75rem;
            color: var(--pos-text-primary);
            font-family: monospace;
        }

        .pos-item-subtotal {
            font-size: 0.8125rem;
            font-weight: 900;
            color: var(--pos-text-primary);
            font-family: monospace;
        }

        .pos-cart-footer {
            padding: 0.875rem 1.125rem;
            border-top: 1px solid var(--pos-border);
            background: var(--pos-bg-subtle);
            display: flex;
            flex-direction: column;
            gap: 0.625rem;
        }

        .pos-summary-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.75rem;
            color: var(--pos-text-secondary);
        }
        .pos-summary-total {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            border-top: 1px solid var(--pos-border);
            padding-top: 0.35rem;
        }
        .pos-total-title {
            font-size: 0.8125rem;
            font-weight: 700;
            color: var(--pos-text-primary);
        }
        .pos-total-val {
            font-size: 1.35rem;
            font-weight: 900;
            color: var(--pos-primary);
            font-family: monospace;
            letter-spacing: -0.02em;
        }

        .pos-checkout-btn {
            width: 100%;
            padding: 0.75rem 1rem;
            border-radius: 0.75rem;
            border: none;
            font-size: 0.8125rem;
            font-weight: 800;
            color: #ffffff;
            background: var(--pos-primary);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.15s ease;
            box-shadow: 0 4px 6px -1px rgba(217, 119, 6, 0.25);
        }
        .pos-checkout-btn:hover {
            background: var(--pos-primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 6px 10px -2px rgba(217, 119, 6, 0.35);
        }
        .pos-checkout-btn:disabled {
            background: var(--pos-border);
            color: var(--pos-text-muted);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        .pos-checkout-kbd {
            background: rgba(255, 255, 255, 0.25);
            color: #ffffff;
            font-size: 0.6875rem;
            font-family: monospace;
            font-weight: 800;
            padding: 0.15rem 0.35rem;
            border-radius: 0.375rem;
        }

        /* Modal Styles */
        .pos-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.65);
            backdrop-filter: blur(3px);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .pos-modal-card {
            background: var(--pos-bg-card);
            border: 1px solid var(--pos-border);
            border-radius: 1.25rem;
            width: 100%;
            max-width: 440px;
            box-shadow: var(--pos-shadow-lg);
            overflow: hidden;
        }
        .pos-modal-head {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--pos-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .pos-modal-body {
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .pos-modal-foot {
            padding: 0.875rem 1.25rem;
            border-top: 1px solid var(--pos-border);
            background: var(--pos-bg-subtle);
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.5rem;
        }

        .pos-methods-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.5rem;
        }
        .pos-method-card {
            padding: 0.625rem 0.5rem;
            border-radius: 0.75rem;
            border: 1.5px solid var(--pos-border);
            background: var(--pos-bg-input);
            color: var(--pos-text-primary);
            font-size: 0.75rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.2rem;
            transition: all 0.15s;
        }
        .pos-method-card:hover {
            border-color: var(--pos-primary);
        }
        .pos-method-card.active {
            background: var(--pos-primary);
            color: #ffffff;
            border-color: var(--pos-primary);
            box-shadow: 0 2px 6px rgba(217, 119, 6, 0.3);
        }

        .pos-input-money {
            width: 100%;
            height: 2.75rem;
            padding: 0 0.875rem 0 2.25rem;
            border-radius: 0.75rem;
            border: 1.5px solid var(--pos-border);
            background: var(--pos-bg-input);
            color: var(--pos-text-primary);
            font-size: 1.125rem;
            font-weight: 800;
            font-family: monospace;
            outline: none;
        }
        .pos-input-money:focus {
            border-color: var(--pos-primary);
            box-shadow: 0 0 0 3px var(--pos-primary-light);
        }

        .pos-quick-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem;
        }
        .pos-quick-btn {
            padding: 0.3rem 0.6rem;
            border-radius: 0.5rem;
            border: 1px solid var(--pos-border);
            background: var(--pos-bg-input);
            color: var(--pos-text-primary);
            font-size: 0.6875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s;
        }
        .pos-quick-btn:hover {
            background: var(--pos-border);
        }
        .pos-quick-btn.exact {
            background: var(--pos-primary-light);
            color: var(--pos-primary);
            border-color: var(--pos-primary);
            font-weight: 800;
        }

        .pos-change-box {
            padding: 0.75rem 1rem;
            background: var(--pos-bg-input);
            border-radius: 0.75rem;
            border: 1px solid var(--pos-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .pos-btn-secondary {
            padding: 0.5rem 1rem;
            border-radius: 0.625rem;
            border: 1px solid var(--pos-border);
            background: var(--pos-bg-card);
            color: var(--pos-text-secondary);
            font-size: 0.75rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.15s;
        }
        .pos-btn-secondary:hover {
            background: var(--pos-bg-input);
            color: var(--pos-text-primary);
        }

        .pos-btn-primary {
            padding: 0.5rem 1.125rem;
            border-radius: 0.625rem;
            border: none;
            background: var(--pos-primary);
            color: #ffffff;
            font-size: 0.75rem;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.15s;
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }
        .pos-btn-primary:hover {
            background: var(--pos-primary-hover);
        }

        /* Thermal Receipt Print Styles */
        @media print {
            body * { visibility: hidden !important; }
            #thermal-receipt-print-area, #thermal-receipt-print-area * { visibility: visible !important; }
            #thermal-receipt-print-area {
                position: fixed !important;
                left: 0 !important;
                top: 0 !important;
                width: 58mm !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #ffffff !important;
                color: #000000 !important;
                z-index: 99999 !important;
            }
            @page { size: 58mm auto; margin: 0mm; }
        }
    </style>

    {{-- Area Print Thermal Struk (Tersembunyi di layar normal, tampil saat window.print) --}}
    <div id="thermal-receipt-print-area" style="display: none;">
        @if ($lastOrder)
            @include('filament.pages.partials.receipt', ['order' => $lastOrder])
        @endif
    </div>

    {{-- ================= KONTEN UTAMA POS ================= --}}
    <div class="pos-layout">
        {{-- ================= SISI KIRI: KATALOG MENU & PENCARIAN ================= --}}
        <div style="display: flex; flex-direction: column; gap: 0.875rem;">
            {{-- Toolbar: Kategori & Search --}}
            <div class="pos-toolbar">
                {{-- Search Bar --}}
                <div class="pos-search-box">
                    <div class="pos-search-icon">
                        <svg class="pos-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input
                        id="pos-search-input"
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Ketik nama menu makanan / minuman (Cari cepat / F2)..."
                        class="pos-search-input"
                    />
                    <kbd class="pos-search-kbd">F2</kbd>
                </div>

                {{-- Category Filter Tabs --}}
                <div class="pos-cat-tabs">
                    <button
                        type="button"
                        wire:click="selectCategory(null)"
                        class="pos-cat-btn {{ is_null($selectedCategoryId) ? 'active' : '' }}"
                    >
                        Semua Kategori
                    </button>
                    @foreach ($this->categories as $category)
                        <button
                            type="button"
                            wire:click="selectCategory({{ $category->id }})"
                            class="pos-cat-btn {{ $selectedCategoryId === $category->id ? 'active' : '' }}"
                        >
                            {{ $category->name }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Grid Menu Produk --}}
            <div class="pos-grid-menu">
                @forelse ($this->products as $product)
                    @php
                        $isOutOfStock = $product->stock <= 0;
                        $inCart = isset($cart[$product->id]);
                        $cartQty = $cart[$product->id]['qty'] ?? 0;
                    @endphp
                    <div
                        @if (! $isOutOfStock)
                            wire:click="addToCart({{ $product->id }})"
                        @endif
                        class="pos-card {{ $isOutOfStock ? 'out-of-stock' : '' }} {{ $inCart ? 'in-cart' : '' }}"
                    >
                        {{-- Foto atau Placeholder --}}
                        <div class="pos-card-img-wrap">
                            @if ($product->image_path)
                                <img
                                    src="{{ asset('storage/' . $product->image_path) }}"
                                    alt="{{ $product->name }}"
                                    class="pos-card-img"
                                />
                            @else
                                <svg class="pos-icon-lg" style="color: var(--pos-text-muted);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                </svg>
                            @endif

                            {{-- Badge Kuantitas di Keranjang --}}
                            @if ($inCart)
                                <div class="pos-qty-badge">
                                    {{ $cartQty }}
                                </div>
                            @endif

                            {{-- Badge Habis --}}
                            @if ($isOutOfStock)
                                <div class="pos-out-badge">
                                    <span class="pos-out-text">HABIS</span>
                                </div>
                            @endif
                        </div>

                        {{-- Info Produk --}}
                        <div>
                            <div class="pos-card-meta">
                                <span class="pos-card-cat">
                                    {{ $product->category?->name }}
                                </span>
                                <span class="pos-card-stock {{ $isOutOfStock ? 'pos-stock-empty' : ($product->isLowStock() ? 'pos-stock-low' : 'pos-stock-safe') }}">
                                    Stok: {{ $product->stock }}
                                </span>
                            </div>

                            <div class="pos-card-title">
                                {{ $product->name }}
                            </div>

                            <div class="pos-card-price">
                                Rp {{ number_format((float) $product->selling_price, 0, ',', '.') }}
                            </div>
                        </div>
                    </div>
                @empty
                    <div style="grid-column: 1 / -1; padding: 3rem 1rem; text-align: center; background: var(--pos-bg-card); border-radius: 1rem; border: 1.5px dashed var(--pos-border); color: var(--pos-text-muted);">
                        <svg class="pos-icon-xl" style="margin: 0 auto 0.5rem auto; color: var(--pos-text-muted);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p style="font-size: 0.875rem; font-weight: 600; color: var(--pos-text-secondary); margin: 0;">
                            Tidak ada menu yang sesuai dengan filter atau pencarian.
                        </p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- ================= SISI KANAN: KERANJANG PESANAN ================= --}}
        <div>
            <div class="pos-cart-panel">
                {{-- Header Keranjang --}}
                <div class="pos-cart-header">
                    <div class="pos-cart-title-box">
                        <svg class="pos-icon" style="color: var(--pos-primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                        <span class="pos-cart-title">Ringkasan Pesanan</span>
                        @if ($this->totalItems > 0)
                            <span class="pos-cart-pill">{{ $this->totalItems }} item</span>
                        @endif
                    </div>

                    @if (count($cart) > 0)
                        <button
                            type="button"
                            wire:click="clearCart"
                            wire:confirm="Kosongkan seluruh keranjang belanja?"
                            class="pos-cart-clear-btn"
                        >
                            Kosongkan
                        </button>
                    @endif
                </div>

                {{-- Daftar Item Keranjang --}}
                <div class="pos-cart-body">
                    @forelse ($cart as $productId => $item)
                        <div class="pos-cart-item">
                            <div class="pos-item-top">
                                <div style="flex: 1; min-width: 0;">
                                    <div class="pos-item-name">{{ $item['name'] }}</div>
                                    <div class="pos-item-unit">Rp {{ number_format((float) $item['price'], 0, ',', '.') }} / porsi</div>
                                </div>
                                <button
                                    type="button"
                                    wire:click="removeFromCart({{ $productId }})"
                                    class="pos-item-del-btn"
                                    title="Hapus menu"
                                >
                                    <svg class="pos-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>

                            <div class="pos-item-bottom">
                                {{-- Tombol +/- Kuantitas --}}
                                <div class="pos-qty-control">
                                    <button
                                        type="button"
                                        wire:click="decrementQty({{ $productId }})"
                                        class="pos-qty-btn"
                                    >
                                        -
                                    </button>
                                    <span class="pos-qty-number">
                                        {{ $item['qty'] }}
                                    </span>
                                    <button
                                        type="button"
                                        wire:click="incrementQty({{ $productId }})"
                                        class="pos-qty-btn"
                                    >
                                        +
                                    </button>
                                </div>

                                {{-- Subtotal Per Baris --}}
                                <div class="pos-item-subtotal">
                                    Rp {{ number_format((float) $item['subtotal'], 0, ',', '.') }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="pos-cart-empty-box">
                            <div class="pos-cart-empty-icon">
                                <svg class="pos-icon-lg" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                            <div style="font-size: 0.8125rem; font-weight: 700; color: var(--pos-text-primary); margin-bottom: 0.25rem;">
                                Keranjang Masih Kosong
                            </div>
                            <div style="font-size: 0.75rem; color: var(--pos-text-muted); max-width: 220px;">
                                Klik menu di sisi kiri untuk mulai mencatat pesanan pelanggan.
                            </div>
                        </div>
                    @endforelse
                </div>

                {{-- Footer & Total Tagihan --}}
                <div class="pos-cart-footer">
                    <div class="pos-summary-row">
                        <span>Total Kuantitas</span>
                        <span style="font-weight: 700; color: var(--pos-text-primary);">{{ $this->totalItems }} Porsi</span>
                    </div>

                    <div class="pos-summary-total">
                        <span class="pos-total-title">Total Tagihan</span>
                        <span class="pos-total-val">
                            Rp {{ number_format($this->totalAmount, 0, ',', '.') }}
                        </span>
                    </div>

                    {{-- Tombol Lanjut ke Pembayaran --}}
                    <button
                        type="button"
                        wire:click="openPaymentModal"
                        @if (count($cart) === 0) disabled @endif
                        class="pos-checkout-btn"
                    >
                        <svg class="pos-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <span>Proses Pembayaran</span>
                        <kbd class="pos-checkout-kbd">F9</kbd>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= MODAL PEMBAYARAN KASIR ================= --}}
    @if ($showPaymentModal)
        <div class="pos-modal-overlay">
            <div class="pos-modal-card">
                {{-- Header Modal --}}
                <div class="pos-modal-head">
                    <div>
                        <div style="font-size: 1rem; font-weight: 800; color: var(--pos-text-primary);">
                            Pembayaran Kasir
                        </div>
                        <div style="font-size: 0.75rem; color: var(--pos-text-muted);">
                            Total tagihan: <span style="font-weight: 800; color: var(--pos-primary);">Rp {{ number_format($this->totalAmount, 0, ',', '.') }}</span>
                        </div>
                    </div>
                    <button
                        type="button"
                        wire:click="closePaymentModal"
                        class="pos-btn-secondary"
                        style="padding: 0.25rem 0.5rem;"
                    >
                        ✕
                    </button>
                </div>

                {{-- Body Modal --}}
                <div class="pos-modal-body">
                    {{-- Pilihan Metode Bayar --}}
                    <div>
                        <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--pos-text-secondary); margin-bottom: 0.35rem;">
                            Metode Pembayaran
                        </label>
                        <div class="pos-methods-grid">
                            <button
                                type="button"
                                wire:click="setPaymentMethod('cash')"
                                class="pos-method-card {{ $paymentMethod === 'cash' ? 'active' : '' }}"
                            >
                                <span>💵 Tunai</span>
                                <span style="font-size: 0.65rem; font-weight: normal; opacity: 0.85;">Cash</span>
                            </button>

                            <button
                                type="button"
                                wire:click="setPaymentMethod('qris')"
                                class="pos-method-card {{ $paymentMethod === 'qris' ? 'active' : '' }}"
                            >
                                <span>📱 QRIS</span>
                                <span style="font-size: 0.65rem; font-weight: normal; opacity: 0.85;">Non-Tunai</span>
                            </button>

                            <button
                                type="button"
                                wire:click="setPaymentMethod('transfer')"
                                class="pos-method-card {{ $paymentMethod === 'transfer' ? 'active' : '' }}"
                            >
                                <span>🏦 Transfer</span>
                                <span style="font-size: 0.65rem; font-weight: normal; opacity: 0.85;">Bank</span>
                            </button>
                        </div>
                    </div>

                    {{-- Form Input Tunai Diterima (Hanya Tampil Jika Metode Cash) --}}
                    @if ($paymentMethod === 'cash')
                        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                            <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--pos-text-secondary);">
                                Uang Tunai Diterima (Rp)
                            </label>
                            <div style="position: relative; display: flex; align-items: center;">
                                <span style="position: absolute; left: 0.75rem; font-size: 0.875rem; font-weight: 800; color: var(--pos-text-muted);">
                                    Rp
                                </span>
                                <input
                                    type="number"
                                    wire:model.live="paidAmount"
                                    placeholder="0"
                                    class="pos-input-money"
                                    autofocus
                                />
                            </div>

                            {{-- Tombol Shortcut Pecahan Cepat --}}
                            <div class="pos-quick-grid">
                                <button
                                    type="button"
                                    wire:click="setExactCash"
                                    class="pos-quick-btn exact"
                                >
                                    Uang Pas (Rp {{ number_format($this->totalAmount, 0, ',', '.') }})
                                </button>
                                <button
                                    type="button"
                                    wire:click="setQuickCash(10000)"
                                    class="pos-quick-btn"
                                >
                                    10.000
                                </button>
                                <button
                                    type="button"
                                    wire:click="setQuickCash(20000)"
                                    class="pos-quick-btn"
                                >
                                    20.000
                                </button>
                                <button
                                    type="button"
                                    wire:click="setQuickCash(50000)"
                                    class="pos-quick-btn"
                                >
                                    50.000
                                </button>
                                <button
                                    type="button"
                                    wire:click="setQuickCash(100000)"
                                    class="pos-quick-btn"
                                >
                                    100.000
                                </button>
                            </div>

                            {{-- Kalkulasi Kembalian --}}
                            <div class="pos-change-box">
                                <span style="font-size: 0.75rem; font-weight: 600; color: var(--pos-text-secondary);">Kembalian:</span>
                                <span style="font-size: 1.125rem; font-weight: 900; font-family: monospace; color: {{ $this->changeAmount >= 0 ? 'var(--pos-success)' : 'var(--pos-danger)' }};">
                                    Rp {{ number_format($this->changeAmount, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    @else
                        {{-- Info QRIS / Transfer --}}
                        <div style="padding: 0.875rem 1rem; border-radius: 0.75rem; background: var(--pos-info-bg); border: 1px solid rgba(37, 99, 235, 0.25); color: var(--pos-info); font-size: 0.75rem;">
                            <div style="font-weight: 800; margin-bottom: 0.2rem;">Pembayaran Otomatis Uang Pas</div>
                            <div style="font-size: 0.7rem; opacity: 0.9;">
                                Transaksi akan langsung dicatat lunas sejumlah <strong>Rp {{ number_format($this->totalAmount, 0, ',', '.') }}</strong> tanpa uang kembalian.
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Footer Modal --}}
                <div class="pos-modal-foot">
                    <button
                        type="button"
                        wire:click="closePaymentModal"
                        class="pos-btn-secondary"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        wire:click="checkout"
                        class="pos-btn-primary"
                    >
                        <svg class="pos-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Konfirmasi Pembayaran
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ================= MODAL TRANSAKSI SUKSES & NOTA ================= --}}
    @if ($showSuccessModal && $lastOrder)
        <div class="pos-modal-overlay">
            <div class="pos-modal-card" style="max-width: 360px; text-align: center; padding: 1.25rem;">
                <div style="width: 2.75rem; height: 2.75rem; border-radius: 9999px; background: var(--pos-success-bg); color: var(--pos-success); display: flex; align-items: center; justify-content: center; margin: 0 auto 0.75rem auto;">
                    <svg class="pos-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--pos-success);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>

                <div>
                    <h3 style="font-size: 1.125rem; font-weight: 900; color: var(--pos-text-primary); margin: 0;">
                        Transaksi Sukses!
                    </h3>
                    <p style="font-size: 0.75rem; color: var(--pos-text-muted); margin: 0.25rem 0 0.75rem 0;">
                        Nota: <strong style="color: var(--pos-text-primary);">{{ $lastOrder->order_number }}</strong>
                    </p>
                </div>

                {{-- Preview Struk Visual Thermal --}}
                <div style="padding: 0.625rem; background: var(--pos-bg-subtle); border-radius: 0.75rem; border: 1.5px dashed var(--pos-border); text-align: left; overflow: hidden; margin-bottom: 1rem;">
                    @include('filament.pages.partials.receipt', ['order' => $lastOrder])
                </div>

                <div style="display: flex; gap: 0.5rem;">
                    <button
                        type="button"
                        wire:click="printLastReceipt"
                        class="pos-btn-secondary"
                        style="flex: 1; padding: 0.625rem 0.5rem;"
                    >
                        🖨️ Cetak Ulang
                    </button>
                    <button
                        type="button"
                        wire:click="closeSuccessModal"
                        class="pos-btn-primary"
                        style="flex: 1; padding: 0.625rem 0.5rem; justify-content: center;"
                    >
                        Selesai (Baru)
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Script Keyboard Shortcuts (F2: Cari Menu, F9: Bayar) & Trigger Otomatis window.print() --}}
    <script>
        document.addEventListener('keydown', (e) => {
            // F2: Fokus langsung ke kolom pencarian menu
            if (e.key === 'F2') {
                e.preventDefault();
                const searchInput = document.getElementById('pos-search-input');
                if (searchInput) {
                    searchInput.focus();
                    searchInput.select();
                }
            }

            // F9: Membuka modal pembayaran
            if (e.key === 'F9') {
                e.preventDefault();
                @this.call('openPaymentModal');
            }
        });

        document.addEventListener('livewire:initialized', () => {
            Livewire.on('print-receipt', () => {
                setTimeout(() => {
                    window.print();
                }, 300);
            });
        });
    </script>
</x-filament-panels::page>
