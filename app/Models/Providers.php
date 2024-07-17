<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Providers extends Model
{
    use HasFactory;

    protected $table = 'CNCDIR';

    protected $fillable = [
        'CNCDIRID',
        'CNCDIRNOM'
    ];

    public function orders()
    {
        return $this->hasMany(Order::class, 'CNCDIRID');
    }
}
