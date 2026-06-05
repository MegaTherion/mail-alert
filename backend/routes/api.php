<?php

use App\Http\Controllers\MailAlertController;
use Illuminate\Support\Facades\Route;

Route::post('/mail-alert', [MailAlertController::class, 'store']);
Route::get('/alerts', [MailAlertController::class, 'index']);
