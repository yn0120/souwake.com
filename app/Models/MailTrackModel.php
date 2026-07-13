<?php

namespace App\Models;

use App\Libraries\Utils;
use Illuminate\Database\Eloquent\Model;

class MailTrackModel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'mail_tracks';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = ['id'];

    /**
     * tokenカラムの一意なトークンを返す
     *
     * @return string
     */
    public static function makeUniqueToken()
    {
        do {
            $token = Utils::makeRandomStr(4, 256);
        } while (static::where('token', $token)->exists());

        return $token;
    }
}
