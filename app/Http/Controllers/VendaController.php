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
                'venda' => $this->formatVenda($venda),
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
            ->map(fn (Venda $venda) => $this->formatVenda($venda))
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

    public function update(Request $request, Venda $venda): RedirectResponse|JsonResponse
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

        $venda->fill($dados)->save();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Venda atualizada com sucesso.',
                'venda' => $this->formatVenda($venda->fresh()),
            ]);
        }

        return redirect()
            ->route('vendas.index')
            ->with('status', 'Venda atualizada com sucesso.');
    }

    public function pagamentoOpcoes(Venda $venda): JsonResponse
    {
        $queryNaoPagas = Venda::query()
            ->where('nome_cliente', $venda->nome_cliente)
            ->where('pago', false);

        $naoPagas = (clone $queryNaoPagas)->count();
        $valorTotalPendente = (clone $queryNaoPagas)->sum('valor_consumo');

        return response()->json([
            'venda_id' => $venda->id,
            'nome_cliente' => $venda->nome_cliente,
            'nao_pagas' => $naoPagas,
            'valor_total_pendente' => number_format((float) $valorTotalPendente, 2, ',', '.'),
            'valor_total_pendente_numero' => number_format((float) $valorTotalPendente, 2, '.', ''),
            'pode_pagar_todos' => $naoPagas > 1,
            'item_ja_pago' => (bool) $venda->pago,
        ]);
    }

    public function pagar(Request $request, Venda $venda): JsonResponse
    {
        $dados = $request->validate([
            'escopo' => ['required', 'in:item,todos'],
        ], [
            'escopo.required' => 'Informe como deseja pagar a venda.',
            'escopo.in' => 'Opcao de pagamento invalida.',
        ]);

        $agora = now();

        if ($dados['escopo'] === 'item') {
            if (!$venda->pago) {
                $venda->forceFill([
                    'pago' => true,
                    'pago_em' => $agora,
                ])->save();
            }

            $vendaAtualizada = $venda->fresh();

            return response()->json([
                'message' => 'Venda marcada como paga.',
                'escopo' => 'item',
                'venda' => $this->formatVenda($vendaAtualizada),
                'ids_atualizados' => [$vendaAtualizada->id],
            ]);
        }

        $ids = Venda::query()
            ->where('nome_cliente', $venda->nome_cliente)
            ->where('pago', false)
            ->pluck('id')
            ->values();

        if ($ids->isNotEmpty()) {
            Venda::query()
                ->whereIn('id', $ids)
                ->update([
                    'pago' => true,
                    'pago_em' => $agora,
                    'updated_at' => $agora,
                ]);
        }

        return response()->json([
            'message' => 'Todas as vendas da pessoa foram marcadas como pagas.',
            'escopo' => 'todos',
            'nome_cliente' => $venda->nome_cliente,
            'ids_atualizados' => $ids,
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

    private function formatVenda(Venda $venda): array
    {
        return [
            'id' => $venda->id,
            'nome_cliente' => $venda->nome_cliente,
            'mes_venda' => $venda->mes_venda,
            'valor_consumo' => number_format((float) $venda->valor_consumo, 2, ',', '.'),
            'valor_consumo_numero' => number_format((float) $venda->valor_consumo, 2, '.', ''),
            'vendido_em' => $venda->vendido_em->format('d/m/Y H:i'),
            'pago' => (bool) $venda->pago,
            'status' => $venda->pago ? 'Pago' : 'Em aberto',
        ];
    }
}
