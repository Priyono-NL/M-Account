<?php

return [
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
            'name'        => 'Penjualan',
            'desc'        => 'Akses penuh ke halaman transaksi penjualan kasir.',
            'icon'        => 'fa-solid fa-cash-register',
            'url'         => 'index.php?page=pos',
            'rule'        => 'public',
            'active_rule' => 'pos_main'
        ],
        [
            'type'        => 'link',
            'key'         => 'receive',
            'name'        => 'Penerimaan',
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
            'type'        => 'link',
            'key'         => 'origins',
            'name'        => 'Master Origin Code',
            'desc'        => 'Mengatur informasi data origins barang.',
            'icon'        => 'fa-solid fa-code-branch',
            'url'         => 'index.php?page=origins',
            'rule'        => 'admin',
            'active_rule' => 'default'
        ],
        [
            'type'        => 'divider',
            'rule'        => 'public'
        ],
        [
            'type'        => 'link',
            'key'         => 'stocks',
            'name'        => 'Stock Item',
            'desc'        => 'Melihat status dan jumlah stok item yang tersedia saat ini.',
            'icon'        => 'fa-solid fa-boxes-stacked',
            'url'         => 'index.php?page=stocks',
            'rule'        => 'public',
            'active_rule' => 'stock_main'
        ],
        [
            'type'        => 'link',
            'key'         => 'stockCard',
            'name'        => 'Kartu Stok Barang',
            'desc'        => 'Melihat log kronologis mutasi masuk dan keluar per item barang.',
            'icon'        => 'fa-solid fa-clipboard-list',
            'url'         => 'index.php?page=stocks&action=card',
            'rule'        => 'public',
            'active_rule' => 'stock_card'
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
            'type'        => 'divider',
            'rule'        => 'public'
        ],
        [
            'type'        => 'link',
            'key'         => 'sales',
            'name'        => 'Laporan Penjualan',
            'desc'        => 'Melihat rekapitulasi dan omset penjualan.',
            'icon'        => 'fa-solid fa-file-export',
            'url'         => 'index.php?page=sales',
            'rule'        => 'public',
            'active_rule' => 'default'
        ],
        [
            'type'        => 'link',
            'key'         => 'receivePivot',
            'name'        => 'Laporan Penerimaan',
            'desc'        => 'Melihat rekapitulasi dan jumlah Penerimaan.',
            'icon'        => 'fa-solid fa-file-import',
            'url'         => 'index.php?page=receivePivot',
            'rule'        => 'public',
            'active_rule' => 'default'
        ],
        [
            'type'        => 'link',
            'key'         => 'sales_detail',
            'name'        => 'Penjualan Detail',
            'desc'        => 'Melihat riwayat transaksi kasir secara terperinci.',
            'icon'        => 'fa-solid fa-file-invoice',
            'url'         => 'index.php?page=pos&action=history',
            'rule'        => 'public',
            'active_rule' => 'sales_history'
        ],
        [
            'type'        => 'link',
            'key'         => 'receive_history',
            'name'        => 'Penerimaan Detail',
            'desc'        => 'Melihat log riwayat penerimaan barang dari supplier.',
            'icon'        => 'fa-solid fa-clipboard-check',
            'url'         => 'index.php?page=receive&action=history',
            'rule'        => 'public',
            'active_rule' => 'receive_history'
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
            'type'        => 'divider',
            'rule'        => 'superadmin'
        ],
        [
            'type'        => 'link',
            'key'         => 'permission',
            'name'        => 'Permission',
            'desc'        => 'Halaman manajemen permission khusus superadmin.',
            'icon'        => 'fa-solid fa-user-lock',            
            'url'         => 'index.php?page=permission',
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