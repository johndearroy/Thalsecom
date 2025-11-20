<!DOCTYPE html>
<html>
<head>
    <title>Low Stock Alert</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
<div style="max-width: 600px; margin: 0 auto; padding: 20px;">
    <h2 style="color: #dc3545;">Low Stock Alert</h2>

    <p>This is an automated alert to notify you that one of your products is running low on stock.</p>

    <div style="background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0;">
        <h3>Product Information</h3>
        <p><strong>Product:</strong> {{ $productName }}</p>
        <p><strong>Variant:</strong> {{ $variantName }}</p>
        <p><strong>SKU:</strong> {{ $sku }}</p>
        <p><strong>Current Stock:</strong> <span style="color: #dc3545; font-weight: bold;">{{ $currentStock }} units</span></p>
    </div>

    <p>Please restock this item to avoid running out of inventory.</p>

    <p style="margin-top: 30px;">Best regards,<br>{{ config('app.name') }} Inventory System</p>
</div>
</body>
</html>
