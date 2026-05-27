<?php

use Illuminate\Support\Facades\Route;

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


