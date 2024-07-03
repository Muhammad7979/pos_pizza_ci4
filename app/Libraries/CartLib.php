<?php

namespace App\Libraries;

use CodeIgniter\Session\Session;

/**
 * Shopping Cart Class
 */
class CartLib
{
    /**
     * These are the regular expression rules that we use to validate the product ID and product name
     * alpha-numeric, dashes, underscores, or periods
     *
     * @var string
     */
    public $product_id_rules = '\.a-z0-9_-';

    /**
     * These are the regular expression rules that we use to validate the product ID and product name
     * alpha-numeric, dashes, underscores, colons or periods
     *
     * @var string
     */
    public $product_name_rules = '\w \-\.\:';

    /**
     * only allow safe product names
     *
     * @var bool
     */
    public $product_name_safe = TRUE;

    /**
     * Contents of the cart
     *
     * @var array
     */
    protected $_cart_contents = [];

    /**
     * Reference to the session
     *
     * @var Session
     */
    protected $session;

    /**
     * Shopping Cart Constructor
     *
     * @param array $params
     */
    public function __construct($params = [])
    {
        // Get the session instance
        $this->session = session();

        // Are any config settings being passed manually? If so, set them
        $config = is_array($params) ? $params : [];

        // Grab the shopping cart array from the session table
        $this->_cart_contents = $this->session->get('cart_contents');
        if ($this->_cart_contents === NULL)
        {
            // No cart exists so we'll set some base values
            $this->_cart_contents = ['cart_total' => 0, 'total_items' => 0];
        }

        log_message('info', 'CartLib Class Initialized');
    }

    /**
     * Insert items into the cart and save it to the session
     *
     * @param array $items
     * @return bool|string
     */
    public function insert($items = [])
    {
        // Was any cart data passed? No? Bah...
        if (!is_array($items) || count($items) === 0)
        {
            log_message('error', 'The insert method must be passed an array containing data.');
            return FALSE;
        }

        $save_cart = FALSE;
        if (isset($items['id']))
        {
            if (($rowid = $this->_insert($items)))
            {
                $save_cart = TRUE;
            }
        }
        else
        {
            foreach ($items as $val)
            {
                if (is_array($val) && isset($val['id']))
                {
                    if ($this->_insert($val))
                    {
                        $save_cart = TRUE;
                    }
                }
            }
        }

        // Save the cart data if the insert was successful
        if ($save_cart === TRUE)
        {
            $this->_save_cart();
            return isset($rowid) ? $rowid : TRUE;
        }

        return FALSE;
    }

    /**
     * Insert
     *
     * @param array $items
     * @return bool|string
     */
    protected function _insert($items = [])
    {
        // Was any cart data passed? No? Bah...
        if (!is_array($items) || count($items) === 0)
        {
            log_message('error', 'The insert method must be passed an array containing data.');
            return FALSE;
        }

        // Does the $items array contain an id, quantity, price, and name?  These are required
        if (!isset($items['id'], $items['qty'], $items['price'], $items['name']))
        {
            log_message('error', 'The cart array must contain a product ID, quantity, price, and name.');
            return FALSE;
        }

        // Prep the quantity. It can only be a number.  Duh... also trim any leading zeros
        $items['qty'] = (float) $items['qty'];

        // If the quantity is zero or blank there's nothing for us to do
        if ($items['qty'] == 0)
        {
            return FALSE;
        }

        // Validate the product ID. It can only be alpha-numeric, dashes, underscores or periods
        if (!preg_match('/^[' . $this->product_id_rules . ']+$/i', $items['id']))
        {
            log_message('error', 'Invalid product ID. The product ID can only contain alpha-numeric characters, dashes, and underscores.');
            return FALSE;
        }

        // Validate the product name. It can only be alpha-numeric, dashes, underscores, colons or periods.
        if ($this->product_name_safe && !preg_match('/^[' . $this->product_name_rules . ']+$/i', $items['name']))
        {
            log_message('error', 'An invalid name was submitted as the product name: ' . $items['name'] . ' The name can only contain alpha-numeric characters, dashes, underscores, colons, and spaces.');
            return FALSE;
        }

        // Prep the price. Remove leading zeros and anything that isn't a number or decimal point.
        $items['price'] = (float) $items['price'];

        // Create a unique identifier for the item being inserted into the cart.
        if (isset($items['options']) && count($items['options']) > 0)
        {
            $rowid = md5($items['id'] . serialize($items['options']));
        }
        else
        {
            $rowid = md5($items['id']);
        }

        // Now that we have our unique "row ID", we'll add our cart items to the master array
        $old_quantity = isset($this->_cart_contents[$rowid]['qty']) ? (int) $this->_cart_contents[$rowid]['qty'] : 0;

        $items['rowid'] = $rowid;
        $items['qty'] += $old_quantity;
        $this->_cart_contents[$rowid] = $items;

        return $rowid;
    }

