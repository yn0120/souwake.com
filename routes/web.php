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
use App\Http\Controllers\Office\OfficeSecretsVaultController;
use App\Http\Controllers\Office\OfficeTopController;
use App\Http\Controllers\Wedding\WeddingRsvpController;
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
        // 管理者初期アカウント設定
        Route::get('/init/input', [OfficeAuthController::class, 'initInput'])->name('officeInitInput');
        Route::post('/init/complete', [OfficeAuthController::class, 'initExecute'])->name('officeInitExecute');
        Route::get('/init/complete', [OfficeAuthController::class, 'initComplete'])->name('officeInitComplete');

        // 管理者パスワードを忘れたら
        Route::get('/forgot/pw/input', [OfficeAuthController::class, 'forgotPwInput'])->name('officeForgotPwInput');
        Route::post('/forgot/pw/complete', [OfficeAuthController::class, 'forgotPwExecute'])->name('officeForgotPwExecute');
        Route::get('/forgot/pw/complete', [OfficeAuthController::class, 'forgotPwComplete'])->name('officeForgotPwComplete');

        // 管理者ログイン
        Route::get('/login', [OfficeAuthController::class, 'loginInput'])->name('officeLoginInput');
        Route::post('/login', [OfficeAuthController::class, 'loginExecute'])->name('officeLoginExecute');

        // 管理者ワンタイムキー
        Route::get('/onetime/input', [OfficeAuthController::class, 'onetimeInput'])->name('officeOnetimeInput');
        Route::post('/onetime/complete', [OfficeAuthController::class, 'onetimeExecute'])->name('officeOnetimeExecute');
    });

    // 管理者パスワード設定
    Route::get('/set/pw/input', [OfficeAuthController::class, 'setPwInput'])->name('officeSetPwInput');
    Route::post('/set/pw/complete', [OfficeAuthController::class, 'setPwExecute'])->name('officeSetPwExecute');
    Route::get('/set/pw/complete', [OfficeAuthController::class, 'setPwComplete'])->name('officeSetPwComplete');

    // 管理者ログアウト
    Route::get('/logout', [OfficeAuthController::class, 'logout'])->name('officeLogoutExecute');

    Route::middleware([RedirectIfNotAuthenticated::class.':office', RedirectIfNoUser::class.':office', TouchAdminActivity::class])->group(function () {
        Route::middleware([CheckRoutePermission::class])->group(function () {
            // 管理者トップページ
            Route::get('/', [OfficeTopController::class, 'index'])->name('officeTop')->setDefaults(['description' => 'ダッシュボード']);

            // 権限一覧
            Route::get('/roles', [OfficeRolesController::class, 'index'])->name('officeRoleIndex')->setDefaults(['description' => '権限一覧']);

            // 権限詳細
            Route::get('/roles/{id}', [OfficeRolesController::class, 'show'])->name('officeRoleShow')->setDefaults(['description' => '権限詳細']);

            // 権限登録
            Route::get('/roles/create/input', [OfficeRolesController::class, 'createInput'])->name('officeRoleCreateInput')->setDefaults(['description' => '権限登録']);
            Route::post('/roles/create/confirm', [OfficeRolesController::class, 'createConfirm'])->name('officeRoleCreateConfirm');
            Route::post('/roles/create/complete', [OfficeRolesController::class, 'createExecute'])->name('officeRoleCreateExecute');
            Route::get('/roles/create/complete', [OfficeRolesController::class, 'createComplete'])->name('officeRoleCreateComplete');

            // 権限編集
            Route::get('/roles/{id}/edit/input', [OfficeRolesController::class, 'editInput'])->name('officeRoleEditInput')->setDefaults(['description' => '権限編集']);
            Route::post('/roles/{id}/edit/confirm', [OfficeRolesController::class, 'editConfirm'])->name('officeRoleEditConfirm');
            Route::post('/roles/{id}/edit/complete', [OfficeRolesController::class, 'editExecute'])->name('officeRoleEditExecute');
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

            // 管理者登録
            Route::get('/admins/create/input', [OfficeAdminsController::class, 'createInput'])->name('officeAdminCreateInput')->setDefaults(['description' => '管理者登録']);
            Route::post('/admins/create/confirm', [OfficeAdminsController::class, 'createConfirm'])->name('officeAdminCreateConfirm');
            Route::post('/admins/create/complete', [OfficeAdminsController::class, 'createExecute'])->name('officeAdminCreateExecute');
            Route::get('/admins/create/complete', [OfficeAdminsController::class, 'createComplete'])->name('officeAdminCreateComplete');

            // 管理者編集
            Route::get('/admins/{id}/edit/input', [OfficeAdminsController::class, 'editInput'])->name('officeAdminEditInput')->setDefaults(['description' => '管理者編集']);
            Route::post('/admins/{id}/edit/confirm', [OfficeAdminsController::class, 'editConfirm'])->name('officeAdminEditConfirm');
            Route::post('/admins/{id}/edit/complete', [OfficeAdminsController::class, 'editExecute'])->name('officeAdminEditExecute');
            Route::get('/admins/{id}/edit/complete', [OfficeAdminsController::class, 'editComplete'])->name('officeAdminEditComplete');

            // 管理者パスワード再通知（処理）
            Route::post('/admins/{id}/remind', [OfficeAdminsController::class, 'remindExecute'])->name('officeAdminRemindExecute')->setDefaults(['description' => '管理者パスワード再通知']);

            // 管理者削除（処理）
            Route::post('/admins/{id}/delete', [OfficeAdminsController::class, 'deleteExecute'])->name('officeAdminDeleteExecute')->setDefaults(['description' => '管理者削除']);

            // パスワード管理一覧
            Route::get('/password-manager', [OfficePasswordManagerController::class, 'index'])->name('officePasswordManagerIndex')->setDefaults(['description' => 'パスワード管理一覧']);
            Route::get('/password-manager/list', [OfficePasswordManagerController::class, 'list'])->name('officePasswordManagerList')->setDefaults(['description' => 'パスワード管理一覧取得処理']);

            // パスワード管理サイト登録（処理）
            Route::post('/password-manager', [OfficePasswordManagerController::class, 'createExecute'])->name('officePasswordManagerCreateExecute')->setDefaults(['description' => 'パスワード管理 サイト登録']);

            // パスワード管理サイト更新（処理）
            Route::post('/password-manager/{id}', [OfficePasswordManagerController::class, 'editExecute'])->name('officePasswordManagerEditExecute')->setDefaults(['description' => 'パスワード管理 サイト編集']);

            // パスワード管理サイト削除（処理）
            Route::post('/password-manager/{id}/delete', [OfficePasswordManagerController::class, 'deleteExecute'])->name('officePasswordManagerDeleteExecute')->setDefaults(['description' => 'パスワード管理 サイト削除']);

            // パスワード管理項目追加（処理）
            Route::post('/password-manager/{id}/items', [OfficePasswordManagerController::class, 'itemCreateExecute'])->name('officePasswordManagerItemCreateExecute')->setDefaults(['description' => 'パスワード管理 項目登録']);

            // パスワード管理項目更新（処理）
            Route::post('/password-manager/{id}/items/{itemId}', [OfficePasswordManagerController::class, 'itemEditExecute'])->name('officePasswordManagerItemEditExecute')->setDefaults(['description' => 'パスワード管理 項目編集']);

            // パスワード管理項目削除（処理）
            Route::post('/password-manager/{id}/items/{itemId}/delete', [OfficePasswordManagerController::class, 'itemDeleteExecute'])->name('officePasswordManagerItemDeleteExecute')->setDefaults(['description' => 'パスワード管理 項目削除']);

            // 家計簿入力
            Route::get('/budget', [OfficeBudgetController::class, 'createInput'])->name('officeBudgetCreateInput')->setDefaults(['description' => '家計簿登録']);

            // スプレッドシートへ登録処理
            Route::post('/budget', [OfficeBudgetController::class, 'createExecute'])->name('officeBudgetCreateExecute')->setDefaults(['description' => 'スプレッドシートへ登録処理']);

            // 口座選択肢追加処理
            Route::post('/budget/accounts', [OfficeBudgetController::class, 'accountCreateExecute'])->name('officeBudgetAccountCreateExecute')->setDefaults(['description' => '口座選択肢追加処理']);

            // 科目選択肢追加処理
            Route::post('/budget/categories', [OfficeBudgetController::class, 'categoryCreateExecute'])->name('officeBudgetCategoryCreateExecute')->setDefaults(['description' => '科目選択肢追加処理']);

            // スプレッドシートURL設定処理
            Route::post('/budget/spreadsheet', [OfficeBudgetController::class, 'spreadsheetEditExecute'])->name('officeBudgetSpreadsheetEditExecute')->setDefaults(['description' => 'スプレッドシートURL設定処理']);

            // プロフィール編集
            Route::get('/profile', [OfficeProfileController::class, 'editInput'])->name('officeProfileEditInput')->setDefaults(['description' => 'プロフィール編集']);
            Route::post('/profile', [OfficeProfileController::class, 'editExecute'])->name('officeProfileEditExecute');
        });

        // エラーページ
        Route::get('/errors/{code}', [OfficeTopController::class, 'error'])->name('officeError');
    });
});

