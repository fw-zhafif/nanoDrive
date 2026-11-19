    <?php

use App\Http\Controllers\GroupController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DriveController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // --- RUTE MANAJEMEN GROUP ---
    Route::get('/groups', [GroupController::class, 'index'])->name('groups.index');
    Route::post('/groups', [GroupController::class, 'store'])->name('groups.store');
    Route::post('/groups/{group}/members', [GroupController::class, 'addMember'])->name('groups.members.add');
    Route::delete('/groups/{group}/members/{user}', [GroupController::class, 'removeMember'])->name('groups.members.remove');
    Route::delete('/groups/{group}', [GroupController::class, 'destroy'])->name('groups.destroy');

    // --- RUTE FITUR DASAR DRIVE ---
    Route::get('/drive/{folder?}', [DriveController::class, 'index'])->name('drive.index');
    Route::post('/drive/folder', [DriveController::class, 'storeFolder'])->name('drive.folder.store');
    Route::post('/drive/upload', [DriveController::class, 'uploadFile'])->name('drive.file.upload');
    Route::get('/drive/item/{item}/download', [DriveController::class, 'download'])->name('drive.item.download');
    Route::patch('/drive/item/{item}/rename', [DriveController::class, 'rename'])->name('drive.item.rename');
    Route::delete('/drive/item/{item}', [DriveController::class, 'destroy'])->name('drive.item.destroy');
    Route::patch('/drive/item/{item}/permissions', [DriveController::class, 'updatePermissions'])->name('drive.item.permissions');
});


require __DIR__.'/auth.php';