    /**
     * Update the cart
     *
     * @param array $items
     * @return bool
     */
    public function update($items = [])
    {
        // Was any cart data passed?
        if (!is_array($items) || count($items) === 0)
        {
            return FALSE;
        }

        $save_cart = FALSE;
        if (isset($items['rowid']))
        {
            if ($this->_update($items) === TRUE)
            {
                $save_cart = TRUE;
            }
        }
        else
        {
            foreach ($items as $val)
            {
                if (is_array($val) && isset($val['rowid']))
                {
                    if ($this->_update($val) === TRUE)
                    {
                        $save_cart = TRUE;
                    }
                }
            }
        }

        // Save the cart data if the insert was successful
        if ($save_cart === TRUE)
        {
            $this->_save_cart();
            return TRUE;
        }

        return FALSE;
    }

    /**
     * Update the cart
     *
     * This function permits changing item properties.
     * Typically it is called from the "view cart" page if a user makes
     * changes to the quantity before checkout. That array must contain the
     * rowid and quantity for each item.
     *
     * @param array $items
     * @return bool
     */
    protected function _update($items = [])
    {
        // Without these array indexes there is nothing we can do
        if (!isset($items['rowid'], $this->_cart_contents[$items['rowid']]))
        {
            return FALSE;
        }

        // Prep the quantity
        if (isset($items['qty']))
        {
            $items['qty'] = (float) $items['qty'];
            // Is the quantity zero?  If so we will remove the item from the cart.
            // If the quantity is greater than zero we are updating
            if ($items['qty'] == 0)
            {
                unset($this->_cart_contents[$items['rowid']]);
                return TRUE;
            }
        }

        // find updatable keys
        $keys = array_intersect(array_keys($this->_cart_contents[$items['rowid']]), array_keys($items));
        // if a price was passed, make sure it contains valid data
        if (isset($items['price']))
        {
            $items['price'] = (float) $items['price'];
        }

        // product id & name shouldn't be changed
        foreach (array_diff($keys, ['id', 'name']) as $key)
        {
            $this->_cart_contents[$items['rowid']][$key] = $items[$key];
        }

        return TRUE;
    }

    /**
     * Save the cart array to the session
     *
     * @return bool
     */
    protected function _save_cart()
    {
        // Add up the individual prices and set the cart sub-total
        $this->_cart_contents['total_items'] = $this->_cart_contents['cart_total'] = 0;
        foreach ($this->_cart_contents as $key => $val)
        {
            // Ensure the array contains the proper indexes
            if (!is_array($val) || !isset($val['price'], $val['qty']))
            {
                continue;
            }

            $this->_cart_contents['cart_total'] += ($val['price'] * $val['qty']);
            $this->_cart_contents['total_items'] += $val['qty'];
            $this->_cart_contents[$key]['subtotal'] = ($this->_cart_contents[$key]['price'] * $this->_cart_contents[$key]['qty']);
        }

        // Is our cart empty? If so we delete it from the session
        if (count($this->_cart_contents) <= 2)
        {
            $this->session->remove('cart_contents');
            return FALSE;
        }

        // Update the session
        $this->session->set('cart_contents', $this->_cart_contents);
        return TRUE;
    }

    /**
     * Cart Total
     *
     * @return int
     */
    public function total()
    {
        return $this->_cart_contents['cart_total'];
    }

    /**
     * Total Items
     *
     * @return int
     */
    public function total_items()
    {
        return $this->_cart_contents['total_items'];
    }

    /**
     * Cart Contents
     *
     * @return array
     */
    public function contents()
    {
        $cart = $this->_cart_contents;

        // Remove these so they don't create a problem when showing the cart table
        unset($cart['total_items']);
        unset($cart['cart_total']);

        return $cart;
    }

    /**
     * Has options
     *
     * Returns TRUE if the rowid passed to this function correlates to an item
     * that has options associated with it.
     *
     * @param string $rowid
     * @return bool
     */
    public function has_options($rowid = '')
    {
        return (isset($this->_cart_contents[$rowid]['options']) && count($this->_cart_contents[$rowid]['options']) !== 0);
    }

    /**
     * Product options
     *
     * Returns the an array of options, for a particular product row ID
     *
     * @param string $rowid
     * @return array
     */
    public function product_options($rowid = '')
    {
        return isset($this->_cart_contents[$rowid]['options']) ? $this->_cart_contents[$rowid]['options'] : [];
    }

    /**
     * Format Number
     *
     * Returns the supplied number with commas and a decimal point.
     *
     * @param float $n
     * @return string
     */
    public function format_number($n = '')
    {
        return ($n === '') ? '' : number_format((float) $n, 2, '.', ',');
    }

    /**
     * Destroy the cart
     *
     * Empties the cart and kills the session
     *
     * @return void
     */
    public function destroy()
    {
        $this->_cart_contents = ['cart_total' => 0, 'total_items' => 0];
        $this->session->remove('cart_contents');
    }
}
