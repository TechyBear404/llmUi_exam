<?php

use App\Http\Controllers\ConversationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AskController;
use App\Http\Controllers\CustomInstructionController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Route::middleware([
//     'auth:sanctum',
//     config('jetstream.auth_session'),
//     'verified',
// ])->group(function () {
//     Route::get('/chat', [AskController::class, 'index'])->name('ask.index');
// });

Route::middleware('auth')->group(function () {
    Route::get('/', [AskController::class, 'index'])->name('ask.index');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');


    // Chat routes
    Route::post('/conversations/{conversation}/ask', [AskController::class, 'streamMessage'])->name('conversations.ask');
    Route::post('/conversations/{conversation}/title', [ConversationController::class, 'updateTitle'])->name('conversations.title');
    Route::put('/conversations/{conversation}/custom-instruction', [ConversationController::class, 'updateCustomInstruction'])->name('conversations.custom-instruction');
    Route::get('/conversations/{conversation}', [ConversationController::class, 'show'])->name('conversations.show');
    Route::post('/conversations', [ConversationController::class, 'store'])->name('conversations.store');
    Route::put('/conversations/{conversation}', [ConversationController::class, 'update'])->name('conversations.update');
    Route::delete('/conversations/{conversation}', [ConversationController::class, 'destroy'])->name('conversations.destroy');
    Route::put('/user/update-model', [ConversationController::class, 'updateUserModel'])->name('user.update-model');

    // Custom instruction routes
    Route::get('/custom-instructions', [CustomInstructionController::class, 'index'])->name('custom-instructions.index');
    Route::get('/custom-instructions/create', [CustomInstructionController::class, 'create'])->name('custom-instructions.create');
    Route::post('/custom-instructions', [CustomInstructionController::class, 'store'])->name('custom-instructions.store');
    Route::get('/custom-instructions/{customInstruction}', [CustomInstructionController::class, 'show'])->name('custom-instructions.show');
    Route::put('/custom-instructions/{customInstruction}', [CustomInstructionController::class, 'update'])->name('custom-instructions.update');
});

require __DIR__ . '/auth.php';
