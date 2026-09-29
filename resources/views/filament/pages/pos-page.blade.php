<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12 -mt-4">
        {{-- ================= SISI KIRI: KATALOG MENU & PENCARIAN (65% / 8 COLS) ================= --}}
        <div class="lg:col-span-7 xl:col-span-8 space-y-4">
            {{-- Toolbar: Kategori & Search --}}
            <div class="bg-white dark:bg-gray-900 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-800 space-y-3">
                {{-- Search Bar --}}
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Ketik nama menu makanan atau minuman (Cari cepat)..."
                        class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:text-gray-100 placeholder-gray-400"
                    />
                </div>

                {{-- Category Filter Tabs --}}
                <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-thin">
                    <button
                        type="button"
                        wire:click="selectCategory(null)"
                        class="px-4 py-2 rounded-lg text-xs font-semibold whitespace-nowrap transition-all duration-150 {{ is_null($selectedCategoryId) ? 'bg-primary-600 text-white shadow-sm ring-2 ring-primary-500/20' : 'bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300' }}"
                    >
                        Semua Kategori
                    </button>
                    @foreach ($this->categories as $category)
                        <button
                            type="button"
                            wire:click="selectCategory({{ $category->id }})"
                            class="px-4 py-2 rounded-lg text-xs font-semibold whitespace-nowrap transition-all duration-150 {{ $selectedCategoryId === $category->id ? 'bg-primary-600 text-white shadow-sm ring-2 ring-primary-500/20' : 'bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300' }}"
                        >
                            {{ $category->name }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Grid Menu Produk --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3.5">
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
                        class="group relative bg-white dark:bg-gray-900 rounded-xl p-3.5 border transition-all duration-200 flex flex-col justify-between select-none
                            {{ $isOutOfStock 
                                ? 'opacity-60 bg-gray-50 dark:bg-gray-950 border-gray-200 dark:border-gray-800 cursor-not-allowed' 
                                : 'hover:border-primary-500 hover:shadow-md cursor-pointer border-gray-200 dark:border-gray-800 active:scale-[0.98]' }}
                            {{ $inCart ? 'ring-2 ring-primary-500 border-primary-500 bg-primary-50/20 dark:bg-primary-950/20' : '' }}"
                    >
                        {{-- Foto atau Placeholder --}}
                        <div class="w-full h-28 rounded-lg bg-gray-100 dark:bg-gray-800 overflow-hidden mb-2.5 relative flex items-center justify-center">
                            @if ($product->image_path)
                                <img
                                    src="{{ asset('storage/' . $product->image_path) }}"
                                    alt="{{ $product->name }}"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200"
                                />
                            @else
                                <div class="text-gray-400 dark:text-gray-600 flex flex-col items-center">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                    </svg>
                                </div>
                            @endif

                            {{-- Badge Kuantitas di Keranjang --}}
                            @if ($inCart)
                                <div class="absolute top-1.5 right-1.5 bg-primary-600 text-white text-xs font-bold w-6 h-6 rounded-full flex items-center justify-center shadow">
                                    {{ $cartQty }}
                                </div>
                            @endif

                            {{-- Badge Habis --}}
                            @if ($isOutOfStock)
                                <div class="absolute inset-0 bg-black/50 backdrop-blur-[2px] flex items-center justify-center">
                                    <span class="bg-red-600 text-white text-xs font-black px-2.5 py-1 rounded-md tracking-wider">
                                        HABIS
                                    </span>
                                </div>
                            @endif
                        </div>

                        {{-- Info Produk --}}
                        <div class="space-y-1">
                            <div class="flex items-center justify-between gap-1">
                                <span class="text-[11px] font-medium text-gray-500 dark:text-gray-400 truncate">
                                    {{ $product->category?->name }}
                                </span>
                                <span class="text-[11px] font-semibold {{ $isOutOfStock ? 'text-red-500' : ($product->isLowStock() ? 'text-amber-500' : 'text-emerald-600') }}">
                                    Stok: {{ $product->stock }}
                                </span>
                            </div>

                            <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100 line-clamp-1 leading-snug">
                                {{ $product->name }}
                            </h3>

                            <div class="pt-1 flex items-center justify-between">
                                <span class="text-sm font-black text-primary-600 dark:text-primary-400">
                                    Rp {{ number_format((float) $product->selling_price, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full py-12 text-center bg-white dark:bg-gray-900 rounded-xl border border-dashed border-gray-300 dark:border-gray-800">
                        <svg class="w-12 h-12 mx-auto text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Tidak ada menu yang sesuai dengan filter atau pencarian.
                        </p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- ================= SISI KANAN: KERANJANG PESANAN (35% / 4 COLS) ================= --}}
        <div class="lg:col-span-5 xl:col-span-4">
            <div class="sticky top-20 bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 flex flex-col h-[calc(100vh-6.5rem)]">
                {{-- Header Keranjang --}}
                <div class="p-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                        <h2 class="text-base font-bold text-gray-900 dark:text-gray-100">
                            Ringkasan Pesanan
                        </h2>
                        @if ($this->totalItems > 0)
                            <span class="bg-primary-100 text-primary-700 dark:bg-primary-950 dark:text-primary-300 text-xs font-bold px-2 py-0.5 rounded-full">
                                {{ $this->totalItems }} item
                            </span>
                        @endif
                    </div>

                    @if (count($cart) > 0)
                        <button
                            type="button"
                            wire:click="clearCart"
                            wire:confirm="Yakin ingin mengosongkan keranjang pesanan?"
                            class="text-xs text-red-500 hover:text-red-700 font-semibold transition cursor-pointer"
                        >
                            Kosongkan
                        </button>
                    @endif
                </div>

                {{-- Daftar Item Keranjang (Scrollable) --}}
                <div class="flex-1 overflow-y-auto p-4 space-y-3 divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($cart as $productId => $item)
                        <div class="pt-3 first:pt-0 flex items-center justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <h4 class="text-sm font-bold text-gray-900 dark:text-gray-100 truncate">
                                    {{ $item['name'] }}
                                </h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    @ Rp {{ number_format((float) $item['price'], 0, ',', '.') }}
                                </p>
                                <p class="text-xs font-bold text-primary-600 dark:text-primary-400 mt-0.5">
                                    Subtotal: Rp {{ number_format((float) $item['subtotal'], 0, ',', '.') }}
                                </p>
                            </div>

                            {{-- Kontrol Kuantitas & Hapus --}}
                            <div class="flex items-center gap-1.5">
                                <button
                                    type="button"
                                    wire:click="decrementQty({{ $productId }})"
                                    class="w-7 h-7 rounded-lg bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 flex items-center justify-center font-bold text-sm transition cursor-pointer"
                                >
                                    -
                                </button>
                                <span class="w-7 text-center font-bold text-sm text-gray-900 dark:text-gray-100">
                                    {{ $item['qty'] }}
                                </span>
                                <button
                                    type="button"
                                    wire:click="incrementQty({{ $productId }})"
                                    class="w-7 h-7 rounded-lg bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 flex items-center justify-center font-bold text-sm transition cursor-pointer"
                                >
                                    +
                                </button>
                                <button
                                    type="button"
                                    wire:click="removeFromCart({{ $productId }})"
                                    class="ml-1 text-gray-400 hover:text-red-500 p-1 rounded transition cursor-pointer"
                                    title="Hapus Menu"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="h-full flex flex-col items-center justify-center py-12 text-center text-gray-400 space-y-2">
                            <div class="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-300">
                                Keranjang Masih Kosong
                            </p>
                            <p class="text-xs text-gray-400 max-w-[200px]">
                                Klik item menu makanan / minuman di sisi kiri untuk mulai mencatat pesanan.
                            </p>
                        </div>
                    @endforelse
                </div>

                {{-- Footer & Total Tagihan --}}
                <div class="p-4 border-t border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50 rounded-b-xl space-y-3">
                    <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                        <span>Total Kuantitas</span>
                        <span class="font-bold text-gray-900 dark:text-gray-200">{{ $this->totalItems }} Porsi</span>
                    </div>

                    <div class="flex items-baseline justify-between pt-1 border-t border-gray-200/60 dark:border-gray-800">
                        <span class="text-sm font-bold text-gray-700 dark:text-gray-300">Total Tagihan</span>
                        <span class="text-2xl font-black text-primary-600 dark:text-primary-400 tracking-tight">
                            Rp {{ number_format($this->totalAmount, 0, ',', '.') }}
                        </span>
                    </div>

                    {{-- Tombol Lanjut ke Pembayaran --}}
                    <button
                        type="button"
                        wire:click="openPaymentModal"
                        @if (count($cart) === 0) disabled @endif
                        class="w-full py-3 px-4 rounded-xl font-bold text-sm text-white transition flex items-center justify-center gap-2 shadow-sm
                            {{ count($cart) > 0 
                                ? 'bg-primary-600 hover:bg-primary-700 active:scale-[0.99] cursor-pointer' 
                                : 'bg-gray-300 dark:bg-gray-800 text-gray-400 cursor-not-allowed' }}"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        Proses Pembayaran (F9)
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= MODAL PEMBAYARAN KASIR ================= --}}
    @if ($showPaymentModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-900 rounded-2xl max-w-lg w-full shadow-2xl border border-gray-200 dark:border-gray-800 overflow-hidden animate-in fade-in zoom-in-95 duration-150">
                {{-- Header Modal --}}
                <div class="p-5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-black text-gray-900 dark:text-gray-100">
                            Pembayaran Kasir
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Total Tagihan: <span class="font-bold text-primary-600 dark:text-primary-400">Rp {{ number_format($this->totalAmount, 0, ',', '.') }}</span> ({{ $this->totalItems }} porsi)
                        </p>
                    </div>
                    <button
                        type="button"
                        wire:click="closePaymentModal"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-1.5 rounded-lg"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="p-5 space-y-4">
                    {{-- Pilihan Metode Pembayaran --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">
                            Pilih Metode Pembayaran
                        </label>
                        <div class="grid grid-cols-3 gap-2">
                            <button
                                type="button"
                                wire:click="setPaymentMethod('cash')"
                                class="py-2.5 px-3 rounded-xl border text-xs font-bold transition flex flex-col items-center gap-1 cursor-pointer
                                    {{ $paymentMethod === 'cash' 
                                        ? 'border-primary-600 bg-primary-50 dark:bg-primary-950 text-primary-700 dark:text-primary-300 ring-2 ring-primary-500/20' 
                                        : 'border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50' }}"
                            >
                                💵 Tunai (Cash)
                            </button>
                            <button
                                type="button"
                                wire:click="setPaymentMethod('qris')"
                                class="py-2.5 px-3 rounded-xl border text-xs font-bold transition flex flex-col items-center gap-1 cursor-pointer
                                    {{ $paymentMethod === 'qris' 
                                        ? 'border-primary-600 bg-primary-50 dark:bg-primary-950 text-primary-700 dark:text-primary-300 ring-2 ring-primary-500/20' 
                                        : 'border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50' }}"
                            >
                                📱 QRIS
                            </button>
                            <button
                                type="button"
                                wire:click="setPaymentMethod('transfer')"
                                class="py-2.5 px-3 rounded-xl border text-xs font-bold transition flex flex-col items-center gap-1 cursor-pointer
                                    {{ $paymentMethod === 'transfer' 
                                        ? 'border-primary-600 bg-primary-50 dark:bg-primary-950 text-primary-700 dark:text-primary-300 ring-2 ring-primary-500/20' 
                                        : 'border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50' }}"
                            >
                                🏦 Transfer Bank
                            </button>
                        </div>
                    </div>

                    {{-- Form Pembayaran Tunai --}}
                    @if ($paymentMethod === 'cash')
                        <div class="space-y-3 pt-2">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                    Uang Tunai Diterima (Rp)
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400 font-bold text-sm">
                                        Rp
                                    </div>
                                    <input
                                        type="number"
                                        wire:model.live="paidAmount"
                                        placeholder="0"
                                        class="w-full pl-12 pr-4 py-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-lg font-black text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                    />
                                </div>
                                @error('paidAmount')
                                    <p class="text-xs text-red-500 mt-1 font-semibold">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Tombol Shortcut Pecahan Tunai Cepat --}}
                            <div>
                                <label class="block text-[11px] font-semibold text-gray-500 dark:text-gray-400 mb-1.5">
                                    Pecahan Cepat / Uang Pas:
                                </label>
                                <div class="grid grid-cols-5 gap-1.5">
                                    <button
                                        type="button"
                                        wire:click="setPaidAmount(10000)"
                                        class="py-1.5 px-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 rounded-lg text-xs font-bold text-gray-800 dark:text-gray-200 transition"
                                    >
                                        10rb
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="setPaidAmount(20000)"
                                        class="py-1.5 px-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 rounded-lg text-xs font-bold text-gray-800 dark:text-gray-200 transition"
                                    >
                                        20rb
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="setPaidAmount(50000)"
                                        class="py-1.5 px-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 rounded-lg text-xs font-bold text-gray-800 dark:text-gray-200 transition"
                                    >
                                        50rb
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="setPaidAmount(100000)"
                                        class="py-1.5 px-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 rounded-lg text-xs font-bold text-gray-800 dark:text-gray-200 transition"
                                    >
                                        100rb
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="setExactCash"
                                        class="py-1.5 px-2 bg-primary-100 hover:bg-primary-200 dark:bg-primary-950 dark:hover:bg-primary-900 rounded-lg text-xs font-black text-primary-700 dark:text-primary-300 transition"
                                    >
                                        Pas
                                    </button>
                                </div>
                            </div>

                            {{-- Tampilan Kembalian --}}
                            <div class="p-3.5 bg-gray-50 dark:bg-gray-800/80 rounded-xl border border-gray-200/80 dark:border-gray-700/80 flex items-center justify-between">
                                <span class="text-xs font-bold text-gray-600 dark:text-gray-300">
                                    Uang Kembalian:
                                </span>
                                <span class="text-lg font-black {{ $this->changeAmount > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-900 dark:text-gray-100' }}">
                                    Rp {{ number_format($this->changeAmount, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    @else
                        {{-- Non Tunai Alert --}}
                        <div class="p-4 bg-primary-50/50 dark:bg-primary-950/40 rounded-xl border border-primary-200/60 dark:border-primary-800/60 text-center space-y-1">
                            <p class="text-xs font-semibold text-gray-600 dark:text-gray-300">
                                Pembayaran non-tunai otomatis disesuaikan tepat:
                            </p>
                            <p class="text-xl font-black text-primary-600 dark:text-primary-400">
                                Rp {{ number_format($this->totalAmount, 0, ',', '.') }}
                            </p>
                        </div>
                    @endif
                </div>

                {{-- Footer Modal Actions --}}
                <div class="p-5 border-t border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/60 flex items-center justify-end gap-3">
                    <button
                        type="button"
                        wire:click="closePaymentModal"
                        class="px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-700 text-sm font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        wire:click="checkout"
                        class="px-5 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-700 text-white text-sm font-bold shadow-md hover:shadow-lg transition flex items-center gap-2 cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
            <div class="bg-white dark:bg-gray-900 rounded-2xl max-w-md w-full shadow-2xl border border-gray-200 dark:border-gray-800 p-6 space-y-5 text-center">
                <div class="w-16 h-16 bg-emerald-100 dark:bg-emerald-950 text-emerald-600 rounded-full mx-auto flex items-center justify-center">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>

                <div>
                    <h3 class="text-xl font-black text-gray-900 dark:text-gray-100">
                        Transaksi Berhasil!
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Nomor Nota: <span class="font-bold text-gray-800 dark:text-gray-200">{{ $lastOrder->order_number }}</span>
                    </p>
                </div>

                {{-- Detail Nota Ringkas --}}
                <div class="p-4 bg-gray-50 dark:bg-gray-800/60 rounded-xl text-left text-xs space-y-2">
                    <div class="flex justify-between text-gray-600 dark:text-gray-300">
                        <span>Metode Pembayaran:</span>
                        <span class="font-bold uppercase">{{ $lastOrder->payment_method }}</span>
                    </div>
                    <div class="flex justify-between text-gray-600 dark:text-gray-300">
                        <span>Total Tagihan:</span>
                        <span class="font-bold">Rp {{ number_format((float) $lastOrder->total_amount, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-gray-600 dark:text-gray-300">
                        <span>Nominal Bayar:</span>
                        <span class="font-bold">Rp {{ number_format((float) $lastOrder->paid_amount, 0, ',', '.') }}</span>
                    </div>
                    @if ($lastOrder->payment_method === 'cash')
                        <div class="flex justify-between text-emerald-600 font-bold border-t border-gray-200 dark:border-gray-700 pt-1.5">
                            <span>Kembalian:</span>
                            <span>Rp {{ number_format((float) $lastOrder->change_amount, 0, ',', '.') }}</span>
                        </div>
                    @endif
                </div>

                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        wire:click="closeSuccessModal"
                        class="w-full py-2.5 px-4 rounded-xl bg-primary-600 hover:bg-primary-700 text-white font-bold text-sm transition cursor-pointer"
                    >
                        Selesai / Transaksi Baru
                    </button>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
