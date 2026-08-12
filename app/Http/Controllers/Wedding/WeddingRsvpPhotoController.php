<?php

namespace App\Http\Controllers\Wedding;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessWeddingRsvpPhoto;
use App\Libraries\Utils;
use App\Models\WeddingRsvpPhotoModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * 結婚式サイトの「お祝い画像」アップロード用エンドポイント（すべてAJAX）。
 *
 * フォーム送信とは切り離し、ファイル選択の時点で1枚ずつ非同期にアップロードする。
 * サーバー側は一時領域へ保存してジョブを投げるだけで即応答し、リサイズ・圧縮は
 * ProcessWeddingRsvpPhoto（Horizonのweddingキュー）が担当する。
 *
 * ログイン不要の公開エンドポイントのため、ブラウザが生成してlocalStorageに保持する
 * session_token を持つ相手だけが自分の画像を削除・復元できる。
 */
class WeddingRsvpPhotoController extends Controller
{
    /** 1枚あたりの最大サイズ（バイト） */
    public const MAX_FILE_SIZE = 20 * 1024 * 1024;

    /** 1つのブラウザ（session_token）が未送信のまま保持できる最大枚数 */
    public const MAX_FILES_PER_SESSION = 20;

    /**
     * 画像を1枚受け取り、一時領域へ保存してから変換ジョブを投げる
     */
    public function store(Request $request): JsonResponse
    {
        $sessionToken = (string) $request->input('session_token');
        if (! self::isValidToken($sessionToken)) {
            return response()->json(['error' => '不正なリクエストです。ページを再読み込みしてお試しください。'], 400);
        }

        $validator = validator($request->all(), [
            'photo' => [
                'bail',
                'required',
                'file',
                'mimetypes:'.implode(',', ProcessWeddingRsvpPhoto::ALLOWED_MIME),
                'max:'.(int) (self::MAX_FILE_SIZE / 1024),
            ],
        ], [
            'photo.required' => '画像が送信されていません。',
            'photo.file' => '画像の受け取りに失敗しました。もう一度お試しください。',
            'photo.mimetypes' => 'JPEG・PNG・WebP・GIF・HEIC形式の画像をお選びください。',
            'photo.max' => '1枚あたり20MBまでの画像をお選びください。',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first('photo')], 422);
        }

        $currentCount = (int) WeddingRsvpPhotoModel::getBy([
            'session_token' => $sessionToken,
            'unattached' => true,
            'method' => 'count',
        ]);
        if ($currentCount >= self::MAX_FILES_PER_SESSION) {
            return response()->json(['error' => 'お祝い画像は'.self::MAX_FILES_PER_SESSION.'枚までアップロードいただけます。'], 422);
        }

        $uploadedFile = $request->file('photo');
        $uuid = (string) Str::uuid();
        $stagingPath = 'staging/'.$uuid;

        try {
            $stored = Storage::disk('wedding_photos')->putFileAs('staging', $uploadedFile, $uuid);
            if ($stored === false) {
                throw new \RuntimeException('一時ファイルの保存に失敗しました。');
            }

            $photo = WeddingRsvpPhotoModel::create([
                'uuid' => $uuid,
                'session_token' => $sessionToken,
                'original_name' => mb_substr($uploadedFile->getClientOriginalName(), 0, 255),
                'mime_type' => $uploadedFile->getMimeType() ?: 'application/octet-stream',
                'size_bytes' => $uploadedFile->getSize() ?: null,
                'staging_path' => $stagingPath,
                'status' => 'pending',
            ]);

            ProcessWeddingRsvpPhoto::dispatch($photo->id);
        } catch (\Throwable $e) {
            Storage::disk('wedding_photos')->delete($stagingPath);
            Utils::log('error', '結婚式お祝い画像のアップロードに失敗 '.__METHOD__.'#'.__LINE__." >>> {$e}");

            return response()->json(['error' => 'アップロードに失敗しました。時間をおいて再度お試しください。'], 500);
        }

