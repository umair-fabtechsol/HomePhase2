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

        <form id="payment-form">
            @csrf
            <input type="hidden" id="stripe-key" value="{{ config('services.stripe.public') }}">
            <div id="card-element" class="form-control"></div>
            <button id="submit-button" class="btn btn-primary mt-3">Pay</button>
        </form>

        <div id="payment-message" class="mt-3"></div>
    </div>

    <script>
        const stripe = Stripe(document.getElementById('stripe-key').value);

        const elements = stripe.elements();
        const cardElement = elements.create('card');

        cardElement.mount('#card-element');

        document.getElementById('payment-form').addEventListener('submit', async function(event) {
            event.preventDefault();

            const {
                paymentMethod,
                error
            } = await stripe.createPaymentMethod({
                type: 'card',
                card: cardElement,
            });
            var crd = paymentMethod;


            if (error) {
                document.getElementById('payment-message').textContent = error.message;
            } else {
                fetch("{{ route('stripe.charge') }}", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": document.querySelector('input[name="_token"]').value
                        },
                        body: JSON.stringify({
                            payment_method: paymentMethod.id
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById('payment-message').textContent = "Payment successful!";
                        } else {
                            document.getElementById('payment-message').textContent = data.error;
                        }
                    });
            }
        });
    </script>

</body>

</html>
