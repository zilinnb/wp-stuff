<?php
/**
 * Plugin Name: WP-Stuff
 * Plugin URI: https://fuleo.net/archives/231.html
 * Description: 一个用于添加和展示好物的WordPress插件，支持分类、短代码以及响应式设计。
 * Version: 1.0.0
 * Author: Fuleo
 * Author URI: https://fuleo.net
 * Text Domain: wp-stuff
 * Domain Path: /languages
 */

// 如果直接访问此文件，则退出
if (!defined('ABSPATH')) {
    exit;
}

// 定义插件常量
define('WP_STUFF_VERSION', '1.0.0');
define('WP_STUFF_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_STUFF_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WP_STUFF_PLUGIN_BASENAME', plugin_basename(__FILE__));

// 引入必要的文件
require_once WP_STUFF_PLUGIN_DIR . 'includes/class-wp-stuff.php';

// 激活插件时的钩子
function wp_stuff_activate() {
    // 刷新伪静态规则
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'wp_stuff_activate');

// 停用插件时的钩子
function wp_stuff_deactivate() {
    // 刷新伪静态规则
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'wp_stuff_deactivate');

// 启动插件
function wp_stuff_init() {
    $wp_stuff = new WP_Stuff();
    $wp_stuff->init();
}
add_action('plugins_loaded', 'wp_stuff_init'); 