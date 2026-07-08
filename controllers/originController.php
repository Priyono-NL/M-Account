<?php
require_once 'BaseController.php';
require_once 'models/originModel.php';

class OriginController extends BaseController {
    private $model;

    public function __construct() {
        parent::__construct();
        $this->model = new OriginModel();
    }

    public function index() {
         OriginView::render([]);
    }

    public function filter_api() {
        $search = $this->getPost('search', '');
        
        $paging = $this->getPaginationParams(10);
        $result = $this->model->getFilteredPaginated($search, $paging['limit'], $paging['offset']);
        $paginationMeta = $this->buildPaginationMeta($result['total'], $paging['page'], $paging['limit']);
          
        return $this->jsonSuccess(
            "Data Filtered", 
            $result['data'], 
            ['pagination' => $paginationMeta]
        );
    }

}
?>