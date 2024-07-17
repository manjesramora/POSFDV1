<?php

// app/Models/Freight.php
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
        'store'
    ];
}

    
