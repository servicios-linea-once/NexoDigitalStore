<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<title>Recibo #{{ strtoupper(substr($order->ulid, -8)) }} — {{ $storeName }}</title>
<style>
  /* ── Reset ──────────────────────────────────────────────── */
  * { margin: 0; padding: 0; }
  body {
    font-family: 'DejaVu Sans', Arial, sans-serif;
    font-size: 12px;
    color: #1e293b;
    background: #ffffff;
  }

  /* ── Wrapper ────────────────────────────────────────────── */
  .page {
    width: 720px;
    margin: 0 auto;
    padding: 40px 48px;
  }

  /* ── Header (table-based, dompdf-safe) ──────────────────── */
  .header-table {
    width: 100%;
    border-bottom: 2px solid #6366f1;
    padding-bottom: 18px;
    margin-bottom: 26px;
  }
  .header-table td { vertical-align: middle; padding: 0; }
  .store-name   { font-size: 22px; font-weight: 700; color: #6366f1; }
  .store-tagline{ font-size: 10px; color: #64748b; margin-top: 4px; }
  .receipt-label{ font-size: 15px; font-weight: 700; color: #1e293b; text-align: right; }
  .receipt-id   { font-size: 10px; color: #64748b; text-align: right; margin-top: 4px; }
  .receipt-date { font-size: 10px; color: #64748b; text-align: right; margin-top: 2px; }

  /* ── Section title ──────────────────────────────────────── */
  .section-title {
    font-size: 9px;
    font-weight: 700;
    color: #6366f1;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 8px;
  }

  /* ── Info table (cliente + pago) ────────────────────────── */
  .info-table { width: 100%; margin-bottom: 22px; }
  .info-table td { vertical-align: top; width: 50%; padding: 0; }
  .info-table td + td { padding-left: 30px; }

  .info-row { font-size: 11px; color: #1e293b; margin-bottom: 4px; }
  .info-label { color: #64748b; }
  .badge-paid {
    display: inline;
    background: #dcfce7;
    color: #15803d;
    font-size: 10px;
    font-weight: 700;
    padding: 2px 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
    border: 1px solid #86efac;
  }

  /* ── Divider ────────────────────────────────────────────── */
  .divider {
    width: 100%;
    border: none;
    border-top: 1px solid #e2e8f0;
    margin: 18px 0;
  }

  /* ── Items table ────────────────────────────────────────── */
  .items-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 16px;
  }
  .items-table th {
    background: #f1f5f9;
    text-align: left;
    padding: 8px 12px;
    font-size: 10px;
    font-weight: 700;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
  .items-table th.right { text-align: right; }
  .items-table td {
    padding: 10px 12px;
    font-size: 11px;
    border-bottom: 1px solid #e2e8f0;
    vertical-align: top;
  }
  .items-table tr:last-child td { border-bottom: none; }
  .items-table td.right { text-align: right; }
  .product-name { font-weight: 700; color: #1e293b; }
  .product-meta { font-size: 10px; color: #64748b; margin-top: 3px; }

  /* ── Totals table ───────────────────────────────────────── */
  .totals-wrap { width: 100%; }
  .totals-table {
    width: 280px;
    border-collapse: collapse;
    margin-left: auto;
  }
  .totals-table td {
    padding: 5px 8px;
    font-size: 11px;
    vertical-align: middle;
  }
  .totals-table .lbl  { color: #64748b; text-align: right; }
  .totals-table .val  { text-align: right; font-weight: 600; color: #1e293b; }
  .totals-table .val-green { text-align: right; font-weight: 600; color: #16a34a; }
  .totals-table .val-indigo { text-align: right; font-weight: 600; color: #6366f1; }
  .totals-divider td { border-top: 1px solid #e2e8f0; height: 4px; }
  .totals-total td {
    border-top: 2px solid #6366f1;
    padding-top: 10px;
    font-size: 13px;
    font-weight: 800;
  }
  .totals-total .lbl { color: #1e293b; }
  .totals-total .val { color: #6366f1; }

  /* ── Security notice (table-based para dompdf) ──────────────── */
  .notice-table {
    width: 100%;
    border-collapse: collapse;
    margin: 22px 0 18px;
  }
  .notice-table td {
    border: 1px solid #fbbf24;
    background: #fffbeb;
    padding: 10px 14px;
    font-size: 10px;
    color: #92400e;
  }

  /* ── Footer ─────────────────────────────────────────────── */
  .footer-table { width: 100%; border-top: 1px solid #e2e8f0; margin-top: 24px; }
  .footer-table td {
    padding-top: 14px;
    font-size: 10px;
    color: #94a3b8;
    text-align: center;
  }
  .footer-brand { color: #6366f1; font-weight: 700; }
</style>
</head>
<body>
<div class="page">

  {{-- ── HEADER ────────────────────────────────────────────── --}}
  <table class="header-table" cellspacing="0" cellpadding="0">
    <tr>
      <td>
        <div class="store-name">{{ $storeName }}</div>
        <div class="store-tagline">{{ $storeTagline }}</div>
      </td>
      <td>
        <div class="receipt-label">RECIBO DE COMPRA</div>
        <div class="receipt-id">Nro. {{ strtoupper(substr($order->ulid, -8)) }}</div>
        <div class="receipt-date">
          {{ ($order->paid_at ?? $order->completed_at ?? $order->created_at)?->format('d/m/Y H:i') }} UTC
        </div>
      </td>
    </tr>
  </table>

  {{-- ── DATOS CLIENTE + PAGO ───────────────────────────────── --}}
  <table class="info-table" cellspacing="0" cellpadding="0">
    <tr>
      <td>
        <div class="section-title">Datos del cliente</div>
        <div class="info-row"><strong>{{ $order->buyer->name }}</strong></div>
        <div class="info-row"><span class="info-label">Email:</span> {{ $order->buyer->email }}</div>
        @if($order->ip_address)
        <div class="info-row"><span class="info-label">IP:</span> {{ $order->ip_address }}</div>
        @endif
        @if($order->buyer->username)
        <div class="info-row"><span class="info-label">Usuario:</span> {{ '@' . $order->buyer->username }}</div>
        @endif
      </td>
      <td>
        <div class="section-title">Información de pago</div>
        <div class="badge-paid">&#10003; Completado</div>
        <div class="info-row"><span class="info-label">Método:</span> <strong>{{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}</strong></div>
        <div class="info-row"><span class="info-label">Moneda:</span> {{ $order->currency }}</div>
        @if($order->payment_reference)
        <div class="info-row"><span class="info-label">Referencia:</span> {{ $order->payment_reference }}</div>
        @endif
        @if($order->paid_at)
        <div class="info-row"><span class="info-label">Fecha de pago:</span> {{ $order->paid_at->format('d/m/Y H:i') }} UTC</div>
        @endif
      </td>
    </tr>
  </table>

  <hr class="divider" />

  {{-- ── PRODUCTOS ───────────────────────────────────────────── --}}
  <div class="section-title">Detalle de productos</div>

  <table class="items-table" cellspacing="0" cellpadding="0">
    <thead>
      <tr>
        <th style="width:50%">Producto</th>
        <th style="width:12%" class="right">Cant.</th>
        <th style="width:19%" class="right">Precio unit.</th>
        <th style="width:19%" class="right">Total</th>
      </tr>
    </thead>
    <tbody>
      @foreach($order->items as $item)
      <tr>
        <td>
          <div class="product-name">{{ $item->product_name }}</div>
          @if($item->cashback_amount > 0)
          <div class="product-meta">Cashback: +{{ number_format($item->cashback_amount, 2) }} NT</div>
          @endif
          @if($item->delivery_status === 'delivered')
          <div class="product-meta" style="color:#16a34a;">&#10003; Entregado</div>
          @endif
        </td>
        <td class="right">{{ $item->quantity }}</td>
        <td class="right">${{ number_format($item->unit_price, 2) }}</td>
        <td class="right"><strong>${{ number_format($item->total_price, 2) }}</strong></td>
      </tr>
      @endforeach
    </tbody>
  </table>

  {{-- ── TOTALES ─────────────────────────────────────────────── --}}
  <table class="totals-wrap" cellspacing="0" cellpadding="0">
    <tr>
      <td style="width:55%">&nbsp;</td>
      <td style="width:45%">
        <table class="totals-table" cellspacing="0" cellpadding="0">
          <tr>
            <td class="lbl">Subtotal:</td>
            <td class="val">${{ number_format($order->subtotal, 2) }}</td>
          </tr>

          @if((float)$order->discount_amount > 0)
          <tr>
            <td class="lbl">Descuento suscripción:</td>
            <td class="val-green">-${{ number_format($order->discount_amount, 2) }}</td>
          </tr>
          @endif

          @if((float)$order->nexocoins_used > 0)
          <tr>
            <td class="lbl">NexoTokens aplicados:</td>
            <td class="val-indigo">-{{ number_format($order->nexocoins_used, 2) }} NT</td>
          </tr>
          @endif

          <tr class="totals-divider"><td colspan="2"></td></tr>

          <tr class="totals-total">
            <td class="lbl">TOTAL PAGADO:</td>
            <td class="val">${{ number_format($order->total, 2) }} {{ $order->currency }}</td>
          </tr>

          @if($order->currency !== 'USD' && (float)$order->exchange_rate > 0)
          <tr>
            <td class="lbl" style="font-size:10px; color:#94a3b8;">Equiv. USD:</td>
            <td class="val" style="font-size:10px; color:#94a3b8;">
              ${{ number_format($order->total / $order->exchange_rate, 2) }} USD
            </td>
          </tr>
          @endif
        </table>
      </td>
    </tr>
  </table>

  {{-- ── AVISO DE SEGURIDAD (table para dompdf-safe) ──────────────────── --}}
  <table class="notice-table" cellspacing="0" cellpadding="0">
    <tr>
      <td>
        <strong>&#9888; Aviso de seguridad:</strong>
        Las claves digitales adquiridas solo se muestran dentro de tu cuenta en
        <strong>{{ $storeUrl }}</strong>. No compartimos claves por correo electr&oacute;nico.
        Accede a <strong>{{ $storeUrl }}/orders/{{ $order->ulid }}</strong> para ver tus claves en cualquier momento.
      </td>
    </tr>
  </table>

  {{-- ── FOOTER ──────────────────────────────────────────────── --}}
  <table class="footer-table" cellspacing="0" cellpadding="0">
    <tr>
      <td>
        <span class="footer-brand">{{ $storeName }}</span> &mdash; {{ $supportEmail }}<br>
        Este documento es un comprobante válido de tu compra digital.<br>
        Generado automáticamente el {{ now()->format('d/m/Y H:i') }} UTC.
      </td>
    </tr>
  </table>

</div>
</body>
</html>
