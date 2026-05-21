<?php
require_once 'BaseController.php';

class SalesPivotController extends BaseController {
    private $model;

    public function __construct() {
        parent::__construct();
        
        $this->model = new SalesModel();
    }

    public function index() {
        $data = $this->model->getFiltered();
        SalesPivotView::render($data);
    }

    public function filter_api() {
        $search    = $this->getPost('search', '');
        $warehouse = $this->getPost('warehouse', '');
        $type      = $this->getPost('type', '');
        $startDate = $this->getPost('start_date', '');
        $endDate   = $this->getPost('end_date', '');

        $items = $this->model->getFiltered($search, $warehouse, $startDate, $endDate, $type);
        
        return $this->jsonSuccess("Data Filtered", $items);
    }

    public function export_xls() {
        $html = $this->getPost('tabel_html', '');

        if (empty($html)) {
            return $this->jsonError("Data tabel HTML tidak diterima oleh server.", 400);
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

                $val     = trim($cell->textContent);                
                $colspan = $cell->hasAttribute('colspan') ? (int)$cell->getAttribute('colspan') : 1;
                $rowspan = $cell->hasAttribute('rowspan') ? (int)$cell->getAttribute('rowspan') : 1;

                while (isset($matrix[$rowIndex][$colIndex])) {
                    $colIndex++;
                }

                if ($colspan > 1 || $rowspan > 1) {
                    $startCol = $numToAlpha($colIndex);
                    $startRow = $rowIndex + 1;
                    
                    $endCol = $numToAlpha($colIndex + $colspan - 1);
                    $endRow = $rowIndex + $rowspan;
                    
                    $merges[] = "{$startCol}{$startRow}:{$endCol}{$endRow}";
                    $val = "<top><center>" . $val . "</center></top>";
                }

                for ($rs = 0; $rs < $rowspan; $rs++) {
                    for ($cs = 0; $cs < $colspan; $cs++) {
                        $matrix[$rowIndex + $rs][$colIndex + $cs] = ($rs === 0 && $cs === 0) ? $val : "";
                    }
                }
            }
        }

        ksort($matrix);
        foreach ($matrix as &$rowItem) {
            ksort($rowItem);
        }

        $xlsx = \Shuchkin\SimpleXLSXGen::fromArray($matrix);
        foreach ($merges as $mergeRange) {
            $xlsx->mergeCells($mergeRange);
        }
        
        $fileName = "Laporan_Pivot_" . date('Ymd_His') . ".xlsx";
        $xlsx->downloadAs($fileName);
        exit;
    }
}
?>