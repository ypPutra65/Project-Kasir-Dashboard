<div class="space-y-4">
    <div class="flex items-center justify-between p-3 bg-amber-50 dark:bg-amber-950/40 rounded-xl border border-amber-200 dark:border-amber-800">
        <div>
            <div class="text-xs text-amber-800 dark:text-amber-300 font-medium">Cetak Struk Transaksi</div>
            <div class="text-sm font-bold text-amber-950 dark:text-amber-100">{{ $order->order_number }}</div>
        </div>
        <button
            type="button"
            onclick="printReceiptFromModal('thermal-receipt-reprint-area')"
            class="inline-flex items-center gap-1.5 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold rounded-lg shadow-sm transition cursor-pointer"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 1rem; height: 1rem; flex-shrink: 0;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            Cetak Struk Sekarang
        </button>
    </div>

    {{-- Area Preview Struk Thermal --}}
    <div id="thermal-receipt-reprint-area" class="p-3 bg-white rounded-xl border border-dashed border-gray-300 max-w-[320px] mx-auto shadow-xs">
        @include('filament.pages.partials.receipt', ['order' => $order])
    </div>

    <script>
        function printReceiptFromModal(containerId) {
            const printEl = document.getElementById(containerId);
            if (!printEl) return;

            let printIframe = document.getElementById('modal-thermal-print-iframe');
            if (!printIframe) {
                printIframe = document.createElement('iframe');
                printIframe.id = 'modal-thermal-print-iframe';
                printIframe.style.position = 'fixed';
                printIframe.style.right = '0';
                printIframe.style.bottom = '0';
                printIframe.style.width = '0';
                printIframe.style.height = '0';
                printIframe.style.border = '0';
                document.body.appendChild(printIframe);
            }

            const doc = printIframe.contentDocument || printIframe.contentWindow.document;
            doc.open();
            doc.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="utf-8">
                    <title>Struk Pembayaran - ${"{{ $order->order_number }}"}</title>
                    <style>
                        @page { size: 58mm auto; margin: 0; }
                        html, body {
                            margin: 0;
                            padding: 2mm 1mm;
                            width: 58mm;
                            background: #ffffff;
                            color: #000000;
                            font-family: 'Courier New', Courier, monospace;
                            box-sizing: border-box;
                        }
                        * {
                            box-sizing: border-box;
                        }
                    </style>
                </head>
                <body>
                    ${printEl.innerHTML}
                </body>
                </html>
            `);
            doc.close();

            setTimeout(() => {
                printIframe.contentWindow.focus();
                printIframe.contentWindow.print();
            }, 250);
        }
    </script>
</div>