// 結婚式サイト（wedding.souwake.com）
Route::domain(config('app.env_domain').'wedding.'.config('app.domain'))->group(function () {
    Route::get('/', [WeddingRsvpController::class, 'index'])->name('weddingRsvpInput');
    Route::post('/rsvp', [WeddingRsvpController::class, 'createExecute'])->name('weddingRsvpCreateExecute');
    Route::get('/rsvp/complete', [WeddingRsvpController::class, 'complete'])->name('weddingRsvpComplete');
});

// ファイル管理機能（office.souwake.com）
// サイドメニュー・トップページ等からの導線は一切設けず、直接URLアクセスのみとする。
// admin.souwake.com（通常のOfficeパネル）とは意図的にセッション・ログインを分離しており、
// CheckRoutePermission（全ルートを自動登録し権限管理画面に一覧表示される）も通さない。
Route::domain(config('app.env_domain').'office.'.config('app.domain'))->group(function () {
    Route::middleware([RedirectIfAuthenticatedSecrets::class])->group(function () {
        Route::get('/login', [OfficeSecretsAuthController::class, 'loginInput'])->name('officeSecretsLoginInput');
        // 総当たり対策のレート制限。Cloudflare側にも同等のルールを置くが、エッジの設定に
        // 依存せずorigin単体でも守れるようにアプリ側にも必ず持たせる（多層防御）。
        Route::post('/login', [OfficeSecretsAuthController::class, 'loginExecute'])->middleware('throttle:5,1')->name('officeSecretsLoginExecute');
    });

    Route::get('/logout', [OfficeSecretsAuthController::class, 'logout'])->name('officeSecretsLogout');

    Route::middleware([
        RedirectIfNotAuthenticatedSecrets::class,
        RedirectIfNoUserSecrets::class,
        EnsureSecretsAdmin::class,
        TouchAdminActivity::class,
        NoStoreCache::class,
    ])->group(function () {
        // 固定パスワードの入力自体はRequireSecretsPasswordの外に置く（無限リダイレクト防止）。
        // この2本だけが例外であり、他のsecrets系ルートは必ずRequireSecretsPassword配下に置くこと。
        Route::get('/secrets/password', [OfficeSecretsPasswordController::class, 'input'])->name('officeSecretsPasswordInput');
        Route::post('/secrets/password', [OfficeSecretsPasswordController::class, 'verify'])->middleware('throttle:5,1')->name('officeSecretsPasswordVerify');

        Route::middleware([RequireSecretsPassword::class])->group(function () {
            Route::get('/secrets', [OfficeSecretsController::class, 'index'])->name('officeSecretsIndex');
            Route::get('/secrets/list', [OfficeSecretsController::class, 'list'])->name('officeSecretsList');

            // 閲覧はE2E暗号化。サーバーは復号せず、
            //   - /secrets/meta/{id} で「vault公開鍵でラップされたままの鍵」とチャンク情報を返し
            //   - /secrets/raw/{id}  で暗号文そのものを（X-Accel-Redirectでnginxから）返す
            // ブラウザのService Worker（secrets-sw.js）が /secrets/media/{id} を横取りして
            // 復号し、平文座標の206レスポンスに組み立て直す。
            Route::get('/secrets/meta/{id}', [OfficeSecretsController::class, 'meta'])->name('officeSecretsMeta');
            Route::get('/secrets/raw/{id}', [OfficeSecretsController::class, 'raw'])->name('officeSecretsRaw');

            // vault鍵（E2E復号鍵）の登録・一覧。秘密鍵はラップ済みのものしかやり取りしない。
            Route::get('/secrets/vault', [OfficeSecretsVaultController::class, 'index'])->name('officeSecretsVaultIndex');
            Route::post('/secrets/vault', [OfficeSecretsVaultController::class, 'store'])->name('officeSecretsVaultStore');
            Route::delete('/secrets/vault/{id}', [OfficeSecretsVaultController::class, 'destroy'])->name('officeSecretsVaultDestroy');
            Route::post('/secrets/vault/{id}/touch', [OfficeSecretsVaultController::class, 'touch'])->name('officeSecretsVaultTouch');

            // アップロードもゲートパスワード必須にする（admin id=1のセッションを奪われただけでは
            // 書き込みもできないようにするため。閲覧だけを保護しても意味が薄い）。
            Route::get('/secrets/upload', [OfficeSecretsUploadController::class, 'input'])->name('officeSecretsUploadInput');
            Route::post('/secrets/upload', [OfficeSecretsUploadController::class, 'chunk'])->name('officeSecretsUploadChunk');
        });
    });
});
