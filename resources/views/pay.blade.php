<!DOCTYPE html>
<html>

<head>
    <title>Laravel - Stripe Payment Gateway Integration</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/css/bootstrap.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
</head>

<body>

    <div class="container">

        <div class="row">
            <div class="col-md-6 col-md-offset-3">
                <div class="panel panel-default credit-card-box">
                    <div class="panel-heading display-table">
                        <h3 class="panel-title">Payment Details</h3>
                    </div>
                    <div class="panel-body">

                        @if (Session::has('success'))
                            <div class="alert alert-success text-center">
                                <a href="#" class="close" data-dismiss="alert" aria-label="close">×</a>
                                <p>{{ Session::get('success') }}</p>
                            </div>
                        @endif

                        <form role="form" action="{{ route('pay') }}" method="post" id="payment-form">
                            @csrf

                            <div class='form-row row'>
                                <div class='col-xs-12 form-group required'>
                                    <label class='control-label'>Name on Card</label> 
                                    <input class='form-control' id="cardholder-name" type='text' required>
                                </div>
                            </div>

                            <div class='form-row row'>
                                <div class='col-xs-12 form-group required'>
                                    <label class='control-label'>Card Details</label> 
                                    <div id="card-element" class="form-control"></div>
                                </div>
                            </div>

                            <div class='form-row row'>
                                <div class='col-md-12 error form-group hide'>
                                    <div class='alert-danger alert'>Please correct the errors and try again.</div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-xs-12">
                                    <button class="btn btn-primary btn-lg btn-block" id="submit-button" type="submit">
                                        Pay Now ($100)
                                    </button>
                                </div>
                            </div>

                            <input type="hidden" id="payment-method-id" name="payment_method_id">
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>

</body>

<script src="https://js.stripe.com/v3/"></script>

<script type="text/javascript">
    $(document).ready(function() {
        var stripe = Stripe("{{ config('services.stripe.public') }}"); 
        var elements = stripe.elements();
        var cardElement = elements.create('card');
        cardElement.mount('#card-element');

        var form = document.getElementById('payment-form');
        var submitButton = document.getElementById('submit-button');

        form.addEventListener('submit', function(event) {
            event.preventDefault();
            submitButton.disabled = true;

            stripe.createPaymentMethod({
                type: 'card',
                card: cardElement,
                billing_details: {
                    name: document.getElementById('cardholder-name').value
                }
            }).then(function(result) {
                if (result.error) {
                    $('.error')
                        .removeClass('hide')
                        .find('.alert')
                        .text(result.error.message);
                    submitButton.disabled = false;
                } else {
                    document.getElementById('payment-method-id').value = result.paymentMethod.id;
                    form.submit();
                }
            });
        });
    });
</script>

</html>
