<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\SessionsController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ProgramaEstudioController;
use App\Http\Controllers\TrabajoAplicacionController;
use App\Http\Controllers\ChangePasswordController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PublicController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [PublicController::class, 'index'])->name('publics.index');
Route::get('publics/{id}', [PublicController::class, 'show'])->name('publics.show');

Route::post('/login', function () {
    return view('auth.login');
});

Route::get('/login', [SessionsController::class, 'create'])
        ->middleware('guest')
        ->name('login.index');

Route::post('/login', [SessionsController::class, 'store'])
        ->name('login.store');

Route::get('/logout', [SessionsController::class, 'destroy'])
        ->middleware('auth')
        ->name('login.destroy');

Route::get('/cambiar-contrasena', [ChangePasswordController::class, 'showChangePasswordForm'])
        ->middleware('auth')
        ->name('changeme.showChangePasswordForm');

Route::post('/cambiar-contrasena', [ChangePasswordController::class, 'changePassword'])
        ->middleware('auth')
        ->name('changeme.changePassword');

Route::resource('/trabajoAplicacion',TrabajoAplicacionController::class)
        ->middleware('auth');
Route::resource('/programaEstudios',ProgramaEstudioController::class)
    ->middleware('auth');
Route::resource('/usuarios',UserController::class)
    ->middleware('auth');
Route::get('/usuarios/{id}/cambiar-contrasena', [UserController::class, 'cambiarContrasena'])
    ->middleware('auth')
    ->name('usuarios.cambiarContrasena');
Route::put('/usuarios/{id}/actualizar-contrasena', [UserController::class, 'actualizarContrasena'])
    ->middleware('auth')
    ->name('usuarios.actualizarContrasena');

Route::get('/register', [RegisterController::class, 'create'])
    ->middleware('auth')
    ->name('register.create');
Route::post('/register', [RegisterController::class, 'store'])
    ->middleware('auth')
    ->name('register.store');

