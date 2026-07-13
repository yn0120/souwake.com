<?php

use App\Http\Controllers\Office\OfficeAdminsController;
use App\Http\Controllers\Office\OfficeAuthController;
use App\Http\Controllers\Office\OfficeRolesController;
use App\Http\Controllers\Office\OfficeTopController;
use App\Http\Middleware\Office\CheckRoutePermission;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\RedirectIfNotAuthenticated;
use App\Http\Middleware\RedirectIfNoUser;
use Illuminate\Support\Facades\Route;

Route::domain(config('app.env_domain').'admin.'.config('app.domain'))->group(function () {
    Route::middleware([RedirectIfAuthenticated::class.':office'])->group(function () {
        // 管理者初期アカウント設定（入力）
        Route::get('/init/input', [OfficeAuthController::class, 'initInput'])->name('officeInitInput');
        // 管理者初期アカウント設定（処理）
        Route::post('/init/complete', [OfficeAuthController::class, 'initExecute'])->name('officeInitExecute');
        // 管理者初期アカウント設定（完了）
        Route::get('/init/complete', [OfficeAuthController::class, 'initComplete'])->name('officeInitComplete');

        // 管理者パスワードを忘れたら（入力）
        Route::get('/forgot/pw/input', [OfficeAuthController::class, 'forgotPwInput'])->name('officeForgotPwInput');
        // 管理者パスワードを忘れたら（処理）
        Route::post('/forgot/pw/complete', [OfficeAuthController::class, 'forgotPwExecute'])->name('officeForgotPwExecute');
        // 管理者パスワードを忘れたら（完了）
        Route::get('/forgot/pw/complete', [OfficeAuthController::class, 'forgotPwComplete'])->name('officeForgotPwComplete');

        // 管理者パスワード設定（入力）
        Route::get('/set/pw/input', [OfficeAuthController::class, 'setPwInput'])->name('officeSetPwInput');
        // 管理者パスワード設定（処理）
        Route::post('/set/pw/complete', [OfficeAuthController::class, 'setPwExecute'])->name('officeSetPwExecute');
        // 管理者パスワード設定（完了）
        Route::get('/set/pw/complete', [OfficeAuthController::class, 'setPwComplete'])->name('officeSetPwComplete');

        // 管理者ログイン（入力）
        Route::get('/login', [OfficeAuthController::class, 'loginInput'])->name('officeLoginInput');
        // 管理者ログイン（処理）
        Route::post('/login', [OfficeAuthController::class, 'loginExecute'])->name('officeLoginExecute');

        // 管理者ワンタイムキー（入力）
        Route::get('/onetime/input', [OfficeAuthController::class, 'onetimeInput'])->name('officeOnetimeInput');
        // 管理者ワンタイムキー（処理）
        Route::post('/onetime/complete', [OfficeAuthController::class, 'onetimeExecute'])->name('officeOnetimeExecute');
    });

    // 管理者ログアウト
    Route::get('/logout', [OfficeAuthController::class, 'logout'])->name('officeLogout');

    Route::middleware([RedirectIfNotAuthenticated::class.':office', RedirectIfNoUser::class.':office'])->group(function () {
        Route::middleware([CheckRoutePermission::class])->group(function () {
            // 管理者トップページ
            Route::get('/', [OfficeTopController::class, 'index'])->name('officeTop')->setDefaults(['description' => 'ダッシュボード']);

            // 権限一覧
            Route::get('/roles', [OfficeRolesController::class, 'index'])->name('officeRoleIndex')->setDefaults(['description' => '権限一覧']);

            // 権限詳細
            Route::get('/roles/{id}', [OfficeRolesController::class, 'show'])->name('officeRoleShow')->setDefaults(['description' => '権限詳細']);

            // 権限登録（入力）
            Route::get('/roles/create/input', [OfficeRolesController::class, 'createInput'])->name('officeRoleCreateInput')->setDefaults(['description' => '権限登録']);
            // 権限登録（確認）
            Route::post('/roles/create/confirm', [OfficeRolesController::class, 'createConfirm'])->name('officeRoleCreateConfirm');
            // 権限登録（処理）
            Route::post('/roles/create/complete', [OfficeRolesController::class, 'createExecute'])->name('officeRoleCreateExecute');
            // 権限登録（完了）
            Route::get('/roles/create/complete', [OfficeRolesController::class, 'createComplete'])->name('officeRoleCreateComplete');

            // 権限編集（入力）
            Route::get('/roles/{id}/edit/input', [OfficeRolesController::class, 'editInput'])->name('officeRoleEditInput')->setDefaults(['description' => '権限編集']);
            // 権限編集（確認）
            Route::post('/roles/{id}/edit/confirm', [OfficeRolesController::class, 'editConfirm'])->name('officeRoleEditConfirm');
            // 権限編集（処理）
            Route::post('/roles/{id}/edit/complete', [OfficeRolesController::class, 'editExecute'])->name('officeRoleEditExecute');
            // 権限編集（完了）
            Route::get('/roles/{id}/edit/complete', [OfficeRolesController::class, 'editComplete'])->name('officeRoleEditComplete');

            // 権限削除（処理）
            Route::post('/roles/{id}/delete', [OfficeRolesController::class, 'deleteExecute'])->name('officeRoleDeleteExecute')->setDefaults(['description' => '権限削除']);

            // 権限付与（入力）
            Route::get('/role-route/edit/input', [OfficeRolesController::class, 'roleRouteEditInput'])->name('officeRoleRouteEditInput')->setDefaults(['description' => '権限付与']);

            // 権限付与（処理）
            Route::post('/role-route/edit/complete', [OfficeRolesController::class, 'roleRouteEditExecute'])->name('officeRoleRouteEditExecute');

            // 管理者一覧
            Route::get('/admins', [OfficeAdminsController::class, 'index'])->name('officeAdminIndex')->setDefaults(['description' => '管理者一覧']);

            // 管理者詳細
            Route::get('/admins/{id}', [OfficeAdminsController::class, 'show'])->name('officeAdminShow')->setDefaults(['description' => '管理者詳細']);

            // 管理者登録（入力）
            Route::get('/admins/create/input', [OfficeAdminsController::class, 'createInput'])->name('officeAdminCreateInput')->setDefaults(['description' => '管理者登録']);
            // 管理者登録（確認）
            Route::post('/admins/create/confirm', [OfficeAdminsController::class, 'createConfirm'])->name('officeAdminCreateConfirm');
            // 管理者登録（処理）
            Route::post('/admins/create/complete', [OfficeAdminsController::class, 'createExecute'])->name('officeAdminCreateExecute');
            // 管理者登録（完了）
            Route::get('/admins/create/complete', [OfficeAdminsController::class, 'createComplete'])->name('officeAdminCreateComplete');

            // 管理者編集（入力）
            Route::get('/admins/{id}/edit/input', [OfficeAdminsController::class, 'editInput'])->name('officeAdminEditInput')->setDefaults(['description' => '管理者編集']);
            // 管理者編集（確認）
            Route::post('/admins/{id}/edit/confirm', [OfficeAdminsController::class, 'editConfirm'])->name('officeAdminEditConfirm');
            // 管理者編集（処理）
            Route::post('/admins/{id}/edit/complete', [OfficeAdminsController::class, 'editExecute'])->name('officeAdminEditExecute');
            // 管理者編集（完了）
            Route::get('/admins/{id}/edit/complete', [OfficeAdminsController::class, 'editComplete'])->name('officeAdminEditComplete');

            // 管理者パスワード再通知（処理）
            Route::post('/admins/{id}/remind', [OfficeAdminsController::class, 'remindExecute'])->name('officeAdminRemindExecute')->setDefaults(['description' => '管理者パスワード再通知']);

            // 管理者削除（処理）
            Route::post('/admins/{id}/delete', [OfficeAdminsController::class, 'deleteExecute'])->name('officeAdminDeleteExecute')->setDefaults(['description' => '管理者削除']);
        });

        // エラーページ
        Route::get('/errors/{code}', [OfficeTopController::class, 'error'])->name('officeError');
    });
});
