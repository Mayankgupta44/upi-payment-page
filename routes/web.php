
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\PaymentController;

Route::get('/', [PaymentController::class, 'create'])
    ->name('payment.create');

Route::get('/session-check', function (\Illuminate\Http\Request $request) {
    $request->session()->put('session_check', 'working');

    return response()->json([
        'session_driver' => config('session.driver'),
        'session_cookie' => config('session.cookie'),
        'session_secure' => config('session.secure'),
        'session_domain' => config('session.domain'),
        'session_path' => config('session.path'),
        'session_same_site' => config('session.same_site'),
    ])->withCookie(cookie('cookie_probe', 'test123', 5));
});

Route::post('/payment', [PaymentController::class, 'store'])
    ->name('payment.store');

Route::post('/payment/verify', [PaymentController::class, 'verify'])
    ->name('payment.verify');

Route::post('/webhooks/razorpay', [\App\Http\Controllers\RazorpayWebhookController::class, 'handle'])
    ->name('razorpay.webhook');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth'])->group(function () {

    Route::get('/admin/dashboard', function (\Illuminate\Http\Request $request) {
        abort_unless($request->user()?->is_admin, 403);

        return app(AdminController::class)->index();
    })->name('admin.dashboard');

    Route::get('/profile', function () {
        return redirect()->route('admin.dashboard');
    })->name('profile.edit');

});

require __DIR__.'/auth.php';
