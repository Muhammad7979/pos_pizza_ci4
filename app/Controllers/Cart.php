<?php
namespace App\Controllers;
use App\Libraries\CartLib;
class Cart extends BaseController{
     
    protected $cart;
    function __construct(){
        // parent::__construct();
        $this->cart = new CartLib() ;
    }
 
    // function index(){
    //     $data['data']=$this->product_model->get_all_product();
    //     $this->load->view('product_view',$data);
    // }
    

    // Add to Cart Item
    function add_to_cart(){ 

        $price = (request()->getPost('price_single_wf2')) ? request()->getPost('price_single_wf2') : request()->getPost('price_single');
        $extras_price = (request()->getPost('extras_price')) ? request()->getPost('extras_price') : 0;

        $price += $extras_price;

        
        $id = request()->getPost('item_id');
        $name = request()->getPost('item_name');
        $qty = request()->getPost('quantity');
        $size = request()->getPost('size');
        $item_category1 = request()->getPost('item_category1');


        // Type 4 is for pizza items only
        $type = (request()->getPost('type')) ? request()->getPost('type') : 4;
        $layer = (request()->getPost('layer')) ? request()->getPost('layer') : 0;
        $dough = (request()->getPost('dough')) ? request()->getPost('dough') : 0;
        $is_half = (request()->getPost('is_half')) ? request()->getPost('is_half') : 0;
        $add_item_id = (request()->getPost('add_item_id')) ? request()->getPost('add_item_id') : '';
        $item_category2 = (request()->getPost('item_category2')) ? request()->getPost('item_category2') : '';
        $add_item_name = (request()->getPost('add_item_name')) ? request()->getPost('add_item_name') : '';
        $item_description = (request()->getPost('item_description')) ? request()->getPost('item_description') : '';

        $extras1_title = (request()->getPost('extras1_title')) ? request()->getPost('extras1_title') : '';
        $extras2_title = (request()->getPost('extras2_title')) ? request()->getPost('extras2_title') : '';
        $ingredients1_title = (request()->getPost('ingredients1_title')) ? request()->getPost('ingredients1_title') : '';
        $ingredients2_title = (request()->getPost('ingredients2_title')) ? request()->getPost('ingredients2_title') : '';

        // Extra Toppings
            $extras1 = request()->getPost('extras1');
            $extras1 == null && $extras1 = [];
            $extras1_string = '';
            if(count($extras1)>0){
                foreach ($extras1 as $key => $value) {
                    $extras1_string .= $value.',';
                }
                $extras1_string = rtrim($extras1_string,',');
            }

            $extras2 = request()->getPost('extras2');
            $extras2 == null && $extras2 = [];

            $extras2_string = '';
            if(count($extras2)>0){
                foreach ($extras2 as $key => $value) {
                    $extras2_string .= $value.',';
                }
                $extras2_string = rtrim($extras2_string,',');
            }

            $extras1 = ($extras1_string) ? $extras1_string : '';
            $extras1_trim = str_replace(',', '', $extras1);
            $extras2 = ($extras2_string) ? $extras2_string : '';
            $extras2_trim = str_replace(',', '', $extras2);
        // Extra Toppings

        // Inredients
            if(request()->getPost('extras3')){
                $ingredients1 = request()->getPost('extras3');
                $ingredients1_string = '';
                if(count($ingredients1)>0){
                    foreach ($ingredients1 as $key => $value) {
                        $ingredients1_string .= $value.',';
                    }
                    $ingredients1_string = rtrim($ingredients1_string,',');
                }

                $ingredients1 = ($ingredients1_string) ? $ingredients1_string : '';
                $ingredients1_trim = str_replace(',', '', $ingredients1);
            }else{
                $ingredients1 = request()->getPost('ingredients1');
                $ingredients1_trim = request()->getPost('ingredients1');
            }

            $ingredients2 = request()->getPost('extras4');
            $ingredients2 == null && $ingredients2 = [];

            $ingredients2_string = '';
            if(count($ingredients2)>0){
                foreach ($ingredients2 as $key => $value) {
                    $ingredients2_string .= $value.',';
                }
                $ingredients2_string = rtrim($ingredients2_string,',');
            }

            $ingredients2 = ($ingredients2_string) ? $ingredients2_string : '';
            $ingredients2_trim = str_replace(',', '', $ingredients2);
        // Inredients

        $get_size = ['','Mini','Small','Medium','Large','Xlarge'];
        $get_crust = ['Thick','Thin'];
        $get_dough = ['Plain','W WH'];
        $get_isHalf = ['No','Yes'];

        $data = array(
            'id' => $id, 
            'name' => $name, 
            'item_category1' => $item_category1, 
            'price' => $price, 
            'qty' => $qty, 
            'type' => $type, 
            'size' => $size, 
            'size_title' => $get_size[$size], 
            'layer' => $layer, 
            'layer_title' => $get_crust[$layer], 
            'dough' => $dough, 
            'dough_title' => $get_dough[$dough], 
            'is_half' => $is_half, 
            'is_half_title' => $get_isHalf[$is_half], 
            'add_item_id' => $add_item_id, 
            'add_item_name' => $add_item_name, 
            'item_category2' => $item_category2, 
            'item_description' => $item_description, 
            'extras1' => $extras1, 
            'extras1_title' => $extras1_title, 
            'extras2' => $extras2, 
            'extras2_title' => $extras2_title, 
            'ingredients1' => $ingredients1, 
            'ingredients1_title' => $ingredients1_title, 
            'ingredients2' => $ingredients2, 
            'ingredients2_title' => $ingredients2_title, 
            'options' => array('Size' => $size, 'Type' => $type, 'Layer' => $layer, 'Dough' => $dough, 'Ishalf' => $is_half, 'Additem' => $add_item_id, 'Ingredients1' => $ingredients1_trim, 'Ingredients2' => $ingredients2_trim, 'Extras1' => $extras1_trim, 'Extras2' => $extras2_trim),
        );

        $this->cart->insert($data);
        echo $this->show_cart(); 
    }
 
