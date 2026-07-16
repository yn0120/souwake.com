<?php

use App\Http\Controllers\Office\OfficeAdminsController;
use App\Http\Controllers\Office\OfficeAuthController;
use App\Http\Controllers\Office\OfficeBudgetController;
use App\Http\Controllers\Office\OfficePasswordManagerController;
use App\Http\Controllers\Office\OfficeProfileController;
use App\Http\Controllers\Office\OfficeRolesController;
use App\Http\Controllers\Office\OfficeSecretsAuthController;
use App\Http\Controllers\Office\OfficeSecretsController;
use App\Http\Controllers\Office\OfficeSecretsPasswordController;
use App\Http\Controllers\Office\OfficeSecretsUploadController;
use App\Http\Controllers\Office\OfficeTopController;
use App\Http\Middleware\Office\CheckRoutePermission;
use App\Http\Middleware\Secrets\EnsureSecretsAdmin;
use App\Http\Middleware\Secrets\NoStoreCache;
use App\Http\Middleware\Secrets\RedirectIfAuthenticatedSecrets;
use App\Http\Middleware\Secrets\RedirectIfNotAuthenticatedSecrets;
use App\Http\Middleware\Secrets\RedirectIfNoUserSecrets;
use App\Http\Middleware\Secrets\RequireSecretsPassword;
use App\Http\Middleware\TouchAdminActivity;
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

    Route::middleware([RedirectIfNotAuthenticated::class.':office', RedirectIfNoUser::class.':office', 'touch.activity'])->group(function () {
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

        // パスワード管理（管理者ごとにプライベートなデータのため、役割による権限制御(CheckRoutePermission)の対象外とする）
        // 一覧
        Route::get('/password-manager', [OfficePasswordManagerController::class, 'index'])->name('officePasswordManagerIndex');
        // 一覧（非同期検索・ソート）
        Route::get('/password-manager/list', [OfficePasswordManagerController::class, 'list'])->name('officePasswordManagerList');
        // サイト登録（処理）
        Route::post('/password-manager', [OfficePasswordManagerController::class, 'createExecute'])->name('officePasswordManagerCreateExecute');
        // サイト更新（処理）
        Route::post('/password-manager/{id}', [OfficePasswordManagerController::class, 'updateExecute'])->name('officePasswordManagerUpdateExecute');
        // サイト削除（処理）
        Route::post('/password-manager/{id}/delete', [OfficePasswordManagerController::class, 'deleteExecute'])->name('officePasswordManagerDeleteExecute');
        // 項目追加（処理）
        Route::post('/password-manager/{id}/items', [OfficePasswordManagerController::class, 'itemCreateExecute'])->name('officePasswordManagerItemCreateExecute');
        // 項目更新（処理）
        Route::post('/password-manager/{id}/items/{itemId}', [OfficePasswordManagerController::class, 'itemUpdateExecute'])->name('officePasswordManagerItemUpdateExecute');
        // 項目削除（処理）
        Route::post('/password-manager/{id}/items/{itemId}/delete', [OfficePasswordManagerController::class, 'itemDeleteExecute'])->name('officePasswordManagerItemDeleteExecute');

        // 家計簿（管理者ごとにプライベートなデータのため、役割による権限制御(CheckRoutePermission)の対象外とする）
        // 入力フォーム
        Route::get('/budget', [OfficeBudgetController::class, 'index'])->name('officeBudgetIndex');
        // 登録（処理。スプレッドシートへ保存）
        Route::post('/budget', [OfficeBudgetController::class, 'submitExecute'])->name('officeBudgetSubmitExecute');
        // 口座の選択肢を追加（処理）
        Route::post('/budget/accounts', [OfficeBudgetController::class, 'accountCreateExecute'])->name('officeBudgetAccountCreateExecute');
        // 科目の選択肢を追加（処理）
        Route::post('/budget/categories', [OfficeBudgetController::class, 'categoryCreateExecute'])->name('officeBudgetCategoryCreateExecute');
        // スプレッドシートURLの設定（処理）
        Route::post('/budget/spreadsheet', [OfficeBudgetController::class, 'spreadsheetUpdateExecute'])->name('officeBudgetSpreadsheetUpdateExecute');

        // プロフィール編集（管理者ごとにプライベートなデータのため、役割による権限制御(CheckRoutePermission)の対象外とする）
        // 入力フォーム
        Route::get('/profile', [OfficeProfileController::class, 'index'])->name('officeProfileIndex');
        // 更新（処理）
        Route::post('/profile', [OfficeProfileController::class, 'updateExecute'])->name('officeProfileUpdateExecute');
    });
});

// ファイル管理機能（office.souwake.com）
// サイドメニュー・トップページ等からの導線は一切設けず、直接URLアクセスのみとする。
// admin.souwake.com（通常のOfficeパネル）とは意図的にセッション・ログインを分離しており、
// CheckRoutePermission（全ルートを自動登録し権限管理画面に一覧表示される）も通さない。
Route::domain(config('app.env_domain').'office.'.config('app.domain'))->group(function () {
    Route::middleware([RedirectIfAuthenticatedSecrets::class])->group(function () {
        Route::get('/login', [OfficeSecretsAuthController::class, 'loginInput'])->name('officeSecretsLoginInput');
        Route::post('/login', [OfficeSecretsAuthController::class, 'loginExecute'])->name('officeSecretsLoginExecute');
    });

    Route::get('/logout', [OfficeSecretsAuthController::class, 'logout'])->name('officeSecretsLogout');

    Route::middleware([
        RedirectIfNotAuthenticatedSecrets::class,
        RedirectIfNoUserSecrets::class,
        EnsureSecretsAdmin::class,
        TouchAdminActivity::class,
        NoStoreCache::class,
    ])->group(function () {
        // 固定パスワードの入力自体はRequireSecretsPasswordの外に置く（無限リダイレクト防止）
        Route::get('/secrets/password', [OfficeSecretsPasswordController::class, 'input'])->name('officeSecretsPasswordInput');
        Route::post('/secrets/password', [OfficeSecretsPasswordController::class, 'verify'])->name('officeSecretsPasswordVerify');

        Route::get('/secrets/upload', [OfficeSecretsUploadController::class, 'input'])->name('officeSecretsUploadInput');
        Route::post('/secrets/upload', [OfficeSecretsUploadController::class, 'chunk'])->name('officeSecretsUploadChunk');

        Route::middleware([RequireSecretsPassword::class])->group(function () {
            Route::get('/secrets', [OfficeSecretsController::class, 'index'])->name('officeSecretsIndex');
            Route::get('/secrets/list', [OfficeSecretsController::class, 'list'])->name('officeSecretsList');
            Route::get('/secrets/view/{id}', [OfficeSecretsController::class, 'view'])->name('officeSecretsView');
        });
    });
});
