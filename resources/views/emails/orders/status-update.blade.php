<!DOCTYPE html>
<html>
<head>
    <title>Order Status Update</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
<div style="max-width: 600px; margin: 0 auto; padding: 20px;">
    <h2>Order Status Update</h2>

    <p>Dear {{ $customerName }},</p>

    <p>Your order status has been updated.</p>

    <div style="background-color: #f5f5f5; padding: 15px; margin: 20px 0;">
        <p><strong>Order Number:</strong> {{ $order->order_number }}</p>
        <p><strong>Previous Status:</strong> {{ $oldStatus }}</p>
        <p><strong>Current Status:</strong> <span style="color: #28a745;">{{ $newStatus }}</span></p>
    </div>

    @if($order->status === 'shipped')
        <p>Your order has been shipped and is on its way to you!</p>
    @elseif($order->status === 'delivered')
        <p>Your order has been delivered. We hope you enjoy your purchase!</p>
    @elseif($order->status === 'cancelled')
        <p>Your order has been cancelled. If you have any questions, please contact our support team.</p>
    @endif

    <p style="margin-top: 30px;">Best regards,<br>{{ config('app.name') }} Team</p>
</div>
</body>
</html>
