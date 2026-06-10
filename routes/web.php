<?php

use Illuminate\Support\Facades\Route;
use Statamic\Statamic;

/*
|--------------------------------------------------------------------------
| User Registration
|--------------------------------------------------------------------------
| Exposes the /register page using Statamic's built-in user:register_form
| tag. New users are auto-assigned the 'editor' role.
|
*/

Route::statamic('register', 'register', [
    'title' => 'Register',
    'layout' => 'layout_simple',
]);

Route::statamic('login', 'login', [
    'title' => 'Login',
    'layout' => 'layout_simple',
]);

Route::statamic('forgot-password', 'forgot-password', [
    'title' => 'Forgot Password',
    'layout' => 'layout_simple',
]);

Route::get('/reset-password/{token}', function ($token) {
    return (new \Statamic\View\View)
        ->template('reset-password')
        ->layout('layout_simple')
        ->with([
            'title' => 'Reset Password',
            'token' => $token,
            'email' => request('email'),
        ]);
})->name('statamic.password.reset');

Route::statamic('stories', 'stories/index', [
    'title' => 'Stories'
]);

Route::statamic('', 'stories/index', [
    'title' => 'Stories'
]);

Route::middleware(['web', 'statamic.cp', 'statamic.cp.authenticated'])
    ->name('statamic.cp.ssg-exporter.')
    ->prefix(config('statamic.cp.route', 'cp') . '/ssg-exporter')
    ->group(function () {
        Route::get('/', [\App\Http\Controllers\CP\SSGUtilityController::class, 'index'])->name('index');
        Route::post('/export', [\App\Http\Controllers\CP\SSGUtilityController::class, 'export'])->name('export');
        Route::get('/download/{file}', [\App\Http\Controllers\CP\SSGUtilityController::class, 'download'])->name('download');
        Route::delete('/delete/{file}', [\App\Http\Controllers\CP\SSGUtilityController::class, 'delete'])->name('delete');
    });

Route::get('/dev-login', function () {
    $user = \Statamic\Facades\User::findByEmail('hannes.tscherrig@ffhs.ch') ?: \Statamic\Facades\User::all()->first();
    if ($user) {
        \Illuminate\Support\Facades\Auth::login($user);
        return redirect('/cp');
    }
    return 'No user found';
});


