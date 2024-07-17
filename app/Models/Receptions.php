<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Receptions extends Model
{
    protected $table = 'acmvor1';
    protected $primaryKey = 'ACMVOIDOC';
    public $incrementing = false;
    public $timestamps = false;

    // Define any necessary relationships here
}
