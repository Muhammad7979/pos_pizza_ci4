<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
// $routes->add('/', 'Login::index');
$routes->add('no_access/(:segment)', 'no_access::index/$1');
$routes->add('no_access/(:segment)/(:segment)', 'no_acces::index/$1/$2');
$routes->add('reports/(summary_:any)/(:segment)/(:segment)', 'Reports::$1/$2/$3/$4/$5/$6');
$routes->add('reports/summary_:any', 'Reports::date_input');
$routes->add('reports/(graphical_:any)/(:segment)/(:segment)', 'Reports::$1/$2/$3/$4');
$routes->add('reports/graphical_:any', 'Reports::date_input');
$routes->add('reports/(inventory_:any)/(:segment)', 'Reports::$1/$2');
$routes->add('reports/inventory_summary', 'Reports::inventory_summary_input');
$routes->add('reports/(inventory_summary)/(:segment)/(:segment)/(:segment)', 'Reports::$1/$2/$3/$4');
$routes->add('reports/(detailed_:any)/(:segment)/(:segment)/(:segment)', 'Reports::$1/$2/$3/$4');
$routes->add('reports/detailed_:any', 'Reports::date_input_sales');
$routes->add('reports/(detailed_receivings)/(:segment)/(:segment)/(:segment)', 'Reports::$1/$2/$3/$4');
$routes->add('reports/detailed_receivings', 'Reports::date_input_recv');
$routes->add('reports/(specific_:any)/(:segment)/(:segment)/(:segment)', 'Reports::$1/$2/$3/$4');
$routes->add('reports/specific_customer', 'Reports::specific_customer_input');
$routes->add('reports/specific_employee', 'Reports::specific_employee_input');
$routes->add('reports/specific_discount', 'Reports::specific_discount_input');

$routes->add('reports/(inventoryi_:any)/([^/]+)/([^/]+)','Reports::$1/$2/$3/$4/$5/$6');
$routes->add('reports/inventoryi_:any','Reports::inventoryi_input');


$routes->group('api', function($routes) {
    $routes->get('get_completed_orders', 'Api::pizza_completed_orders');
    $routes->delete('delete_pizza_completed_order/(:segment)', 'Api::delete_pizza_completed_order/$1');
});

/// API Routes
$routes->group('api/v1/user', function($routes) {
    $routes->post('login', 'Api::login_user');
    $routes->post('logout', 'Api::logout_user');

    $routes->get('orders_counts/(:any)', 'Api::user_orders_counts/$1');
    $routes->get('orders_items/(:any)', 'Api::user_orders_items/$1');
    $routes->get('items_type', 'Api::user_items_type');
    $routes->get('items_type_items/(:any)', 'Api::user_items_type_items/$1');
    $routes->get('generate_slip/(:any)/(:any)/(:any)', 'Api::user_generate_slip/$1/$2/$3');
    $routes->get('generatePdf/(:any)/(:any)/(:any)', 'Api::generatePDF/$1/$2/$3');
    $routes->get('generate_report/(:any)/(:any)', 'Api::user_generate_report/$1/$2');
    $routes->get('generateReportPdf/(:any)/(:any)', 'Api::generateReportPDF/$1/$2');

    $routes->get('store_products/(:any)', 'Api::user_store_products/$1');
    $routes->get('counter_products/(:any)', 'Api::user_counter_products/$1');
    $routes->get('production_products/(:any)', 'Api::user_production_products/$1');
    $routes->get('production_products_attributes/(:any)', 'Api::user_production_products_attributes/$1');
    $routes->get('production_counter_items/(:any)', 'Api::user_production_counter_items/$1');

    $routes->get('counters/(:any)/(:any)', 'Api::user_counters/$1/$2');
    $routes->get('production/(:any)/(:any)/(:any)', 'Api::user_production/$1/$2/$3');
    $routes->get('pizza_products', 'Api::user_pizza_products');
    $routes->get('pizza_order_products', 'Api::user_pizza_order_products');
    $routes->get('pizza_reorder_products', 'Api::user_pizza_reorder_products');
    $routes->get('other_pizza_products/(:any)/(:any)', 'Api::user_other_pizza_products/$1/$2');
    $routes->get('other_pizza_extras/(:any)', 'Api::user_other_pizza_extras/$1');
    $routes->get('other_pizza_ingredients/(:any)', 'Api::user_other_pizza_ingredients/$1');
    $routes->get('pizza_order_list/(:any)/(:any)', 'Api::user_pizza_order_list/$1/$2');
    $routes->get('pizza_order_status/(:any)', 'Api::user_pizza_order_status/$1');
    $routes->get('pizza_order_canceled/(:any)', 'Api::user_pizza_order_canceled/$1');
    $routes->delete('pizza_order_delete/(:any)/(:any)', 'Api::user_pizza_order_delete/$1/$2');
    $routes->delete('pizza_order_cancel_waste/(:any)/(:any)', 'Api::user_pizza_order_cancel_waste/$1/$2');
    $routes->put('pizza_order_update_status/(:any)/(:any)', 'Api::user_pizza_order_update_status/$1/$2');
    $routes->get('order_products', 'Api::user_order_products');
    $routes->get('order_products/(:any)', 'Api::user_order_products/$1');
    $routes->get('order_product/(:any)', 'Api::user_order_single_product/$1');
    $routes->post('update_order', 'Api::user_update_order_product');
    $routes->get('counter_items/(:any)', 'Api::user_counter_items/$1');
    $routes->get('discard_types', 'Api::user_discard_types');
    $routes->get('discard_counter_items', 'Api::user_discard_counter_items');
    $routes->get('received_orders/(:any)', 'Api::user_received_order/$1');
    $routes->post('update_deliver_order', 'Api::user_update_deliver_order_product');

    $routes->get('generate_counter_report/(:any)/(:any)/(:any)/(:any)', 'Api::inventoryi_counter_item/$1/$2/$3/$4');
    $routes->get('generate_pizza_report/(:any)/(:any)/(:any)/(:any)', 'Api::inventoryi_pizza_stock/$1/$2/$3/$4');

    // Expo Notifications
    $routes->post('create_new_token', 'Api::user_create_new_token');
    // Notifications
    $routes->get('notifications/(:any)', 'Api::user_notifications/$1');
    $routes->get('notifications_count/(:any)', 'Api::user_notifications_count/$1');
    $routes->get('notifications_check/(:any)', 'Api::user_notifications_check/$1');
});
