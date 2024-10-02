<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssortmentSheet extends Model
{
    // Definimos el nombre de la tabla
    protected $table = 'AVMVOR';

    // Establecemos que la clave primaria no es auto incrementable
    public $incrementing = false;

    // Deshabilitamos las marcas de tiempo
    public $timestamps = false;

    // Definimos la clave primaria compuesta
    protected $primaryKey = ['CNCIASID', 'CNTDOCID', 'AVMVORDOC'];

    // Definimos los campos que vamos a usar en nuestro controlador
    protected $fillable = [
        'INALMNID',     // Almacén
        'CNCIASID',     // Compañía
        'CNTDOCID',     // Tipo de documento
        'AVMVORDOC',    // Número de documento
        'AVMVORFSPC',    // Fecha
        'AVMVORCXC'     // Numero de cliente
    ];

    // Sobrescribimos el método para que funcione con claves compuestas
    protected function setKeysForSaveQuery($query)
    {
        $keys = $this->getKeyName();
        if (!is_array($keys)) {
            return parent::setKeysForSaveQuery($query);
        }

        foreach ($keys as $keyName) {
            $query->where($keyName, '=', $this->getKeyForSaveQuery($keyName));
        }

        return $query;
    }

    // Este método se asegura de obtener el valor correcto para cada clave
    protected function getKeyForSaveQuery($keyName = null)
    {
        if (is_null($keyName)) {
            $keyName = $this->getKeyName();
        }

        return $this->getAttribute($keyName);
    }
}
