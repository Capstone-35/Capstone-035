@extends('layouts.staff', ['title' => 'Buat Pesanan Manual POS'])

@section('heading', 'Kasir POS — Entri Pesanan Manual (Workstation)')

@section('content')
<form method="POST" action="{{ route('pos.orders.store') }}" id="manualOrderForm">
    @csrf
    <input type="hidden" name="client_request_id" value="{{ $clientRequestId }}">

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Left Pane: Menu Selection Grid (65%) -->
        <div class="lg:col-span-8 bg-white rounded-2xl border border-[#E6DED2] p-5 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-[#F2ECE1] pb-3">
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
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                                @foreach($catProducts as $p)
                                    <button type="button"
                                            onclick="addItem({{ $p->id }}, '{{ addslashes($p->name) }}', {{ $p->price }})"
                                            class="p-3 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] hover:border-[#3B2A20] hover:bg-white text-left transition flex flex-col justify-between min-h-[72px]">
                                        <div class="font-bold text-xs text-[#1F1A17] line-clamp-1">{{ $p->name }}</div>
                                        <div class="text-xs font-extrabold text-[#3B2A20] tabular-nums mt-1">
                                            {{ $p->formattedPrice() }}
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>

        <!-- Right Pane: Order Cart & Customer Information (35%) -->
        <div class="lg:col-span-4 space-y-4">
            <div class="bg-white rounded-2xl border border-[#E6DED2] p-5 shadow-sm space-y-3">
                <h2 class="text-sm font-extrabold text-[#1F1A17] border-b border-[#F2ECE1] pb-2">Informasi Meja & Pelanggan</h2>

                <div>
                    <label class="block text-[11px] font-bold uppercase text-[#6F6259] mb-1">Meja</label>
                    <select name="table_id" class="w-full px-3 py-2 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] text-xs font-bold text-[#1F1A17]">
                        <option value="">Takeaway / Tanpa Meja</option>
                        @foreach($tables as $t)
                            <option value="{{ $t->id }}">{{ $t->label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase text-[#6F6259] mb-1">Pilih Member (Opsional)</label>
                    <select name="member_id" id="member_select" onchange="onMemberSelected()"
                            class="w-full px-3 py-2 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] text-xs">
                        <option value="">Pelanggan Tamu (Guest)</option>
                        @foreach($members as $m)
                            <option value="{{ $m->id }}" data-name="{{ $m->user?->name }}" data-email="{{ $m->user?->email }}" data-phone="{{ $m->user?->phone }}">
                                {{ $m->member_code }} — {{ $m->user?->name }} ({{ number_format($m->points_balance, 0, ',', '.') }} Poin)
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase text-[#3B2A20] mb-1">Nama Pelanggan *</label>
                    <input type="text" name="customer_name" id="cust_name" required placeholder="Nama pemesan"
                           class="w-full px-3 py-2 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] text-xs">
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase text-[#3B2A20] mb-1">Email (Untuk Invoice)</label>
                    <input type="email" name="customer_email" id="cust_email" placeholder="nama@email.com"
                           class="w-full px-3 py-2 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] text-xs">
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase text-[#3B2A20] mb-1">Nomor WhatsApp</label>
                    <input type="text" name="customer_phone" id="cust_phone" placeholder="0812xxxxxxxx"
                           class="w-full px-3 py-2 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] text-xs">
                </div>
            </div>

            <!-- Cart Table in POS -->
            <div class="bg-white rounded-2xl border border-[#E6DED2] p-5 shadow-sm space-y-3">
                <h2 class="text-sm font-extrabold text-[#1F1A17] border-b border-[#F2ECE1] pb-2">Item Pesanan Kasir</h2>
                <div id="cart_container" class="space-y-2 text-xs max-h-56 overflow-y-auto">
                    <p id="empty_cart_msg" class="text-gray-400 text-center py-4">Belum ada item dipilih.</p>
                </div>

                <div class="pt-3 border-t border-[#E6DED2] space-y-1.5 text-xs">
                    <div class="flex justify-between text-[#6F6259]">
                        <span>Subtotal:</span>
                        <span id="pos_subtotal" class="font-bold text-[#1F1A17] tabular-nums">Rp0</span>
                    </div>
                    @if($taxRate > 0)
                        <div class="flex justify-between text-[#6F6259]">
                            <span>Pajak ({{ number_format($taxRate, 0) }}%):</span>
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
                        <label class="p-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="payment_method" value="tunai" checked onchange="toggleCashInput(true)">
                            <span>Tunai</span>
                        </label>
                        <label class="p-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="payment_method" value="qris" onchange="toggleCashInput(false)">
                            <span>QRIS</span>
                        </label>
                    </div>

                    <div id="cash_options" class="space-y-2 pt-2">
                        <label class="flex items-center gap-2 text-xs font-bold text-[#166534]">
                            <input type="checkbox" name="confirm_cash_now" value="1" checked class="rounded text-[#1F7A4C]">
                            <span>Konfirmasi LUNAS Tunai Sekarang</span>
                        </label>
                        <input type="number" name="cash_received" id="cash_received" placeholder="Uang Tunai Diterima (Rp)"
                               class="w-full px-3 py-2 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] text-xs font-bold tabular-nums">
                    </div>
                </div>

                <button type="submit" class="w-full min-h-[46px] rounded-xl bg-[#3B2A20] text-[#FAF6EF] text-xs font-extrabold hover:bg-[#2A1E17] transition mt-3">
                    Proses Pesanan Manual
                </button>
            </div>
        </div>
    </div>
</form>

<script>
    let cart = {};
    const taxRate = {{ (float)$taxRate }};

    function onMemberSelected() {
        const select = document.getElementById('member_select');
        const opt = select.options[select.selectedIndex];
        if (opt.value) {
            document.getElementById('cust_name').value = opt.getAttribute('data-name') || '';
            document.getElementById('cust_email').value = opt.getAttribute('data-email') || '';
            document.getElementById('cust_phone').value = opt.getAttribute('data-phone') || '';
        }
    }

    function toggleCashInput(isCash) {
        document.getElementById('cash_options').style.display = isCash ? 'block' : 'none';
    }

    function addItem(id, name, price) {
        if (!cart[id]) {
            cart[id] = { id: id, name: name, price: price, quantity: 1, note: '' };
        } else {
            cart[id].quantity += 1;
        }
        renderCart();
    }

    function removeItem(id) {
        delete cart[id];
        renderCart();
    }

    function changeQty(id, qty) {
        const q = parseInt(qty, 10);
        if (q <= 0) {
            delete cart[id];
        } else {
            cart[id].quantity = q;
        }
        renderCart();
    }

    function renderCart() {
        const container = document.getElementById('cart_container');
        const keys = Object.keys(cart);
        if (keys.length === 0) {
            container.innerHTML = '<p class="text-gray-400 text-center py-4">Belum ada item dipilih.</p>';
            updateTotals(0);
            return;
        }

        let html = '';
        let subtotal = 0;
        let index = 0;

        keys.forEach(k => {
            const item = cart[k];
            const lineTotal = item.price * item.quantity;
            subtotal += lineTotal;

            html += `
                <div class="p-2 rounded-xl bg-[#FAF6EF] border border-[#E6DED2] flex items-center justify-between gap-2">
                    <div class="truncate flex-1">
                        <div class="font-bold text-[#1F1A17] truncate">${item.name}</div>
                        <div class="text-[10px] text-[#6F6259]">Rp${item.price.toLocaleString('id-ID')} x ${item.quantity} = Rp${lineTotal.toLocaleString('id-ID')}</div>
                        <input type="hidden" name="items[${index}][product_id]" value="${item.id}">
                        <input type="hidden" name="items[${index}][quantity]" value="${item.quantity}">
                        <input type="hidden" name="items[${index}][note]" value="${item.note}">
                    </div>
                    <div class="flex items-center gap-1">
                        <input type="number" min="1" max="99" value="${item.quantity}" onchange="changeQty(${item.id}, this.value)"
                               class="w-12 px-1.5 py-1 rounded border border-[#D6CBBC] bg-white text-center text-xs font-bold">
                        <button type="button" onclick="removeItem(${item.id})" class="px-2 py-1 text-red-600 font-bold hover:bg-red-50 rounded">×</button>
                    </div>
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

        document.getElementById('pos_subtotal').innerText = 'Rp' + subtotal.toLocaleString('id-ID');
        if (document.getElementById('pos_tax')) {
            document.getElementById('pos_tax').innerText = 'Rp' + tax.toLocaleString('id-ID');
        }
        document.getElementById('pos_total').innerText = 'Rp' + total.toLocaleString('id-ID');
        if (document.getElementById('cash_received')) {
            document.getElementById('cash_received').value = total;
        }
    }
</script>
@endsection
