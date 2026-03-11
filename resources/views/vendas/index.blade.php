@extends('layouts.app')

@section('title', 'Registro de Vendas')

@section('content')

    <section class="list-card reveal delay-2">
        <div id="sale-success" class="alert-success hidden" role="status" aria-live="polite"></div>

        <div class="list-header">
            <h2>Vendas do Adalberto</h2>
            <span id="sales-count">{{ $vendas->count() }} registros</span>
        </div>

        <div class="filter-form">
            <label>
                <input type="month" id="sales-month-filter" value="{{ $mesAtual }}">
            </label>

            <label>
                <div class="filter-input-wrap">
                    <input type="text" id="sales-filter-input" placeholder="Busque pelo Nome do cliente">
                    <button type="button" id="clear-filter-input" class="clear-icon-btn hidden" aria-label="Limpar pesquisa">X</button>
                </div>
            </label>
        </div>

        <p class="total-sales" id="sales-total">Total: R$ 0,00</p>
        <div id="sales-list">

            @forelse ($vendas as $venda)
                <article
                    class="sale-item"
                    data-name="{{ \Illuminate\Support\Str::lower($venda->nome_cliente) }}"
                    data-month="{{ $venda->mes_venda }}"
                    data-amount="{{ number_format((float) $venda->valor_consumo, 2, '.', '') }}"
                >
                    <div>
                        <p class="client-name">{{ $venda->nome_cliente }}</p>
                        <p class="month">Mes {{ $venda->mes_venda }}</p>
                    </div>
                    <div class="sale-meta">
                        <p class="price">R$ {{ number_format((float) $venda->valor_consumo, 2, ',', '.') }}</p>
                        <p class="datetime">{{ $venda->vendido_em->format('d/m/Y H:i') }}</p>
                    </div>
                </article>
            @empty
                <p class="empty-state" id="empty-sales-message">Nenhuma venda registrada ainda.</p>
            @endforelse
        </div>

        <p class="empty-state hidden" id="empty-filter-message">Nenhuma venda encontrada para esse filtro.</p>
    </section>

    <div class="modal-backdrop" id="sale-modal-backdrop" hidden>
        <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="sale-modal-title">
            <div class="modal-head">
                <h3 id="sale-modal-title">Registro de venda</h3>
                <button type="button" id="close-sale-modal" class="modal-close" aria-label="Fechar janela">X</button>
            </div>

            <form action="{{ route('vendas.store') }}" method="POST" class="sale-form" id="sale-form">
                @csrf
                <input type="hidden" id="sale-mes-venda" name="mes_venda" value="{{ $mesAtual }}">

                <label>
                    Nome da pessoa
                    <div class="autocomplete-wrap">
                        <input
                            type="text"
                            id="sale-nome-cliente"
                            name="nome_cliente"
                            placeholder="Ex: Maria Oliveira"
                            maxlength="120"
                            autocomplete="off"
                            required
                        >
                        <div id="sale-nome-sugestoes" class="autocomplete-list hidden" role="listbox"></div>
                    </div>
                </label>

                <label>
                    Valor do consumo (R$)
                    <input
                        type="text"
                        id="sale-valor-consumo"
                        name="valor_consumo"
                        placeholder="0,00"
                        inputmode="decimal"
                        autocomplete="off"
                        required
                    >
                </label>

                <div id="sale-form-errors" class="alert-error hidden"></div>
                <button type="submit" id="sale-submit-btn">Salvar venda</button>
            </form>
        </div>
    </div>

    <button type="button" id="open-sale-modal" class="primary-action floating-sale-button">Nova venda</button>

    <script>
        const openModalBtn = document.getElementById('open-sale-modal');
        const closeModalBtn = document.getElementById('close-sale-modal');
        const saleModal = document.getElementById('sale-modal-backdrop');
        const saleForm = document.getElementById('sale-form');
        const saleFormErrors = document.getElementById('sale-form-errors');
        const saleSuccess = document.getElementById('sale-success');
        const salesList = document.getElementById('sales-list');
        const salesCount = document.getElementById('sales-count');
        const salesTotal = document.getElementById('sales-total');
        const monthFilter = document.getElementById('sales-month-filter');
        const modalMonthInput = document.getElementById('sale-mes-venda');
        const saleNomeClienteInput = document.getElementById('sale-nome-cliente');
        const saleNomeSugestoes = document.getElementById('sale-nome-sugestoes');
        const saleValorConsumoInput = document.getElementById('sale-valor-consumo');
        const filterInput = document.getElementById('sales-filter-input');
        const clearFilterBtn = document.getElementById('clear-filter-input');
        const emptySalesMessage = document.getElementById('empty-sales-message');
        const emptyFilterMessage = document.getElementById('empty-filter-message');
        const salesByMonthUrl = "{{ route('vendas.por-mes') }}";
        const salesClientesUrl = "{{ route('vendas.clientes') }}";
        let autocompleteController = null;

        const toggleModal = (show) => {
            saleModal.hidden = !show;
            document.body.classList.toggle('modal-open', show);

            if (show) {
                saleFormErrors.classList.add('hidden');
                saleFormErrors.innerHTML = '';
            }
        };

        const formatCurrencyFromDigits = (digits) => {
            const number = Number.parseInt(digits || '0', 10) / 100;
            return number.toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
        };

        const normalizeCurrencyToBackend = (formattedValue) => {
            const normalized = formattedValue
                .replace(/\./g, '')
                .replace(',', '.')
                .trim();

            return normalized;
        };

        const hideNameSuggestions = () => {
            saleNomeSugestoes.classList.add('hidden');
            saleNomeSugestoes.innerHTML = '';
        };

        const showNameSuggestions = (nomes) => {
            if (!nomes.length) {
                hideNameSuggestions();
                return;
            }

            saleNomeSugestoes.innerHTML = nomes
                .map((nome) => `<button type="button" class="autocomplete-item">${nome}</button>`)
                .join('');
            saleNomeSugestoes.classList.remove('hidden');
        };

        const fetchNameSuggestions = async () => {
            const termo = saleNomeClienteInput.value.trim();

            if (termo.length < 2) {
                hideNameSuggestions();
                return;
            }

            if (autocompleteController) {
                autocompleteController.abort();
            }

            autocompleteController = new AbortController();

            try {
                const url = new URL(salesClientesUrl, window.location.origin);
                url.searchParams.set('termo', termo);

                const response = await fetch(url.toString(), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    signal: autocompleteController.signal,
                });

                if (!response.ok) {
                    hideNameSuggestions();
                    return;
                }

                const data = await response.json();
                showNameSuggestions(data.nomes || []);
            } catch (error) {
                if (error.name !== 'AbortError') {
                    hideNameSuggestions();
                }
            }
        };

        const updateVisibleCount = () => {
            const visibleItems = salesList.querySelectorAll('.sale-item:not(.hidden)').length;
            const totalItems = salesList.querySelectorAll('.sale-item').length;
            const totalValue = Array.from(salesList.querySelectorAll('.sale-item:not(.hidden)')).reduce((acc, item) => {
                const amount = Number.parseFloat(item.dataset.amount || '0');
                return acc + (Number.isNaN(amount) ? 0 : amount);
            }, 0);

            salesCount.textContent = `${visibleItems} registros`;
            salesTotal.textContent = `Total das vendas: R$ ${totalValue.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

            if (totalItems === 0) {
                if (emptySalesMessage) {
                    emptySalesMessage.classList.remove('hidden');
                }
                emptyFilterMessage.classList.add('hidden');
                return;
            }

            if (emptySalesMessage) {
                emptySalesMessage.classList.add('hidden');
            }

            emptyFilterMessage.classList.toggle('hidden', visibleItems > 0);
        };

        const applyClientFilter = () => {
            const term = filterInput.value.trim().toLowerCase();
            const selectedMonth = monthFilter.value;
            const items = salesList.querySelectorAll('.sale-item');

            items.forEach((item) => {
                const name = item.dataset.name || '';
                const month = item.dataset.month || '';
                const matchesName = term === '' || name.includes(term);
                const matchesMonth = selectedMonth === '' || month === selectedMonth;

                item.classList.toggle('hidden', !(matchesName && matchesMonth));
            });

            clearFilterBtn.classList.toggle('hidden', term === '');
            modalMonthInput.value = selectedMonth;
            updateVisibleCount();
        };

        const prependSaleItem = (venda) => {
            const item = document.createElement('article');
            item.className = 'sale-item';
            item.dataset.name = venda.nome_cliente.toLowerCase();
            item.dataset.month = venda.mes_venda;
            item.dataset.amount = venda.valor_consumo_numero;
            item.innerHTML = `
                <div>
                    <p class="client-name">${venda.nome_cliente}</p>
                    <p class="month">Mes ${venda.mes_venda}</p>
                </div>
                <div class="sale-meta">
                    <p class="price">R$ ${venda.valor_consumo}</p>
                    <p class="datetime">${venda.vendido_em}</p>
                </div>
            `;

            salesList.prepend(item);
        };

        const renderSalesList = (vendas) => {
            salesList.innerHTML = '';

            vendas.forEach((venda) => {
                prependSaleItem(venda);
            });

            if (vendas.length === 0) {
                const empty = document.createElement('p');
                empty.className = 'empty-state';
                empty.id = 'empty-sales-message';
                empty.textContent = 'Nenhuma venda registrada ainda.';
                salesList.append(empty);
            }
        };

        const fetchSalesByMonth = async () => {
            const selectedMonth = monthFilter.value;
            modalMonthInput.value = selectedMonth;

            try {
                const url = new URL(salesByMonthUrl, window.location.origin);
                url.searchParams.set('mes', selectedMonth);

                const response = await fetch(url.toString(), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    return;
                }

                const data = await response.json();
                renderSalesList(data.vendas || []);
                applyClientFilter();
            } catch (error) {
                // Keep current list if request fails.
            }
        };

        openModalBtn.addEventListener('click', () => toggleModal(true));
        closeModalBtn.addEventListener('click', () => toggleModal(false));

        saleNomeClienteInput.addEventListener('input', fetchNameSuggestions);
        saleNomeClienteInput.addEventListener('blur', () => {
            setTimeout(hideNameSuggestions, 120);
        });
        saleNomeClienteInput.addEventListener('focus', fetchNameSuggestions);

        saleNomeSugestoes.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof HTMLButtonElement)) {
                return;
            }

            saleNomeClienteInput.value = target.textContent || '';
            hideNameSuggestions();
        });

        saleValorConsumoInput.addEventListener('input', () => {
            const digits = saleValorConsumoInput.value.replace(/\D/g, '');
            saleValorConsumoInput.value = formatCurrencyFromDigits(digits);
        });

        saleModal.addEventListener('click', (event) => {
            if (event.target === saleModal) {
                toggleModal(false);
            }
        });

        filterInput.addEventListener('input', applyClientFilter);
        monthFilter.addEventListener('change', fetchSalesByMonth);

        clearFilterBtn.addEventListener('click', () => {
            filterInput.value = '';
            applyClientFilter();
            filterInput.focus();
        });

        saleForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            saleFormErrors.classList.add('hidden');
            saleFormErrors.innerHTML = '';

            try {
                const valorOriginal = saleValorConsumoInput.value;
                saleValorConsumoInput.value = normalizeCurrencyToBackend(valorOriginal);

                const response = await fetch(saleForm.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: new FormData(saleForm),
                });

                saleValorConsumoInput.value = valorOriginal;

                const data = await response.json();

                if (!response.ok) {
                    const erros = data.errors ? Object.values(data.errors).flat() : ['Nao foi possivel salvar a venda.'];
                    saleFormErrors.innerHTML = erros.map((erro) => `<p>${erro}</p>`).join('');
                    saleFormErrors.classList.remove('hidden');
                    return;
                }

                prependSaleItem(data.venda);
                applyClientFilter();

                saleSuccess.textContent = data.message;
                saleSuccess.classList.remove('hidden');

                toggleModal(false);
                saleForm.reset();
                hideNameSuggestions();
                setTimeout(() => saleSuccess.classList.add('hidden'), 3500);
            } catch (error) {
                saleValorConsumoInput.value = formatCurrencyFromDigits(saleValorConsumoInput.value.replace(/\D/g, ''));
                saleFormErrors.innerHTML = '<p>Erro de comunicacao ao salvar venda.</p>';
                saleFormErrors.classList.remove('hidden');
            }
        });

        applyClientFilter();
    </script>
@endsection
