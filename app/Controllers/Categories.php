<?php 
namespace App\Controllers;
use App\Controllers\SecureController;
use App\Libraries\BarcodeLib;
use App\Models\Employee;
use App\Models\Category;
use App\Models\Raw_item;
use App\Models\Stock_location;
use App\Models\Raw_inventory;
use App\Models\Appconfig;
use App\Controllers\Upload;
use App\Libraries\AppData;
use App\Libraries\Gu;
use App\Models\Item;
use App\Models\Module;
use Config\Services;

class Categories extends SecureController
{
    protected $Employee;
    protected $Category;
    protected $Raw_item;
    protected $Stock_location;
    protected $Raw_inventory;
    protected $Appconfig;
    protected $Upload;
	protected $appData;
    protected $Module;


	public function __construct()
	{
		parent::__construct('categories');
        $this->Employee = new Employee();
        $this->Category = new Category();
        $this->Raw_item = new Raw_item();
        $this->Stock_location = new Stock_location();
        $this->Raw_inventory = new Raw_inventory();
        $this->Appconfig = new Appconfig();
        $this->Upload = new Upload();
		$this->appData = new AppData();
        $this->Module = new Module();

	}


	public function index($data= null,$module_id=null)
	{
 
		$data['table_headers'] = $this->xss_clean(get_categories_manage_table_headers());
		$data = array_merge($data, $this->data);

		return view('categories/manage', $data);
	}

    /**
     * Sync db giftcard
     */
    public function sync_categories()
    {
        $data = array();
        $data['success'] = shell_exec('php index.php cli sync_categories');
        if (!request()->isAJAX()) {
            $this->index($data);
        }
        echo $data['success'];

    }

	/*
	Returns categories table data rows. This will be called with AJAX.
	*/
	public function search()
	{
		$employee_id = $this->Employee->get_logged_in_employee_info()->person_id;

		$search = request()->getGet('search');
		$limit  = request()->getGet('limit');
		$offset = request()->getGet('offset');
		$sort   = request()->getGet('sort');
		$order  = request()->getGet('order');

		$filters = array('start_date' => request()->getGet('start_date'),
            'end_date' => request()->getGet('end_date'));

        $search = $search !== null ? $search : '';
		$sort = ($sort !== null) ? $sort : 'item_id';
		$categories = $this->Category->search($search, $filters, $employee_id, $limit, $offset, $sort, $order);
		$total_rows = $this->Category->get_found_rows($search, $filters, $employee_id);

		$data_rows = array();
		foreach($categories->getResult() as $category)
		{
			// get attributes
			$suggestions = [];
			foreach ($this->Category->get_attributes_all($category->item_id,0)->getResultArray() as $row) {
	            $suggestions[] = $this->xss_clean($row['attribute_title']);
	        }
	        $category->category_attribute = implode(',', $suggestions);

			$data_rows[] = $this->xss_clean(get_category_data_row($category, $this));
		}

		$data_rows = $this->xss_clean($data_rows);

		echo json_encode(array('total' => $total_rows, 'rows' => $data_rows));
	}

	/*
	Gives search suggestions based on what is being searched for
	*/
	public function suggest_search()
	{
		$suggestions = $this->xss_clean($this->Category->get_search_suggestions(request()->getPost('term')));

		echo json_encode($suggestions);
	}

    /*
    Gives search suggestions based on what is being searched for
    */
    public function suggest_category()
    {
        $suggestions = $this->xss_clean($this->Category->get_category_suggestions(request()->getGet('term')));

        echo json_encode($suggestions);
    }

	public function get_row($row_id)
	{
		$category = $this->Category->get_info($row_id);
		$suggestions = [];
		foreach ($this->Category->get_attributes_all($category->item_id,0)->getResultArray() as $row) {
            $suggestions[] = $this->xss_clean($row['attribute_title']);
        }
        $category->category_attribute = implode(',', $suggestions);

        // convert special character
        //$category_value = $this->specialCharacterReplace($category->category_title, $sort=1);
        //$category->category_title = strtoupper(str_replace('_', ' ', $category_value));

		$data_row = $this->xss_clean(get_category_data_row($category, $this));

		echo json_encode($data_row);
	}

