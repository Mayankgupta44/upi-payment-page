<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>UPI Payment Portal</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <style>
        body {
            background: #f2f5fa;
            font-family: Arial, sans-serif;
        }

        .payment-card {
            max-width: 550px;
            margin: 50px auto;
            background: #fff;
            padding: 35px;
            border-radius: 16px;
            box-shadow: 0 8px 35px rgba(0,0,0,0.08);
        }

        .brand {
            color: #2457d6;
            font-weight: 700;
            font-size: 25px;
        }

        .form-control {
            padding: 12px;
            border-radius: 8px;
        }

        .btn-pay {
            background: #2457d6;
            color: white;
            padding: 13px;
            border-radius: 8px;
            font-weight: 600;
        }

        .btn-pay:hover {
            background: #1744b5;
            color: white;
        }

        .secure-text {
            font-size: 13px;
            color: #777;
        }
    </style>
</head>

<body>

<div class="container">

    <div class="payment-card">

        <div class="text-center mb-4">

            <div class="brand">UPI Payment Portal</div>

            <p class="text-muted mt-2">
                Simple, Secure & Fast Payments
            </p>

            <hr>

        </div>

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">
                <strong>Please check the following fields:</strong>
                <ul class="mb-0 mt-2">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('payment.store') }}" method="POST">

            @csrf

            <div class="mb-3">
                <label class="form-label">Full Name</label>

                <input type="text"
                       name="full_name"
                       class="form-control"
                       placeholder="Enter your full name"
                       value="{{ old('full_name') }}"
                       maxlength="100"
                       required>
                
                @error('full_name')
                    <div class="text-danger small mt-1">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Mobile Number</label>

                <input type="tel"
                       name="mobile_number"
                       class="form-control"
                       placeholder="Enter 10-digit mobile number"
                       value="{{ old('mobile_number') }}"
                       maxlength="10"
                       pattern="[6-9][0-9]{9}"
                       required>
                    
                @error('mobile_number')
                    <div class="text-danger small mt-1">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Email ID</label>

                <input type="email"
                       name="email"
                       class="form-control"
                       placeholder="Enter your email address"
                       value="{{ old('email') }}"
                       maxlength="150"
                       required>

                @error('email')
                    <div class="text-danger small mt-1">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Father's Name</label>

                <input type="text"
                       name="father_name"
                       class="form-control"
                       placeholder="Enter father's name"
                       value="{{ old('father_name') }}"
                       maxlength="100"
                       required>

                @error('father_name')
                    <div class="text-danger small mt-1">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="mb-4">
                <label class="form-label">Payment Amount (INR)</label>

                <div class="input-group">
                    <span class="input-group-text">₹</span>

                    <input type="number"
                           name="amount"
                           class="form-control"
                           placeholder="Enter payment amount"
                           value="{{ old('amount') }}"
                           min="1"
                           max="100000"
                           step="0.01"
                           required>

                    @error('amount')
                        <div class="text-danger small mt-1">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
            </div>

            <button type="submit" class="btn btn-pay w-100">
                Continue to Payment
            </button>

        </form>

        <div class="text-center mt-4">

            <p class="secure-text">
                🔒 Your information is securely submitted.
            </p>

            <p class="text-muted small">
                Secure payments powered by Razorpay.
                Your payment status is verified securely.
            </p>

        </div>

    </div>

</div>

@if (isset($razorpayOrder))
    <script src="https://checkout.razorpay.com/v1/checkout.js"
            onerror="alert('Razorpay Checkout could not load. Please check your internet connection.')">
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            if (typeof Razorpay === 'undefined') {
                alert('Razorpay Checkout library failed to load.');
                return;
            }

            const options = {
                key: @json($razorpayOrder['key']),
                amount: @json($razorpayOrder['amount']),
                currency: 'INR',
                name: 'UPI Payment Portal',
                description: 'Customer payment',
                order_id: @json($razorpayOrder['order_id']),

                prefill: {
                    name: @json($razorpayOrder['customer_name']),
                    email: @json($razorpayOrder['email']),
                    contact: @json($razorpayOrder['mobile'])
                },

                theme: {
                    color: '#2457d6'
                },

                handler: async function (response) {
                    try {
                        const result = await fetch(
                            @json(route('payment.verify')),
                            {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': @json(csrf_token())
                                },
                                body: JSON.stringify(response)
                            }
                        );

                        const data = await result.json();

                        if (result.ok) {
                            alert('Payment successful and verified!');
                            window.location.href = data.redirect;
                        } else {
                            alert(data.message || 'Payment verification failed.');
                        }

                    } catch (error) {
                        console.error('Verification error:', error);
                        alert('Unable to verify payment. Please contact the administrator.');
                    }
                },

                modal: {
                    ondismiss: function () {
                        console.log('Razorpay checkout closed.');
                    }
                }
            };

            try {
                const checkout = new Razorpay(options);
                checkout.open();
            } catch (error) {
                console.error('Razorpay initialization error:', error);
                alert('Unable to open Razorpay Checkout: ' + error.message);
            }
        });
    </script>
@endif
</body>
</html>
