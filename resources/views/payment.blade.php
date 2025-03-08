<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stripe Elements Payment</title>
    <script src="https://js.stripe.com/v3/"></script>
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

        <form id="payment-form" action="{{ route('stripe.charge') }}" method="POST">
            @csrf
            <input type="hidden" id="stripe-key" value="{{ config('services.stripe.public') }}">
            <input type="hidden" id="payment-method" name="payment_method">

            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" id="name" name="name" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="amount" class="form-label">Amount (USD)</label>
                <input type="number" id="amount" name="amount" class="form-control" required min="1">
            </div>

            <label class="form-label">Card Details</label>
            <div class="mb-3">
                <label class="form-label">Card Number</label>
                <div id="card-number" class="form-control"></div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Expiry Date</label>
                    <div id="card-expiry" class="form-control"></div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">CVC</label>
                    <div id="card-cvc" class="form-control"></div>
                </div>
            </div>

            <button id="submit-button" class="btn btn-primary mt-3">Pay</button>
        </form>

        <div id="payment-message" class="mt-3"></div>
    </div>

    <script>
        const stripe = Stripe(document.getElementById('stripe-key').value);
        const elements = stripe.elements();

        // Separate elements for card details
        const cardNumber = elements.create('cardNumber');
        const cardExpiry = elements.create('cardExpiry');
        const cardCvc = elements.create('cardCvc');

        // Mount elements to the form
        cardNumber.mount('#card-number');
        cardExpiry.mount('#card-expiry');
        cardCvc.mount('#card-cvc');

        document.getElementById('payment-form').addEventListener('submit', async function(event) {
            event.preventDefault();

            const {
                paymentMethod,
                error
            } = await stripe.createPaymentMethod({
                type: 'card',
                card: cardNumber,
                billing_details: {
                    name: document.getElementById('name').value
                }
            });

            if (error) {
                document.getElementById('payment-message').textContent = error.message;
            } else {
                document.getElementById('payment-method').value = paymentMethod.id;
                this.submit(); // Submit form to Laravel backend
            }
        });
    </script>

</body>

</html>
