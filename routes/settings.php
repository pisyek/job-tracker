<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/security');
});

Route::middleware(['auth', 'password.confirm'])->group(function () {
    Route::livewire('settings/security', 'pages::settings.security')->name('security.edit');
});
