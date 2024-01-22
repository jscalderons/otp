<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TwoFactor extends Model
{
    use HasFactory;

    const CREATED_AT = 'df_fecha_creacion';
    const UPDATED_AT = 'df_fecha_actualizacion';

    protected $table = 'public.doble_factor';
    protected $primaryKey = 'df_usuario';
    protected $fillable = [
        'df_usuario',
        'df_correo',
        'df_codigo',
        'df_intentos',
        'df_plataforma',
        'df_fecha_vencimiento',
    ];
}
