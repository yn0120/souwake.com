<?php

namespace App\Http\Controllers\Office;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OfficeTopController extends Controller
{
    /**
     * 管理者用トップページ
     *
     * @param  Request               $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $assign = [];

        return view('office/index', compact('assign'));
    }

    /**
     * 管理者用エラーページ
     *
     * @param  Request               $request
     * @param  int                   $code
     * @return \Illuminate\View\View
     */
    public function error(Request $request, $code = 500)
    {
        dd(123);
        $assign['code'] = $code;
        switch ($assign['code']) {
            case 404:
                $assign['msg'] = 'ページが見つかりません。';
                break;

            default:
                $assign['msg'] = '予期せぬエラーが発生しました。時間をおいて再度お試しください。';
                break;
        }

        return view('office/errors/index', compact('assign'));
    }
}
