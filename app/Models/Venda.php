<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venda extends Model
{
    protected $fillable = [
        'mes_venda',
        'nome_cliente',
        'valor_consumo',
        'pago',
        'pago_em',
        'vendido_em',
    ];

    protected $casts = [
        'valor_consumo' => 'decimal:2',
        'pago' => 'boolean',
        'pago_em' => 'datetime',
        'vendido_em' => 'datetime',
    ];
}
