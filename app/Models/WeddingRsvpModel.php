<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeddingRsvpModel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wedding_rsvps';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = ['id'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'companion_flag' => 'boolean',
            'arrival_date' => 'date',
            'departure_date' => 'date',
        ];
    }
}
