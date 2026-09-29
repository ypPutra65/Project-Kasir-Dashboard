@if ($order)
<div class="thermal-receipt-box">
    <style>
        .thermal-receipt-box {
            font-family: 'Courier New', Courier, monospace;
            font-size: 11px;
            line-height: 1.3;
            color: #000000 !important;
            background: #ffffff !important;
            width: 100%;
            max-width: 280px;
            margin: 0 auto;
            padding: 8px 6px;
            box-sizing: border-box;
        }
        .tr-header {
            text-align: center;
            padding-bottom: 6px;
            border-bottom: 1px dashed #000000;
            margin-bottom: 6px;
        }
        .tr-brand {
            font-size: 14px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin: 0 0 2px 0;
            color: #000000 !important;
        }
        .tr-sub {
            font-size: 10px;
            margin: 0;
            color: #333333 !important;
        }
        .tr-meta {
            padding-bottom: 6px;
            border-bottom: 1px dashed #000000;
            margin-bottom: 6px;
            font-size: 10px;
        }
        .tr-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2px;
        }
        .tr-items {
            padding-bottom: 6px;
            border-bottom: 1px dashed #000000;
            margin-bottom: 6px;
        }
        .tr-item {
            margin-bottom: 4px;
        }
        .tr-item-name {
            font-weight: 700;
            font-size: 11px;
            color: #000000 !important;
        }
        .tr-totals {
            padding-bottom: 6px;
            border-bottom: 1px dashed #000000;
            margin-bottom: 6px;
            font-size: 11px;
        }
        .tr-total-bold {
            font-size: 12px;
            font-weight: 800;
            color: #000000 !important;
            padding-top: 2px;
        }
        .tr-footer {
            text-align: center;
            padding-top: 4px;
            font-size: 10px;
            color: #000000 !important;
        }
    </style>

    {{-- Header Struk --}}
    <div class="tr-header">
        <div class="tr-brand">BUDHE LAMONGAN</div>
        <p class="tr-sub">Spesialis Bebek & Lele Sambal Lamongan</p>
        <p class="tr-sub">Jl. Raya Lamongan No. 65</p>
    </div>

    {{-- Metadata Nota --}}
    <div class="tr-meta">
        <div class="tr-row">
            <span>No: {{ $order->order_number }}</span>
            <span>{{ $order->ordered_at ? \Carbon\Carbon::parse($order->ordered_at)->format('d/m/y H:i') : now()->format('d/m/y H:i') }}</span>
        </div>
        <div class="tr-row">
            <span>Kasir: Kasir Utama</span>
            <span style="text-transform: uppercase;">Bayar: {{ $order->payment_method }}</span>
        </div>
    </div>

    {{-- Daftar Rincian Menu --}}
    <div class="tr-items">
        @foreach ($order->orderItems as $item)
            <div class="tr-item">
                <div class="tr-item-name">{{ $item->product?->name ?? 'Menu' }}</div>
                <div class="tr-row" style="font-size: 10px;">
                    <span>{{ $item->quantity }} x {{ number_format((float) $item->unit_price, 0, ',', '.') }}</span>
                    <span>Rp {{ number_format((float) $item->subtotal, 0, ',', '.') }}</span>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Kalkulasi Total Tagihan --}}
    <div class="tr-totals">
        <div class="tr-row tr-total-bold">
            <span>TOTAL</span>
            <span>Rp {{ number_format((float) $order->total_amount, 0, ',', '.') }}</span>
        </div>
        <div class="tr-row" style="font-size: 10px;">
            <span>Bayar ({{ strtoupper($order->payment_method) }})</span>
            <span>Rp {{ number_format((float) $order->paid_amount, 0, ',', '.') }}</span>
        </div>
        @if ($order->payment_method === 'cash')
            <div class="tr-row" style="font-size: 10px; font-weight: 700;">
                <span>Kembalian</span>
                <span>Rp {{ number_format((float) $order->change_amount, 0, ',', '.') }}</span>
            </div>
        @endif
    </div>

    {{-- Penutup --}}
    <div class="tr-footer">
        <p style="font-weight: 700; margin: 0 0 2px 0;">Matur Nuwun sampun rawuh</p>
        <p style="font-size: 9px; margin: 0; color: #555555;">Semoga Berkah & Selamat Menikmati</p>
    </div>
</div>
@endif
