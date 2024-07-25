<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class INFCCN extends Model
{
    use HasFactory;

    protected $table = 'INFCCN';

    protected $fillable = [
        'INPRODID', 'INUMINID', 'INUMFNID', 'INFCCNQTI', 'INFCCNQTF'
    ];
}
