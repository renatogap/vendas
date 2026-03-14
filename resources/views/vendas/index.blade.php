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

            <fieldset class="status-filter-group" aria-label="Filtrar status de pagamento">
                <legend>Status</legend>
                <label class="status-option">
                    <input type="radio" name="sales-status-filter" value="all" checked>
                    <span>Todos</span>
                </label>
                <label class="status-option">
                    <input type="radio" name="sales-status-filter" value="pending">
                    <span>Em aberto</span>
                </label>
                <label class="status-option">
                    <input type="radio" name="sales-status-filter" value="paid">
                    <span>Pagos</span>
                </label>
            </fieldset>

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
                    data-id="{{ $venda->id }}"
                    data-name="{{ \Illuminate\Support\Str::lower($venda->nome_cliente) }}"
                    data-month="{{ $venda->mes_venda }}"
                    data-amount="{{ number_format((float) $venda->valor_consumo, 2, '.', '') }}"
                    data-paid="{{ $venda->pago ? '1' : '0' }}"
                >
                    <div>
                        <p class="client-name">{{ $venda->nome_cliente }}</p>
                        <div class="sale-meta-row">
                            <p class="month">Mes {{ $venda->mes_venda }}</p>
                            @if ($venda->pago)
                                <p class="sale-status paid">
                                    <span class="material-symbols-rounded" aria-hidden="true">check_circle</span>
                                    Pago
                                </p>
                            @endif
                        </div>
                    </div>
                    <div class="sale-meta">
                        <p class="price">R$ {{ number_format((float) $venda->valor_consumo, 2, ',', '.') }}</p>
                        <p class="datetime">{{ $venda->vendido_em->format('d/m/Y H:i') }}</p>
                        <div class="sale-actions">
                            <button type="button" class="sale-action-btn {{ $venda->pago ? 'disabled' : '' }}" data-action="pay" data-sale-id="{{ $venda->id }}" aria-label="Marcar pagamento" {{ $venda->pago ? 'disabled' : '' }}>
                                <span class="material-symbols-rounded" aria-hidden="true">payments</span>
                            </button>
                            <button type="button" class="sale-action-btn delete" data-action="delete" data-sale-id="{{ $venda->id }}" aria-label="Remover venda">
                                <span class="material-symbols-rounded" aria-hidden="true">delete</span>
                            </button>
                            <button type="button" class="sale-action-btn" data-action="edit" data-sale-id="{{ $venda->id }}" aria-label="Editar venda">
                                <span class="material-symbols-rounded" aria-hidden="true">edit</span>
                            </button>
                        </div>
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

    <div class="modal-backdrop" id="payment-modal-backdrop" hidden>
        <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="payment-modal-title">
            <div class="modal-head">
                <h3 id="payment-modal-title">Confirmar pagamento</h3>
                <button type="button" id="close-payment-modal" class="modal-close" aria-label="Fechar janela">X</button>
            </div>

            <p id="payment-modal-text" class="payment-modal-text"></p>
            <div id="payment-modal-error" class="alert-error hidden"></div>
            <div class="payment-actions">
                <button type="button" id="pay-selected-btn">Pagar somente este item</button>
                <button type="button" id="pay-all-btn" class="secondary-btn">Pagar todos desta pessoa</button>
            </div>
        </div>
    </div>

    <button type="button" id="open-sale-modal" class="primary-action floating-sale-button">Nova venda</button>

    <script>
        const openModalBtn = document.getElementById('open-sale-modal');
        const closeModalBtn = document.getElementById('close-sale-modal');
        const saleModal = document.getElementById('sale-modal-backdrop');
        const saleForm = document.getElementById('sale-form');
        const saleFormErrors = document.getElementById('sale-form-errors');
        const saleModalTitle = document.getElementById('sale-modal-title');
        const saleSubmitBtn = document.getElementById('sale-submit-btn');
        const saleSuccess = document.getElementById('sale-success');
        const salesList = document.getElementById('sales-list');
        const salesCount = document.getElementById('sales-count');
        const salesTotal = document.getElementById('sales-total');
        const monthFilter = document.getElementById('sales-month-filter');
        const statusFilters = document.querySelectorAll('input[name="sales-status-filter"]');
        const modalMonthInput = document.getElementById('sale-mes-venda');
        const saleNomeClienteInput = document.getElementById('sale-nome-cliente');
        const saleNomeSugestoes = document.getElementById('sale-nome-sugestoes');
        const saleValorConsumoInput = document.getElementById('sale-valor-consumo');
        const filterInput = document.getElementById('sales-filter-input');
        const clearFilterBtn = document.getElementById('clear-filter-input');
        const emptySalesMessage = document.getElementById('empty-sales-message');
        const emptyFilterMessage = document.getElementById('empty-filter-message');
        const paymentModal = document.getElementById('payment-modal-backdrop');
        const closePaymentModalBtn = document.getElementById('close-payment-modal');
        const paymentModalText = document.getElementById('payment-modal-text');
        const paymentModalError = document.getElementById('payment-modal-error');
        const paySelectedBtn = document.getElementById('pay-selected-btn');
        const payAllBtn = document.getElementById('pay-all-btn');
        const salesByMonthUrl = "{{ route('vendas.por-mes') }}";
        const salesClientesUrl = "{{ route('vendas.clientes') }}";
        const salesStoreUrl = "{{ route('vendas.store') }}";
        const salesDeleteBaseUrl = "{{ url('/vendas') }}";
        const salesPagamentoOpcoesBaseUrl = "{{ url('/vendas') }}";
        const csrfToken = "{{ csrf_token() }}";
        let autocompleteController = null;
        let saleFormMode = 'create';
        let editingSaleId = null;
        let paymentTargetSaleId = null;
        let paymentCanPayAll = false;

        const toggleModal = (show) => {
            saleModal.hidden = !show;
            document.body.classList.toggle('modal-open', show || !paymentModal.hidden);

            if (show) {
                saleFormErrors.classList.add('hidden');
                saleFormErrors.innerHTML = '';
            }
        };

        const togglePaymentModal = (show) => {
            paymentModal.hidden = !show;
            document.body.classList.toggle('modal-open', show || !saleModal.hidden);

            if (show) {
                paymentModalError.classList.add('hidden');
                paymentModalError.innerHTML = '';
            }
        };

        const openCreateModal = () => {
            saleFormMode = 'create';
            editingSaleId = null;
            saleModalTitle.textContent = 'Registro de venda';
            saleSubmitBtn.textContent = 'Salvar venda';
            saleForm.reset();
            saleValorConsumoInput.value = '';
            modalMonthInput.value = monthFilter.value;
            hideNameSuggestions();
            toggleModal(true);
        };

        const openEditModal = (saleItem) => {
            saleFormMode = 'edit';
            editingSaleId = saleItem.dataset.id;
            saleModalTitle.textContent = 'Editar venda';
            saleSubmitBtn.textContent = 'Alterar venda';
            saleFormErrors.classList.add('hidden');
            saleFormErrors.innerHTML = '';
            modalMonthInput.value = saleItem.dataset.month || monthFilter.value;
            saleNomeClienteInput.value = saleItem.querySelector('.client-name')?.textContent?.trim() || '';

            const amount = Number.parseFloat(saleItem.dataset.amount || '0');
            saleValorConsumoInput.value = amount.toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });

            hideNameSuggestions();
            toggleModal(true);
        };

        const setSaleItemPaidState = (saleItem, paid) => {
            saleItem.dataset.paid = paid ? '1' : '0';
            const metaRow = saleItem.querySelector('.sale-meta-row');
            if (!metaRow) {
                return;
            }

            const status = saleItem.querySelector('.sale-status');
            if (paid) {
                if (!status) {
                    const badge = document.createElement('p');
                    badge.className = 'sale-status paid';
                    badge.innerHTML = '<span class="material-symbols-rounded" aria-hidden="true">check_circle</span>Pago';
                    metaRow.append(badge);
                }
            } else if (status) {
                status.remove();
            }

            const payBtn = saleItem.querySelector('.sale-action-btn[data-action="pay"]');
            if (payBtn instanceof HTMLButtonElement) {
                payBtn.disabled = paid;
                payBtn.classList.toggle('disabled', paid);
            }
        };

        const findSaleItemById = (saleId) => {
            return salesList.querySelector(`.sale-item[data-id="${saleId}"]`);
        };

        const updateSaleItemElement = (saleItem, venda) => {
            saleItem.dataset.id = String(venda.id);
            saleItem.dataset.name = venda.nome_cliente.toLowerCase();
            saleItem.dataset.month = venda.mes_venda;
            saleItem.dataset.amount = venda.valor_consumo_numero;

            const clientName = saleItem.querySelector('.client-name');
            const month = saleItem.querySelector('.month');
            const price = saleItem.querySelector('.price');
            const datetime = saleItem.querySelector('.datetime');

            if (clientName) {
                clientName.textContent = venda.nome_cliente;
            }

            if (month) {
                month.textContent = `Mes ${venda.mes_venda}`;
            }

            if (price) {
                price.textContent = `R$ ${venda.valor_consumo}`;
            }

            if (datetime) {
                datetime.textContent = venda.vendido_em;
            }

            setSaleItemPaidState(saleItem, Boolean(venda.pago));
        };

        const showSuccessMessage = (message) => {
            saleSuccess.textContent = message;
            saleSuccess.classList.remove('hidden');
            setTimeout(() => saleSuccess.classList.add('hidden'), 3500);
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
            const selectedStatus = Array.from(statusFilters).find((input) => input.checked)?.value || 'all';
            const items = salesList.querySelectorAll('.sale-item');

            items.forEach((item) => {
                const name = item.dataset.name || '';
                const month = item.dataset.month || '';
                const paid = item.dataset.paid === '1';
                const matchesName = term === '' || name.includes(term);
                const matchesMonth = selectedMonth === '' || month === selectedMonth;
                const matchesStatus = selectedStatus === 'all'
                    || (selectedStatus === 'paid' && paid)
                    || (selectedStatus === 'pending' && !paid);

                item.classList.toggle('hidden', !(matchesName && matchesMonth && matchesStatus));
            });

            clearFilterBtn.classList.toggle('hidden', term === '');
            modalMonthInput.value = selectedMonth;
            updateVisibleCount();
        };

        const escapeHtml = (text) => {
            return text
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        };

        const createSaleItemElement = (venda) => {
            const item = document.createElement('article');
            item.className = 'sale-item';
            item.dataset.id = String(venda.id);
            item.dataset.name = venda.nome_cliente.toLowerCase();
            item.dataset.month = venda.mes_venda;
            item.dataset.amount = venda.valor_consumo_numero;
            item.dataset.paid = venda.pago ? '1' : '0';

            const isPaid = Boolean(venda.pago);
            item.innerHTML = `
                <div>
                    <p class="client-name">${escapeHtml(venda.nome_cliente)}</p>
                    <div class="sale-meta-row">
                        <p class="month">Mes ${venda.mes_venda}</p>
                        ${isPaid ? '<p class="sale-status paid"><span class="material-symbols-rounded" aria-hidden="true">check_circle</span>Pago</p>' : ''}
                    </div>
                </div>
                <div class="sale-meta">
                    <p class="price">R$ ${venda.valor_consumo}</p>
                    <p class="datetime">${venda.vendido_em}</p>
                    <div class="sale-actions">
                        <button type="button" class="sale-action-btn ${isPaid ? 'disabled' : ''}" data-action="pay" data-sale-id="${venda.id}" aria-label="Marcar pagamento" ${isPaid ? 'disabled' : ''}>
                            <span class="material-symbols-rounded" aria-hidden="true">payments</span>
                        </button>
                        <button type="button" class="sale-action-btn delete" data-action="delete" data-sale-id="${venda.id}" aria-label="Remover venda">
                            <span class="material-symbols-rounded" aria-hidden="true">delete</span>
                        </button>
                        <button type="button" class="sale-action-btn" data-action="edit" data-sale-id="${venda.id}" aria-label="Editar venda">
                            <span class="material-symbols-rounded" aria-hidden="true">edit</span>
                        </button>
                    </div>
                </div>
            `;

            return item;
        };

        const prependSaleItem = (venda) => {
            salesList.prepend(createSaleItemElement(venda));
        };

        const renderSalesList = (vendas) => {
            salesList.innerHTML = '';

            vendas.forEach((venda) => {
                salesList.append(createSaleItemElement(venda));
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

        const loadPaymentOptions = async (saleId) => {
            try {
                const response = await fetch(`${salesPagamentoOpcoesBaseUrl}/${saleId}/pagamento-opcoes`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    paymentModalError.innerHTML = '<p>Nao foi possivel consultar opcoes de pagamento.</p>';
                    paymentModalError.classList.remove('hidden');
                    paySelectedBtn.disabled = true;
                    payAllBtn.disabled = true;
                    return;
                }

                const data = await response.json();
                paymentCanPayAll = Boolean(data.pode_pagar_todos);

                if (data.item_ja_pago) {
                    paymentModalText.textContent = `Esta venda de ${data.nome_cliente} ja esta marcada como paga.`;
                    paySelectedBtn.hidden = true;
                    payAllBtn.hidden = true;
                    return;
                }

                paySelectedBtn.hidden = false;
                payAllBtn.hidden = !paymentCanPayAll;
                paySelectedBtn.disabled = false;
                payAllBtn.disabled = false;

                if (data.nao_pagas > 1) {
                    paymentModalText.textContent = `${data.nome_cliente} possui ${data.nao_pagas} consumos em aberto, totalizando R$ ${data.valor_total_pendente}. Deseja pagar somente o item selecionado ou todos?`;
                    return;
                }

                paymentModalText.textContent = `${data.nome_cliente} possui apenas este consumo em aberto. Deseja marcar este item como pago?`;
            } catch (error) {
                paymentModalError.innerHTML = '<p>Erro de comunicacao ao consultar pagamento.</p>';
                paymentModalError.classList.remove('hidden');
                paySelectedBtn.disabled = true;
                payAllBtn.disabled = true;
            }
        };

        const pagarVenda = async (escopo) => {
            if (!paymentTargetSaleId) {
                return;
            }

            paySelectedBtn.disabled = true;
            payAllBtn.disabled = true;
            paymentModalError.classList.add('hidden');
            paymentModalError.innerHTML = '';

            try {
                const body = new FormData();
                body.append('escopo', escopo);

                const response = await fetch(`${salesPagamentoOpcoesBaseUrl}/${paymentTargetSaleId}/pagar`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body,
                });

                const data = await response.json();

                if (!response.ok) {
                    const erros = data.errors ? Object.values(data.errors).flat() : ['Nao foi possivel registrar o pagamento.'];
                    paymentModalError.innerHTML = erros.map((erro) => `<p>${erro}</p>`).join('');
                    paymentModalError.classList.remove('hidden');
                    paySelectedBtn.disabled = false;
                    payAllBtn.disabled = !paymentCanPayAll;
                    return;
                }

                const idsAtualizados = Array.isArray(data.ids_atualizados) ? data.ids_atualizados : [];
                idsAtualizados.forEach((id) => {
                    const item = findSaleItemById(String(id));
                    if (item) {
                        setSaleItemPaidState(item, true);
                    }
                });

                togglePaymentModal(false);
                showSuccessMessage(data.message || 'Pagamento atualizado com sucesso.');
            } catch (error) {
                paymentModalError.innerHTML = '<p>Erro de comunicacao ao registrar pagamento.</p>';
                paymentModalError.classList.remove('hidden');
                paySelectedBtn.disabled = false;
                payAllBtn.disabled = !paymentCanPayAll;
            }
        };

        openModalBtn.addEventListener('click', openCreateModal);
        closeModalBtn.addEventListener('click', () => toggleModal(false));
        closePaymentModalBtn.addEventListener('click', () => togglePaymentModal(false));

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

        paymentModal.addEventListener('click', (event) => {
            if (event.target === paymentModal) {
                togglePaymentModal(false);
            }
        });

        salesList.addEventListener('click', async (event) => {
            const target = event.target;
            if (!(target instanceof HTMLElement)) {
                return;
            }

            const saleItem = target.closest('.sale-item');
            if (!saleItem) {
                return;
            }

            const button = target.closest('.sale-action-btn');
            if (!button) {
                const isOpen = saleItem.classList.contains('show-actions');
                salesList.querySelectorAll('.sale-item.show-actions').forEach((item) => {
                    item.classList.remove('show-actions');
                });

                if (!isOpen) {
                    saleItem.classList.add('show-actions');
                }

                return;
            }

            const action = button.getAttribute('data-action');

            const saleId = button.getAttribute('data-sale-id');
            if (!saleId) {
                return;
            }

            if (action === 'edit') {
                openEditModal(saleItem);
                return;
            }

            if (action === 'pay') {
                paymentTargetSaleId = saleId;
                paymentCanPayAll = false;
                paySelectedBtn.hidden = false;
                payAllBtn.hidden = false;
                paymentModalText.textContent = 'Carregando opcoes de pagamento...';
                togglePaymentModal(true);
                await loadPaymentOptions(saleId);
                return;
            }

            if (action !== 'delete') {
                return;
            }

            const confirmed = window.confirm('Deseja remover esta venda?');
            if (!confirmed) {
                return;
            }

            try {
                const response = await fetch(`${salesDeleteBaseUrl}/${saleId}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                });

                if (!response.ok) {
                    return;
                }

                const saleItem = button.closest('.sale-item');
                if (saleItem) {
                    saleItem.remove();
                    applyClientFilter();
                }

                showSuccessMessage('Venda removida com sucesso.');
            } catch (error) {
                // Keep UI unchanged if deletion fails.
            }
        });

        filterInput.addEventListener('input', applyClientFilter);
        monthFilter.addEventListener('change', fetchSalesByMonth);
        statusFilters.forEach((input) => {
            input.addEventListener('change', applyClientFilter);
        });

        clearFilterBtn.addEventListener('click', () => {
            filterInput.value = '';
            applyClientFilter();
            filterInput.focus();
        });

        paySelectedBtn.addEventListener('click', () => {
            pagarVenda('item');
        });

        payAllBtn.addEventListener('click', () => {
            pagarVenda('todos');
        });

        saleForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            saleFormErrors.classList.add('hidden');
            saleFormErrors.innerHTML = '';

            try {
                const valorOriginal = saleValorConsumoInput.value;
                saleValorConsumoInput.value = normalizeCurrencyToBackend(valorOriginal);

                const requestUrl = saleFormMode === 'edit' && editingSaleId
                    ? `${salesDeleteBaseUrl}/${editingSaleId}`
                    : salesStoreUrl;

                const requestMethod = saleFormMode === 'edit' ? 'PUT' : 'POST';

                const response = await fetch(requestUrl, {
                    method: requestMethod,
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
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

                if (saleFormMode === 'edit' && editingSaleId) {
                    const saleItem = findSaleItemById(editingSaleId);
                    if (saleItem) {
                        updateSaleItemElement(saleItem, data.venda);
                    }
                } else {
                    prependSaleItem(data.venda);
                }

                applyClientFilter();

                showSuccessMessage(data.message);

                toggleModal(false);
                saleForm.reset();
                hideNameSuggestions();
            } catch (error) {
                saleValorConsumoInput.value = formatCurrencyFromDigits(saleValorConsumoInput.value.replace(/\D/g, ''));
                saleFormErrors.innerHTML = '<p>Erro de comunicacao ao salvar venda.</p>';
                saleFormErrors.classList.remove('hidden');
            }
        });

        applyClientFilter();
    </script>
@endsection
