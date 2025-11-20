<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice - {{ $order->order_number }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #333;
        }
        .container {
            width: 100%;
            padding: 20px;
        }
        .header {
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .company-info {
            float: left;
            width: 50%;
        }
        .invoice-info {
            float: right;
            width: 50%;
            text-align: right;
        }
        .clearfix {
            clear: both;
        }
        h1 {
            margin: 0;
            font-size: 28px;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 10px;
        }
        .info-box {
            background-color: #f5f5f5;
            padding: 10px;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table th {
            background-color: #333;
            color: white;
            padding: 10px;
            text-align: left;
        }
        table td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .totals {
            margin-top: 20px;
            float: right;
            width: 40%;
        }
        .totals table {
            margin-top: 0;
        }
        .totals td {
            border: none;
            padding: 5px 10px;
        }
        .grand-total {
            font-size: 16px;
            font-weight: bold;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="company-info">
            <h1>{{ config('app.name') }}</h1>
            <p>123 Business Street<br>
                City, State 12345<br>
                Phone: (555) 123-4567<br>
                Email: support@example.com</p>
        </div>
        <div class="invoice-info">
            <h2>INVOICE</h2>
            <p><strong>Invoice #:</strong> {{ $order->order_number }}<br>
                <strong>Date:</strong> {{ $order->created_at->format('F j, Y') }}<br>
                <strong>Status:</strong> {{ ucfirst($order->status) }}</p>
        </div>
        <div class="clearfix"></div>
    </div>

    <div class="section-title">Bill To:</div>
    <div class="info-box">
        <strong>{{ $order->customer->name }}</strong><br>
        {{ $order->customer->email }}<br>
        {{ $order->billing_address ?? $order->shipping_address }}
    </div>

    <div class="section-title">Ship To:</div>
    <div class="info-box">
        {{ $order->shipping_address }}
    </div>

    <div class="section-title">Order Items:</div>
    <table>
        <thead>
        <tr>
            <th>Item</th>
            <th class="text-center">Quantity</th>
            <th class="text-right">Unit Price</th>
            <th class="text-right">Total</th>
        </tr>
        </thead>
        <tbody>
        @foreach($order->items as $item)
            <tr>
                <td>
                    <strong>{{ $item->product_name }}</strong>
                    @if($item->variant_name)
                        <br><small>{{ $item->variant_name }}</small>
                    @endif
                </td>
                <td class="text-center">{{ $item->quantity }}</td>
                <td class="text-right">${{ number_format($item->price, 2) }}</td>
                <td class="text-right">${{ number_format($item->subtotal, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="clearfix"></div>

    <div class="totals">
        <table>
            <tr>
                <td>Subtotal:</td>
                <td class="text-right">${{ number_format($order->total_amount - $order->tax_amount - $order->shipping_amount, 2) }}</td>
            </tr>
            <tr>
                <td>Tax:</td>
                <td class="text-right">${{ number_format($order->tax_amount, 2) }}</td>
            </tr>
            <tr>
                <td>Shipping:</td>
                <td class="text-right">${{ number_format($order->shipping_amount, 2) }}</td>
            </tr>
            <tr class="grand-total">
                <td>Total:</td>
                <td class="text-right">${{ number_format($order->total_amount, 2) }}</td>
            </tr>
        </table>
    </div>

    <div class="clearfix"></div>

    @if($order->notes)
        <div class="section-title">Notes:</div>
        <p>{{ $order->notes }}</p>
    @endif

    <div class="footer">
        <p>Thank you for your business!<br>
            For any questions, please contact us at support@thalsecom.com</p>
    </div>
</div>
</body>
</html>
