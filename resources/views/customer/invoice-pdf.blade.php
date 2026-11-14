<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1F1A17; margin: 24px; }
        .header { border-bottom: 2px solid #3B2A20; padding-bottom: 12px; margin-bottom: 16px; }
        .title { font-size: 18px; font-weight: bold; color: #3B2A20; }
        .badge { display: inline-block; padding: 3px 8px; background: #DCFCE7; color: #166534; font-weight: bold; font-size: 10px; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { padding: 8px 6px; border-bottom: 1px solid #E6DED2; text-align: left; }
        th { background: #F2ECE1; font-weight: bold; }
        .right { text-align: right; }
        .total-row td { font-weight: bold; font-size: 14px; border-top: 2px solid #3B2A20; }
        .footer { margin-top: 24px; font-size: 11px; color: #6F6259; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">{{ $snapshot['outlet_name'] ?? '774 HUB — Coffee & Roastery' }}</div>
        <div>{{ $snapshot['outlet_address'] ?? 'Jl. Bung Tarjo No. 12, Yogyakarta' }} | Telp: {{ $snapshot['outlet_phone'] ?? '0274-555774' }}</div>
        <div style="margin-top: 8px;">
            <strong>No. Invoice:</strong> {{ $invoice->invoice_number }} &nbsp;|&nbsp;
            <strong>No. Transaksi:</strong> {{ $order->order_number }} &nbsp;|&nbsp;
            <span class="badge">DIBAYAR ({{ $snapshot['payment_method'] ?? strtoupper($order->payment_method?->value ?? 'Tunai') }})</span>
        </div>
        <div style="margin-top: 4px;">
            <strong>Pelanggan:</strong> {{ $order->customer_name }} ({{ $order->customer_email }}) &nbsp;|&nbsp;
            <strong>Meja:</strong> {{ $order->table?->label ?? 'Takeaway / POS' }} &nbsp;|&nbsp;
            <strong>Kode Penyerahan:</strong> {{ $order->order_code }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Menu</th>
                <th class="right">Jumlah</th>
                <th class="right">Harga Satuan</th>
                <th class="right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
                <tr>
                    <td>
                        {{ $item->product_name }}
                        @if($item->note)
                            <br><small>Catatan: {{ $item->note }}</small>
                        @endif
                    </td>
                    <td class="right">{{ $item->quantity }}</td>
                    <td class="right">Rp{{ number_format($item->unit_price, 0, ',', '.') }}</td>
                    <td class="right">Rp{{ number_format($item->line_total, 0, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="3" class="right">Subtotal</td>
                <td class="right">Rp{{ number_format($order->subtotal, 0, ',', '.') }}</td>
            </tr>
            @if($order->discount_amount > 0)
                <tr>
                    <td colspan="3" class="right">Potongan Promo / Voucher</td>
                    <td class="right">-Rp{{ number_format($order->discount_amount, 0, ',', '.') }}</td>
                </tr>
            @endif
            @if($order->tax_rate > 0)
                <tr>
                    <td colspan="3" class="right">Pajak ({{ number_format($order->tax_rate, 0) }}%)</td>
                    <td class="right">Rp{{ number_format($order->tax_amount, 0, ',', '.') }}</td>
                </tr>
            @endif
            <tr class="total-row">
                <td colspan="3" class="right">TOTAL DIBAYAR</td>
                <td class="right">Rp{{ number_format($order->total_amount, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        Invoice Digital Resmi 774 HUB • Diterbitkan pada {{ $snapshot['issued_at'] ?? $invoice->issued_at?->format('d/m/Y H:i') }}
    </div>
</body>
</html>
