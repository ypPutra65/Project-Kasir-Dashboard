<div class="space-y-4">
    {{-- CSS Khusus Printer Thermal 58mm / 80mm --}}
    <style>
        @media print {
            body * {
                visibility: hidden !important;
            }
            #thermal-receipt-reprint-area, #thermal-receipt-reprint-area * {
                visibility: visible !important;
            }
            #thermal-receipt-reprint-area {
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

    <div class="flex items-center justify-between p-3 bg-amber-50 dark:bg-amber-950/40 rounded-xl border border-amber-200 dark:border-amber-800">
        <div>
            <div class="text-xs text-amber-800 dark:text-amber-300 font-medium">Cetak Struk Transaksi</div>
            <div class="text-sm font-bold text-amber-950 dark:text-amber-100">{{ $order->order_number }}</div>
        </div>
        <button
            type="button"
            onclick="window.print()"
            class="inline-flex items-center gap-1.5 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold rounded-lg shadow-sm transition cursor-pointer"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            Cetak Struk Sekarang
        </button>
    </div>

    {{-- Area Preview Struk Thermal --}}
    <div id="thermal-receipt-reprint-area" class="p-3 bg-white dark:bg-gray-900 rounded-xl border border-dashed border-gray-300 dark:border-gray-700 max-w-[320px] mx-auto shadow-xs">
        @include('filament.pages.partials.receipt', ['order' => $order])
    </div>
</div>
