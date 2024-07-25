<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Freight extends Model
{
    use HasFactory;

    protected $table = 'Freights';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'document_type',
        'document_number',
        'document_type1',
        'document_number1',
        'cost',
        'supplier_number',
        'carrier_number',
        'reception_date',
        'reference',
        'reference_type',
        'carrier_name',
        'supplier_name',
        'store',
        'freight'
    ];

    public function getFreightPercentageAttribute()
    {
        // Calcular el total general (cost + freight) para todos los registros
        $totalCost = Freight::sum('cost');
        $totalFreight = Freight::sum('freight');
        $totalGeneral = $totalCost + $totalFreight;

        // Calcular el porcentaje de flete en relación con el total general
        return $totalGeneral > 0 ? ($this->freight / $totalGeneral) * 100 : 0;
    }
}

