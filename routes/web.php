<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\JobApplicationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('applications.index');
})->name('home');

Route::middleware('auth')->group(function () {
    Route::resource('applications', JobApplicationController::class);
    Route::resource('companies', CompanyController::class)->only(['index', 'create', 'store', 'show']);
});

require __DIR__.'/settings.php';
