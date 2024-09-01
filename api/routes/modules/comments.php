<?php

/*
|--------------------------------------------------------------------------
| Comments Routes
|--------------------------------------------------------------------------
| This file defines the routes related to comments project entities.
| Comments are a vital part of our application for facilitating
| discussions and interactions. This file contains all the route
| definitions for managing and handling comment-related functionality.
|
| The routes in this file encompass various aspects of comments, including:
| - Posting and managing comments on content
| - Displaying comment threads and replies
| - Moderating and reporting comments
| - User interactions with comments
|
| Maintaining proper organization and adhering to clear route naming conventions
| within this file is essential for consistency throughout the project.
|--------------------------------------------------------------------------
*/

use App\Enums\UserRole;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Comment\CommentController;

Route::namespace('Comments')->prefix('comments/{entity_type}/{entity_id}')->group(function () {
    Route::get('/', [CommentController::class, 'getAllFromEntity']);
    Route::get('/{comment_id}', [CommentController::class, 'getSingleFromEntity']);
    Route::post('/', [CommentController::class, 'create'])
        ->name('create')
        ->middleware(['access:' . UserRole::admin()->getIndex() . ',' .
            UserRole::owner()->getIndex()  . ',' .
            UserRole::accountant()->getIndex()]);
    Route::put('/{comment_id}', [CommentController::class, 'update']);
    Route::delete('/', [CommentController::class, 'delete']);
});
