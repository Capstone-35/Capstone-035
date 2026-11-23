@extends('layouts.staff', ['title' => 'Buat Pesanan Manual POS'])

@section('heading', 'Kasir POS — Entri Pesanan Manual (Workstation)')

@section('content')
@php
    $oldItems = collect(old('items', []))->map(function ($row) use ($products) {
        $prod = $products->firstWhere('id', (int) ($row['product_id'] ?? 0));
        if (!$prod) return null;
        return [
            'id' => $prod->id,
            'name' => $prod->name,
            'price' => $prod->price,
            'quantity' => max(1, min(99, (int) ($row['quantity'] ?? 1))),
            'note' => (string) ($row['note'] ?? ''),
        ];
    })->filter()->values();
@endphp
<form method="POST" action="{{ route('pos.orders.store') }}" id="manualOrderForm">
    @csrf
    <input type="hidden" name="client_request_id" value="{{ old('client_request_id', $clientRequestId) }}">

    <div class="grid grid-cols-1 md:grid-cols-12 gap-5 items-start">
        <!-- Left Pane: Menu Selection Grid -->
        <div class="md:col-span-7 xl:col-span-8 bg-white rounded-2xl border border-[#E6DED2] p-4 sm:p-5 shadow-sm space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[#F2ECE1] pb-3">
                <h2 class="text-base font-extrabold text-[#1F1A17]">Pilih Menu</h2>
                <span class="text-xs text-[#6F6259]">Klik menu untuk memasukkan ke keranjang kasir</span>
            </div>

            <!-- Categories and Products -->
            <div class="space-y-4">
                @foreach($categories as $cat)
                    @php
                        $catProducts = $products->where('category_id', $cat->id);
                    @endphp
                    @if($catProducts->isNotEmpty())
                        <div>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-[#A9552F] mb-2">{{ $cat->name }}</h3>
                            <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-2.5">
                                @foreach($catProducts as $p)
                                    @if($p->is_available)
                                        <button type="button"
                                                data-product-id="{{ $p->id }}"
                                                data-product-name="{{ $p->name }}"
                                                data-product-price="{{ $p->price }}"
                                                onclick="addItemFromButton(this)"
                                                class="p-3 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] hover:border-[#3B2A20] hover:bg-white text-left transition flex flex-col justify-between min-h-[78px] cursor-pointer">
                                            <div class="font-bold text-xs text-[#1F1A17] line-clamp-2 leading-snug">{{ $p->name }}</div>
                                            <div class="text-xs font-extrabold text-[#3B2A20] tabular-nums mt-1.5">
                                                {{ $p->formattedPrice() }}
                                            </div>
                                        </button>
                                    @else
                                        <button type="button" disabled
                                                class="p-3 rounded-xl border border-gray-200 bg-gray-100/80 text-left flex flex-col justify-between min-h-[78px] opacity-60 cursor-not-allowed">
                                            <div class="font-bold text-xs text-gray-500 line-clamp-2 leading-snug">{{ $p->name }}</div>
                                            <div class="flex items-center justify-between gap-1 mt-1.5">
                                                <span class="text-[11px] font-bold text-gray-400 tabular-nums">{{ $p->formattedPrice() }}</span>
                                                <span class="px-1.5 py-0.5 rounded bg-red-100 text-red-700 text-[9px] font-extrabold uppercase">Habis</span>
                                            </div>
                                        </button>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>

        <!-- Right Pane: Order Cart & Customer Information -->
        <div class="md:col-span-5 xl:col-span-4 md:sticky md:top-20 space-y-4">
            <div class="bg-white rounded-2xl border border-[#E6DED2] p-4 sm:p-5 shadow-sm space-y-3">
                <h2 class="text-sm font-extrabold text-[#1F1A17] border-b border-[#F2ECE1] pb-2">Informasi Meja & Pelanggan</h2>

                <div>
                    <label class="block text-[11px] font-bold uppercase text-[#6F6259] mb-1">Meja</label>
                    <select name="table_id" class="w-full min-h-[42px] px-3 py-2 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] text-xs font-bold text-[#1F1A17]">
                        <option value="">Takeaway / Tanpa Meja</option>
                        @foreach($tables as $t)
                            <option value="{{ $t->id }}" {{ (string) old('table_id') === (string) $t->id ? 'selected' : '' }}>{{ $t->label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase text-[#6F6259] mb-1">Pilih Member (Opsional)</label>
                    <select name="member_id" id="member_select" onchange="onMemberSelected()"
                            class="w-full min-h-[42px] px-3 py-2 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] text-xs">
                        <option value="">Pelanggan Tamu (Guest)</option>
                        @foreach($members as $m)
                            <option value="{{ $m->id }}" {{ (string) old('member_id') === (string) $m->id ? 'selected' : '' }}
                                    data-name="{{ $m->user?->name }}" data-email="{{ $m->user?->email }}" data-phone="{{ $m->user?->phone }}">
                                {{ $m->member_code }} — {{ $m->user?->name }} ({{ number_format($m->points_balance, 0, ',', '.') }} Poin)
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase text-[#3B2A20] mb-1">Nama Pelanggan *</label>
                    <input type="text" name="customer_name" id="cust_name" required maxlength="80" value="{{ old('customer_name') }}" placeholder="Nama pemesan"
                           class="w-full min-h-[42px] px-3 py-2 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] text-xs">
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase text-[#3B2A20] mb-1">Email (Untuk Invoice)</label>
                    <input type="email" name="customer_email" id="cust_email" maxlength="120" value="{{ old('customer_email') }}" placeholder="nama@email.com"
                           class="w-full min-h-[42px] px-3 py-2 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] text-xs">
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase text-[#3B2A20] mb-1">Nomor WhatsApp</label>
                    <input type="tel" name="customer_phone" id="cust_phone" maxlength="30" value="{{ old('customer_phone') }}" placeholder="0812xxxxxxxx"
                           class="w-full min-h-[42px] px-3 py-2 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] text-xs">
                </div>
            </div>

            <!-- Cart Table in POS -->
            <div class="bg-white rounded-2xl border border-[#E6DED2] p-4 sm:p-5 shadow-sm space-y-3">
                <h2 class="text-sm font-extrabold text-[#1F1A17] border-b border-[#F2ECE1] pb-2">Item Pesanan Kasir</h2>
                <div id="cart_container" class="space-y-2 text-xs max-h-60 overflow-y-auto">
                    <p id="empty_cart_msg" class="text-gray-400 text-center py-4">Belum ada item dipilih.</p>
                </div>

                <div class="pt-3 border-t border-[#E6DED2] space-y-1.5 text-xs">
                    <div class="flex justify-between text-[#6F6259]">
                        <span>Subtotal:</span>
                        <span id="pos_subtotal" class="font-bold text-[#1F1A17] tabular-nums">Rp0</span>
                    </div>
                    @if($taxRate > 0)
                        <div class="flex justify-between text-[#6F6259]">
                            <span>Pajak ({{ rtrim(rtrim(number_format((float) $taxRate, 2, '.', ''), '0'), '.') }}%):</span>
                            <span id="pos_tax" class="font-bold text-[#1F1A17] tabular-nums">Rp0</span>
                        </div>
                    @endif
                    <div class="pt-2 border-t border-[#E6DED2] flex justify-between text-base font-extrabold text-[#1F1A17]">
                        <span>Total:</span>
                        <span id="pos_total" class="tabular-nums text-[#3B2A20]">Rp0</span>
                    </div>
                </div>

                <!-- Payment Selection -->
                <div class="pt-3 border-t border-[#E6DED2] space-y-2">
                    <label class="block text-[11px] font-bold uppercase text-[#3B2A20]">Metode Pembayaran</label>
                    <div class="grid grid-cols-2 gap-2 text-xs font-bold">
                        <label class="min-h-[42px] p-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="payment_method" value="tunai" {{ old('payment_method', 'tunai') === 'tunai' ? 'checked' : '' }} onchange="toggleCashInput(true)" class="accent-[#3B2A20]">
                            <span>Tunai</span>
                        </label>
                        <label class="min-h-[42px] p-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="payment_method" value="qris" {{ old('payment_method') === 'qris' ? 'checked' : '' }} onchange="toggleCashInput(false)" class="accent-[#3B2A20]">
                            <span>QRIS</span>
                        </label>
                    </div>

                    <div id="cash_options" class="space-y-2 pt-2">
                        <label class="flex items-center gap-2 text-xs font-bold text-[#166534] cursor-pointer">
                            <input type="checkbox" id="confirm_cash_now" name="confirm_cash_now" value="1" {{ old('confirm_cash_now', '1') ? 'checked' : '' }} class="rounded accent-[#1F7A4C]">
                            <span>Konfirmasi LUNAS Tunai Sekarang</span>
                        </label>
                        <input type="number" name="cash_received" id="cash_received" min="0" value="{{ old('cash_received') }}" oninput="onCashInput()" placeholder="Uang Tunai Diterima (Rp)"
                               class="w-full min-h-[42px] px-3 py-2 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] text-xs font-bold tabular-nums">
                        <div id="pos_change_preview" class="text-[11px] font-bold text-[#166534] hidden"></div>
                    </div>
                </div>

                <button type="submit" id="pos_submit_btn" disabled class="w-full min-h-[46px] rounded-xl bg-[#3B2A20] text-[#FAF6EF] text-xs font-extrabold hover:bg-[#2A1E17] disabled:bg-gray-300 disabled:text-gray-500 disabled:cursor-not-allowed transition mt-3">
                    Proses Pesanan Manual
                </button>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script>
    let cart = {};
    let currentGrandTotal = 0;
    let cashManuallyEdited = false;
    const taxRate = {{ (float)$taxRate }};
    const initialOldItems = @json($oldItems);

    function escapeHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function onMemberSelected() {
        const select = document.getElementById('member_select');
        const opt = select.options[select.selectedIndex];
        if (opt && opt.value) {
            document.getElementById('cust_name').value = opt.getAttribute('data-name') || '';
            document.getElementById('cust_email').value = opt.getAttribute('data-email') || '';
            document.getElementById('cust_phone').value = opt.getAttribute('data-phone') || '';
        } else {
            document.getElementById('cust_name').value = '';
            document.getElementById('cust_email').value = '';
            document.getElementById('cust_phone').value = '';
        }
    }

    function toggleCashInput(isCash) {
        const box = document.getElementById('cash_options');
        const cb = document.getElementById('confirm_cash_now');
        const input = document.getElementById('cash_received');
        if (box) box.style.display = isCash ? 'block' : 'none';
        if (cb) cb.disabled = !isCash;
        if (input) input.disabled = !isCash;
    }

    function addItemFromButton(btn) {
        const id = parseInt(btn.getAttribute('data-product-id'), 10);
        const name = btn.getAttribute('data-product-name') || '';
        const price = parseInt(btn.getAttribute('data-product-price'), 10) || 0;
        addItem(id, name, price);
    }

    function addItem(id, name, price) {
        if (!cart[id]) {
            cart[id] = { id: id, name: name, price: price, quantity: 1, note: '' };
        } else {
            cart[id].quantity = Math.min(99, cart[id].quantity + 1);
        }
        renderCart();
    }

    function removeItem(id) {
        delete cart[id];
        renderCart();
    }

    function changeQty(id, qty) {
        const q = parseInt(qty, 10);
        if (isNaN(q) || q <= 0) {
            delete cart[id];
        } else {
            cart[id].quantity = Math.min(99, q);
        }
        renderCart();
    }

    function changeNote(id, val) {
        if (cart[id]) {
            cart[id].note = String(val || '').slice(0, 200);
        }
    }

    function onCashInput() {
        cashManuallyEdited = true;
        updateChangePreview();
    }

    function updateChangePreview() {
        const input = document.getElementById('cash_received');
        const preview = document.getElementById('pos_change_preview');
        if (!input || !preview) return;
        const val = parseInt(input.value, 10);
        if (isNaN(val) || currentGrandTotal <= 0) {
            preview.classList.add('hidden');
            return;
        }
        if (val >= currentGrandTotal) {
            preview.className = 'text-[11px] font-bold text-[#166534]';
            preview.textContent = 'Kembalian: Rp' + (val - currentGrandTotal).toLocaleString('id-ID');
        } else {
            preview.className = 'text-[11px] font-bold text-red-700';
            preview.textContent = 'Kurang: Rp' + (currentGrandTotal - val).toLocaleString('id-ID');
        }
    }

    function renderCart() {
        const container = document.getElementById('cart_container');
        const submitBtn = document.getElementById('pos_submit_btn');
        const keys = Object.keys(cart);
        if (keys.length === 0) {
            container.innerHTML = '<p class="text-gray-400 text-center py-4">Belum ada item dipilih.</p>';
            if (submitBtn) submitBtn.disabled = true;
            updateTotals(0);
            return;
        }

        if (submitBtn) submitBtn.disabled = false;
        let html = '';
        let subtotal = 0;
        let index = 0;

        keys.forEach(k => {
            const item = cart[k];
            const lineTotal = item.price * item.quantity;
            subtotal += lineTotal;

            html += `
                <div class="p-2.5 rounded-xl bg-[#FAF6EF] border border-[#E6DED2] space-y-1.5">
                    <div class="flex items-center justify-between gap-2">
                        <div class="min-w-0 flex-1">
                            <div class="font-bold text-[#1F1A17] truncate">${escapeHtml(item.name)}</div>
                            <div class="text-[10px] text-[#6F6259] tabular-nums">Rp${item.price.toLocaleString('id-ID')} x ${item.quantity} = Rp${lineTotal.toLocaleString('id-ID')}</div>
                            <input type="hidden" name="items[${index}][product_id]" value="${item.id}">
                            <input type="hidden" name="items[${index}][quantity]" value="${item.quantity}">
                        </div>
                        <div class="flex items-center gap-1 shrink-0">
                            <input type="number" min="1" max="99" value="${item.quantity}" onchange="changeQty(${item.id}, this.value)"
                                   class="w-14 min-h-[36px] px-1.5 py-1 rounded-lg border border-[#D6CBBC] bg-white text-center text-xs font-bold">
                            <button type="button" onclick="removeItem(${item.id})" aria-label="Hapus item" class="min-w-[36px] min-h-[36px] text-red-600 font-bold hover:bg-red-50 rounded-lg">×</button>
                        </div>
                    </div>
                    <input type="text" name="items[${index}][note]" maxlength="200" value="${escapeHtml(item.note)}" oninput="changeNote(${item.id}, this.value)"
                           placeholder="Catatan dapur (opsional)..."
                           class="w-full px-2.5 py-1.5 rounded-lg border border-[#E6DED2] bg-white text-[11px]">
                </div>
            `;
            index++;
        });

        container.innerHTML = html;
        updateTotals(subtotal);
    }

    function updateTotals(subtotal) {
        const tax = taxRate > 0 ? Math.round(subtotal * (taxRate / 100)) : 0;
        const total = subtotal + tax;
        currentGrandTotal = total;

        document.getElementById('pos_subtotal').innerText = 'Rp' + subtotal.toLocaleString('id-ID');
        if (document.getElementById('pos_tax')) {
            document.getElementById('pos_tax').innerText = 'Rp' + tax.toLocaleString('id-ID');
        }
        document.getElementById('pos_total').innerText = 'Rp' + total.toLocaleString('id-ID');
        const cashEl = document.getElementById('cash_received');
        if (cashEl && !cashManuallyEdited) {
            cashEl.value = total > 0 ? total : '';
        }
        updateChangePreview();
    }

    // Restore cart on validation error and sync payment toggle
    (function initPosManualOrder() {
        if (Array.isArray(initialOldItems) && initialOldItems.length > 0) {
            initialOldItems.forEach((it) => {
                cart[it.id] = it;
            });
            renderCart();
        }
        const checkedMethod = document.querySelector('input[name="payment_method"]:checked');
        toggleCashInput(!checkedMethod || checkedMethod.value === 'tunai');
    })();
</script>
@endpush
@endsection
