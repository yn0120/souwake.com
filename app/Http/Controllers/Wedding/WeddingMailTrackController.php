<?php

namespace App\Http\Controllers\Wedding;

use App\Http\Controllers\Controller;
use App\Models\MailTrackModel;
use Illuminate\Http\Response;

class WeddingMailTrackController extends Controller
{
    /**
     * メール開封トラッキング（処理）
     *
     * @param  string  $token
     * @return Response
     */
    public function show($token)
    {
        MailTrackModel::markOpened($token);

        // 1x1の透明なGIF画像を返す
        return response(base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'))
            ->header('Content-Type', 'image/gif')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }
}
