<?php

namespace App\Models\Hr;

use App\Traits\InactiveModel;
use Illuminate\Database\Eloquent\Model;

class Circular extends Model
{
    use InactiveModel;

    const CIRCULAR = 80;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = '0_circulars';

    /**
     * The attributes that are guarded from mass assigning.
     *
     * @var array
     */
    protected $guarded = [];

}
