<!DOCTYPE html>
<html>
<head>
    <title>Order Confirmation</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
<div style="max-width: 600px; margin: 0 auto; padding: 20px;">
    <h2>Order Confirmation</h2>

    <p>Dear {{ $customerName }},</p>

    <p>Thank you for your order! Your order has been received and is being processed.</p>

    <div style="background-color: #f5f5f5; padding: 15px; margin: 20px 0;">
        <h3>Order Details</h3>
        <p><strong>Order Number:</strong> {{ $order->order_number }}</p>
        <p><strong>Order Date:</strong> {{ $order->created_at->format('F j, Y') }}</p>
        <p><strong>Status:</strong> {{ ucfirst($order->status) }}</p>
    </div>

    <h3>Items Ordered</h3>
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
        <tr style="background-color: #f5f5f5;">
            <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Product</th>
            <th style="padding: 10px; text-align: center; border-bottom: 2px solid #ddd;">Qty</th>
            <th style="padding: 10px; text-align: right; border-bottom: 2px solid #ddd;">Price</th>
        </tr>
        </thead>
        <tbody>
        @foreach($order->items as $item)
            <tr>
                <td style="padding: 10px; border-bottom: 1px solid #ddd;">
                    {{ $item->product_name }}
                    @if($item->variant_name)
                        <br><small>{{ $item->variant_name }}</small>
                    @endif
                </td>
                <td style="padding: 10px; text-align: center; border-bottom: 1px solid #ddd;">{{ $item->quantity }}</td>
                <td style="padding: 10px; text-align: right; border-bottom: 1px solid #ddd;">${{ number_format($item->subtotal, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div style="margin-top: 20px; text-align: right;">
        <p><strong>Subtotal:</strong> ${{ number_format($order->total_amount - $order->tax_amount - $order->shipping_amount, 2) }}</p>
        <p><strong>Tax:</strong> ${{ number_format($order->tax_amount, 2) }}</p>
        <p><strong>Shipping:</strong> ${{ number_format($order->shipping_amount, 2) }}</p>
        <p style="font-size: 18px;"><strong>Total:</strong> ${{ number_format($order->total_amount, 2) }}</p>
    </div>

    <div style="margin-top: 30px;">
        <h3>Shipping Address</h3>
        <p>{{ $order->shipping_address }}</p>
    </div>

    <p style="margin-top: 30px;">If you have any questions, please contact our support team.</p>

    <p>Best regards,<br>{{ config('app.name') }} Team</p>
</div>
</body>
</html>
