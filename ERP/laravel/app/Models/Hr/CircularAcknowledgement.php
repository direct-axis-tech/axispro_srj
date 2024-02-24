<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;

class CircularAcknowledgement extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = '0_circular_acknowledgement_details';

    /**
     * The attributes that are guarded from mass assigning.
     *
     * @var array
     */
    protected $guarded = [];

}
