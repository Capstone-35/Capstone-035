<?php

return [
    'kasir' => [
        ['label' => 'Dashboard', 'route' => 'pos.dashboard', 'active' => 'pos.dashboard', 'icon' => 'layout-dashboard', 'permission' => 'pos.access'],
        ['label' => 'Pesanan Masuk', 'route' => 'pos.orders.index', 'active' => 'pos.orders.index|pos.orders.show', 'icon' => 'clipboard-list', 'permission' => 'orders.view'],
        ['label' => 'Pesanan Manual', 'route' => 'pos.orders.create', 'active' => 'pos.orders.create', 'icon' => 'plus-circle', 'permission' => 'orders.create'],
        ['label' => 'Pembayaran', 'route' => 'pos.payments.index', 'active' => 'pos.payments.*', 'icon' => 'wallet', 'permission' => 'orders.confirm_cash'],
        ['label' => 'Transaksi', 'route' => 'pos.transactions.index', 'active' => 'pos.transactions.*', 'icon' => 'receipt', 'permission' => 'orders.view'],
        ['label' => 'Invoice', 'route' => 'pos.invoices.index', 'active' => 'pos.invoices.*', 'icon' => 'file-text', 'permission' => 'invoices.view'],
    ],

    'manager' => [
        ['label' => 'Dashboard', 'route' => 'manager.dashboard', 'active' => 'manager.dashboard', 'icon' => 'layout-dashboard', 'permission' => 'reports.sales.view', 'group' => 'Utama'],
        ['label' => 'Riwayat Transaksi', 'route' => 'manager.transactions.index', 'active' => 'manager.transactions.*', 'icon' => 'receipt', 'permission' => 'orders.view', 'group' => 'Transaksi'],
        ['label' => 'Monitoring Pesanan', 'route' => 'manager.orders.index', 'active' => 'manager.orders.*', 'icon' => 'clipboard-list', 'permission' => 'orders.view', 'group' => 'Transaksi'],
        ['label' => 'Produk', 'route' => 'manager.products.index', 'active' => 'manager.products.*', 'icon' => 'coffee', 'permission' => 'products.manage', 'group' => 'Katalog Menu'],
        ['label' => 'Kategori', 'route' => 'manager.categories.index', 'active' => 'manager.categories.*', 'icon' => 'layers', 'permission' => 'products.manage', 'group' => 'Katalog Menu'],
        ['label' => 'Meja & QR', 'route' => 'manager.tables.index', 'active' => 'manager.tables.*', 'icon' => 'qr-code', 'permission' => 'products.manage', 'group' => 'Katalog Menu'],
        ['label' => 'Analitik RFM', 'route' => 'manager.rfm.index', 'active' => 'manager.rfm.*', 'icon' => 'pie-chart', 'permission' => 'rfm.view', 'group' => 'Promosi & RFM'],
        ['label' => 'Promosi', 'route' => 'manager.promotions.index', 'active' => 'manager.promotions.*', 'icon' => 'sparkles', 'permission' => 'promotions.manage', 'group' => 'Promosi & RFM'],
        ['label' => 'Reward & Loyalty', 'route' => 'manager.rewards.index', 'active' => 'manager.rewards.*|manager.loyalty.*', 'icon' => 'gift', 'permission' => 'loyalty.manage', 'group' => 'Promosi & RFM'],
        ['label' => 'Data Member', 'route' => 'manager.members.index', 'active' => 'manager.members.*', 'icon' => 'users', 'permission' => 'members.view', 'group' => 'Promosi & RFM'],
        ['label' => 'Laporan Penjualan', 'route' => 'manager.reports.index', 'active' => 'manager.reports.*', 'icon' => 'bar-chart-3', 'permission' => 'reports.revenue.view', 'group' => 'Laporan'],
        ['label' => 'Pengguna & Staf', 'route' => 'manager.users.index', 'active' => 'manager.users.*', 'icon' => 'shield', 'permission' => 'users.manage_staff', 'group' => 'Pengaturan'],
    ],

    'owner' => [
        ['label' => 'Dashboard', 'route' => 'owner.dashboard', 'active' => 'owner.dashboard', 'icon' => 'layout-dashboard', 'permission' => 'reports.revenue.view'],
        ['label' => 'Laporan Penjualan', 'route' => 'owner.reports.sales', 'active' => 'owner.reports.sales', 'icon' => 'trending-up', 'permission' => 'reports.sales.view'],
        ['label' => 'Laporan Pendapatan', 'route' => 'owner.reports.revenue', 'active' => 'owner.reports.revenue', 'icon' => 'banknote', 'permission' => 'reports.revenue.view'],
        ['label' => 'Pelanggan', 'route' => 'owner.customers.index', 'active' => 'owner.customers.*', 'icon' => 'users', 'permission' => 'members.view'],
        ['label' => 'Performa Promosi', 'route' => 'owner.promotions.index', 'active' => 'owner.promotions.*', 'icon' => 'sparkles', 'permission' => 'promotions.view'],
        ['label' => 'Pengguna', 'route' => 'owner.users.index', 'active' => 'owner.users.*', 'icon' => 'shield', 'permission' => 'users.manage_all'],
    ],
];
