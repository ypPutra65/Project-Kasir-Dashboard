@if ($order)
<div id="thermal-receipt" class="thermal-receipt-container font-mono text-[11px] leading-tight text-black p-2 max-w-[300px] mx-auto bg-white">
    {{-- Header Struk --}}
    <div class="text-center pb-2 border-b border-dashed border-black">
        <h2 class="text-sm font-bold uppercase tracking-wider">BUDHE LAMONGAN</h2>
        <p class="text-[10px]">Spesialis Bebek & Lele Sambal Lamongan</p>
        <p class="text-[10px]">Jl. Raya Lamongan No. 65</p>
    </div>

    {{-- Metadata Nota --}}
    <div class="py-1.5 border-b border-dashed border-black text-[10px] space-y-0.5">
        <div class="flex justify-between">
            <span>No: {{ $order->order_number }}</span>
            <span>{{ $order->ordered_at ? \Carbon\Carbon::parse($order->ordered_at)->format('d/m/y H:i') : now()->format('d/m/y H:i') }}</span>
        </div>
        <div class="flex justify-between">
            <span>Kasir: Owner</span>
            <span class="uppercase">Bayar: {{ $order->payment_method }}</span>
        </div>
    </div>

    {{-- Daftar Rincian Menu --}}
    <div class="py-1.5 border-b border-dashed border-black space-y-1">
        @foreach ($order->orderItems as $item)
            <div>
                <div class="font-bold truncate">{{ $item->product?->name ?? 'Menu' }}</div>
                <div class="flex justify-between text-[10px]">
                    <span>{{ $item->quantity }} x {{ number_format((float) $item->unit_price, 0, ',', '.') }}</span>
                    <span>Rp {{ number_format((float) $item->subtotal, 0, ',', '.') }}</span>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Kalkulasi Total Tagihan --}}
    <div class="py-1.5 border-b border-dashed border-black space-y-0.5 text-[10px]">
        <div class="flex justify-between font-bold text-xs pt-0.5">
            <span>TOTAL</span>
            <span>Rp {{ number_format((float) $order->total_amount, 0, ',', '.') }}</span>
        </div>
        <div class="flex justify-between">
            <span>Bayar ({{ strtoupper($order->payment_method) }})</span>
            <span>Rp {{ number_format((float) $order->paid_amount, 0, ',', '.') }}</span>
        </div>
        @if ($order->payment_method === 'cash')
            <div class="flex justify-between font-semibold">
                <span>Kembalian</span>
                <span>Rp {{ number_format((float) $order->change_amount, 0, ',', '.') }}</span>
            </div>
        @endif
    </div>

    {{-- Penutup --}}
    <div class="text-center pt-2 text-[10px] space-y-0.5">
        <p class="font-bold">Matur Nuwun sampun rawuh</p>
        <p class="text-[9px]">Semoga Berkah & Selamat Menikmati</p>
    </div>
</div>
@endif
