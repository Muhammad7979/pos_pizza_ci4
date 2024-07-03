<?php 
namespace App\Libraries;
use emberlabs\Barcode\BarcodeBase;
require APPPATH.'/Views/barcodes/BarcodeBase.php';
require APPPATH.'/Views/barcodes/Code39.php';
require APPPATH.'/Views/barcodes/Code128.php';
require APPPATH.'/Views/barcodes/Ean13.php';
require APPPATH.'/Views/barcodes/Ean8.php';
use emberlabs\Barcode\Code39;
use emberlabs\Barcode\Code128;
use emberlabs\Barcode\Ean13;
use emberlabs\Barcode\Ean8;

use App\Models\Appconfig;

class BarcodeLib
{
	private $CI;
	private $supported_barcodes = array('Code39' => 'Code 39', 'Code128' => 'Code 128', 'Ean8' => 'EAN 8', 'Ean13' => 'EAN 13');
	protected $Appconfig;
	public function __construct()
	{
		// $this->CI =& get_instance();
        $this->Appconfig = new Appconfig();
	}
	
	public function get_list_barcodes()
	{
		return $this->supported_barcodes;
	}
	
	public function get_barcode_config()
	{
		$data['company'] = $this->Appconfig->get('company');
		$data['barcode_content'] = $this->Appconfig->get('barcode_content');
		$data['barcode_type'] = $this->Appconfig->get('barcode_type');
		$data['barcode_font'] = $this->Appconfig->get('barcode_font');
		$data['barcode_font_size'] = $this->Appconfig->get('barcode_font_size');
		$data['barcode_height'] = $this->Appconfig->get('barcode_height');
		$data['barcode_width'] = $this->Appconfig->get('barcode_width');
		$data['barcode_quality'] = $this->Appconfig->get('barcode_quality');
		$data['barcode_first_row'] = $this->Appconfig->get('barcode_first_row');
		$data['barcode_second_row'] = $this->Appconfig->get('barcode_second_row');
		$data['barcode_third_row'] = $this->Appconfig->get('barcode_third_row');
		$data['barcode_num_in_row'] = $this->Appconfig->get('barcode_num_in_row');
		$data['barcode_page_width'] = $this->Appconfig->get('barcode_page_width');	  
		$data['barcode_page_cellspacing'] = $this->Appconfig->get('barcode_page_cellspacing');
		$data['barcode_generate_if_empty'] = $this->Appconfig->get('barcode_generate_if_empty');
		
		return $data;
	}

	public function validate_barcode($barcode)
	{
		$barcode_type = $this->Appconfig->get('barcode_type');
		$barcode_instance = $this->get_barcode_instance($barcode_type);
		return $barcode_instance->validate($barcode);
	}

	public static function barcode_instance($item, $barcode_config)
	{
		$barcode_instance = BarcodeLib::get_barcode_instance($barcode_config['barcode_type']);
		$is_valid = empty($item['item_number']) && $barcode_config['barcode_generate_if_empty'] || $barcode_instance->validate($item['item_number']);

		// if barcode validation does not succeed,
		if (!$is_valid)
		{
			$barcode_instance = BarcodeLib::get_barcode_instance();
		}
		$seed = BarcodeLib::barcode_seed($item, $barcode_instance, $barcode_config);
		$barcode_instance->setData($seed);

		return $barcode_instance;
	}

	private static function get_barcode_instance($barcode_type='Code128')
	{
		switch($barcode_type)
		{
			case 'Code39':
				return new Code39();
				break;
				
			case 'Code128':
			default:
				return new Code128();
				break;
				
			case 'Ean8':
				return new Ean8();
				break;
				
			case 'Ean13':
				return new Ean13();
				break;
		}
	}

	// private static function barcode_seed($item, $barcode_instance, $barcode_config)
	// {
	// 	$seed = $barcode_config['barcode_content'] !== "id" && !empty($item['item_number']) ? $item['item_number'] : $item['item_id'];

	// 	if( $barcode_config['barcode_content'] !== "id" && !empty($item['item_number']))
	// 	{
	// 		$seed = $item['item_number'];
	// 	}
	// 	else
	// 	{
	// 		if ($barcode_config['barcode_generate_if_empty'])
	// 		{
	// 			// generate barcode with the correct instance
	// 			$seed = $barcode_instance->generate($seed);
	// 		}
	// 		else
	// 		{
	// 			$seed = $item['item_id'];
	// 		}
	// 	}
	// 	return $seed;
	// }

	private static function barcode_seed($item, $barcode_instance, $barcode_config)
	{
		$seed = $barcode_config['barcode_content'] !== "id" && !empty($item['item_number']) ? $item['item_number'] : $item['item_id'];

		if( $barcode_config['barcode_content'] !== "id" && !empty($item['item_number']))
		{
			$seed = $item['item_number'];
		}
		else
		{
			if ($barcode_config['barcode_generate_if_empty'])
			{
				// generate barcode with the correct instance
				$seed = $barcode_instance->generate($seed);
			}
			else
			{
				$seed = $item['item_id'];
			}
		}

		if(isset($item['order_id'])){
			$seed = 'PIZA'.$item['order_id'];
		}
		return $seed;
	}

