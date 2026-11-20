<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CronController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\StoreController;
use App\Http\Middleware\CheckExpiryAlerts;
use Illuminate\Support\Facades\Route;

Route::get('/', [StoreController::class, 'index'])->name('home');
Route::get('/servicio/{service}', [StoreController::class, 'show'])->name('store.service');
Route::get('/moneda/{currency}', CurrencyController::class)->name('store.currency');

Route::get('/carrito', [CartController::class, 'show'])->name('cart.show');
Route::post('/carrito', [CartController::class, 'store'])->name('cart.store');
Route::patch('/carrito/{item}', [CartController::class, 'update'])->where('item', '(plan|combo)-[0-9]+')->name('cart.update');
Route::delete('/carrito/{item}', [CartController::class, 'destroy'])->where('item', '(plan|combo)-[0-9]+')->name('cart.destroy');

Route::post('/pedido', [OrderController::class, 'store'])->middleware('throttle:10,1')->name('orders.store');
Route::get('/pedido/{order:token}', [OrderController::class, 'show'])->name('orders.show');

// Cron externo (Vercel Cron, cron-job.org): revisa las cuentas por vencer.
Route::get('/cron/avisos', CronController::class)->middleware('throttle:10,1')->name('cron.alerts');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware(['auth', CheckExpiryAlerts::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');

    Route::get('notificaciones', [Admin\NotificationController::class, 'index'])->name('notifications.index');
    Route::get('notificaciones/estado', [Admin\NotificationController::class, 'status'])->name('notifications.status');
    Route::get('notificaciones/{notification}', [Admin\NotificationController::class, 'open'])->name('notifications.open');
    Route::post('notificaciones/leidas', [Admin\NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('notificaciones/revisar', [Admin\NotificationController::class, 'check'])->name('notifications.check');
    Route::resource('categorias', Admin\CategoryController::class)
        ->except('show')
        ->parameters(['categorias' => 'category'])
        ->names('categories');

    Route::patch('servicios/{service}/disponibilidad', [Admin\ServiceController::class, 'toggle'])->name('services.toggle');
    Route::resource('servicios', Admin\ServiceController::class)
        ->except('show')
        ->parameters(['servicios' => 'service'])
        ->names('services');

    Route::patch('planes/{plan}/disponibilidad', [Admin\PlanController::class, 'toggle'])->name('plans.toggle');
    Route::resource('servicios.planes', Admin\PlanController::class)
        ->except(['index', 'show'])
        ->parameters(['servicios' => 'service', 'planes' => 'plan'])
        ->names('plans')
        ->shallow();

    Route::patch('promociones/{promotion}/estado', [Admin\PromotionController::class, 'toggle'])->name('promotions.toggle');
    Route::resource('promociones', Admin\PromotionController::class)
        ->except('show')
        ->parameters(['promociones' => 'promotion'])
        ->names('promotions');

    Route::resource('ordenes', Admin\OrderController::class)
        ->only(['index', 'show', 'update', 'destroy'])
        ->parameters(['ordenes' => 'order'])
        ->names('orders');

    Route::patch('combos/{combo}/disponibilidad', [Admin\ComboController::class, 'toggle'])->name('combos.toggle');
    Route::resource('combos', Admin\ComboController::class)->except('show');

    Route::get('cuentas/exportar', [Admin\SubscriptionController::class, 'export'])->name('subscriptions.export');
    Route::patch('cuentas/{subscription}/renovar', [Admin\SubscriptionController::class, 'renew'])->name('subscriptions.renew');
    Route::resource('cuentas', Admin\SubscriptionController::class)
        ->except('show')
        ->parameters(['cuentas' => 'subscription'])
        ->names('subscriptions');

    Route::get('/ajustes', [Admin\SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/ajustes', [Admin\SettingsController::class, 'update'])->name('settings.update');
    Route::post('/ajustes/tasa', [Admin\SettingsController::class, 'refreshRate'])->name('settings.rate');
});
