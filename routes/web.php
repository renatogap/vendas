<?php

use App\Http\Controllers\VendaController;
use Illuminate\Support\Facades\Route;

Route::get('/', [VendaController::class, 'index'])->name('vendas.index');
Route::get('/vendas/mes', [VendaController::class, 'vendasPorMes'])->name('vendas.por-mes');
Route::get('/vendas/clientes', [VendaController::class, 'nomesClientes'])->name('vendas.clientes');
Route::get('/vendas/{venda}/pagamento-opcoes', [VendaController::class, 'pagamentoOpcoes'])->name('vendas.pagamento-opcoes');
Route::post('/vendas', [VendaController::class, 'store'])->name('vendas.store');
Route::put('/vendas/{venda}', [VendaController::class, 'update'])->name('vendas.update');
Route::post('/vendas/{venda}/pagar', [VendaController::class, 'pagar'])->name('vendas.pagar');
Route::delete('/vendas/{venda}', [VendaController::class, 'destroy'])->name('vendas.destroy');
