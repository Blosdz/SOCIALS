<?php

use App\Http\Controllers\Api\SocialAccountController;
use App\Http\Controllers\MarketingShareController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');
Route::view('/login', 'auth.login')->name('login');
Route::view('/privacy-policy', 'privacy-policy')->name('privacy.policy');
Route::view('/terms', 'terms')->name('terms');
Route::view('/feed', 'feed.index')->name('feed');
Route::view('/contacts', 'contacts.index')->name('contacts');
Route::get('/share/{marketingContent}', [MarketingShareController::class, 'show'])->name('marketing.share');
Route::get('/oauth/{provider}/callback', [SocialAccountController::class, 'callback'])->name('oauth.callback');
Route::view('/integrations', 'integrations.index')->name('integrations');
