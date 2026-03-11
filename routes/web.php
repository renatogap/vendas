<?php

use App\Http\Controllers\VendaController;
use Illuminate\Support\Facades\Route;

Route::get('/', [VendaController::class, 'index'])->name('vendas.index');
Route::get('/vendas/mes', [VendaController::class, 'vendasPorMes'])->name('vendas.por-mes');
Route::get('/vendas/clientes', [VendaController::class, 'nomesClientes'])->name('vendas.clientes');
Route::post('/vendas', [VendaController::class, 'store'])->name('vendas.store');
