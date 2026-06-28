<?php

use App\Livewire\ApartmentDirectory;
use App\Livewire\ApartmentProfile;
use App\Livewire\OperationalDashboard;
use App\Livewire\ResidentApprovalQueue;
use App\Livewire\ResidentDirectory;
use App\Livewire\VehiclesAndCards;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/dashboard'));

Route::middleware(['auth'])->group(function () {
    // WEB-01-01 — Bảng điều khiển vận hành.
    Route::get('/dashboard', OperationalDashboard::class)->name('dashboard');

    // WEB-02 — Cư dân & Căn hộ.
    Route::get('/residents', ResidentDirectory::class)->name('residents');                       // WEB-02-01
    Route::get('/apartments', ApartmentDirectory::class)->name('apartments');                     // WEB-02-02 (list)
    Route::get('/apartments/{apartment}/profile', ApartmentProfile::class)->name('apartments.profile'); // WEB-02-02 (detail)
    Route::get('/vehicles-cards', VehiclesAndCards::class)->name('vehicles-cards');               // WEB-02-03
    Route::get('/resident-approvals', ResidentApprovalQueue::class)->name('resident-approvals');  // WEB-02-04
});
