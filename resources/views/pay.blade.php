<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stripe Elements Payment</title>
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

        <form action="{{ route('stripe.charge') }}" method="POST">
            @csrf
            <label for="card_number">Card Number</label>
            <input type="text" name="card_number" class="form-control" required>

            <label for="expiry_month" class="mt-2">Expiry Month</label>
            <input type="text" name="expiry_month" class="form-control" required>

            <label for="expiry_year" class="mt-2">Expiry Year</label>
            <input type="text" name="expiry_year" class="form-control" required>

            <label for="cvc" class="mt-2">CVC</label>
            <input type="text" name="cvc" class="form-control" required>

            <button type="submit" class="btn btn-primary mt-3">Pay</button>
        </form>
    </div>

</body>

</html>