	public function pic_thumb($pic_id)
    {
        helper('file');
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

	public function view($item_id = -1)
	{
		$category_info = $this->Category->get_info($item_id);
        foreach (get_object_vars($category_info) as $property => $value) {
            $category_info->$property = $this->xss_clean($value);
        }

        $data['category_info'] = $category_info;

        $data['logo_exists'] = ($category_info->pic_id != '') ? true : false;
        if($data['logo_exists']){
            $images = glob("./uploads/item_pics/" . $category_info->pic_id . ".*");
        }
        else{
            $images = null;
        }
        if($item_id != -1 && $images !=null)
        {
        $data['image_path'] = sizeof($images) > 0 ? base_url($images[0]) : '';
        }

        $data['suggest_attributes'] = [];
        $data['suggest_attributes_extras'] = [];
        $data['suggest_attributes_ingredients'] = [];

        // on edit
        if($item_id!=-1){
        	$suggestions = [];
        	foreach ($this->Category->get_attributes_all($category_info->item_id,0)->getResultArray() as $row) {
	            $suggestions[] = [
	            		'attribute_title' => $this->xss_clean($row['attribute_title']),
	            		'attribute_price' => $this->xss_clean(parse_decimals($row['attribute_price'])),
	            	];
	        }
        	$data['suggest_attributes'] = $suggestions;

        	$suggestions2 = [];
        	foreach ($this->Category->get_attributes_all($category_info->item_id,1)->getResultArray() as $row) {
	            $suggestions2[] = [
	            		'attribute_title' => $this->xss_clean($row['attribute_title']),
	            		'attribute_price' => $this->xss_clean(parse_decimals($row['attribute_price'])),
	            	];
	        }
        	$data['suggest_attributes_extras'] = $suggestions2;

            $suggestions3 = [];
            foreach ($this->Category->get_attributes_all($category_info->item_id,2)->getResultArray() as $row) {
                $suggestions3[] = [
                        'attribute_title' => $this->xss_clean($row['attribute_title']),
                        'attribute_price' => $this->xss_clean(parse_decimals($row['attribute_price'])),
                    ];
            }
            $data['suggest_attributes_ingredients'] = $suggestions3;

        }
        $data['controller_name'] = 'categories';
        $data['appData'] = $this->appData->getAppData();
		$data = $this->xss_clean($data);

		return view("categories/form", $data);
	
	}

	// private function _handle_image_upload()
    // {
    //     helper('directory');

    //     $map = directory_map('./uploads/item_pics/', 1);

    //     // load upload library
    //     $config = array('upload_path' => './uploads/item_pics/',
    //         'allowed_types' => 'gif|jpg|jpeg|png',
    //         'max_size' => '100',
    //         'max_width' => '640',
    //         'max_height' => '480',
    //         'file_name' => sizeof($map) + 1
    //     );
    //     $this->load->library('upload', $config);
    //     $this->upload->do_upload('item_image');

    //     return strlen($this->upload->display_errors()) == 0 || !strcmp($this->upload->display_errors(), '<p>' . $this->lang->line('upload_no_file_selected') . '</p>');
    // }

    // private function _handle_image_upload()
    // {
    //     helper('filesystem');

    //     $map = directory_map('./uploads/item_pics/', 1);

    //     // Configuration for the upload
    //     $config = [
    //         'upload_path' => './uploads/item_pics/',
    //         'allowed_types' => 'gif|jpg|jpeg|png',
    //         'max_size' => 100, // 100 KB
    //         'max_width' => 640, // 640 pixels
    //         'max_height' => 480, // 480 pixels
    //         'file_name' => sizeof($map) + 1 // Unique file name based on file count
    //     ];

    //     $upload = Services::upload();
    //     $upload->initialize($config);

    //     if ($upload->do_upload('item_image')) {
    //         return true;
    //     } else {
    //         $error = $upload->display_errors();
    //         return strlen($error) == 0 || strcmp($error, '<p>' . lang('upload_no_file_selected') . '</p>') === 0;
    //     }
    // }


    private function _handle_image_upload($file = null)
    {
        helper(['filesystem', 'url']);
        $imageService =  Services::image();
        $uploadPath = './uploads/item_pics/';
        if (!is_dir($uploadPath)) {
            // mkdir($uploadPath, 0777, true);
            //if directory not exist
            return false;
        }
        
        $map = directory_map($uploadPath, 1);
        $newName = sizeof($map) + 1;
        // Configure file paths and names
        $fileName = $newName . '.' . $file->getClientExtension();
        $imagePath = $uploadPath . $fileName;
        $thumbPath = $uploadPath . $newName . '_thumb.' . $file->getClientExtension();
        if ($file->isValid() && !$file->hasMoved()) {
            if ($file->move($uploadPath, $fileName)) {
                $imageService->withFile($imagePath)
                ->resize(32, 32, true, 'auto')
                ->save($thumbPath);
                return [
                    'image_path' => $imagePath,
                    'thumb_path' => $thumbPath,
                    'file_name'  => $newName
                ];
            } else {
                // if the file could not be moved
                return false;
            }
        } else {
            // if the file is not valid or has already been moved
            return false;
        }
    }
    



    public function remove_logo($item_id)
    {
        $item_data = array('pic_id' => NULL);
        $result = $this->Raw_item->save_raw_item($item_data, $item_id);

        echo json_encode(array('success' => $result));
    }
 
	// public function save($item_id = -1)
	// {
    //     // echo "<pre>";
    //     // print_r(request()->getPost());
    //     // echo "</pre>";
    //     // exit();
	// 	// $upload_success = $this->_handle_image_upload();
    //     // $upload_data = $this->upload->data();
    //     $employee_id = $this->Employee->get_logged_in_employee_info()->person_id;
    //     $upload_data = request()->getFile('item_image');
    //     $Item = new Item();
    //     if(isset($upload_data))
    //     {
    //         $originalFilename = $upload_data->getName();
    //         $timestamp = time();
    //        $newFilename = $timestamp . '_' . $originalFilename;
    
    //        if ($upload_data && $upload_data->isValid() && !$upload_data->hasMoved()) {
    //            $destinationDirectory = './uploads/item_pics/';
    //            if (!is_dir($destinationDirectory)) {
    //                mkdir($destinationDirectory, 0777, true); // Create directory recursively
    //            }
           
    //            // Original uploaded filename
    //            $fileExtension = pathinfo($originalFilename, PATHINFO_EXTENSION); // Get the file extension
           
    //            // Generate a new filename with a timestamp
              
           
    //            $destination = $destinationDirectory . $newFilename;
               
    //            $itemid=$Item->get_info($item_id);
    //            $existingimage = $destinationDirectory . $itemid->pic_id;
   
    //            if(is_file($existingimage))
    //            {
    //                unlink($existingimage);
    //            }
    //            // Check if the file already exists
    //            if (is_file($destination || is_file($existingimage) )) {
    //                // Delete the previous file
    //                unlink($destination);
                  
    //            }
           
    //            // Move the uploaded file to the new destination
    //            $upload_data->move($destinationDirectory, $newFilename);
    //        }
    //     }  
        
	// 	$category_data = array(
	// 		'item_number' => request()->getPost('item_number'),
	// 		'category' => request()->getPost('category'),
	// 		'person_id' => $employee_id,
	// 		'name' => request()->getPost('category_name'),
	// 		'description' => request()->getPost('description'),
    //         'custom2' => request()->getPost('custom2'),
	// 		'item_type' => 4,
    //         'item_processed' => 0,
    //         'item_attributes' => 1,
	// 	);

    //     if(request()->getPost('is_pizza')){
    //         $category_data['is_pizza'] = request()->getPost('is_pizza');
    //     }

	// 	// item type 4 is for sub type items only
    //     // item processed 1 if for processed items only 
    //     // item attributes 1 is for sub type items only

	// 	// if (!empty($upload_data['orig_name'])) {
           
    //     //         $category_data['pic_id'] = $upload_data['raw_name'];
            
    //     // }

	// 	$attribute_titles = request()->getPost('attribute_titles')? request()->getPost('attribute_titles'): [];
	// 	$attribute_prices = request()->getPost('attribute_prices');

	// 	$attribute_extra_titles = request()->getPost('attribute_extra_titles');
	// 	$attribute_extra_prices = request()->getPost('attribute_extra_prices');

    //     $attribute_ingredient_titles = request()->getPost('attribute_ingredient_titles');
    //     // $attribute_ingredient_prices = request()->getPost('attribute_ingredient_prices');


	// 	if($this->Category->save_category($category_data, $item_id))
	// 	{
	// 		$success = TRUE;
	// 		$new_category = FALSE;
	// 		//New category
    //         if ($item_id == -1) {
    //             $item_id = $category_data['item_id'];
    //             $new_category = TRUE;
    //         }

    //         // save inventory
    //         $stock_locations = $this->Stock_location->get_undeleted_all('categories')->getResultArray();
    //         foreach ($stock_locations as $location) {
    //             if ($new_category) {

    //                 $inv_data = array(
    //                     'trans_date' => date('Y-m-d H:i:s'),
    //                     'trans_items' => $item_id,
    //                     'trans_user' => $employee_id,
    //                     'trans_location' => $location['location_id'],
    //                     'trans_comment' => ($new_category) ? lang('raw_items.raw_items_manually_editing_of_quantity') : lang('raw_items.raw_items_ordered_editing_of_quantity'),
    //                     'trans_inventory' => '0.000'
    //                 );

    //                 $success &= $this->Raw_inventory->insert_raw_inventory($inv_data);
    //             }
    //         }

    //         // for ($i=0; $i < count($attribute_titles); $i++) { 
    //         // 	$attributes_data[] = [
    //         // 		'attribute_id' => $i+1,
    //         //         'item_id' => $item_id,
    //         // 		'attribute_title' => $attribute_titles[$i],
    //         // 		'attribute_price' => $attribute_prices[$i],
    //         // 		'attribute_category' => 0,
    //         // 	];
    //         // }
    //         foreach ($attribute_titles as $key => $value) {
    //            if(!empty($attribute_titles[$key])){
    //                 $attributes_data[] = [
    //                     'attribute_id' => $key+1,
    //                     'item_id' => $item_id,
    //                     'attribute_title' => $attribute_titles[$key],
    //                     'attribute_price' => $attribute_prices[$key],
    //                     'attribute_category' => 0,
    //                 ];
    //             }
    //         }
        
    //         // for ($i=0; $i < count($attribute_extra_titles); $i++) { 
    //         // 	if(!empty($attribute_extra_titles[$i])){
	//            //  	$attributes_data[] = [
	//            //  		'attribute_id' => $i+1,
    //         //             'item_id' => $item_id,
	//            //  		'attribute_title' => $attribute_extra_titles[$i],
	//            //  		'attribute_price' => $attribute_extra_prices[$i],
	//            //  		'attribute_category' => 1,
	//            //  	];
	//            //  }
    //         // }

    //         foreach ($attribute_extra_titles as $key => $value) {
    //            if(!empty($attribute_extra_titles[$key])){
    //                 $attributes_data[] = [
    //                     'attribute_id' => $key+1,
    //                     'item_id' => $item_id,
    //                     'attribute_title' => $attribute_extra_titles[$key],
    //                     'attribute_price' => $attribute_extra_prices[$key],
    //                     'attribute_category' => 1,
    //                 ];
    //             }
    //         }
           
    //         foreach ($attribute_ingredient_titles as $key => $value) {
    //            if(!empty($attribute_ingredient_titles[$key])){
    //                 $attributes_data[] = [
    //                     'attribute_id' => $key+1,
    //                     'item_id' => $item_id,
    //                     'attribute_title' => $attribute_ingredient_titles[$key],
    //                     'attribute_price' => '0.00',
    //                     'attribute_category' => 2,
    //                 ];
    //             }
    //         }

    //         //delete old attributes list
    //         $this->Category->delete_attributes($item_id);
    //         //inserting batch attribute list

    //         $success = isset($attributes_data) ? $this->Category->save_attributes($attributes_data): true;


	// 		//$category_data = $this->xss_clean($category_data);

	// 		// if ($success && $upload_success) {
    //             if ($success) {
    //             $message = $this->xss_clean(lang('categories_lang.categories_successful_' . ($new_category ? 'adding' : 'updating')) . ' ' . $category_data['name']);

    //             echo json_encode(array('success' => TRUE, 'message' => $message, 'id' => $item_id));
    //         } else {

    //             $message = $this->xss_clean(lang('categories_lang.categories_error_adding_updating') . ' ' . $category_data['name']);

    //             echo json_encode(array('success' => FALSE, 'message' => $message, 'id' => $item_id));
    //         }


	// 	}
	// 	else //failure
	// 	{
	// 		$category_data = $this->xss_clean($category_data);
			
	// 		echo json_encode(array('success' => FALSE, 'message' => lang('categories_lang.categories_error_adding_updating').' '.
	// 						$category_data['name'], 'id' => -1));
	// 	}
	// }

    public function save($item_id = -1)
	{
        $file = $this->request->getFile('item_image');
        $upload_success = true;
        if(!empty($file)){
            $upload_success = $this->_handle_image_upload($file);
        } 
         $employee_id = $this->Employee->get_logged_in_employee_info()->person_id;
        
		$category_data = array(
			'item_number' => request()->getPost('item_number'),
			'category' => request()->getPost('category'),
			'person_id' => $employee_id,
			'name' => request()->getPost('category_name'),
			'description' => request()->getPost('description'),
            'custom2' => request()->getPost('custom2'),
			'item_type' => 4,
            'item_processed' => 0,
            'item_attributes' => 1,
		);
       
        if(!empty($file)){
            $category_data['pic_id'] = $upload_success['file_name'];
        }

        if(request()->getPost('is_pizza')){
            $category_data['is_pizza'] = request()->getPost('is_pizza');
        }

		// item type 4 is for sub type items only
        // item processed 1 if for processed items only 
        // item attributes 1 is for sub type items only

		// if (!empty($upload_data['orig_name'])) {
           
        //         $category_data['pic_id'] = $upload_data['raw_name'];
            
        // }

		$attribute_titles = request()->getPost('attribute_titles')? request()->getPost('attribute_titles'): [];
		$attribute_prices = request()->getPost('attribute_prices');

		$attribute_extra_titles = request()->getPost('attribute_extra_titles');
		$attribute_extra_prices = request()->getPost('attribute_extra_prices');

        $attribute_ingredient_titles = request()->getPost('attribute_ingredient_titles');
        // $attribute_ingredient_prices = request()->getPost('attribute_ingredient_prices');


		if($this->Category->save_category($category_data, $item_id))
		{
			$success = TRUE;
			$new_category = FALSE;
			//New category
            if ($item_id == -1) {
                $item_id = $category_data['item_id'];
                $new_category = TRUE;
            }

            // save inventory
            $stock_locations = $this->Stock_location->get_undeleted_all('categories')->getResultArray();
            foreach ($stock_locations as $location) {
                if ($new_category) {

                    $inv_data = array(
                        'trans_date' => date('Y-m-d H:i:s'),
                        'trans_items' => $item_id,
                        'trans_user' => $employee_id,
                        'trans_location' => $location['location_id'],
                        'trans_comment' => ($new_category) ? lang('raw_items.raw_items_manually_editing_of_quantity') : lang('raw_items.raw_items_ordered_editing_of_quantity'),
                        'trans_inventory' => '0.000'
                    );

                    $success &= $this->Raw_inventory->insert_raw_inventory($inv_data);
                }
            }

            // for ($i=0; $i < count($attribute_titles); $i++) { 
            // 	$attributes_data[] = [
            // 		'attribute_id' => $i+1,
            //         'item_id' => $item_id,
            // 		'attribute_title' => $attribute_titles[$i],
            // 		'attribute_price' => $attribute_prices[$i],
            // 		'attribute_category' => 0,
            // 	];
            // }
            foreach ($attribute_titles as $key => $value) {
               if(!empty($attribute_titles[$key])){
                    $attributes_data[] = [
                        'attribute_id' => $key+1,
                        'item_id' => $item_id,
                        'attribute_title' => $attribute_titles[$key],
                        'attribute_price' => $attribute_prices[$key],
                        'attribute_category' => 0,
                    ];
                }
            }
        
            // for ($i=0; $i < count($attribute_extra_titles); $i++) { 
            // 	if(!empty($attribute_extra_titles[$i])){
	           //  	$attributes_data[] = [
	           //  		'attribute_id' => $i+1,
            //             'item_id' => $item_id,
	           //  		'attribute_title' => $attribute_extra_titles[$i],
	           //  		'attribute_price' => $attribute_extra_prices[$i],
	           //  		'attribute_category' => 1,
	           //  	];
	           //  }
            // }

            foreach ($attribute_extra_titles as $key => $value) {
               if(!empty($attribute_extra_titles[$key])){
                    $attributes_data[] = [
                        'attribute_id' => $key+1,
                        'item_id' => $item_id,
                        'attribute_title' => $attribute_extra_titles[$key],
                        'attribute_price' => $attribute_extra_prices[$key],
                        'attribute_category' => 1,
                    ];
                }
            }
           
            foreach ($attribute_ingredient_titles as $key => $value) {
               if(!empty($attribute_ingredient_titles[$key])){
                    $attributes_data[] = [
                        'attribute_id' => $key+1,
                        'item_id' => $item_id,
                        'attribute_title' => $attribute_ingredient_titles[$key],
                        'attribute_price' => '0.00',
                        'attribute_category' => 2,
                    ];
                }
            }

            //delete old attributes list
            $this->Category->delete_attributes($item_id);
            //inserting batch attribute list

            $success = isset($attributes_data) ? $this->Category->save_attributes($attributes_data): true;


			//$category_data = $this->xss_clean($category_data);

			if ($success && $upload_success) {
                // if ($success) {
                $message = $this->xss_clean(lang('categories_lang.categories_successful_' . ($new_category ? 'adding' : 'updating')) . ' ' . $category_data['name']);

                echo json_encode(array('success' => TRUE, 'message' => $message, 'id' => $item_id));
            } else {

                // $message = $this->xss_clean(lang('categories_lang.categories_error_adding_updating') . ' ' . $category_data['name']);
                $message = $this->xss_clean($upload_success ? lang('categories_lang.categories_error_adding_updating') . ' ' . $category_data['name'] : 'Error in uploading image.');

                echo json_encode(array('success' => FALSE, 'message' => $message, 'id' => $item_id));
            }


		}
		else //failure
		{
			$category_data = $this->xss_clean($category_data);
			
			echo json_encode(array('success' => FALSE, 'message' => lang('categories_lang.categories_error_adding_updating').' '.
							$category_data['name'], 'id' => -1));
		}
	}
    
    public function check_item_number()
    {
        $exists = $this->Raw_item->item_number_exists(request()->getPost('item_number'), request()->getPost('item_id'));
        return !$exists ? 'true' : 'false';
    }

	public function delete()
	{
		$categories_to_delete = $this->xss_clean(request()->getPost('ids'));

		if($this->Category->delete_list($categories_to_delete))
		{
			echo json_encode(array('success' => TRUE, 'message' => lang('categories_lang.categories_successful_deleted').' '.
							count($categories_to_delete).' '.lang('categories_lang.categories_one_or_multiple')));
		}
		else
		{
			echo json_encode(array('success' => FALSE, 'message' => lang('categories_lang.categories_cannot_be_deleted')));
		}
	}

	public function generate_barcodes($item_ids)
    {
        $barcode_lib = new BarcodeLib();

        $item_ids = explode(':', $item_ids);
        $result = $this->Raw_item->get_multiple_info($item_ids)->getResultArray();
        $config = $barcode_lib->get_barcode_config();

        $data['barcode_config'] = $config;

        // check the list of items to see if any item_number field is empty
        foreach ($result as &$item) {
            $item = $this->xss_clean($item);

            // update the UPC/EAN/ISBN field if empty / NULL with the newly generated barcode
            if (empty($item['item_number']) && $this->Appconfig->get('barcode_generate_if_empty')) {
                // get the newly generated barcode
                $barcode_instance = BarcodeLib::barcode_instance($item, $config);
                $item['item_number'] = $barcode_instance->getData();

                $save_item = array('item_number' => $item['item_number']);

                // update the item in the database in order to save the UPC/EAN/ISBN field
                $this->Raw_item->save_raw_item($save_item, $item['item_id']);
            }
        }
        $data['items'] = $result;

        // display barcodes
        return view('barcodes/barcode_sheet', $data);
    }

	// public function suggest_attributes($category)
	// {
	// 	foreach ($this->Category->get_attributes_all($category)->result_array() as $row) {
 //            $suggestions[$this->xss_clean($row['attribute_title'])] = $this->xss_clean($row['attribute_title']);
 //        }
	// 	echo json_encode($suggestions);
	// }
}
?>
