<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\MarketingContentController;
use App\Http\Controllers\Api\SocialAccountController;
use App\Http\Controllers\Api\SocialPublishingController;
use App\Http\Controllers\Api\TwilioCallController;
use App\Http\Middleware\AuthenticateAdviser;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/webhooks/twilio/voice/{voiceCall}', [TwilioCallController::class, 'consent']);
Route::post('/twilio/voice', [TwilioCallController::class, 'browserVoice']);
Route::post('/twilio/status', [TwilioCallController::class, 'browserStatus']);
Route::post('/webhooks/twilio/customer-screen/{voiceCall}', [TwilioCallController::class, 'screenCustomer']);
Route::post('/webhooks/twilio/customer-consent/{voiceCall}', [TwilioCallController::class, 'customerConsent']);
Route::post('/webhooks/twilio/dial-status/{voiceCall}', [TwilioCallController::class, 'dialStatus']);
Route::post('/webhooks/twilio/call-status/{voiceCall}', [TwilioCallController::class, 'callStatus']);
Route::post('/webhooks/twilio/recording/{voiceCall}', [TwilioCallController::class, 'recording']);
Route::post('/webhooks/twilio/transcription', [TwilioCallController::class, 'transcription']);
Route::middleware(AuthenticateAdviser::class)->group(function () {
    Route::get('/customers', [CustomerController::class, 'index']);
    Route::post('/customers', [CustomerController::class, 'store']);
    Route::get('/marketing-contents', [MarketingContentController::class, 'index']);
    Route::post('/marketing-contents/{marketingContent}/publish', [SocialPublishingController::class, 'publish']);
    Route::get('/tiktok/creator-info', [SocialPublishingController::class, 'tiktokCreatorInfo']);
    Route::get('/social-publications/{socialPublication}/status', [SocialPublishingController::class, 'publicationStatus']);
    Route::get('/social-accounts', [SocialAccountController::class, 'index']);
    Route::post('/social-accounts/{provider}/connect', [SocialAccountController::class, 'connect']);
    Route::delete('/social-accounts/{provider}', [SocialAccountController::class, 'disconnect']);
    Route::get('/twilio/calls', [TwilioCallController::class, 'index']);
    Route::get('/twilio/token', [TwilioCallController::class, 'token']);
    Route::post('/twilio/calls', [TwilioCallController::class, 'store']);
    Route::post('/twilio/calls/{voiceCall}/cancel', [TwilioCallController::class, 'cancel']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
});
