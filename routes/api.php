<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ActualityController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\EventRegistrationController;
use App\Http\Controllers\Api\PublicationController;
use App\Http\Controllers\Api\GalleryPhotoController;
use App\Http\Controllers\Api\FinancingRequestController;
use App\Http\Controllers\Api\PublicationRequestController;
use App\Http\Controllers\Api\NewsletterSubscriptionController;
use App\Http\Controllers\Api\TrainingRegistrationController;
use App\Http\Controllers\Api\AuthController;

// Routes d'authentification
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/me', [AuthController::class, 'me'])->middleware('auth:sanctum');
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Actualities routes
Route::prefix('actualities')->group(function () {
    Route::get('/', [ActualityController::class, 'index']);
    Route::post('/', [ActualityController::class, 'store']);
    Route::get('/{id}', [ActualityController::class, 'show']);
    Route::put('/{id}', [ActualityController::class, 'update']);
    Route::patch('/{id}', [ActualityController::class, 'update']);
    Route::delete('/{id}', [ActualityController::class, 'destroy']);
});

// Events routes
Route::prefix('events')->group(function () {
    Route::get('/', [EventController::class, 'index']);
    Route::post('/', [EventController::class, 'store']);
    Route::get('/{id}', [EventController::class, 'show']);
    Route::put('/{id}', [EventController::class, 'update']);
    Route::patch('/{id}', [EventController::class, 'update']);
    Route::delete('/{id}', [EventController::class, 'destroy']);
    
    // Inscriptions aux événements
    Route::post('/{id}/register', [EventRegistrationController::class, 'register']);
    Route::get('/{id}/registrations', [EventRegistrationController::class, 'getEventRegistrations']);
});

// Event Registrations routes (gestion globale)
Route::prefix('event-registrations')->group(function () {
    Route::get('/', [EventRegistrationController::class, 'index']);
    Route::patch('/{id}/status', [EventRegistrationController::class, 'updateStatus']);
    Route::delete('/{id}', [EventRegistrationController::class, 'destroy']);
});

// Publications routes
Route::prefix('publications')->group(function () {
    Route::get('/', [PublicationController::class, 'index']);
    Route::post('/', [PublicationController::class, 'store']);
    Route::get('/{id}', [PublicationController::class, 'show']);
    Route::put('/{id}', [PublicationController::class, 'update']);
    Route::patch('/{id}', [PublicationController::class, 'update']);
    Route::delete('/{id}', [PublicationController::class, 'destroy']);
});

// Gallery photos routes
Route::prefix('gallery')->group(function () {
    Route::get('/', [GalleryPhotoController::class, 'index']);
    Route::get('/categories', [GalleryPhotoController::class, 'categories']);
    Route::post('/', [GalleryPhotoController::class, 'store']);
    Route::get('/{id}', [GalleryPhotoController::class, 'show']);
    Route::put('/{id}', [GalleryPhotoController::class, 'update']);
    Route::patch('/{id}', [GalleryPhotoController::class, 'update']);
    Route::delete('/{id}', [GalleryPhotoController::class, 'destroy']);
});

// Financing requests routes
Route::prefix('financing-requests')->group(function () {
    Route::get('/', [FinancingRequestController::class, 'index']);
    Route::post('/', [FinancingRequestController::class, 'store']);
    Route::get('/{id}', [FinancingRequestController::class, 'show']);
    Route::put('/{id}', [FinancingRequestController::class, 'update']);
    Route::patch('/{id}', [FinancingRequestController::class, 'update']);
    Route::patch('/{id}/status', [FinancingRequestController::class, 'updateStatus']);
    Route::delete('/{id}', [FinancingRequestController::class, 'destroy']);
});

// Publication requests routes
Route::prefix('publication-requests')->group(function () {
    Route::get('/', [PublicationRequestController::class, 'index']);
    Route::post('/', [PublicationRequestController::class, 'store']);
    Route::get('/{id}', [PublicationRequestController::class, 'show']);
    Route::put('/{id}', [PublicationRequestController::class, 'update']);
    Route::patch('/{id}', [PublicationRequestController::class, 'update']);
    Route::patch('/{id}/status', [PublicationRequestController::class, 'updateStatus']);
    Route::delete('/{id}', [PublicationRequestController::class, 'destroy']);
});

// Newsletter routes (public routes)
Route::prefix('newsletter')->group(function () {
    Route::post('/subscribe', [NewsletterSubscriptionController::class, 'subscribe']);
    Route::post('/unsubscribe', [NewsletterSubscriptionController::class, 'unsubscribe']);
    Route::get('/status', [NewsletterSubscriptionController::class, 'status']);
});

// Newsletter routes (admin routes - require authentication)
Route::prefix('newsletter')->middleware('auth:sanctum')->group(function () {
    Route::get('/subscribers', [NewsletterSubscriptionController::class, 'subscribers']);
    Route::get('/', [NewsletterSubscriptionController::class, 'index']);
    Route::get('/{id}', [NewsletterSubscriptionController::class, 'show']);
    Route::put('/{id}', [NewsletterSubscriptionController::class, 'update']);
    Route::patch('/{id}', [NewsletterSubscriptionController::class, 'update']);
    Route::delete('/{id}', [NewsletterSubscriptionController::class, 'destroy']);
});

// Training Registrations routes (public route for registration)
Route::prefix('training-registrations')->group(function () {
    Route::post('/', [TrainingRegistrationController::class, 'store']);
});

// Training Registrations routes (admin routes - require authentication)
Route::prefix('training-registrations')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [TrainingRegistrationController::class, 'index']);
    Route::get('/{id}', [TrainingRegistrationController::class, 'show']);
    Route::put('/{id}', [TrainingRegistrationController::class, 'update']);
    Route::patch('/{id}', [TrainingRegistrationController::class, 'update']);
    Route::delete('/{id}', [TrainingRegistrationController::class, 'destroy']);
});


