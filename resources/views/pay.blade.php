<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stripe Payment</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body>

    <div class="container mt-5">
        <h2 class="text-center">Stripe Payment</h2>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form action="{{ route('pay') }}" method="POST">
            @csrf
            <label for="card_no">Card Number</label>
            <input type="text" name="card_no" class="form-control" value="4242424242424242" required>

            <label for="ccExpiryMonth" class="mt-2">Expiry Month</label>
            <input type="text" name="ccExpiryMonth" class="form-control" value="12" required>

            <label for="ccExpiryYear" class="mt-2">Expiry Year</label>
            <input type="text" name="ccExpiryYear" class="form-control" value="2025" required>

            <label for="cvvNumber" class="mt-2">CVC</label>
            <input type="text" name="cvvNumber" class="form-control" value="123" required>

            <button type="submit" class="btn btn-primary mt-3">Pay</button>
        </form>
    </div>

</body>

</html>
