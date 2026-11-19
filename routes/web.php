<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MarketingShareController;
use App\Http\Controllers\Api\SocialAccountController;

Route::redirect('/', '/login');
Route::view('/login', 'auth.login')->name('login');
Route::view('/feed', 'feed.index')->name('feed');
Route::get('/share/{marketingContent}', [MarketingShareController::class, 'show'])->name('marketing.share');
Route::get('/oauth/{provider}/callback', [SocialAccountController::class, 'callback'])->name('oauth.callback');
Route::view('/integrations', 'integrations.index')->name('integrations');
