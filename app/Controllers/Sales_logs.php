<?php 
namespace App\Controllers;
use App\Controllers\SecureController;
use App\Models\Sale;

class Sales_logs extends SecureController
{
    protected $Sale;

	public function __construct()
    {
        $this->Sale = new Sale();
        // parent::__construct('sales_logs');

    }

	public function index()
	{
		$person_id = session()->get('person_id');
        helper('table');

        $data['table_headers'] = get_deleted_sales_table_headers();

        return view('sales/sales_log', $data);
	}

	public function search()
    {

        $search = request()->getGet('search');
        $limit = request()->getGet('limit');
        $offset = request()->getGet('offset');
        $sort = request()->getGet('sort');
        $order = request()->getGet('order');
        $branch_code = request()->getGet('branch_code');

        $filters = array(
            'branch_code' => $branch_code,
            'start_date' => request()->getGet('start_date'),
            'end_date' => request()->getGet('end_date')
        );

        $sales = $this->Sale->search_deleted_logs($search, $filters, $limit, $offset, $sort, $order);

        $total_rows = $this->Sale->get_found_deleted_logs_rows($search, $filters);
   

        $data_rows = array();
        foreach ($sales->getResult() as $sale) {

            helper('table');
            $data_rows[] = $this->xss_clean(get_sale_deleted_log_data_row($sale, $this));
        }

        echo json_encode(array('total' => $total_rows, 'rows' => $data_rows));
    }
}
