<?php

namespace App\Models;
use App\Models\Providers; // Importa la clase Provider aquí
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'ACMVOR';

    protected $fillable = [
        'CNCDIRID',
        'CNTDOCID',
        'ACMVOIDOC',
        'ACMVOIALID',
        'ACMVOIFDOC',
        'ACMVOIULIN',
        'ACMVOIPENT',
    ];
    public function store()
    {
        return $this->belongsTo(StoreCostCenter::class, 'ACMVOIALID', 'id');
    }
    public function provider()
    {
        return $this->belongsTo(Providers::class, 'CNCDIRID', 'CNCDIRID'); // Especifica la clave primaria de Provider
    }
}