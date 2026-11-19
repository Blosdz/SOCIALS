<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MarketingContentController;
use App\Http\Controllers\Api\SocialAccountController;
use App\Http\Controllers\Api\TwilioCallController;
use App\Http\Controllers\Api\SocialPublishingController;
use App\Http\Middleware\AuthenticateAdviser;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/webhooks/twilio/voice/{voiceCall}', [TwilioCallController::class, 'consent']);
Route::post('/webhooks/twilio/call-status/{voiceCall}', [TwilioCallController::class, 'callStatus']);
Route::post('/webhooks/twilio/recording/{voiceCall}', [TwilioCallController::class, 'recording']);
Route::post('/webhooks/twilio/transcription', [TwilioCallController::class, 'transcription']);
Route::middleware(AuthenticateAdviser::class)->group(function () {
    Route::get('/marketing-contents', [MarketingContentController::class, 'index']);
    Route::post('/marketing-contents/{marketingContent}/publish', [SocialPublishingController::class, 'publish']);
    Route::get('/social-accounts', [SocialAccountController::class, 'index']);
    Route::post('/social-accounts/{provider}/connect', [SocialAccountController::class, 'connect']);
    Route::delete('/social-accounts/{provider}', [SocialAccountController::class, 'disconnect']);
    Route::get('/twilio/calls', [TwilioCallController::class, 'index']);
    Route::post('/twilio/calls', [TwilioCallController::class, 'store']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
});
