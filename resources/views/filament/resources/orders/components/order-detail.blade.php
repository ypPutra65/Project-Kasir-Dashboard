@php
    $totalCost = $order->orderItems->sum(function ($item) {
        return (float) $item->cost_price * (int) $item->quantity;
    });
    $grossProfit = (float) $order->total_amount - $totalCost;
    $profitPercentage = (float) $order->total_amount > 0 ? round(($grossProfit / (float) $order->total_amount) * 100, 1) : 0;
@endphp

<div class="space-y-6 text-gray-800 dark:text-gray-200">
    {{-- Header Status & Metadata --}}
    <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-800/80 border border-gray-200 dark:border-gray-700 space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nomor Nota</span>
                <h3 class="text-xl font-black text-gray-900 dark:text-gray-100">{{ $order->order_number }}</h3>
            </div>
            <div class="flex items-center gap-2">
                {{-- Status Badge --}}
                @if ($order->status === 'paid')
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                        <svg class="w-3.5 h-3.5 mr-1" fill="currentColor" viewBox="0 0 20 20" style="width: 0.875rem; height: 0.875rem; flex-shrink: 0; display: inline-block;">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        Lunas (Paid)
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300">
                        <svg class="w-3.5 h-3.5 mr-1" fill="currentColor" viewBox="0 0 20 20" style="width: 0.875rem; height: 0.875rem; flex-shrink: 0; display: inline-block;">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        Dibatalkan (Void)
                    </span>
                @endif

                {{-- Payment Badge --}}
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider {{ match($order->payment_method) {
                    'cash' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800',
                    'qris' => 'bg-blue-50 text-blue-700 dark:bg-blue-950/70 dark:text-blue-300 border border-blue-200 dark:border-blue-800',
                    'transfer' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/70 dark:text-amber-300 border border-amber-200 dark:border-amber-800',
                    default => 'bg-gray-100 text-gray-700'
                } }}">
                    {{ $order->payment_method }}
                </span>
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 pt-2 text-xs border-t border-gray-200 dark:border-gray-700">
            <div>
                <span class="text-gray-500 dark:text-gray-400">Waktu Order:</span>
                <p class="font-bold text-gray-900 dark:text-gray-100">
                    {{ $order->ordered_at ? \Carbon\Carbon::parse($order->ordered_at)->translatedFormat('d F Y, H:i') : '-' }}
                </p>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400">Kasir:</span>
                <p class="font-bold text-gray-900 dark:text-gray-100">Owner (Budhe Lamongan)</p>
            </div>
            @if ($order->notes)
                <div class="col-span-2 sm:col-span-1">
                    <span class="text-gray-500 dark:text-gray-400">Catatan:</span>
                    <p class="font-medium text-gray-800 dark:text-gray-200 truncate">{{ $order->notes }}</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Ringkasan Keuangan (Snapshot Transaksi) --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="p-3 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
            <span class="text-[11px] text-gray-500 dark:text-gray-400 font-medium">Total Omset</span>
            <p class="text-base font-black text-primary-600 dark:text-primary-400">
                Rp {{ number_format((float) $order->total_amount, 0, ',', '.') }}
            </p>
        </div>
        <div class="p-3 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
            <span class="text-[11px] text-gray-500 dark:text-gray-400 font-medium">Uang Diterima</span>
            <p class="text-base font-bold text-gray-900 dark:text-gray-100">
                Rp {{ number_format((float) $order->paid_amount, 0, ',', '.') }}
            </p>
        </div>
        <div class="p-3 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
            <span class="text-[11px] text-gray-500 dark:text-gray-400 font-medium">Kembalian</span>
            <p class="text-base font-bold {{ (float) $order->change_amount > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-500' }}">
                Rp {{ number_format((float) $order->change_amount, 0, ',', '.') }}
            </p>
        </div>
        <div class="p-3 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
            <span class="text-[11px] text-gray-500 dark:text-gray-400 font-medium">Estimasi Laba Kotor</span>
            <p class="text-base font-black text-emerald-600 dark:text-emerald-400">
                Rp {{ number_format($grossProfit, 0, ',', '.') }}
                <span class="text-[10px] font-normal text-gray-500">({{ $profitPercentage }}%)</span>
            </p>
        </div>
    </div>

    {{-- Tabel Rincian Menu & Snapshot Harga --}}
    <div class="space-y-2">
        <h4 class="text-sm font-bold text-gray-900 dark:text-gray-100 flex items-center justify-between">
            <span>Rincian Item Menu</span>
            <span class="text-xs font-normal text-gray-500">({{ $order->orderItems->count() }} jenis menu, {{ $order->orderItems->sum('quantity') }} porsi)</span>
        </h4>

        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-xs">
            <table class="w-full text-left text-xs">
                <thead class="bg-gray-50 dark:bg-gray-800/80 border-b border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 uppercase font-semibold">
                    <tr>
                        <th class="px-3.5 py-2.5">Menu</th>
                        <th class="px-3.5 py-2.5 text-center">Qty</th>
                        <th class="px-3.5 py-2.5 text-right">Snapshot HPP</th>
                        <th class="px-3.5 py-2.5 text-right">Harga Jual</th>
                        <th class="px-3.5 py-2.5 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                    @foreach ($order->orderItems as $item)
                        @php
                            $itemCost = (float) $item->cost_price * (int) $item->quantity;
                            $itemProfit = (float) $item->subtotal - $itemCost;
                        @endphp
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-750 transition">
                            <td class="px-3.5 py-3">
                                <div class="font-bold text-gray-900 dark:text-gray-100">
                                    {{ $item->product?->name ?? 'Menu Terhapus' }}
                                </div>
                                @if ($item->product?->category)
                                    <div class="text-[10px] text-gray-500 dark:text-gray-400">
                                        {{ $item->product->category->name }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-3.5 py-3 text-center font-bold text-gray-800 dark:text-gray-200">
                                {{ $item->quantity }}
                            </td>
                            <td class="px-3.5 py-3 text-right text-gray-600 dark:text-gray-400 font-mono">
                                Rp {{ number_format((float) $item->cost_price, 0, ',', '.') }}
                            </td>
                            <td class="px-3.5 py-3 text-right font-medium text-gray-900 dark:text-gray-100 font-mono">
                                Rp {{ number_format((float) $item->unit_price, 0, ',', '.') }}
                            </td>
                            <td class="px-3.5 py-3 text-right font-bold text-primary-600 dark:text-primary-400 font-mono">
                                Rp {{ number_format((float) $item->subtotal, 0, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50 dark:bg-gray-800/80 border-t border-gray-200 dark:border-gray-700 font-bold">
                    <tr>
                        <td class="px-3.5 py-2.5 text-gray-900 dark:text-gray-100" colspan="2">TOTAL KESELURUHAN</td>
                        <td class="px-3.5 py-2.5 text-right font-mono text-gray-600 dark:text-gray-400">
                            Rp {{ number_format($totalCost, 0, ',', '.') }}
                        </td>
                        <td class="px-3.5 py-2.5 text-right"></td>
                        <td class="px-3.5 py-2.5 text-right font-mono text-sm text-primary-600 dark:text-primary-400">
                            Rp {{ number_format((float) $order->total_amount, 0, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
