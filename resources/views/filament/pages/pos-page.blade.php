<x-filament-panels::page>
    {{-- Scoped Stylesheet untuk Kasir Cepat (POS) & Printer Thermal --}}
    <style>
        /* Reset & Ukuran SVG Ikon */
        .pos-icon-xs { width: 0.875rem !important; height: 0.875rem !important; min-width: 0.875rem !important; max-width: 0.875rem !important; flex-shrink: 0; }
        .pos-icon-sm { width: 1rem !important; height: 1rem !important; min-width: 1rem !important; max-width: 1rem !important; flex-shrink: 0; }
        .pos-icon { width: 1.25rem !important; height: 1.25rem !important; min-width: 1.25rem !important; max-width: 1.25rem !important; flex-shrink: 0; }
        .pos-icon-lg { width: 1.75rem !important; height: 1.75rem !important; min-width: 1.75rem !important; max-width: 1.75rem !important; flex-shrink: 0; }
        .pos-icon-xl { width: 2.5rem !important; height: 2.5rem !important; min-width: 2.5rem !important; max-width: 2.5rem !important; flex-shrink: 0; }

        /* Layout Grid Utama POS */
        .pos-layout-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.25rem;
            width: 100%;
            margin-top: -0.5rem;
        }
        @media (min-width: 1024px) {
            .pos-layout-grid {
                grid-template-columns: minmax(0, 1.85fr) minmax(360px, 1.15fr);
                align-items: start;
            }
        }

        /* Grid Katalog Menu */
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

        /* Sticky Cart Container */
        .pos-cart-panel {
            position: sticky;
            top: 1rem;
            height: calc(100vh - 6.5rem);
            max-height: calc(100vh - 6.5rem);
            display: flex;
            flex-direction: column;
            border-radius: 1rem;
            overflow: hidden;
        }

        /* Scrollbar Halus */
        .pos-scroll-custom::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }
        .pos-scroll-custom::-webkit-scrollbar-track {
            background: transparent;
        }
        .pos-scroll-custom::-webkit-scrollbar-thumb {
            background: rgba(156, 163, 175, 0.4);
            border-radius: 9999px;
        }
        .pos-scroll-custom::-webkit-scrollbar-thumb:hover {
            background: rgba(156, 163, 175, 0.7);
        }

        /* Print Thermal 58mm / 80mm */
        @media print {
            body * {
                visibility: hidden !important;
            }
            #thermal-receipt-print-area, #thermal-receipt-print-area * {
                visibility: visible !important;
            }
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
            @page {
                size: 58mm auto;
                margin: 0mm;
            }
        }
    </style>

    {{-- Area Print Thermal Struk (Tersembunyi di layar normal, tampil saat window.print) --}}
    <div id="thermal-receipt-print-area" class="hidden print:block">
        @if ($lastOrder)
            @include('filament.pages.partials.receipt', ['order' => $lastOrder])
        @endif
    </div>

    {{-- ================= KONTEN UTAMA POS ================= --}}
    <div class="pos-layout-grid print:hidden">
        {{-- ================= SISI KIRI: KATALOG MENU & PENCARIAN ================= --}}
        <div class="space-y-3.5">
            {{-- Toolbar: Kategori & Search --}}
            <div class="bg-white dark:bg-gray-900 rounded-2xl p-3.5 shadow-xs border border-gray-200 dark:border-gray-800 space-y-3">
                {{-- Search Bar --}}
                <div class="relative flex items-center">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                        <svg class="pos-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 1.25rem; height: 1.25rem;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input
                        id="pos-search-input"
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Ketik nama menu makanan / minuman (Cari cepat / F2)..."
                        class="w-full pl-10 pr-12 py-2.5 bg-gray-50 dark:bg-gray-800/80 border border-gray-200 dark:border-gray-700 rounded-xl text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:text-gray-100 placeholder-gray-400 transition"
                    />
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <kbd class="hidden sm:inline-flex items-center px-1.5 py-0.5 text-[10px] font-mono font-bold bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded border border-gray-300 dark:border-gray-600 shadow-xs">
                            F2
                        </kbd>
                    </div>
                </div>

                {{-- Category Filter Tabs --}}
                <div class="flex items-center gap-2 overflow-x-auto pb-1 pos-scroll-custom">
                    <button
                        type="button"
                        wire:click="selectCategory(null)"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all cursor-pointer {{ is_null($selectedCategoryId) ? 'bg-amber-600 text-white shadow-xs ring-2 ring-amber-500/30' : 'bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300' }}"
                    >
                        Semua Kategori
                    </button>
                    @foreach ($this->categories as $category)
                        <button
                            type="button"
                            wire:click="selectCategory({{ $category->id }})"
                            class="px-3.5 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all cursor-pointer {{ $selectedCategoryId === $category->id ? 'bg-amber-600 text-white shadow-xs ring-2 ring-amber-500/30' : 'bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300' }}"
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
                        class="group relative bg-white dark:bg-gray-900 rounded-2xl p-3 border transition-all duration-150 flex flex-col justify-between select-none
                            {{ $isOutOfStock 
                                ? 'opacity-55 bg-gray-50 dark:bg-gray-950 border-gray-200 dark:border-gray-800 cursor-not-allowed' 
                                : 'hover:border-amber-500 hover:shadow-md cursor-pointer border-gray-200 dark:border-gray-800 active:scale-[0.98]' }}
                            {{ $inCart ? 'ring-2 ring-amber-500 border-amber-500 bg-amber-50/15 dark:bg-amber-950/20' : '' }}"
                    >
                        {{-- Foto atau Placeholder --}}
                        <div class="w-full h-24 sm:h-28 rounded-xl bg-gray-100 dark:bg-gray-800 overflow-hidden mb-2 relative flex items-center justify-center">
                            @if ($product->image_path)
                                <img
                                    src="{{ asset('storage/' . $product->image_path) }}"
                                    alt="{{ $product->name }}"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200"
                                />
                            @else
                                <div class="text-gray-400 dark:text-gray-600 flex flex-col items-center justify-center">
                                    <svg class="pos-icon-lg text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 1.75rem; height: 1.75rem;">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                    </svg>
                                </div>
                            @endif

                            {{-- Badge Kuantitas di Keranjang --}}
                            @if ($inCart)
                                <div class="absolute top-1.5 right-1.5 bg-amber-600 text-white text-[11px] font-black w-6 h-6 rounded-full flex items-center justify-center shadow-md ring-2 ring-white dark:ring-gray-900">
                                    {{ $cartQty }}
                                </div>
                            @endif

                            {{-- Badge Habis --}}
                            @if ($isOutOfStock)
                                <div class="absolute inset-0 bg-black/60 backdrop-blur-[2px] flex items-center justify-center">
                                    <span class="bg-red-600 text-white text-[11px] font-black px-2 py-0.5 rounded-md tracking-wider shadow">
                                        HABIS
                                    </span>
                                </div>
                            @endif
                        </div>

                        {{-- Info Produk --}}
                        <div class="space-y-1">
                            <div class="flex items-center justify-between gap-1">
                                <span class="text-[10px] font-semibold text-gray-500 dark:text-gray-400 truncate">
                                    {{ $product->category?->name }}
                                </span>
                                <span class="text-[10px] font-bold {{ $isOutOfStock ? 'text-red-500' : ($product->isLowStock() ? 'text-amber-500' : 'text-emerald-600 dark:text-emerald-400') }}">
                                    Stok: {{ $product->stock }}
                                </span>
                            </div>

                            <h3 class="text-xs sm:text-sm font-bold text-gray-900 dark:text-gray-100 line-clamp-1 leading-tight">
                                {{ $product->name }}
                            </h3>

                            <div class="pt-0.5 flex items-center justify-between">
                                <span class="text-xs sm:text-sm font-black text-amber-600 dark:text-amber-400">
                                    Rp {{ number_format((float) $product->selling_price, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full py-12 text-center bg-white dark:bg-gray-900 rounded-2xl border border-dashed border-gray-300 dark:border-gray-800">
                        <svg class="pos-icon-xl mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 2.5rem; height: 2.5rem; margin: 0 auto;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Tidak ada menu yang sesuai dengan filter atau pencarian.
                        </p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- ================= SISI KANAN: KERANJANG PESANAN ================= --}}
        <div>
            <div class="pos-cart-panel bg-white dark:bg-gray-900 shadow-sm border border-gray-200 dark:border-gray-800">
                {{-- Header Keranjang --}}
                <div class="p-3.5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between bg-gray-50/50 dark:bg-gray-900/50">
                    <div class="flex items-center gap-2">
                        <svg class="pos-icon text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 1.25rem; height: 1.25rem;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                        <h2 class="text-sm font-bold text-gray-900 dark:text-gray-100">
                            Ringkasan Pesanan
                        </h2>
                        @if ($this->totalItems > 0)
                            <span class="bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 text-[11px] font-bold px-2 py-0.5 rounded-full">
                                {{ $this->totalItems }} item
                            </span>
                        @endif
                    </div>

                    @if (count($cart) > 0)
                        <button
                            type="button"
                            wire:click="clearCart"
                            wire:confirm="Kosongkan seluruh keranjang belanja?"
                            class="text-[11px] font-bold text-red-500 hover:text-red-700 transition cursor-pointer"
                        >
                            Kosongkan
                        </button>
                    @endif
                </div>

                {{-- Daftar Item Keranjang --}}
                <div class="flex-1 overflow-y-auto p-3 space-y-2 pos-scroll-custom">
                    @forelse ($cart as $productId => $item)
                        <div class="p-2.5 bg-gray-50 dark:bg-gray-800/60 rounded-xl border border-gray-200/70 dark:border-gray-700/60 space-y-2">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-xs font-bold text-gray-900 dark:text-gray-100 truncate">
                                        {{ $item['name'] }}
                                    </h4>
                                    <div class="text-[10px] text-gray-500 dark:text-gray-400">
                                        Rp {{ number_format((float) $item['price'], 0, ',', '.') }} / porsi
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    wire:click="removeFromCart({{ $productId }})"
                                    class="text-gray-400 hover:text-red-500 transition p-1 cursor-pointer"
                                    title="Hapus menu"
                                >
                                    <svg class="pos-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 1rem; height: 1rem;">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>

                            <div class="flex items-center justify-between pt-1 border-t border-gray-200/50 dark:border-gray-700/50">
                                {{-- Tombol +/- Kuantitas --}}
                                <div class="flex items-center gap-1.5 bg-white dark:bg-gray-900 rounded-lg p-0.5 border border-gray-200 dark:border-gray-700">
                                    <button
                                        type="button"
                                        wire:click="decrementQty({{ $productId }})"
                                        class="w-6 h-6 flex items-center justify-center rounded-md text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 font-bold text-xs transition cursor-pointer"
                                    >
                                        -
                                    </button>
                                    <span class="w-7 text-center font-bold text-xs text-gray-900 dark:text-gray-100">
                                        {{ $item['qty'] }}
                                    </span>
                                    <button
                                        type="button"
                                        wire:click="incrementQty({{ $productId }})"
                                        class="w-6 h-6 flex items-center justify-center rounded-md text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 font-bold text-xs transition cursor-pointer"
                                    >
                                        +
                                    </button>
                                </div>

                                {{-- Subtotal Per Baris --}}
                                <div class="font-black text-xs text-gray-900 dark:text-gray-100 font-mono">
                                    Rp {{ number_format((float) $item['subtotal'], 0, ',', '.') }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="h-full flex flex-col items-center justify-center py-12 text-center text-gray-400 space-y-2">
                            <div class="w-14 h-14 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                                <svg class="pos-icon-lg text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 1.75rem; height: 1.75rem;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                            <p class="text-xs font-bold text-gray-600 dark:text-gray-300">
                                Keranjang Masih Kosong
                            </p>
                            <p class="text-[11px] text-gray-400 max-w-[200px]">
                                Klik menu di sisi kiri untuk mulai mencatat pesanan.
                            </p>
                        </div>
                    @endforelse
                </div>

                {{-- Footer & Total Tagihan --}}
                <div class="p-3.5 border-t border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50 rounded-b-2xl space-y-2.5">
                    <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                        <span>Total Kuantitas</span>
                        <span class="font-bold text-gray-900 dark:text-gray-200">{{ $this->totalItems }} Porsi</span>
                    </div>

                    <div class="flex items-baseline justify-between pt-1 border-t border-gray-200/60 dark:border-gray-800">
                        <span class="text-xs font-bold text-gray-700 dark:text-gray-300">Total Tagihan</span>
                        <span class="text-xl font-black text-amber-600 dark:text-amber-400 tracking-tight font-mono">
                            Rp {{ number_format($this->totalAmount, 0, ',', '.') }}
                        </span>
                    </div>

                    {{-- Tombol Lanjut ke Pembayaran --}}
                    <button
                        type="button"
                        wire:click="openPaymentModal"
                        @if (count($cart) === 0) disabled @endif
                        class="w-full py-3 px-4 rounded-xl font-bold text-xs sm:text-sm text-white transition flex items-center justify-center gap-2 shadow-xs
                            {{ count($cart) > 0 
                                ? 'bg-amber-600 hover:bg-amber-700 active:scale-[0.99] cursor-pointer' 
                                : 'bg-gray-300 dark:bg-gray-800 text-gray-400 cursor-not-allowed' }}"
                    >
                        <svg class="pos-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 1.25rem; height: 1.25rem;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <span>Proses Pembayaran</span>
                        <kbd class="px-1.5 py-0.5 text-[10px] font-mono font-bold bg-white/20 text-white rounded">F9</kbd>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= MODAL PEMBAYARAN KASIR ================= --}}
    @if ($showPaymentModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-900 rounded-2xl max-w-md w-full shadow-2xl border border-gray-200 dark:border-gray-800 overflow-hidden animate-in fade-in zoom-in-95 duration-150">
                {{-- Header Modal --}}
                <div class="p-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-black text-gray-900 dark:text-gray-100">
                            Pembayaran Kasir
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Total tagihan: <span class="font-bold text-amber-600 dark:text-amber-400">Rp {{ number_format($this->totalAmount, 0, ',', '.') }}</span>
                        </p>
                    </div>
                    <button
                        type="button"
                        wire:click="closePaymentModal"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition p-1.5 rounded-lg cursor-pointer"
                    >
                        <svg class="pos-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 1.25rem; height: 1.25rem;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Body Modal --}}
                <div class="p-4 space-y-4">
                    {{-- Pilihan Metode Bayar --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                            Metode Pembayaran
                        </label>
                        <div class="grid grid-cols-3 gap-2">
                            <button
                                type="button"
                                wire:click="setPaymentMethod('cash')"
                                class="py-2 px-3 rounded-xl border text-xs font-bold transition flex flex-col items-center gap-1 cursor-pointer
                                    {{ $paymentMethod === 'cash' 
                                        ? 'bg-amber-600 text-white border-amber-600 shadow-xs' 
                                        : 'bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-700 hover:bg-gray-100' }}"
                            >
                                <span>💵 Tunai</span>
                                <span class="text-[10px] font-normal opacity-90">Cash</span>
                            </button>

                            <button
                                type="button"
                                wire:click="setPaymentMethod('qris')"
                                class="py-2 px-3 rounded-xl border text-xs font-bold transition flex flex-col items-center gap-1 cursor-pointer
                                    {{ $paymentMethod === 'qris' 
                                        ? 'bg-amber-600 text-white border-amber-600 shadow-xs' 
                                        : 'bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-700 hover:bg-gray-100' }}"
                            >
                                <span>📱 QRIS</span>
                                <span class="text-[10px] font-normal opacity-90">Non-Tunai</span>
                            </button>

                            <button
                                type="button"
                                wire:click="setPaymentMethod('transfer')"
                                class="py-2 px-3 rounded-xl border text-xs font-bold transition flex flex-col items-center gap-1 cursor-pointer
                                    {{ $paymentMethod === 'transfer' 
                                        ? 'bg-amber-600 text-white border-amber-600 shadow-xs' 
                                        : 'bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-700 hover:bg-gray-100' }}"
                            >
                                <span>🏦 Transfer</span>
                                <span class="text-[10px] font-normal opacity-90">Bank</span>
                            </button>
                        </div>
                    </div>

                    {{-- Form Input Tunai Diterima (Hanya Tampil Jika Metode Cash) --}}
                    @if ($paymentMethod === 'cash')
                        <div class="space-y-2">
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">
                                Uang Tunai Diterima (Rp)
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-xs font-bold text-gray-400">
                                    Rp
                                </span>
                                <input
                                    type="number"
                                    wire:model.live="paidAmount"
                                    placeholder="0"
                                    class="w-full pl-10 pr-4 py-2.5 text-base font-bold bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-amber-500 dark:text-gray-100"
                                    autofocus
                                />
                            </div>

                            {{-- Tombol Shortcut Pecahan Cepat --}}
                            <div class="flex flex-wrap gap-1.5 pt-1">
                                <button
                                    type="button"
                                    wire:click="setExactCash"
                                    class="px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-800 hover:bg-amber-200 cursor-pointer"
                                >
                                    Uang Pas (Rp {{ number_format($this->totalAmount, 0, ',', '.') }})
                                </button>
                                <button
                                    type="button"
                                    wire:click="setQuickCash(10000)"
                                    class="px-2 py-1 rounded-lg text-xs font-medium bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 cursor-pointer"
                                >
                                    10.000
                                </button>
                                <button
                                    type="button"
                                    wire:click="setQuickCash(20000)"
                                    class="px-2 py-1 rounded-lg text-xs font-medium bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 cursor-pointer"
                                >
                                    20.000
                                </button>
                                <button
                                    type="button"
                                    wire:click="setQuickCash(50000)"
                                    class="px-2 py-1 rounded-lg text-xs font-medium bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 cursor-pointer"
                                >
                                    50.000
                                </button>
                                <button
                                    type="button"
                                    wire:click="setQuickCash(100000)"
                                    class="px-2 py-1 rounded-lg text-xs font-medium bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 cursor-pointer"
                                >
                                    100.000
                                </button>
                            </div>

                            {{-- Kalkulasi Kembalian --}}
                            <div class="p-3 bg-gray-50 dark:bg-gray-800/80 rounded-xl border border-gray-200 dark:border-gray-700 flex items-center justify-between text-xs">
                                <span class="font-medium text-gray-600 dark:text-gray-300">Kembalian:</span>
                                <span class="text-base font-black {{ $this->changeAmount >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500' }} font-mono">
                                    Rp {{ number_format($this->changeAmount, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    @else
                        {{-- Info QRIS / Transfer --}}
                        <div class="p-3.5 bg-blue-50 dark:bg-blue-950/40 rounded-xl border border-blue-200 dark:border-blue-800 text-xs text-blue-800 dark:text-blue-300 space-y-1">
                            <p class="font-bold">Pembayaran Otomatis Uang Pas</p>
                            <p class="text-[11px] opacity-90">
                                Transaksi akan langsung dicatat lunas sejumlah <strong>Rp {{ number_format($this->totalAmount, 0, ',', '.') }}</strong> tanpa kembalian.
                            </p>
                        </div>
                    @endif
                </div>

                {{-- Footer Modal --}}
                <div class="p-4 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-100 dark:border-gray-800 flex items-center justify-end gap-2">
                    <button
                        type="button"
                        wire:click="closePaymentModal"
                        class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-700 text-xs font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        wire:click="checkout"
                        class="px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold shadow-sm transition flex items-center gap-1.5 cursor-pointer"
                    >
                        <svg class="pos-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 1rem; height: 1rem;">
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
        <div class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-900 rounded-2xl max-w-xs sm:max-w-sm w-full shadow-2xl border border-gray-200 dark:border-gray-800 p-4 space-y-3 text-center animate-in fade-in zoom-in-95 duration-150">
                <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-950 text-emerald-600 rounded-full mx-auto flex items-center justify-center">
                    <svg class="pos-icon text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 1.25rem; height: 1.25rem;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>

                <div>
                    <h3 class="text-base font-black text-gray-900 dark:text-gray-100">
                        Transaksi Sukses!
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Nota: <span class="font-bold text-gray-800 dark:text-gray-200">{{ $lastOrder->order_number }}</span>
                    </p>
                </div>

                {{-- Preview Struk Visual Thermal --}}
                <div class="p-2.5 bg-gray-50 dark:bg-gray-800/60 rounded-xl border border-dashed border-gray-300 dark:border-gray-700 text-left overflow-hidden">
                    @include('filament.pages.partials.receipt', ['order' => $lastOrder])
                </div>

                <div class="flex items-center gap-2 pt-1">
                    <button
                        type="button"
                        wire:click="printLastReceipt"
                        class="flex-1 py-2 px-3 rounded-xl border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-800 dark:text-gray-200 font-bold text-xs transition flex items-center justify-center gap-1 cursor-pointer"
                    >
                        🖨️ Cetak Ulang
                    </button>
                    <button
                        type="button"
                        wire:click="closeSuccessModal"
                        class="flex-1 py-2 px-3 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs transition cursor-pointer"
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
