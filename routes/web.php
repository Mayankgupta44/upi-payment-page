
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\PaymentController;

Route::get('/', [PaymentController::class, 'create'])
    ->name('payment.create');

Route::get('/session-check', function () {
    header(
        'Set-Cookie: cookie_probe=test123; Path=/; Secure; SameSite=Lax',
        false
    );

    return response()->json([
        'test' => 'cookie header test',
    ])->header('X-Cookie-Debug', 'route-reached');
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
