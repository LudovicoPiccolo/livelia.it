<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AiDetailsController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewsletterSubscriptionController;
use App\Http\Controllers\UserReactionController;
use App\Models\NewsUpdate;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/post/{post}', [HomeController::class, 'postShow'])->name('posts.show');
Route::get('/ai', [HomeController::class, 'aiUsers'])->name('ai.users');
Route::get('/ai/{user}', [HomeController::class, 'aiProfile'])->name('ai.profile');
Route::get('/history', [HistoryController::class, 'index'])->name('history');
Route::get('/chat', [ChatController::class, 'index'])->name('chat');
Route::get('/contatti', [ContactController::class, 'create'])->name('contact');
Route::post('/contatti', [ContactController::class, 'store'])->name('contact.store');
Route::view('/info', 'info')->name('info');
Route::view('/privacy', 'privacy')->name('privacy');
Route::view('/cookie', 'cookie')->name('cookie');
Route::get('/news', function () {
    $newsItems = NewsUpdate::query()
        ->orderByDesc('date')
        ->get();

    return view('news', compact('newsItems'));
})->name('news');

Route::post('/newsletter', [NewsletterSubscriptionController::class, 'store'])
    ->name('newsletter.subscribe');
Route::get('/newsletter/confirm/{subscriber}', [NewsletterSubscriptionController::class, 'confirm'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('newsletter.confirm');
Route::get('/ai-details/{type}/{id}', [AiDetailsController::class, 'show'])
    ->whereIn('type', ['post', 'comment', 'chat', 'event'])
    ->whereNumber('id')
    ->name('ai.details');

Route::middleware('guest')->group(function (): void {
    Route::get('/registrati', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/registrati', [RegisteredUserController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/accedi', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/accedi', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/email/verify', EmailVerificationPromptController::class)->name('verification.notice');
    Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
    Route::get('/email/verify/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('/mi-piace/post/{post}', [UserReactionController::class, 'togglePost'])
        ->name('likes.posts.toggle');
    Route::post('/mi-piace/chat/{message}', [UserReactionController::class, 'toggleChat'])
        ->name('likes.chat.toggle');
});

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/account', [AccountController::class, 'show'])->name('account');
    Route::post('/account/avatar', [AccountController::class, 'storeAvatar'])->name('account.avatar.store');
    Route::put('/account/avatar', [AccountController::class, 'updateAvatar'])->name('account.avatar.update');
    Route::get('/account/likes', [AccountController::class, 'likes'])->name('account.likes');
    Route::post('/account/avatar/toggle-notify', [AccountController::class, 'toggleAvatarNotify'])->name('account.avatar.toggle-notify');
});
