<?php
/**
 * Plugin Name: Woo Admin Filter By Stock
 * Plugin URI: http://www.extreme-idea.com/
 * Description: Plugin helps you to filter products by stock.
 * Version: 1.0.6
 * Author: EXTREME IDEA LLC
 * Author URI: http://www.extreme-idea.com/
 */

/**
 * Copyright (c) 2018 EXTREME IDEA LLC https://www.extreme-idea.com
 * This software is the proprietary information of EXTREME IDEA LLC.
 *
 * All Rights Reserved.
 * Modification, redistribution and use in source and binary forms, with or without modification
 * are not permitted without prior written approval by the copyright holder.
 *
 */

namespace Com\ExtremeIdea\Woocommerce\AdminFilterByStock;

use Com\ExtremeIdea\Ecommerce\Software\License\Manager\Verifier\Service\Rest\Impl\SoftwareLicenseVerifier;
use Com\ExtremeIdea\Ecommerce\Update\Plugin\Verifier\License\Fields\Service\Wordpress\Impl\WordpressUpdatePluginVerifierLicenseFields;
use Com\ExtremeIdea\Ecommerce\Update\Plugin\Verifier\Service\Wordpress\Impl\WordpressUpdatePluginVerifier;
use Com\ExtremeIdea\Ecommerce\Wordpress\License\Manager\Verifier\Impl\WordpressLicenseVerifier;

require_once __DIR__ . "/vendor/autoload.php";

add_action(
    'plugins_loaded',
    ['Com\ExtremeIdea\Woocommerce\AdminFilterByStock\WooCommerceAdminFilterByStock', 'init']
);

register_activation_hook(
    __FILE__,
    ['Com\ExtremeIdea\Woocommerce\AdminFilterByStock\WooCommerceAdminFilterByStock', 'activation']
);

/**
 * Class WooCommerceAdminFilterByStock
 *
 * @package Com\ExtremeIdea\Woocommerce\AdminFilterByStock
 */
class WooCommerceAdminFilterByStock
{

    const PLUGIN_VERSION = '1.0.6';
    const SOFTWARE_INTERNAL_ID = 'woocommerce_admin_filter_by_stock';

    protected static $instance = null;

    /**
     * WooCommerceAdminFilterByStock constructor.
     *
     * Add Filters
     *
     */
    private function __construct()
    {
        add_filter('woocommerce_product_filters', [$this, 'stockFilter']);
        add_filter('parse_query', [$this, 'stockFilterHandler']);
    }

    /**
     * Singleton function.
     * Init Plugin.
     *
     * @return WooCommerceAdminFilterByStock
     */
    public static function init()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static;
        }

        return static::$instance;
    }

    /**
     * Activation wordpress hook.
     *
     * @return void
     */
    public static function activation()
    {
        if (!is_plugin_active('woocommerce/woocommerce.php')) {
            wp_die(
                'Plugin requires <a href="http://www.woothemes.com/woocommerce/"
            target="_blank">WooCommerce</a> to be activated. Please install and activate <a href="' . admin_url(
                    'plugin-install.php?tab=search&type=term&s=WooCommerce'
                ) . '" target="_blank">WooCommerce</a> first.'
            );
        }
    }

    /**
     * Generate custom filter by stock and qty.
     *
     * @param string $output html
     *
     * @return string
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function stockFilter($output)
    {
        $operators = ['' => 'Stock Filter Operator', '>=' => '>=', '<=' => '<=', '>' => '>', '<' => '<', '=' => '='];

        $filterOperator = $this->buildSelectFilter('filter_by_stock_operator', $operators);

        $value = isset($_GET['filter_by_stock_value']) ? $_GET['filter_by_stock_value'] : '';
        $valueField =
            "<input type='number' name='filter_by_stock_value' value='$value' placeholder='Enter quantity' min='0'>";

        return $output . $filterOperator . $valueField;
    }

    /**
     * Build helper functions for generate select input.
     *
     * @param string $name    param name
     * @param array  $options select options
     *
     * @return string
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    protected function buildSelectFilter($name, $options)
    {
        $filter = "<select name='$name' id='dropdown_$name'>";
        foreach ($options as $value => $option) {
            $filter .= "<option value='$value' ";

            if (isset($_GET[$name])) {
                $filter .= selected($value, $_GET[$name], false);
            }
            $filter .= ">$option</option>";
        }
        $filter .= '</select>';

        return $filter;
    }

    /**
     * Stock filter handler.
     *
     * @param \WP_Query $query query
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function stockFilterHandler($query)
    {
        $qty['key'] = '_stock';
        $qty['param'] = (int)isset($_GET['filter_by_stock_value']) ? $_GET['filter_by_stock_value'] : 0;
        $operator = isset($_GET['filter_by_stock_operator']) ? $_GET['filter_by_stock_operator'] : false;

        $metaStockQuery = ['relation' => 'AND',
            'wafbs_qty_clause' => ['key' => $qty['key'], 'value' => (int)$qty['param'], 'type' => 'numeric',
                'compare' => $operator],];

        if (!$operator or !in_array($operator, ['>=', '<=', '>', '<', '='])) {
            unset($metaStockQuery['wafbs_qty_clause']);
            unset($metaStockQuery['relation']);
        }
        $metaQuery = isset($query->query_vars['meta_query']) ? $query->query_vars['meta_query'] : [];
        $query->query_vars['meta_query'] = array_merge_recursive($metaQuery, $metaStockQuery);
    }
}
