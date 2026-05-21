<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Parametre extends Model
{
    protected $primaryKey = 'cle';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = ['cle', 'valeur', 'description'];

    public static function get(string $cle, mixed $defaut = null): mixed
    {
        return static::find($cle)?->valeur ?? $defaut;
    }

    public static function set(string $cle, mixed $valeur): void
    {
        static::updateOrCreate(['cle' => $cle], ['valeur' => (string) $valeur]);
    }
}
