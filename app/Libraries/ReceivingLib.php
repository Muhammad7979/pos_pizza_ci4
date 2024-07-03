<?php

namespace App\Libraries;
use Config\Services;
class ReceivingLib
{
   

    public function get_cart()
    {
        if (!session()->has('recv_cart')) {
            $this->set_cart([]);
        }

        return session()->get('recv_cart');
    }

    public function set_cart($cart_data)
    {
        session()->set('recv_cart', $cart_data);
    }

    public function empty_cart()
    {
        session()->remove('recv_cart');
    }

    public function get_supplier()
    {
        if (!session()->has('recv_supplier')) {
            $this->set_supplier(-1);
        }

        return session()->get('recv_supplier');
    }

    public function set_supplier($supplier_id)
    {
        session()->set('recv_supplier', $supplier_id);
    }

    public function remove_supplier()
    {
        session()->remove('recv_supplier');
    }

    public function get_mode()
    {
        if (!session()->has('recv_mode')) {
            $this->set_mode('receive');
        }

        return session()->get('recv_mode');
    }

    public function set_mode($mode)
    {
        session()->set('recv_mode', $mode);
    }

    public function clear_mode()
    {
        session()->remove('recv_mode');
    }

    public function get_stock_source()
    {
        if (!session()->has('recv_stock_source')) {
            $this->set_stock_source($this->CI->Stock_location->get_default_location_id());
        }

        return session()->get('recv_stock_source');
    }

    public function get_comment()
    {
        $comment = session()->get('recv_comment');

        return empty($comment) ? '' : $comment;
    }

    public function set_comment($comment)
    {
        session()->set('recv_comment', $comment);
    }

    public function clear_comment()
    {
        session()->remove('recv_comment');
    }

    public function get_reference()
    {
        return session()->get('recv_reference');
    }

    public function set_reference($reference)
    {
        session()->set('recv_reference', $reference);
    }

    public function clear_reference()
    {
        session()->remove('recv_reference');
    }

    public function is_print_after_sale()
    {
        return session()->get('recv_print_after_sale') === 'true' ||
            session()->get('recv_print_after_sale') === '1';
    }

    public function set_print_after_sale($print_after_sale)
    {
        return session()->set('recv_print_after_sale', $print_after_sale);
    }

    public function set_stock_source($stock_source)
    {
        session()->set('recv_stock_source', $stock_source);
    }

	public function clear_stock_source()
    {
       session()->remove('recv_stock_source');
    }

    public function get_stock_destination()
    {
        if (!session()->has('recv_stock_destination')) {
            $this->set_stock_destination($this->CI->Stock_location->get_default_location_id());
        }

        return session()->get('recv_stock_destination');
    }

    public function set_stock_destination($stock_destination)
    {
        session()->set('recv_stock_destination', $stock_destination);
    }

    public function clear_stock_destination()
    {
        session()->remove('recv_stock_destination');
    }

    public function add_item($item_id, $quantity)
    {
        $cart = $this->get_cart();

        if (isset($cart[$item_id])) {
            $cart[$item_id] += $quantity;
        } else {
            $cart[$item_id] = $quantity;
        }

        $this->set_cart($cart);
    }

    public function remove_item($item_id)
    {
        $cart = $this->get_cart();

        if (isset($cart[$item_id])) {
            unset($cart[$item_id]);
        }

        $this->set_cart($cart);
    }

    public function update_quantity($item_id, $quantity)
    {
        $cart = $this->get_cart();

        if (isset($cart[$item_id])) {
            $cart[$item_id] = $quantity;
        }

        $this->set_cart($cart);
    }
}