	private function generate_barcode($item, $barcode_config)
	{
		try
		{
			$barcode_instance = BarcodeLib::barcode_instance($item, $barcode_config);
			$barcode_instance->setQuality($barcode_config['barcode_quality']);
			$barcode_instance->setDimensions($barcode_config['barcode_width'], $barcode_config['barcode_height']);

			$barcode_instance->draw();

			return $barcode_instance->base64();
		} 
		catch(Exception $e)
		{
			echo 'Caught exception: ', $e->getMessage(), "\n";		
		}
	}

	public function generate_receipt_barcode($barcode_content)
	{
		try
		{
			// Code128 is the default and used in this case for the receipts
			$barcode = $this->get_barcode_instance();

			// set the receipt number to generate the barcode for
			$barcode->setData($barcode_content);
			
			// image quality 100
			$barcode->setQuality(100);
			
			// width: 200, height: 30
			$barcode->setDimensions(200, 30);

			// draw the image
			$barcode->draw();
			
			return $barcode->base64();
		} 
		catch(\Exception $e)
		{
			echo 'Caught exception: ', $e->getMessage(), "\n";		
		}
	}

	public function generate_sale_barcode($barcode_content)
	{
		try
		{
			// Code128 is the default and used in this case for the receipts
			$barcode = $this->get_barcode_instance();

			// set the receipt number to generate the barcode for
			$barcode->setData($barcode_content);

			// image quality 100
			$barcode->setQuality(100);

			// width: 320, height: 40
			$barcode->setDimensions(350, 40);

			// draw the image
			$barcode->draw();

			return $barcode->base64();
		}
		catch(\Exception $e)
		{
			echo 'Caught exception: ', $e->getMessage(), "\n";
		}
	}
	
	public function display_barcode($item, $barcode_config)
	{
		$display_table = "<table>";
		$display_table .= "<tr><td align='center'>" . $this->manage_display_layout($barcode_config['barcode_first_row'], $item, $barcode_config) . "</td></tr>";
		$barcode = $this->generate_barcode($item, $barcode_config);
		$display_table .= "<tr><td align='center'><img src='data:image/png;base64,$barcode' /></td></tr>";
		$display_table .= "<tr><td align='center'>" . $this->manage_display_layout($barcode_config['barcode_second_row'], $item, $barcode_config) . "</td></tr>";
		$display_table .= "<tr><td align='center'>" . $this->manage_display_layout($barcode_config['barcode_third_row'], $item, $barcode_config) . "</td></tr>";
		$display_table .= "</table>";
		
		return $display_table;
	}
	
	private function manage_display_layout($layout_type, $item, $barcode_config)
	{
		$result = '';
		
		if($layout_type == 'name')
		{
			$result = lang('items_name') . " " . $item['name'];
		}
		elseif($layout_type == 'category' && isset($item['category']))
		{
			$result = lang('items_category') . " " . $item['category'];
		}
		elseif($layout_type == 'cost_price' && isset($item['cost_price']))
		{
			$result = lang('items_cost_price') . " " . to_currency($item['cost_price']);
		}
		elseif($layout_type == 'unit_price' && isset($item['unit_price']))
		{
			$result = lang('items_unit_price') . " " . to_currency($item['unit_price']);
		}
		elseif($layout_type == 'company_name')
		{
			$result = $barcode_config['company'];
		}
		elseif($layout_type == 'item_code')
		{
			$result = $barcode_config['barcode_content'] !== "id" && isset($item['item_number']) ? $item['item_number'] : $item['item_id'];
		}

		return character_limiter($result, 40);
	}
	
	public function listfonts($folder) 
	{
		$array = array();

		if (($handle = opendir($folder)) !== false)
		{
			while (($file = readdir($handle)) !== false)
			{
				if(substr($file, -4, 4) === '.ttf')
				{
					$array[$file] = $file;
				}
			}
		}

		closedir($handle);

		array_unshift($array, lang('config_none'));

		return $array;
	}

	public function get_font_name($font_file_name)
	{
		return substr($font_file_name, 0, -4);
	}
	public function display_order_barcode($item, $barcode_config)
	{
		$display_table = "<table style='margin: 0 auto;'>";
		if(($item['name'])!=''){
		$display_table .= "<tr><td align='center' style='padding-bottom:5px;'>" . $item['name'] . "</td></tr>";
		}
		$barcode = $this->generate_barcode($item, $barcode_config);
		$display_table .= "<tr><td align='center'><img height='120' src='data:image/png;base64,$barcode' /></td></tr>";
		if(($item['unit_price'])>0){
		$display_table .= "<tr><td align='center' style='padding-top:1px;'>" . " Price " . parse_decimals($item['unit_price']) . "</td></tr>";
		}
		$display_table .= "</table>";
		
		return $display_table;

		// $display_table = "<table style='margin: 0 auto;'>";
		// $barcode = $this->generate_barcode($item, $barcode_config);
		// $display_table .= "<tr><td align='center'><img src='data:image/png;base64,$barcode' /></td></tr>";
		// $display_table .= "<tr><td align='center'>" . $this->manage_display_layout($barcode_config['barcode_second_row'], $item, $barcode_config) . "</td></tr>";
		// $display_table .= "</table>";
		
		// return $display_table;
	}

}

?>