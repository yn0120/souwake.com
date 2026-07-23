<?php

namespace App\Http\Controllers\Office;

use App\Enums\PasswordItemType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Office\PasswordManager\EntryCreateRequest;
use App\Http\Requests\Office\PasswordManager\EntryEditRequest;
use App\Http\Requests\Office\PasswordManager\ItemRequest;
use App\Libraries\Utils;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OfficePasswordManagerController extends Controller
{
    /**
     * パスワード管理一覧（初期表示。データはJS側から /list を非同期取得する）
     *
     * @return View
     */
    public function index(Request $request)
    {
        $assign['itemTypes'] = PasswordItemType::toArray();

        return view('office/password-manager/index', compact('assign'));
    }

    /**
     * パスワード管理一覧（非同期検索・ソート）
     *
     * @return JsonResponse
     */
    public function list(Request $request)
    {
        $query = DB::table('password_entries')
            ->where('admin_id', Auth::id())
            ->whereNull('deleted_at');

        // サイト名のLIKE検索
        if ($request->filled('name')) {
            $query->where('name', 'like', '%'.$request->name.'%');
        }

        // 項目（ラベル・値）のLIKE検索（パスワード型は暗号化されているため検索対象外）
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->whereExists(function ($sub) use ($keyword) {
                $sub->selectRaw('1')
                    ->from('password_entry_items')
                    ->whereColumn('password_entry_items.password_entry_id', 'password_entries.id')
                    ->whereNull('password_entry_items.deleted_at')
                    ->where('password_entry_items.type', '!=', PasswordItemType::PASSWORD->value)
                    ->where(function ($q) use ($keyword) {
                        $q->where('password_entry_items.label', 'like', "%{$keyword}%")
                            ->orWhere('password_entry_items.value', 'like', "%{$keyword}%");
                    });
            });
        }

        // ソート
        $sortColumn = in_array($request->get('sort'), ['name', 'display_order', 'created_at'], true) ? $request->get('sort') : 'display_order';
        $direction = $request->get('direction') === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sortColumn, $direction)->orderBy('id', 'asc');

        // 該当サイトを取得（ページャーなし・全件）
        $entries = $query->get();

        // サイトIDに紐づく項目をまとめて取得（N+1回避）
        $entryIds = $entries->pluck('id')->all();
        $items = DB::table('password_entry_items')
            ->whereIn('password_entry_id', $entryIds)
            ->whereNull('deleted_at')
            ->orderBy('display_order', 'asc')
            ->orderBy('id', 'asc')
            ->get()
            ->groupBy('password_entry_id');

        $records = $entries->map(function ($entry) use ($items) {
            return [
                'id' => $entry->id,
                'name' => $entry->name,
                'display_order' => (int) $entry->display_order,
                'created_at' => $entry->created_at,
                'items' => $items->get($entry->id, collect())->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'label' => $item->label,
                        'type' => $item->type,
                        'value' => self::decryptValue($item->type, $item->value),
                        'display_order' => (int) $item->display_order,
                    ];
                })->values(),
            ];
        })->values();

        return response()->json(['records' => $records]);
    }

    /**
     * サイト登録（項目を同時に登録。空の項目行はスキップする）
     *
     * @return JsonResponse
     */
    public function createExecute(EntryCreateRequest $request)
    {
        $input = $request->validated();

        // 空の項目行（ラベル・値がともに未入力）はinsertをスキップ
        $items = collect($input['items'] ?? [])
            ->filter(fn ($item) => trim($item['label'] ?? '') !== '' || trim($item['value'] ?? '') !== '')
            ->values();

        try {
            DB::beginTransaction();

            $nextOrder = (int) (DB::table('password_entries')->where('admin_id', Auth::id())->whereNull('deleted_at')->max('display_order') ?? 0) + 10;

            $entryId = DB::table('password_entries')->insertGetId([
                'admin_id' => Auth::id(),
                'name' => $input['name'],
                'display_order' => $nextOrder,
            ]);

            $insertItems = [];
            foreach ($items as $index => $item) {
                $type = PasswordItemType::tryFrom($item['type'] ?? '') ?? PasswordItemType::TEXT;
                $insertItems[] = [
                    'password_entry_id' => $entryId,
                    'label' => trim($item['label'] ?? ''),
                    'type' => $type->value,
                    'value' => self::encryptValue($type, $item['value'] ?? null),
                    'display_order' => ($index + 1) * 10,
                ];
            }
            if ($insertItems) {
                DB::table('password_entry_items')->insert($insertItems);
            }

            DB::commit();
        } catch (QueryException $e) {
            DB::rollBack();
            $params = implode(', ', $e->getBindings());
            Utils::log('error', 'サイト登録（処理） '.__METHOD__.'#'.__LINE__."\nSQL: {$e->getSql()}\nParams: {$params}\n{$e}");

            return response()->json(['message' => 'データベースエラーが発生しました。時間をおいて再度お試しください。'], 500);
        } catch (\Throwable $e) {
            DB::rollBack();
            Utils::log('error', 'サイト登録（処理） '.__METHOD__.'#'.__LINE__." >>> {$e}");

            return response()->json(['message' => '予期せぬエラーが発生しました。時間をおいて再度お試しください。'], 500);
        }

        return response()->json(['message' => '登録しました。', 'id' => $entryId]);
    }

    /**
     * サイト更新（サイト名・表示順）
     *
     * @return JsonResponse
     */
    public function editExecute(EntryEditRequest $request, $id)
    {
        $entry = $this->findOwnedEntry($id);
        if (! $entry) {
            return response()->json(['message' => 'サイトが存在しません。'], 404);
        }

        $input = $request->validated();

        try {
            DB::table('password_entries')->where('id', $id)->update([
                'name' => $input['name'],
                'display_order' => $input['display_order'] ?? $entry->display_order,
            ]);
        } catch (\Throwable $e) {
            Utils::log('error', 'サイト更新（処理） '.__METHOD__.'#'.__LINE__." >>> {$e}");

            return response()->json(['message' => '予期せぬエラーが発生しました。時間をおいて再度お試しください。'], 500);
        }

        return response()->json(['message' => '更新しました。']);
    }

    /**
     * サイト削除（論理削除。配下の項目も同時に論理削除）
     *
     * @return JsonResponse
     */
    public function deleteExecute(Request $request, $id)
    {
        $entry = $this->findOwnedEntry($id);
        if (! $entry) {
            return response()->json(['message' => 'サイトが存在しません。'], 404);
        }

        try {
            DB::beginTransaction();

            $now = now();
            DB::table('password_entries')->where('id', $id)->update(['deleted_at' => $now]);
            DB::table('password_entry_items')->where('password_entry_id', $id)->whereNull('deleted_at')->update(['deleted_at' => $now]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Utils::log('error', 'サイト削除（処理） '.__METHOD__.'#'.__LINE__." >>> {$e}");

            return response()->json(['message' => '予期せぬエラーが発生しました。時間をおいて再度お試しください。'], 500);
        }

        return response()->json(['message' => '削除しました。']);
    }

    /**
     * 項目追加
     *
     * @return JsonResponse
     */
    public function itemCreateExecute(ItemRequest $request, $id)
    {
        $entry = $this->findOwnedEntry($id);
        if (! $entry) {
            return response()->json(['message' => 'サイトが存在しません。'], 404);
        }

        $input = $request->validated();
        $type = PasswordItemType::tryFrom($input['type']);

        try {
            $nextOrder = (int) (DB::table('password_entry_items')->where('password_entry_id', $id)->whereNull('deleted_at')->max('display_order') ?? 0) + 10;

            $itemId = DB::table('password_entry_items')->insertGetId([
                'password_entry_id' => $id,
                'label' => $input['label'],
                'type' => $type->value,
                'value' => self::encryptValue($type, $input['value'] ?? null),
                'display_order' => $nextOrder,
            ]);
        } catch (\Throwable $e) {
            Utils::log('error', '項目追加（処理） '.__METHOD__.'#'.__LINE__." >>> {$e}");

            return response()->json(['message' => '予期せぬエラーが発生しました。時間をおいて再度お試しください。'], 500);
        }

        return response()->json([
            'message' => '追加しました。',
            'item' => [
                'id' => $itemId,
                'label' => $input['label'],
                'type' => $type->value,
                'value' => $input['value'] ?? null,
                'display_order' => $nextOrder,
            ],
        ]);
    }

    /**
     * 項目更新
     *
     * @return JsonResponse
     */
    public function itemEditExecute(ItemRequest $request, $id, $itemId)
    {
        $entry = $this->findOwnedEntry($id);
        if (! $entry) {
            return response()->json(['message' => 'サイトが存在しません。'], 404);
        }

        $item = DB::table('password_entry_items')->where('id', $itemId)->where('password_entry_id', $id)->whereNull('deleted_at')->first();
        if (! $item) {
            return response()->json(['message' => '項目が存在しません。'], 404);
        }

        $input = $request->validated();
        $type = PasswordItemType::tryFrom($input['type']);

        try {
            DB::table('password_entry_items')->where('id', $itemId)->update([
                'label' => $input['label'],
                'type' => $type->value,
                'value' => self::encryptValue($type, $input['value'] ?? null),
                'display_order' => $input['display_order'] ?? $item->display_order,
            ]);
        } catch (\Throwable $e) {
            Utils::log('error', '項目更新（処理） '.__METHOD__.'#'.__LINE__." >>> {$e}");

            return response()->json(['message' => '予期せぬエラーが発生しました。時間をおいて再度お試しください。'], 500);
        }

        return response()->json(['message' => '更新しました。']);
    }

    /**
     * 項目削除（論理削除）
     *
     * @return JsonResponse
     */
    public function itemDeleteExecute(Request $request, $id, $itemId)
    {
        $entry = $this->findOwnedEntry($id);
        if (! $entry) {
            return response()->json(['message' => 'サイトが存在しません。'], 404);
        }

        $item = DB::table('password_entry_items')->where('id', $itemId)->where('password_entry_id', $id)->whereNull('deleted_at')->first();
        if (! $item) {
            return response()->json(['message' => '項目が存在しません。'], 404);
        }

        try {
            DB::table('password_entry_items')->where('id', $itemId)->update(['deleted_at' => now()]);
        } catch (\Throwable $e) {
            Utils::log('error', '項目削除（処理） '.__METHOD__.'#'.__LINE__." >>> {$e}");

            return response()->json(['message' => '予期せぬエラーが発生しました。時間をおいて再度お試しください。'], 500);
        }

        return response()->json(['message' => '削除しました。']);
    }

    /**
     * ログイン中の管理者が所有するサイトを取得
     *
     * @param  int  $id
     * @return object|null
     */
    private function findOwnedEntry($id)
    {
        return DB::table('password_entries')->where('id', $id)->where('admin_id', Auth::id())->whereNull('deleted_at')->first();
    }

    /**
     * 保存用に値を加工する（パスワード型のみ暗号化）
     *
     * @param  PasswordItemType  $type
     * @param  string|null  $value
     * @return string|null
     */
    private static function encryptValue(PasswordItemType $type, $value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $type === PasswordItemType::PASSWORD ? Crypt::encryptString($value) : $value;
    }

    /**
     * 表示用に値を復号する（パスワード型のみ復号。失敗時はnullを返しログに残す）
     *
     * @param  string  $type
     * @param  string|null  $value
     * @return string|null
     */
    private static function decryptValue($type, $value)
    {
        if ($value === null) {
            return null;
        }

        if ($type !== PasswordItemType::PASSWORD->value) {
            return $value;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable $e) {
            Utils::log('error', 'パスワード項目の復号に失敗 '.__METHOD__.'#'.__LINE__." >>> {$e}");

            return null;
        }
    }
}
