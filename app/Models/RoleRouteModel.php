<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleRouteModel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'role_route';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = ['id'];
}
