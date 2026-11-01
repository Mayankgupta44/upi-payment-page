<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard - UPI Payments</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">
</head>

<body class="bg-light">

<nav class="navbar navbar-dark bg-dark px-4">
    <span class="navbar-brand">UPI Payment Admin</span>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="btn btn-outline-light btn-sm">Logout</button>
    </form>
</nav>

<div class="container py-4">

    <h2 class="mb-4">Admin Dashboard</h2>

    <div class="row g-3 mb-4">

        <div class="col-md-4">
            <div class="card shadow-sm p-3">
                <h6>Total Customers</h6>
                <h3>{{ $totalCustomers }}</h3>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm p-3">
                <h6>Total Transactions</h6>
                <h3>{{ $totalPayments }}</h3>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm p-3">
                <h6>Total Collected</h6>
                <h3>₹{{ number_format($totalCollected, 2) }}</h3>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm p-3">
                <h6>Successful Payments</h6>
                <h3 class="text-success">{{ $successfulPayments }}</h3>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm p-3">
                <h6>Failed Payments</h6>
                <h3 class="text-danger">{{ $failedPayments }}</h3>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm p-3">
                <h6>Pending Payments</h6>
                <h3 class="text-warning">{{ $pendingPayments }}</h3>
            </div>
        </div>

    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h4 class="mb-3">Recent Transactions</h4>

            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Mobile</th>
                            <th>Amount</th>
                            <th>Payment ID</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($payments as $payment)
                            <tr>
                                <td>{{ $payment->id }}</td>
                                <td>{{ $payment->customer->full_name ?? 'N/A' }}</td>
                                <td>{{ $payment->customer->mobile_number ?? 'N/A' }}</td>
                                <td>₹{{ number_format($payment->amount, 2) }}</td>
                                <td>{{ $payment->gateway_payment_id ?? 'N/A' }}</td>
                                <td>
                                    @if($payment->payment_status === 'success')
                                        <span class="badge bg-success">Success</span>
                                    @elseif($payment->payment_status === 'failed')
                                        <span class="badge bg-danger">Failed</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    @endif
                                </td>
                                <td>{{ $payment->created_at->format('d-m-Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">
                                    No transactions found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $payments->links() }}
        </div>
    </div>

</div>

</body>
</html>