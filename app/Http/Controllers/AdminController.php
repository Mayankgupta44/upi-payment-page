<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function index()
    {
        $totalCustomers = Customer::count();

        $totalPayments = Payment::count();

        $successfulPayments = Payment::where(
            'payment_status',
            'success'
        )->count();

        $failedPayments = Payment::where(
            'payment_status',
            'failed'
        )->count();

        $pendingPayments = Payment::where(
            'payment_status',
            'pending'
        )->count();

        $totalCollected = Payment::where(
            'payment_status',
            'success'
        )->sum('amount');

        $payments = Payment::with('customer')
            ->latest()
            ->paginate(10);

        return view('admin.dashboard', compact(
            'totalCustomers',
            'totalPayments',
            'successfulPayments',
            'failedPayments',
            'pendingPayments',
            'totalCollected',
            'payments'
        ));
    }
}