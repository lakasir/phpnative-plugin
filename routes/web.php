<?php

use App\Livewire\BluetoothManager;
use Illuminate\Support\Facades\Route;

Route::get('/', BluetoothManager::class)->name('home');
