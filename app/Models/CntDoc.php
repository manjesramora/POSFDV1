<?php

// Archivo: app/Models/CntDoc.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CntDoc extends Model
{
    protected $table = 'cntdoc';
    protected $primaryKey = 'CNTDOCID';
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = ['CNTDOCID', 'CNTDOCNSIG'];
}
