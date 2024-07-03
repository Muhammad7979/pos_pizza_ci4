<?php 
namespace App\Controllers;

use App\Libraries\Gu;
use App\Models\Employee;
use App\Models\Stock_location;
use App\Libraries\AppData;
use App\Models\Store_item;

class Store_items extends SecureController
{
    protected $Employee;
    protected $Stock_location;
	protected $appData;
     protected $Store_item;

    public function __construct()
    {
        parent::__construct('store_items');
		$this->Employee = new Employee();
        $this->Stock_location = new Stock_location();
        $this->Store_item = new Store_item();
        // $this->load->library('item_lib');
    }

    public function index($data = null)
    {
        $data = $this->data;
        $employee_id = $this->Employee->get_logged_in_employee_info()->person_id;

        $data['table_headers'] = $this->xss_clean(get_store_items_manage_table_headers());

        //$data['stock_location'] = $this->xss_clean($this->item_lib->get_item_location());
        $data['stock_locations'] = $this->xss_clean($this->Stock_location->get_allowed_locations('raw_items'));

        //$data['item_types'] = [2=>'Warehouse Items', 3=>'Store Items', 1=>'Vendor Items'];

        // if store has permission of processing raw material to make final goods
        if($this->Employee->has_module_grant('raw_items_processing', $employee_id))
        {
            $data['item_types'][4] = 'Processed Items';
        }

        $data['selected_type'] = 2;
        return view('store_items/manage', $data);
    }

    /**
     * Sync db
     */
    // public function sync()
    // {
    //     $data = array();
    //     $data['success'] = shell_exec('php index.php cli sync');

    //     if (!$this->input->is_ajax_request()) {
    //         $this->index($data);
    //     }

    //     echo $data['success'];

    // }


    /*
    Returns Items table data rows. This will be called with AJAX.
    */
    public function search()
    {
        $employee_id = $this->Employee->get_logged_in_employee_info()->person_id;

        $search = request()->getGet('search');
        $limit = request()->getGet('limit');
        $offset = request()->getGet('offset');
        $sort = request()->getGet('sort');
        $order = request()->getGet('order');

		$search = $search !== null ? $search : '';
		$sort = ($sort !== null) ? $sort : 'name';

        $items = $this->Store_item->search($search, $employee_id, $limit, $offset, $sort, $order);
        
        // echo "<pre>";
        // print_r($items->result());
        // exit();
        $total_rows = $this->Store_item->get_found_rows($search, $employee_id);

        $data_rows = array();
        foreach ($items->getResult() as $item) {
            $data_rows[] = $this->xss_clean(get_store_item_data_row($item, $this));
        }

        echo json_encode(array('total' => $total_rows, 'rows' => $data_rows));
    }

    public function pic_thumb($pic_id)
    {
        $this->load->helper('file');
        $this->load->library('image_lib');
        $base_path = "./uploads/item_pics/" . $pic_id;
        $images = glob($base_path . ".*");
        if (sizeof($images) > 0) {
            $image_path = $images[0];
            $ext = pathinfo($image_path, PATHINFO_EXTENSION);
            $thumb_path = $base_path . $this->image_lib->thumb_marker . '.' . $ext;
            if (sizeof($images) < 2) {
                $config['image_library'] = 'gd2';
                $config['source_image'] = $image_path;
                $config['maintain_ratio'] = TRUE;
                $config['create_thumb'] = TRUE;
                $config['width'] = 52;
                $config['height'] = 32;
                $this->image_lib->initialize($config);
                $image = $this->image_lib->resize();
                $thumb_path = $this->image_lib->full_dst_path;
            }
            $this->output->set_content_type(get_mime_by_extension($thumb_path));
            $this->output->set_output(file_get_contents($thumb_path));
        }
    }

}

?>
