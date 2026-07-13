<?php

use App\Http\Controllers\Install\InstallController;
use App\Http\Controllers\ShortLinkController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// 对应00文档"邀请链接形式"：{域名}/r/{短码} 302跳转到当前生效机器人。
Route::get('/r/{shortCode}', [ShortLinkController::class, 'redirect'])->name('short-link.redirect');

// 对应07文档"安装向导"：EnsureInstalled中间件保证装完之前只能访问这组路由，
// 装完之后这组路由整体失效（重定向到/admin）。
Route::prefix('install')->name('install.')->group(function () {
    Route::get('/', [InstallController::class, 'index'])->name('index');

    Route::get('/environment', [InstallController::class, 'environment'])->name('environment');
    Route::post('/environment', [InstallController::class, 'environmentContinue'])->name('environment.continue');

    Route::get('/database', [InstallController::class, 'database'])->name('database');
    Route::post('/database', [InstallController::class, 'databaseStore'])->name('database.store');

    Route::get('/migrate', [InstallController::class, 'migrate'])->name('migrate');
    Route::post('/migrate', [InstallController::class, 'migrateStore'])->name('migrate.store');

    Route::get('/admin', [InstallController::class, 'admin'])->name('admin');
    Route::post('/admin', [InstallController::class, 'adminStore'])->name('admin.store');
});
