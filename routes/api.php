<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\API\BorrowingController;

Route::get('/login', function () {
    return response()->json(
        ['status' => 'failed', 'error' => true, 'message' => ["You are unauthenticated or you do not have enough rights for this operation."]],401);
})->name('login');

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->name('do-login');

Route::middleware(['auth:sanctum'])->prefix('user')->group(function () {

    Route::get('/show', [AuthController::class, 'show']);
    Route::put('/update', [AuthController::class, 'update']);
    Route::post('/logout', [AuthController::class, 'logout']);    

});


Route::prefix('books')->controller(BookController::class)->group(function () {

    Route::get('/', 'index');
    Route::get('/{book}', 'show');

    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::post('/', 'store')->name('books.store');
        Route::put('/{id}', 'update');
        Route::delete('/{id}', 'destroy');
    });
    
});

Route::middleware(['auth:sanctum'])->group(function () {

    Route::post('/borrowings', [BorrowingController::class, 'borrow']);
    Route::post('/borrowings/{id}/return', [BorrowingController::class, 'returnBook']);
    
});
