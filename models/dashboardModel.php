<?php
require_once '_dbHelper.php';

class DashboardModel extends DatabaseHelper {

    public function __construct() {
        parent::__construct();
    }

    public function getData($warehouse = '1') {
        $results = [];

        $q_items = "SELECT SUM(qty_close) AS total 
                    FROM stocks s
                    WHERE s.id IN (
                        SELECT MAX(id) 
                        FROM stocks 
                        WHERE warehouse = :warehouse 
                        GROUP BY item_id
                    )";
        $stmt_items = $this->db->prepare($q_items);
        $stmt_items->execute(['warehouse' => $warehouse]);
        $res_items = $stmt_items->fetch(PDO::FETCH_ASSOC);
        $results['inWarehouse'] = (int)($res_items['total'] ?? 0);

        $q_sales = "SELECT SUM(sd.item_qty * i.unit_price) AS total 
                    FROM sales_detail sd
                    JOIN sales s ON sd.sale_id = s.id
                    JOIN items i ON sd.item_id = i.id
                    WHERE MONTH(s.sales_date) = MONTH(CURRENT_DATE) 
                    AND YEAR(s.sales_date) = YEAR(CURRENT_DATE)
                    AND s.sale_type = 'SLS'
                    AND s.warehouse = :warehouse";
        $stmt_sales = $this->db->prepare($q_sales);
        $stmt_sales->execute(['warehouse' => $warehouse]);
        $res_sales = $stmt_sales->fetch(PDO::FETCH_ASSOC);
        $results['total_sales'] = (int)($res_sales['total'] ?? 0);

        $q_in7 = "SELECT date_receive, COUNT(id) AS total_transaksi
                    FROM receivement
                    WHERE date_receive >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY) 
                    AND warehouse = :warehouse   
                    GROUP BY date_receive
                    ORDER BY date_receive ASC";
        $stmt_in7 = $this->db->prepare($q_in7);
        $stmt_in7->execute(['warehouse' => $warehouse]);
        $results['in7'] = $stmt_in7->fetchAll(PDO::FETCH_ASSOC);

        $q_sales7 = "SELECT sales_date, COUNT(id) AS total_transaksi
                    FROM sales
                    WHERE sales_date >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY) 
                    AND warehouse = :warehouse   
                    GROUP BY sales_date
                    ORDER BY sales_date ASC";
        $stmt_sales7 = $this->db->prepare($q_sales7);
        $stmt_sales7->execute(['warehouse' => $warehouse]);
        $results['sales7'] = $stmt_sales7->fetchAll(PDO::FETCH_ASSOC);

        return $results;
    }
}