<?php

namespace App\Http\Controllers;

use App\Models\Venda;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendaController extends Controller
{
    public function index(): View
    {
        return view('vendas.index', [
            'vendas' => Venda::query()->latest('vendido_em')->latest('id')->get(),
            'mesAtual' => now()->format('Y-m'),
        ]);
    }

    public function store(Request $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $dados = $request->validate([
            'mes_venda' => ['required', 'date_format:Y-m'],
            'nome_cliente' => ['required', 'string', 'max:120'],
            'valor_consumo' => ['required', 'numeric', 'min:0.01'],
        ], [
            'mes_venda.required' => 'Informe o mes da venda.',
            'mes_venda.date_format' => 'Use o formato correto do mes (AAAA-MM).',
            'nome_cliente.required' => 'Informe o nome da pessoa.',
            'valor_consumo.required' => 'Informe o valor do consumo.',
            'valor_consumo.numeric' => 'Digite um valor numerico valido.',
        ]);

        $venda = Venda::create([
            ...$dados,
            'vendido_em' => now(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Venda registrada com sucesso.',
                'venda' => [
                    'id' => $venda->id,
                    'nome_cliente' => $venda->nome_cliente,
                    'mes_venda' => $venda->mes_venda,
                    'valor_consumo' => number_format((float) $venda->valor_consumo, 2, ',', '.'),
                    'valor_consumo_numero' => number_format((float) $venda->valor_consumo, 2, '.', ''),
                    'vendido_em' => $venda->vendido_em->format('d/m/Y H:i'),
                ],
            ], 201);
        }

        return redirect()
            ->route('vendas.index')
            ->with('status', 'Venda registrada com sucesso.');
    }

    public function vendasPorMes(Request $request): JsonResponse
    {
        $mes = trim((string) $request->query('mes', ''));

        $vendas = Venda::query()
            ->when($mes !== '', function ($query) use ($mes) {
                $query->where('mes_venda', $mes);
            })
            ->latest('vendido_em')
            ->latest('id')
            ->get()
            ->map(function (Venda $venda) {
                return [
                    'id' => $venda->id,
                    'nome_cliente' => $venda->nome_cliente,
                    'mes_venda' => $venda->mes_venda,
                    'valor_consumo' => number_format((float) $venda->valor_consumo, 2, ',', '.'),
                    'valor_consumo_numero' => number_format((float) $venda->valor_consumo, 2, '.', ''),
                    'vendido_em' => $venda->vendido_em->format('d/m/Y H:i'),
                ];
            })
            ->values();

        return response()->json([
            'vendas' => $vendas,
        ]);
    }

    public function nomesClientes(Request $request): JsonResponse
    {
        $termo = trim((string) $request->query('termo', ''));

        if ($termo === '' || mb_strlen($termo) < 2) {
            return response()->json(['nomes' => []]);
        }

        $nomes = Venda::query()
            ->select('nome_cliente')
            ->where('nome_cliente', 'like', "%{$termo}%")
            ->distinct()
            ->orderBy('nome_cliente')
            ->limit(8)
            ->pluck('nome_cliente')
            ->values();

        return response()->json([
            'nomes' => $nomes,
        ]);
    }

    public function destroy(Request $request, Venda $venda): RedirectResponse|JsonResponse
    {
        $id = $venda->id;
        $venda->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Venda removida com sucesso.',
                'id' => $id,
            ]);
        }

        return redirect()
            ->route('vendas.index')
            ->with('status', 'Venda removida com sucesso.');
    }
}
