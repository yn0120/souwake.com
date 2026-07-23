<?php

namespace App\Http\Controllers\Office;

use App\Http\Controllers\Controller;
use App\Http\Requests\Office\Budget\AccountCreateRequest;
use App\Http\Requests\Office\Budget\CategoryCreateRequest;
use App\Http\Requests\Office\Budget\EntryCreateRequest;
use App\Http\Requests\Office\Budget\SpreadsheetEditRequest;
use App\Libraries\Utils;
use App\Services\GoogleSheetsBudgetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OfficeBudgetController extends Controller
{
    /**
     * 口座の初期選択肢（管理者ごとに未登録の場合のみ自動で登録する）
     */
    private const DEFAULT_ACCOUNTS = ['楽天銀行', '楽天クレカ', 'viewクレカ', 'SBI新生銀行', 'ゆうちょ銀行'];

    /**
     * 科目の初期選択肢（管理者ごとに未登録の場合のみ自動で登録する）
     */
    private const DEFAULT_CATEGORIES = [
        '飲食自分', '飲食複数人', '交通費', '衣類', '旅行関係・旅行中全般', 'ジム', '医療費',
        '雑費', '通信費', '共同口座へ', '保険', '投資', '税金', '収支合わせ',
    ];

    /**
     * 家計簿入力フォーム
     *
     * @return View
     */
    public function createInput(Request $request)
    {
        $adminId = Auth::id();

        $this->ensureDefaults('budget_accounts', $adminId, self::DEFAULT_ACCOUNTS);
        $this->ensureDefaults('budget_categories', $adminId, self::DEFAULT_CATEGORIES);

        $accounts = $this->fetchOptions('budget_accounts', $adminId);
        $categories = $this->fetchOptions('budget_categories', $adminId);

        $assign['accounts'] = $accounts;
        $assign['categories'] = $categories;
        $assign['defaultAccountId'] = $this->findIdByName($accounts, self::DEFAULT_ACCOUNTS[0]);
        $assign['defaultCategoryId'] = $this->findIdByName($categories, self::DEFAULT_CATEGORIES[0]);
        $assign['spreadsheetUrl'] = DB::table('admins')->where('id', $adminId)->value('budget_spreadsheet_url');
        $assign['today'] = now()->format('Ymd');

        return view('office/budget/input', compact('assign'));
    }

    /**
     * 口座の選択肢を追加
     *
     * @return JsonResponse
     */
    public function accountCreateExecute(AccountCreateRequest $request)
    {
        return $this->createOption('budget_accounts', $request->validated()['name']);
    }

    /**
     * 科目の選択肢を追加
     *
     * @return JsonResponse
     */
    public function categoryCreateExecute(CategoryCreateRequest $request)
    {
        return $this->createOption('budget_categories', $request->validated()['name']);
    }

    /**
     * 家計簿の保存先スプレッドシートURLを更新
     *
     * @return JsonResponse
     */
    public function spreadsheetEditExecute(SpreadsheetEditRequest $request)
    {
        try {
            DB::table('admins')->where('id', Auth::id())->update([
                'budget_spreadsheet_url' => $request->validated()['url'] ?: null,
            ]);
        } catch (\Throwable $e) {
            Utils::log('error', 'スプレッドシートURL更新（処理） '.__METHOD__.'#'.__LINE__." >>> {$e}");

            return response()->json(['message' => '予期せぬエラーが発生しました。時間をおいて再度お試しください。'], 500);
        }

        return response()->json(['message' => '保存しました。']);
    }

    /**
     * 家計簿1件をスプレッドシートへ保存
     *
     * @return JsonResponse
     */
    public function createExecute(EntryCreateRequest $request)
    {
        $adminId = Auth::id();
        $input = $request->validated();

        $admin = DB::table('admins')->where('id', $adminId)->first(['budget_spreadsheet_url', 'google_service_account_json_base64']);
        if (! $admin->budget_spreadsheet_url) {
            return response()->json(['message' => 'スプレッドシートURLが設定されていません。先に設定してください。'], 422);
        }
        if (! $admin->google_service_account_json_base64) {
            return response()->json(['message' => 'Googleサービスアカウントが設定されていません。プロフィール編集から設定してください。'], 422);
        }

        $accountName = DB::table('budget_accounts')->where('id', $input['account_id'])->where('admin_id', $adminId)->value('name');
        $categoryName = DB::table('budget_categories')->where('id', $input['category_id'])->where('admin_id', $adminId)->value('name');

        try {
            GoogleSheetsBudgetService::appendEntry($admin->budget_spreadsheet_url, [
                Carbon::createFromFormat('Ymd', $input['occurred_on'])->format('Y/m/d'),
                $input['amount'],
                $accountName,
                $categoryName,
                $input['memo'] ?? '',
            ], $admin->google_service_account_json_base64);
        } catch (\Throwable $e) {
            Utils::log('error', '家計簿登録（処理） '.__METHOD__.'#'.__LINE__." >>> {$e}");

            return response()->json(['message' => 'スプレッドシートへの保存に失敗しました。共有設定・URLをご確認ください。'], 500);
        }

        return response()->json(['message' => '保存しました。']);
    }

    /**
     * 管理者がまだ選択肢を1件も持っていない場合、初期選択肢を登録する
     *
     * @param  string  $table
     * @param  int  $adminId
     * @param  array  $defaults
     * @return void
     */
    private function ensureDefaults($table, $adminId, array $defaults)
    {
        $exists = DB::table($table)->where('admin_id', $adminId)->whereNull('deleted_at')->exists();
        if ($exists) {
            return;
        }

        $insert = [];
        foreach ($defaults as $index => $name) {
            $insert[] = [
                'admin_id' => $adminId,
                'name' => $name,
                'display_order' => ($index + 1) * 10,
            ];
        }
        DB::table($table)->insert($insert);
    }

    /**
     * 管理者が持つ選択肢一覧を取得
     *
     * @param  string  $table
     * @param  int  $adminId
     * @return array
     */
    private function fetchOptions($table, $adminId)
    {
        return DB::table($table)
            ->where('admin_id', $adminId)
            ->whereNull('deleted_at')
            ->orderBy('display_order')
            ->orderBy('id')
            ->get(['id', 'name'])
            ->map(fn ($row) => ['id' => $row->id, 'name' => $row->name])
            ->values()
            ->all();
    }

    /**
     * 選択肢一覧から名称に一致するIDを取得（無ければ先頭のIDを返す）
     *
     * @param  array  $options
     * @param  string  $name
     * @return int|null
     */
    private function findIdByName(array $options, $name)
    {
        foreach ($options as $option) {
            if ($option['name'] === $name) {
                return $option['id'];
            }
        }

        return $options[0]['id'] ?? null;
    }

    /**
     * 選択肢を追加（口座・科目共通）
     *
     * @param  string  $table
     * @param  string  $name
     * @return JsonResponse
     */
    private function createOption($table, $name)
    {
        $adminId = Auth::id();
        $name = trim($name);

        $duplicate = DB::table($table)->where('admin_id', $adminId)->whereNull('deleted_at')->where('name', $name)->exists();
        if ($duplicate) {
            return response()->json(['message' => '既に登録されている選択肢です。'], 422);
        }

        try {
            $nextOrder = (int) (DB::table($table)->where('admin_id', $adminId)->whereNull('deleted_at')->max('display_order') ?? 0) + 10;

            $id = DB::table($table)->insertGetId([
                'admin_id' => $adminId,
                'name' => $name,
                'display_order' => $nextOrder,
            ]);
        } catch (\Throwable $e) {
            Utils::log('error', '選択肢追加（処理） '.__METHOD__.'#'.__LINE__." >>> {$e}");

            return response()->json(['message' => '予期せぬエラーが発生しました。時間をおいて再度お試しください。'], 500);
        }

        return response()->json(['message' => '追加しました。', 'option' => ['id' => $id, 'name' => $name]]);
    }
}
