<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // Members list (for dev/navigation)
    Route::get('/members', function () {
        $members = \App\Models\Member::orderBy('name')->paginate(20);
        return view('members.index', compact('members'));
    })->name('members.index');

    // Care Management Module
    Route::get('/members/{member}/care-management', \App\Livewire\CareManagement\CareManagementIndex::class)
        ->name('care-management.index');
});
