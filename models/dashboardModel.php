<?php
require_once '_dbHelper.php';

class DashboardModel extends DatabaseHelper {

    public function __construct() {
        parent::__construct();
    }

    public function getData($warehouse = 1) {
        $results = [];

        $sso_warehouse = $this->configs['warehouse'] ?? null;        
        if ($sso_warehouse !== null) {
            $warehouse = $sso_warehouse;
        }

        $warehouse = (int)$warehouse;
        $params = ['warehouse' => $warehouse];

        $q_items = "SELECT SUM(qty_total) AS total 
                    FROM stocks s
                    WHERE s.id IN (
                        SELECT MAX(id) 
                        FROM stocks 
                        WHERE warehouse = :warehouse 
                        GROUP BY item_id
                    )";
        $res_items = $this->query_one($q_items, $params);
        $results['inWarehouse'] = (int)($res_items['total'] ?? 0);

        $q_sales = "SELECT SUM(sd.item_qty * i.unit_price) AS total 
                    FROM sales_detail sd
                    JOIN sales s ON sd.sale_id = s.id
                    JOIN items i ON sd.item_id = i.id
                    WHERE MONTH(s.sales_date) = MONTH(CURRENT_DATE) 
                    AND YEAR(s.sales_date) = YEAR(CURRENT_DATE)
                    AND s.sale_type = 'SLS'
                    AND s.warehouse = :warehouse";
        $res_sales = $this->query_one($q_sales, $params);
        $results['total_sales'] = (int)($res_sales['total'] ?? 0);


        $q_in7 = "SELECT date_receive, COUNT(id) AS total_transaksi
                  FROM receivement
                  WHERE date_receive >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY) 
                  AND warehouse = :warehouse   
                  GROUP BY date_receive
                  ORDER BY date_receive ASC";
        $results['in7'] = $this->query_all($q_in7, $params);


        $q_sales7 = "SELECT sales_date, COUNT(id) AS total_transaksi
                     FROM sales
                     WHERE sales_date >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY) 
                     AND warehouse = :warehouse   
                     GROUP BY sales_date
                     ORDER BY sales_date ASC";
        $results['sales7'] = $this->query_all($q_sales7, $params);

        return $results;
    }
}
?>