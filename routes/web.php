<?php

use App\Http\Controllers\AuthorController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CartAddController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\Plans\CreditPlanIndexController;
use App\Http\Controllers\Plans\DigipayLeasingController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentResultController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\Representative\PaymentReceiptPdfController;
use App\Http\Controllers\Representative\ProformaPdfController;
use App\Http\Controllers\Representative\RepresentativePaymentResultController;
use App\Http\Controllers\ShopSearchSuggestController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\ThumbnailController;
use App\Livewire\Account\AccountDashboard;
use App\Livewire\Account\AddressManager;
use App\Livewire\Account\OrderList;
use App\Livewire\Account\OrderShow;
use App\Livewire\Account\PaymentList;
use App\Livewire\Account\ProfilePage;
use App\Livewire\Account\WishlistPage;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Cart\CartPage;
use App\Livewire\Checkout\CheckoutPage;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/thumb/{section}/{path}', ThumbnailController::class)
    ->where('path', '.*')
    ->name('shop.thumbnail');

Route::get('/data/{path}', function (string $path) {
    $path = ltrim($path, '/');

    if ($path === '' || str_contains($path, '..')) {
        abort(404);
    }

    $disk = Storage::disk('public');

    if (! $disk->exists($path)) {
        abort(404);
    }

    return response()->file($disk->path($path));
})->where('path', '.*')->name('filesystem.public');

Route::get('/session/ping', fn () => response()->noContent()->header('Cache-Control', 'no-store'))
    ->middleware('throttle:30,1')
    ->name('session.ping');

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/shop/search/suggest', ShopSearchSuggestController::class)->name('shop.search.suggest');

Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product:slug}', [ProductController::class, 'show'])->name('products.show');
Route::get('/categories/{category:slug}', [CategoryController::class, 'show'])->name('categories.show');
Route::get('/brands/{brand:slug}', [BrandController::class, 'show'])->name('brands.show');
Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{post:slug}', [BlogController::class, 'show'])->name('blog.show');
Route::get('/authors/{author:slug}', [AuthorController::class, 'show'])->name('authors.show');

Route::get('/product/{slug}', fn (string $slug) => redirect("/products/{$slug}", 301));

Route::get('/pages/{page:slug}', [PageController::class, 'show'])->name('pages.show');

Route::get('/plans', CreditPlanIndexController::class)->name('plans.index');

Route::redirect('/plans/digipay-leasing', '/plans/digipay-leasing/', 301);
Route::get('/plans/digipay-leasing/', DigipayLeasingController::class)->name('plans.digipay-leasing');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', function () {
    $lines = [
        'User-agent: *',
        'Disallow: /admin',
        'Disallow: /account',
        'Disallow: /checkout',
        'Disallow: /cart',
        'Disallow: /payment/',
        'Disallow: /login',
        'Disallow: /register',
        '',
        'Sitemap: '.url('/sitemap.xml'),
    ];

    return response(implode("\n", $lines), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
})->name('robots');

Route::post('/cart/add', CartAddController::class)->name('cart.add');

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/register', Register::class)->name('register');
});

Route::get('/cart', CartPage::class)->name('cart');
Route::get('/checkout', CheckoutPage::class)->name('checkout');
Route::match(['get', 'post'], '/payment/callback', [PaymentController::class, 'callback'])->name('payment.callback');
Route::match(['get', 'post'], '/payment/callback/tara', [PaymentController::class, 'taraCallback'])->name('payment.callback.tara');
Route::match(['get', 'post'], '/payment/callback/bajet', [PaymentController::class, 'bajetCallback'])->name('payment.callback.bajet');
Route::match(['get', 'post'], '/payment/callback/digipay', [PaymentController::class, 'digipayCallback'])->name('payment.callback.digipay');
Route::match(['get', 'post'], '/payment/callback/parsian', [PaymentController::class, 'parsianCallback'])->name('payment.callback.parsian');
Route::match(['get', 'post'], '/payment/callback/pec', [PaymentController::class, 'parsianCallback'])->name('payment.callback.pec');
Route::match(['get', 'post'], '/payment/callback/sadad', [PaymentController::class, 'sadadCallback'])->name('payment.callback.sadad');
Route::match(['get', 'post'], '/payment/callback/nopay', [PaymentController::class, 'nopayCallback'])->name('payment.callback.nopay');
Route::match(['get', 'post'], '/payment/callback/toopi', [PaymentController::class, 'nopayCallback'])->name('payment.callback.toopi');
Route::get('/payment/tara/{tracking}', [PaymentController::class, 'taraRedirect'])->name('payment.tara.redirect');
Route::get('/payment/result/{payment:tracking_code}', [PaymentResultController::class, 'show'])
    ->middleware('signed')
    ->name('payment.result');

Route::get('/representative/payment/result/{payment:tracking_code}', [RepresentativePaymentResultController::class, 'show'])
    ->middleware('signed')
    ->name('representative.payment.result');

Route::get('/representative/payment/{payment:tracking_code}/receipt.pdf', PaymentReceiptPdfController::class)
    ->middleware('signed')
    ->name('representative.payment.receipt.pdf');

Route::middleware('auth')->get('/representative/proforma/{order}/pdf', ProformaPdfController::class)
    ->name('representative.proforma.pdf');

Route::middleware('auth')->prefix('account')->name('account.')->group(function () {
    Route::get('/', AccountDashboard::class)->name('dashboard');
    Route::get('/orders', OrderList::class)->name('orders');
    Route::get('/orders/{order}', OrderShow::class)->name('orders.show');
    Route::get('/payments', PaymentList::class)->name('payments');
    Route::get('/profile', ProfilePage::class)->name('profile');
    Route::get('/addresses', AddressManager::class)->name('addresses');
    Route::get('/wishlist', WishlistPage::class)->name('wishlist');
});
