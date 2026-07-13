<?php

namespace App\Http\Controllers\Office;

use App\Enums\PerPage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Office\Role\CreateRequest;
use App\Http\Requests\Office\Role\EditRequest;
use App\Libraries\Utils;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class OfficeRolesController extends Controller
{
    /**
     * 権限付与（入力）
     *
     * @param  Request               $request
     * @return \Illuminate\View\View
     */
    public function roleRouteEditInput()
    {
        // フォームで使ったセッションを削除（入力途中でやめた場合を考慮）
        session()->forget(['createInputRole', 'insertRole', 'updateInputRole', 'updateRole']);

        // RouteNames
        $assign['routes'] = DB::table('routes')->whereNull('deleted_at')->get();

        // 権限
        $assign['roles'] = DB::table('roles')->whereNull('deleted_at')->get();

        // 付与状況
        $assign['role_routes'] = DB::table('role_route')->whereNull('deleted_at')->get();

        // 権限付与状況を配列で整理
        $permissions = [];
        foreach ($assign['role_routes'] as $roleRoute) {
            $permissions[$roleRoute->role_id][$roleRoute->route_id] = $roleRoute->is_allowed;
        }

        // 各ルートと権限の組み合わせに対する許可状態を取得
        $assign['routePermissions'] = [];
        foreach ($assign['routes'] as $route) {
            foreach ($assign['roles'] as $role) {
                $assign['routePermissions'][$route->id][$role->id] = isset($permissions[$role->id][$route->id])
                    ? $permissions[$role->id][$route->id]
                    : 0; // デフォルトは未許可（0）
            }
        }

        return view('office/roles/routes/edit/input', compact('assign'));
    }

    /**
     * 権限一覧
     *
     * @param  Request               $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // フォームで使ったセッションを削除（入力途中でやめた場合を考慮）
        session()->forget(['createInputRole', 'insertRole', 'updateInputRole', 'updateRole']);

        // 表示件数
        $assign['perPages'] = PerPage::toArray();

        // 権限テーブル（roles）を参照し有効なレコード（deleted_at IS NULL）ID昇順でソートし表示する。
        $builder = DB::table('roles')
            ->selectRaw('roles.id')
            ->selectRaw('roles.name')
            ->selectRaw('roles.note')
            ->selectRaw('(SELECT COUNT(*) FROM admins WHERE admins.role_id = roles.id AND admins.deleted_at IS NULL) AS inuse')
            ->whereNull('roles.deleted_at')
            ->orderByRaw('roles.id ASC');

        // 表示件数を取得
        $assign['per_page'] = Utils::perPage($request->get('per_page', PerPage::FIFTY->getLabel()));

        // ページネーションを設定
        $assign['records'] = $builder->paginate($assign['per_page']);

        // 戻るボタン用に検索条件を保管
        session(['officeRolesIndexSearchParams' => $request->all()]);

        return view('office/roles/index', compact('assign'));
    }

    /**
     * 権限詳細
     *
     * @param  Request               $request
     * @param  int                   $id
     * @return \Illuminate\View\View
     */
    public function show(Request $request, $id)
    {
        // フォームで使ったセッションを削除（入力途中でやめた場合を考慮）
        session()->forget(['createInputRole', 'insertRole', 'updateInputRole', 'updateRole']);

        // 権限取得(URIのidを基に、権限テーブル（roles）を参照し有効なレコード(deleted_at IS NULL)を表示する。)
        $assign['record'] = DB::table('roles')
        ->selectRaw('id')
        ->selectRaw('name')
        ->selectRaw('note')
        ->where('id', $id)
        ->whereNull('deleted_at')
        ->first();
        if (! $assign['record']) {
            return redirect()->route('officeRoleIndex', session('officeRolesIndexSearchParams'))->with('error', '権限が存在しません。');
        }

        return view('office/roles/show', compact('assign'));
    }

    /**
     * 権限登録（入力）
     *
     * @param  Request               $request
     * @return \Illuminate\View\View
     */
    public function createInput(Request $request)
    {
        $assign = [];

        return view('office/roles/create/input');
    }

    /**
     * 権限登録（確認）
     *
     * @param  CreateRequest         $request
     * @return \Illuminate\View\View
     */
    public function createConfirm(CreateRequest $request)
    {
        $input = $request->validated();

        foreach ($input as $key => $value) {
            switch ($key) {
                default:
                    $assign['confirm'][$key] = $value ?? '未設定';
                    $insert[$key] = $value;
                    break;
            }
        }

        session(['createInputRole' => $input, 'insertRole' => $insert]);

        return view('office/roles/create/confirm', compact('assign'));
    }

    /**
     * 権限登録（処理）
     *
     * @param  Request                           $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public static function createExecute(Request $request)
    {
        // 書き直し処理
        if ($request->back) {
            return redirect()->route('officeRoleCreateInput')->withInput(session('createInputRole'));
        }

        $request->session()->regenerateToken();

        // insert用入力値
        $insert = session('insertRole');

        try {
            DB::beginTransaction();

            // 登録
            $newId = DB::table('roles')->insertGetId($insert);

            DB::commit();
        } catch (QueryException $e) {
            DB::rollBack();
            $params = implode(', ', $e->getBindings());
            Utils::log('error', '権限登録（処理） '.__METHOD__.'#'.__LINE__."\nSQL: {$e->getSql()}\nParams: {$params}\n{$e}");

            return redirect()->route('officeRoleCreateInput')->withInput(session('createInputRole'))->with('error', 'データベースエラーが発生しました。時間をおいて再度お試しください。');
        } catch (\Throwable $e) {
            DB::rollBack();
            Utils::log('error', '権限登録（処理） '.__METHOD__.'#'.__LINE__." >>> {$e}");

            return redirect()->route('officeRoleCreateInput')->withInput(session('createInputRole'))->with('error', '予期せぬエラーが発生しました。時間をおいて再度お試しください。');
        }

        return redirect()->route('officeRoleCreateComplete');
    }

    /**
     * 権限登録（完了）
     *
     * @param  Request               $request
     * @return \Illuminate\View\View
     */
    public function createComplete(Request $request)
    {
        // フォームで使ったセッションを削除（入力途中でやめた場合を考慮）
        session()->forget(['createInputRole', 'insertRole', 'updateInputRole', 'updateRole']);

        $assign = [];

        return view('office/roles/create/complete', compact('assign'));
    }

    /**
     * 権限編集（入力）
     *
     * @param  Request               $request
     * @param  int                   $id
     * @return \Illuminate\View\View
     */
    public function editInput(Request $request, $id)
    {
        // 権限取得
        $assign['record'] = DB::table('roles')
            ->selectRaw('id')
            ->selectRaw('name')
            ->selectRaw('note')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first();
        if (! $assign['record']) {
            return redirect()->route('officeRoleIndex', session('officeRolesIndexSearchParams'))->with('error', '権限が存在しません。');
        }

        return view('office/roles/edit/input', compact('assign'));
    }

    /**
     * 権限編集（確認）
     *
     * @param  EditRequest           $request
     * @param  int                   $id
     * @return \Illuminate\View\View
     */
    public function editConfirm(EditRequest $request, $id)
    {
        $input = $request->validated();

        // 権限取得
        $assign['record'] = DB::table('roles')->where('id', $id)->whereNull('deleted_at')->first();
        if (! $assign['record']) {
            return redirect()->route('officeRoleIndex', session('officeRolesIndexSearchParams'))->with('error', '権限が存在しません。');
        }

        foreach ($input as $key => $value) {
            switch ($key) {
                default:
                    $assign['confirm'][$key] = $value ?? '未設定';
                    $update[$key] = $value;
                    break;
            }
        }

        session(['updateInputRole' => $input, 'updateRole' => $update]);

        return view('office/roles/edit/confirm', compact('assign'));
    }

    /**
     * 権限編集（処理）
     *
     * @param  Request                           $request
     * @param  int                               $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public static function editExecute(Request $request, $id)
    {
        // 書き直し処理
        if ($request->back) {
            return redirect()->route('officeRoleEditInput', ['id' => $id])->withInput(session('updateInputRole'));
        }

        // 権限取得
        $record = DB::table('roles')->where('id', $id)->whereNull('deleted_at')->first();
        if (! $record) {
            return redirect()->route('officeRoleIndex', session('officeRolesIndexSearchParams'))->with('error', '権限が存在しません。');
        }

        $request->session()->regenerateToken();

        // update用入力値
        $update = session('updateRole');

        try {
            DB::beginTransaction();

            // 更新
            DB::table('roles')->where('id', $id)->whereNull('deleted_at')->update($update);

            DB::commit();
        } catch (QueryException $e) {
            DB::rollBack();
            $params = implode(', ', $e->getBindings());
            Utils::log('error', '権限編集（処理） '.__METHOD__.'#'.__LINE__."\nSQL: {$e->getSql()}\nParams: {$params}\n{$e}");

            return redirect()->route('officeRoleEditInput', ['id' => $id])->withInput(session('updateInputRole'))->with('error', 'データベースエラーが発生しました。時間をおいて再度お試しください。');
        } catch (\Throwable $e) {
            DB::rollBack();
            Utils::log('error', '権限編集（処理） '.__METHOD__.'#'.__LINE__." >>> {$e}");

            return redirect()->route('officeRoleEditInput', ['id' => $id])->withInput(session('updateInputRole'))->with('error', '予期せぬエラーが発生しました。時間をおいて再度お試しください。');
        }

        return redirect()->route('officeRoleEditComplete', ['id' => $id]);
    }

    /**
     * 権限編集（完了）
     *
     * @param  Request               $request
     * @param  int                   $id
     * @return \Illuminate\View\View
     */
    public function editComplete(Request $request, $id)
    {
        // フォームで使ったセッションを削除（入力途中でやめた場合を考慮）
        session()->forget(['createInputRole', 'insertRole', 'updateInputRole', 'updateRole']);

        $assign['id'] = $id;

        return view('office/roles/edit/complete', compact('assign'));
    }

    /**
     * 権限削除（確認）
     *
     * @param  Request                           $request
     * @param  int                               $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteExecute(Request $request, $id)
    {
        $request->session()->regenerateToken();

        // 対象権限を持つ管理者レコード（admins.role_id = {role.id} AND admins.deleted_at IS NULL）が1件以上である場合削除不可
        if (DB::table('admins')->where('role_id', $id)->whereNull('deleted_at')->exists()) {
            return redirect()->route('officeRoleIndex', session('officeRolesIndexSearchParams'))->with('error', '管理者が権限を使用中です。');
        }

        try {
            DB::beginTransaction();

            // 削除
            DB::table('roles')->where('id', $id)->update(['deleted_at' => Carbon::now()]);

            DB::commit();
        } catch (QueryException $e) {
            DB::rollBack();
            $params = implode(', ', $e->getBindings());
            Utils::log('error', '権限削除（確認） '.__METHOD__.'#'.__LINE__."\nSQL: {$e->getSql()}\nParams: {$params}\n{$e}");

            return redirect()->route('officeRoleIndex', session('officeRolesIndexSearchParams'))->with('error', 'データベースエラーが発生しました。時間をおいて再度お試しください。');
        } catch (\Throwable $e) {
            DB::rollBack();
            Utils::log('error', '権限削除（確認） '.__METHOD__.'#'.__LINE__." >>> {$e}");

            return redirect()->route('officeRoleIndex', session('officeRolesIndexSearchParams'))->with('error', '予期せぬエラーが発生しました。時間をおいて再度お試しください。');
        }

        return redirect()->route('officeRoleIndex', session('officeRolesIndexSearchParams'))->with('success', '削除しました。');
    }

    /**
     * 権限付与（処理）
     *
     * @param  Request                       $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function roleRouteEditExecute(Request $request)
    {
        if (! $request->input('role_id') || ! $request->input('route_id')) {
            return response(json_encode(['code' => 422, 'msg' => '入力値が正しくありません。', 'data' => []], JSON_UNESCAPED_UNICODE));
        }

        try {
            DB::beginTransaction();

            // 更新
            DB::table('role_route')
                ->where('role_id', intval($request->input('role_id')))
                ->where('route_id', intval($request->input('route_id')))
                ->update(['is_allowed' => intval($request->input('is_allowed'))]);

            DB::commit();
        } catch (QueryException $e) {
            DB::rollBack();
            $params = implode(', ', $e->getBindings());
            Utils::log('error', '権限付与（処理） '.__METHOD__.'#'.__LINE__."\nSQL: {$e->getSql()}\nParams: {$params}\n{$e}");

            return response(json_encode(['code' => 500, 'msg' => 'データベースエラーが発生しました。時間をおいて再度お試しください。', 'data' => []], JSON_UNESCAPED_UNICODE));
        } catch (\Throwable $e) {
            DB::rollBack();
            Utils::log('error', '権限付与（処理） '.__METHOD__.'#'.__LINE__." >>> {$e}");

            return response(json_encode(['code' => 500, 'msg' => '予期せぬエラーが発生しました。時間をおいて再度お試しください。', 'data' => []], JSON_UNESCAPED_UNICODE));
        }

        return response(json_encode(['code' => 200, 'msg' => '更新しました。', 'data' => []], JSON_UNESCAPED_UNICODE));
    }
}