        return response()->json(['photo' => self::toArray($photo)], 201);
    }

    /**
     * localStorageに保持されている画像の現在の状態を返す（再訪時の復元・変換完了待ちのポーリングに使用）
     */
    public function status(Request $request): JsonResponse
    {
        $sessionToken = (string) $request->input('session_token');
        if (! self::isValidToken($sessionToken)) {
            return response()->json(['photos' => []]);
        }

        $uuids = $request->input('uuids', []);
        if (! is_array($uuids) || empty($uuids)) {
            return response()->json(['photos' => []]);
        }
        $uuids = array_values(array_filter(array_slice($uuids, 0, self::MAX_FILES_PER_SESSION), self::isValidToken(...)));
        if (empty($uuids)) {
            return response()->json(['photos' => []]);
        }

        $photos = WeddingRsvpPhotoModel::getBy([
            'uuids' => $uuids,
            'session_token' => $sessionToken,
            'unattached' => true,
        ]);

        return response()->json(['photos' => $photos->map(self::toArray(...))->all()]);
    }

    /**
     * 画像を配信する。変換完了前はアップロードされた原本をそのまま返し、プレビューを途切れさせない。
     */
    public function show(string $uuid): BinaryFileResponse
    {
        abort_unless(self::isValidToken($uuid), 404);

        /** @var WeddingRsvpPhotoModel|null $photo */
        $photo = WeddingRsvpPhotoModel::getBy(['uuid' => $uuid, 'method' => 'first']);
        abort_if(! $photo || $photo->status === 'failed', 404);

        $disk = Storage::disk('wedding_photos');
        $path = $photo->stored_path ?: $photo->staging_path;
        abort_if(! $path || ! $disk->exists($path), 404);

        return response()->file($disk->path($path), [
            'Content-Type' => $photo->stored_path ? $photo->mime_type : ($disk->mimeType($path) ?: 'application/octet-stream'),
            'Content-Disposition' => 'inline; filename="'.addslashes($photo->original_name).'"',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    /**
     * 未送信の画像を削除する（送信済み＝回答に紐づいた画像は削除させない）
     */
    public function destroy(Request $request, string $uuid): JsonResponse
    {
        $sessionToken = (string) $request->input('session_token');
        if (! self::isValidToken($uuid) || ! self::isValidToken($sessionToken)) {
            return response()->json(['error' => '不正なリクエストです。'], 400);
        }

        /** @var WeddingRsvpPhotoModel|null $photo */
        $photo = WeddingRsvpPhotoModel::getBy([
            'uuid' => $uuid,
            'session_token' => $sessionToken,
            'unattached' => true,
            'method' => 'first',
        ]);

        // 既に消えている場合もクライアント側の表示は消したいので成功として返す
        if (! $photo) {
            return response()->json(['success' => true]);
        }

        try {
            self::deleteFiles($photo);
            $photo->delete();
        } catch (\Throwable $e) {
            Utils::log('error', '結婚式お祝い画像の削除に失敗 '.__METHOD__.'#'.__LINE__." >>> {$e}");

            return response()->json(['error' => '削除に失敗しました。時間をおいて再度お試しください。'], 500);
        }

        return response()->json(['success' => true]);
    }

    /**
     * 画像の実ファイル（変換前・変換後）をstorageから削除する
     */
    public static function deleteFiles(WeddingRsvpPhotoModel $photo): void
    {
        $disk = Storage::disk('wedding_photos');
        foreach ([$photo->stored_path, $photo->staging_path] as $path) {
            if ($path) {
                $disk->delete($path);
            }
        }
    }

    /**
     * uuid・トークンの形式チェック（パストラバーサル・不正値の混入防止）
     */
    private static function isValidToken(mixed $value): bool
    {
        return is_string($value) && preg_match('/^[0-9a-fA-F-]{36}$/', $value) === 1;
    }

    /**
     * クライアントへ返す画像情報
     *
     * @return array<string, mixed>
     */
    private static function toArray(WeddingRsvpPhotoModel $photo): array
    {
        return [
            'uuid' => $photo->uuid,
            'original_name' => $photo->original_name,
            'size_bytes' => (int) $photo->size_bytes,
            'status' => $photo->status,
            'url' => route('weddingRsvpPhotoShow', ['uuid' => $photo->uuid]),
        ];
    }
}
