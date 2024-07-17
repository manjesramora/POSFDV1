<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    // Nombre de la tabla en la base de datos
    protected $table = 'employees';

    // Los atributos que se pueden asignar de forma masiva (en el método store del controlador)
    // Campos que pueden ser asignados masivamente
    protected $fillable = [
        'first_name', 'last_name', 'middle_name', 'curp', 'rfc', 'colony',
        'street', 'external_number', 'internal_number', 'postal_code',
        'phone', 'phone2', 'birth', 'status'
    ];

    // Establecer el valor predeterminado del atributo status
    protected $attributes = [
        'status' => 1,
    ];

    // Método para formatear la fecha de nacimiento
    public function getBirthAttribute($value)
    {
        return date('d-m-Y', strtotime($value));
    }

    // Método para formatear la fecha antes de guardarla
    public function setBirthAttribute($value)
    {
        $this->attributes['birth'] = date('Y-m-d', strtotime($value));
    }

    // Otros métodos, relaciones, accesores, mutadores, etc., pueden ir aquí
}