    function show_cart(){ 
        $output = '';
        $no = 0;
        foreach ($this->cart->contents() as $items) {
            $no++;
            $output .='
                <tr class="showhr">
                    <td>'.$items['name'].'</td>
                    <td>'.$items['size_title'].'</td>
                    <td>'.$items['layer_title'].' / '.$items['dough_title'].'</td>
                    <td>'.number_format($items['price']).'</td>
                    <td>'.$items['qty'].'</td>
                    <td>'.number_format($items['subtotal']).'</td>
                    <td><button type="button" id="'.$items['rowid'].'" class="remove_cart">Remove</button></td>
                </tr>
            ';
            if($items['is_half']==1){
                $output .='
                    <tr class="hidehr" style="display: none; text-align:left">
                        <td colspan="7">';
                        if($items['ingredients1_title']!=''){
                            $output .= '<span>Removed :</span> '.$items['ingredients1_title'].'<br>';
                        }
                        if($items['extras1_title']!=''){
                            $output .= '<span>Add :</span> '.$items['extras1_title'].'<br>';
                        }

                        $output .= '<span>Is-Half :</span> '.$items['is_half_title'].'<br>
                        <span>Flavor 2 :</span> '.$items['add_item_name'].'<br>';

                        if($items['ingredients2_title']!=''){
                            $output .= '<span>Flavor 2 Removed :</span> '.$items['ingredients2_title'].'<br>';
                        }
                        if($items['extras2_title']!=''){
                            $output .= '<span>Flavor 2 Add :</span> '.$items['extras2_title'].'<br>';
                        }
                           
                        $output .= '</td>
                    </tr>
                ';
            }else{
                if($items['ingredients1_title']!='' && $items['extras1_title']!=''){
                $output .='
                    <tr class="hidehr" style="display: none;">
                        <td colspan="7">';
                            if($items['ingredients1_title']!=''){
                                $output .= '<span>Removed :</span> '.$items['ingredients1_title'].'<br>';
                            }
                            if($items['extras1_title']!=''){
                                $output .= '<span>Add :</span> '.$items['extras1_title'].'<br>';
                            }
                        $output .= '</td>
                    </tr>
                '; 
                }
            }
        }
        // $output .= '
        //     <tr>
        //         <th colspan="3">Total</th>
        //         <th colspan="2">'.'PKR '.number_format($this->cart->total()).'</th>
        //     </tr>
        // ';

        $output .= '<script> var count = '. $no .'; 
        count >= 1 ? $(".orderButton").prop("disabled", false) : $(".orderButton").prop("disabled", true);
        $(".showhr").click(function() {
            $(this).next("tr.hidehr").fadeToggle(300);
        });</script>';

        return $output;
    }
 
    function load_cart(){ 
        echo $this->show_cart();
    }
    
    // Remove From Cart
    function delete_cart(){ 
        $data = array(
            'rowid' => request()->getPost('row_id'), 
            'qty' => 0, 
        );
        $this->cart->update($data);
        echo $this->show_cart();
    }

    // Count Total Items
    // function load_cart_count(){
    //     echo count($this->cart->contents());
    // }

    // Count Total Quantities
    function load_cart_count(){ 
        $output = 0;
        foreach ($this->cart->contents() as $items) {
            $output += $items['qty'];
        }

        echo $output;
    }

    // Count Total Price
    function load_cart_price(){
        echo "PKR ".number_format($this->cart->total())."/";
    }
}