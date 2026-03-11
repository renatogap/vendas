<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venda extends Model
{
    protected $fillable = [
        'mes_venda',
        'nome_cliente',
        'valor_consumo',
        'vendido_em',
    ];

    protected $casts = [
        'valor_consumo' => 'decimal:2',
        'vendido_em' => 'datetime',
    ];
}
