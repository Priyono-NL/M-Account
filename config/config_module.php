<?php

return [
    'app_id'     => getenv('APP_ID'),
    'secret_key' => getenv('APP_SECRET'),
    'modules'    => [
        [
            'type'        => 'link',
            'key'         => 'dashboard',
            'name'        => 'Dashboard',
            'desc'        => 'Halaman utama ringkasan statistik dan grafik.',
            'icon'        => 'fa-solid fa-house',
            'url'         => 'index.php?page=dashboard',
            'rule'        => 'public',
            'active_rule' => 'dashboard'
        ],
        [
            'type'        => 'divider',
            'rule'        => 'public'
        ],
        [
            'type'        => 'link',
            'key'         => 'pos',
            'name'        => 'Penjualan (POS)',
            'desc'        => 'Akses penuh ke halaman transaksi penjualan kasir.',
            'icon'        => 'fa-solid fa-cash-register',
            'url'         => 'index.php?page=pos',
            'rule'        => 'public',
            'active_rule' => 'pos_main'
        ],
        [
            'type'        => 'link',
            'key'         => 'receive',
            'name'        => 'Receivement (Form)',
            'desc'        => 'Mengelola dan mencatat penerimaan barang masuk dari supplier.',
            'icon'        => 'fa-solid fa-truck-ramp-box',
            'url'         => 'index.php?page=receive',
            'rule'        => 'public',
            'active_rule' => 'receive_main'
        ],
        [
            'type'        => 'divider',
            'rule'        => 'public'
        ],
        [
            'type'        => 'link',
            'key'         => 'items',
            'name'        => 'Data Barang',
            'desc'        => 'Melihat, menambah, dan mengedit master data barang.',
            'icon'        => 'fa-solid fa-box',
            'url'         => 'index.php?page=items',
            'rule'        => 'public',
            'active_rule' => 'default'
        ],
        [
            'type'        => 'link',
            'key'         => 'buyers',
            'name'        => 'Data Buyer',
            'desc'        => 'Melihat dan mengelola profil data pelanggan/buyer.',
            'icon'        => 'fa-solid fa-users',
            'url'         => 'index.php?page=buyers',
            'rule'        => 'admin',
            'active_rule' => 'default'
        ],
        [
            'type'        => 'link',
            'key'         => 'company',
            'name'        => 'Data Company',
            'desc'        => 'Mengatur informasi data gudang atau cabang perusahaan.',
            'icon'        => 'fa-solid fa-warehouse',
            'url'         => 'index.php?page=company',
            'rule'        => 'admin',
            'active_rule' => 'default'
        ],
        [
            'type'        => 'divider',
            'rule'        => 'public'
        ],
        [
            'type'        => 'link',
            'key'         => 'stockClose',
            'name'        => 'Stock Item',
            'desc'        => 'Melihat status dan jumlah stok item yang tersedia saat ini.',
            'icon'        => 'fa-solid fa-boxes-stacked',
            'url'         => 'index.php?page=stockClose',
            'rule'        => 'public',
            'active_rule' => 'default'
        ],
        [
            'type'        => 'link',
            'key'         => 'stockOpname',
            'name'        => 'Stock Opname (Form)',
            'desc'        => 'Melakukan pemeriksaan fisik stok berkala.',
            'icon'        => 'fa-solid fa-box-open',
            'url'         => 'index.php?page=stockOpname',
            'rule'        => 'public',
            'active_rule' => 'default'
        ],
        [
            'type'        => 'link',
            'key'         => 'stockAdjustment',
            'name'        => 'Stok Adjustment',
            'desc'        => 'Melakukan penyesuaian/koreksi jumlah stok (Khusus Admin).',
            'icon'        => 'fa-solid fa-check-to-slot',
            'url'         => 'index.php?page=stockAdjustment',
            'rule'        => 'admin',
            'active_rule' => 'default'
        ],
        [
            'type'        => 'link',
            'key'         => 'stockClosing',
            'name'        => 'Closing Stok',
            'desc'        => 'Melakukan penguncian pembukuan stok akhir bulan (Khusus Admin).',
            'icon'        => 'fa-solid fa-boxes-packing',
            'url'         => 'index.php?page=stockClosing',
            'rule'        => 'admin',
            'active_rule' => 'default'
        ],
        [
            'type'        => 'link',
            'key'         => 'sales',
            'name'        => 'Laporan Penjualan',
            'desc'        => 'Melihat rekapitulasi dan omset penjualan.',
            'icon'        => 'fa-solid fa-file-invoice-dollar',
            'url'         => 'index.php?page=sales',
            'rule'        => 'public',
            'active_rule' => 'default'
        ],
        [
            'type'        => 'link',
            'key'         => 'sales_detail',
            'name'        => 'Laporan Penjualan Detail',
            'desc'        => 'Melihat riwayat transaksi kasir secara terperinci.',
            'icon'        => 'fa-solid fa-file-invoice',
            'url'         => 'index.php?page=pos&action=history',
            'rule'        => 'public',
            'active_rule' => 'sales_history'
        ],
        [
            'type'        => 'link',
            'key'         => 'receive_history',
            'name'        => 'Laporan Penerimaan',
            'desc'        => 'Melihat log riwayat penerimaan barang dari supplier.',
            'icon'        => 'fa-solid fa-clipboard-check',
            'url'         => 'index.php?page=receive&action=history',
            'rule'        => 'public',
            'active_rule' => 'receive_history'
        ],
        [
            'type'        => 'link',
            'key'         => 'history',
            'name'        => 'Item Log',
            'desc'        => 'Melihat riwayat mutasi keluar masuk barang secara detail.',
            'icon'        => 'fa-solid fa-clock-rotate-left',
            'url'         => 'index.php?page=history',
            'rule'        => 'public',
            'active_rule' => 'default'
        ],
        [
            'type'        => 'link',
            'key'         => 'users',
            'name'        => 'User Login',
            'desc'        => 'Halaman manajemen user khusus superadmin.',
            'icon'        => 'fa-solid fa-users',            
            'url'         => 'index.php?page=users',
            'rule'        => 'superadmin',
            'active_rule' => 'default'
        ],
        [
            'type'        => 'link',
            'key'         => 'changeLogin',
            'name'        => 'Change Login',
            'desc'        => 'Backdoor Login sistem khusus superadmin.',
            'icon'        => 'fa-solid fa-user-gear',
            'url'         => 'index.php?page=changeLogin',
            'rule'        => 'superadmin',
            'active_rule' => 'default'
        ]
    ]
];