<?php
require_once 'BaseController.php';

class testController extends BaseController {
    private $model;

    public function __construct() {
        $this->model = new StocksModel();
        parent::__construct();
    }

    public function index() {
        $stocks = $this->model->getMonthlyReport();
        TestView::render($stocks);
    }

    public function filter_api() {
        $search   = $this->getPost('search', '');
        $warehouse = $this->getPost('warehouse', '');
        $closeMonth = $this->getPost('closeMonth', '');

        $items = $this->model->getMonthlyReport($search, $warehouse, $closeMonth);
        
        return $this->jsonSuccess("Data Filtered", $items);
    }

    public function export_xls() {
        $html = $_POST['tabel_html'] ?? '';

        if (empty($html)) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(["message" => "Data tabel HTML tidak diterima oleh server."]);
            exit;
        }

        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html); 

        $matrix = []; 
        $merges = []; 

        $numToAlpha = function($n) {
            for($r = ""; $n >= 0; $n = intval($n / 26) - 1) {
                $r = chr($n % 26 + 0x41) . $r;
            }
            return $r;
        };

        $rows = $dom->getElementsByTagName('tr');
        foreach ($rows as $rowIndex => $row) {
            $colIndex = 0;

            foreach ($row->childNodes as $cell) {
                if (!($cell instanceof \DOMElement)) continue;
                if ($cell->nodeName !== 'td' && $cell->nodeName !== 'th') continue;

                $val = trim($cell->textContent);                
                $colspan = $cell->hasAttribute('colspan') ? (int)$cell->getAttribute('colspan') : 1;
                $rowspan = $cell->hasAttribute('rowspan') ? (int)$cell->getAttribute('rowspan') : 1;

                while (isset($matrix[$rowIndex][$colIndex])) $colIndex++;

                if ($colspan > 1 || $rowspan > 1) {
                    $startCol = $numToAlpha($colIndex);
                    $startRow = $rowIndex + 1;
                    
                    $endCol = $numToAlpha($colIndex + $colspan - 1);
                    $endRow = $rowIndex + $rowspan;
                    
                    $merges[] = "{$startCol}{$startRow}:{$endCol}{$endRow}";
                    $val = "<top><center>" . $val . "</center></top>";
                }

                for ($rs = 0; $rs < $rowspan; $rs++) {
                    for ($cs = 0; $cs < $colspan; $cs++) $matrix[$rowIndex + $rs][$colIndex + $cs] = ($rs === 0 && $cs === 0) ? $val : "";
                }
            }
        }

        ksort($matrix);
        foreach ($matrix as &$rowItem) ksort($rowItem);

        $xlsx = \Shuchkin\SimpleXLSXGen::fromArray($matrix);
        foreach ($merges as $mergeRange) $xlsx->mergeCells($mergeRange);
        $fileName = "Laporan_Pivot_" . date('Ymd_His') . ".xlsx";
        $xlsx->downloadAs($fileName);
        exit;
    }

}
?>