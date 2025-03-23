<?php
/**
 * 插件主类
 */
class WP_Stuff {
    /**
     * 构造函数
     */
    public function __construct() {
        // 初始化
    }

    /**
     * 初始化插件
     */
    public function init() {
        // 加载文本域
        add_action('init', array($this, 'load_textdomain'));

        // 注册自定义文章类型
        add_action('init', array($this, 'register_post_type'));

        // 注册自定义分类法
        add_action('init', array($this, 'register_taxonomy'));

        // 添加元框
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));

        // 保存元数据
        add_action('save_post', array($this, 'save_meta_box_data'));

        // 注册短代码
        add_action('init', array($this, 'register_shortcodes'));

        // 加载样式和脚本
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // 添加管理菜单
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // 自定义编辑屏幕
        add_action('admin_head', array($this, 'customize_edit_screen'));
        
        // 添加自定义列
        add_filter('manage_wp_stuff_posts_columns', array($this, 'add_custom_columns'));
        add_action('manage_wp_stuff_posts_custom_column', array($this, 'display_custom_column_content'), 10, 2);
        
        // 注册AJAX处理
        add_action('wp_ajax_load_category_items', array($this, 'ajax_load_category_items'));
        add_action('wp_ajax_nopriv_load_category_items', array($this, 'ajax_load_category_items'));
        
        // 在编辑页面隐藏编辑器
        add_action('init', array($this, 'remove_editor_support'));
        
        // 自定义标题输入框
        add_filter('enter_title_here', array($this, 'custom_enter_title'));
    }

    /**
     * 加载文本域
     */
    public function load_textdomain() {
        load_plugin_textdomain('wp-stuff', false, dirname(WP_STUFF_PLUGIN_BASENAME) . '/languages');
    }

    /**
     * 注册自定义文章类型
     */
    public function register_post_type() {
        $labels = array(
            'name'               => _x('好物', 'post type general name', 'wp-stuff'),
            'singular_name'      => _x('好物', 'post type singular name', 'wp-stuff'),
            'menu_name'          => _x('好物管理', 'admin menu', 'wp-stuff'),
            'name_admin_bar'     => _x('好物', 'add new on admin bar', 'wp-stuff'),
            'add_new'            => _x('添加好物', 'book', 'wp-stuff'),
            'add_new_item'       => __('添加新好物', 'wp-stuff'),
            'new_item'           => __('新好物', 'wp-stuff'),
            'edit_item'          => __('编辑好物', 'wp-stuff'),
            'view_item'          => __('查看好物', 'wp-stuff'),
            'all_items'          => __('所有好物', 'wp-stuff'),
            'search_items'       => __('搜索好物', 'wp-stuff'),
            'parent_item_colon'  => __('父级好物:', 'wp-stuff'),
            'not_found'          => __('没有找到好物。', 'wp-stuff'),
            'not_found_in_trash' => __('回收站中没有好物。', 'wp-stuff')
        );

        $args = array(
            'labels'             => $labels,
            'description'        => __('用于展示和推荐的好物。', 'wp-stuff'),
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array('slug' => 'stuff'),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array('title', 'editor', 'thumbnail', 'excerpt'),
            'menu_icon'          => 'dashicons-cart'
        );

        register_post_type('wp_stuff', $args);
    }

    /**
     * 注册自定义分类法
     */
    public function register_taxonomy() {
        $labels = array(
            'name'              => _x('好物分类', 'taxonomy general name', 'wp-stuff'),
            'singular_name'     => _x('好物分类', 'taxonomy singular name', 'wp-stuff'),
            'search_items'      => __('搜索好物分类', 'wp-stuff'),
            'all_items'         => __('所有好物分类', 'wp-stuff'),
            'parent_item'       => __('父级好物分类', 'wp-stuff'),
            'parent_item_colon' => __('父级好物分类:', 'wp-stuff'),
            'edit_item'         => __('编辑好物分类', 'wp-stuff'),
            'update_item'       => __('更新好物分类', 'wp-stuff'),
            'add_new_item'      => __('添加新好物分类', 'wp-stuff'),
            'new_item_name'     => __('新好物分类名称', 'wp-stuff'),
            'menu_name'         => __('好物分类', 'wp-stuff'),
        );

        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'stuff-category'),
        );

        register_taxonomy('stuff_category', 'wp_stuff', $args);
    }

    /**
     * 添加元框
     */
    public function add_meta_boxes() {
        add_meta_box(
            'wp_stuff_meta_box',
            __('好物详情', 'wp-stuff'),
            array($this, 'render_meta_box'),
            'wp_stuff',
            'normal',
            'high'
        );
    }

    /**
     * 渲染元框
     */
    public function render_meta_box($post) {
        // 添加一个nonce字段，稍后验证
        wp_nonce_field('wp_stuff_meta_box', 'wp_stuff_meta_box_nonce');

        // 获取现有元数据值
        $price = get_post_meta($post->ID, '_wp_stuff_price', true);
        $link = get_post_meta($post->ID, '_wp_stuff_link', true);
        $rating = get_post_meta($post->ID, '_wp_stuff_rating', true);
        $brand = get_post_meta($post->ID, '_wp_stuff_brand', true);
        $brand_image_id = get_post_meta($post->ID, '_wp_stuff_brand_image_id', true);
        $sort_order = get_post_meta($post->ID, '_wp_stuff_sort_order', true);
        $purchase_date = get_post_meta($post->ID, '_wp_stuff_purchase_date', true);
        $brand_image_url = '';
        
        if ($brand_image_id) {
            $brand_image_data = wp_get_attachment_image_src($brand_image_id, 'thumbnail');
            if ($brand_image_data) {
                $brand_image_url = $brand_image_data[0];
            }
        }

        // 输出WordPress原生风格的表单
        ?>
        <div class="wp-stuff-form wp-admin-form">
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><label for="wp_stuff_sort_order"><?php esc_html_e('排序值', 'wp-stuff'); ?></label></th>
                        <td>
                            <input type="number" id="wp_stuff_sort_order" name="wp_stuff_sort_order" value="<?php echo esc_attr($sort_order ? $sort_order : 0); ?>" min="0" step="1" class="regular-text" />
                            <p class="description"><?php esc_html_e('数字越小排序越靠前，默认为0', 'wp-stuff'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="wp_stuff_price"><?php esc_html_e('价格', 'wp-stuff'); ?></label></th>
                        <td>
                            <input type="text" id="wp_stuff_price" name="wp_stuff_price" value="<?php echo esc_attr($price); ?>" placeholder="如：299.00" class="regular-text" />
                            <p class="description"><?php esc_html_e('输入物品价格，仅支持数字和小数点', 'wp-stuff'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="wp_stuff_purchase_date"><?php esc_html_e('购买时间', 'wp-stuff'); ?></label></th>
                        <td>
                            <input type="date" id="wp_stuff_purchase_date" name="wp_stuff_purchase_date" value="<?php echo esc_attr($purchase_date); ?>" class="regular-text" />
                            <p class="description"><?php esc_html_e('选择物品的购买日期', 'wp-stuff'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="wp_stuff_brand"><?php esc_html_e('描述', 'wp-stuff'); ?></label></th>
                        <td>
                            <input type="text" id="wp_stuff_brand" name="wp_stuff_brand" value="<?php echo esc_attr($brand); ?>" placeholder="输入简短描述" class="regular-text" />
                            <p class="description"><?php esc_html_e('简短描述物品的用途或特点', 'wp-stuff'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="wp_stuff_link"><?php esc_html_e('购买链接', 'wp-stuff'); ?></label></th>
                        <td>
                            <input type="url" id="wp_stuff_link" name="wp_stuff_link" value="<?php echo esc_url($link); ?>" placeholder="http://或https://开头的链接" class="regular-text" />
                            <p class="description"><?php esc_html_e('输入完整购买链接地址，必须以http://或https://开头', 'wp-stuff'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label><?php esc_html_e('评分', 'wp-stuff'); ?></label></th>
                        <td>
                            <div class="wp-stuff-rating-stars">
                                <?php for ($i = 1; $i <= 5; $i++) : ?>
                                    <span data-rating="<?php echo $i; ?>" class="<?php echo ($i <= $rating) ? 'active' : ''; ?>">
                                        <?php echo ($i <= $rating) ? '★' : '☆'; ?>
                                    </span>
                                <?php endfor; ?>
                            </div>
                            <input type="hidden" id="wp_stuff_rating" name="wp_stuff_rating" value="<?php echo esc_attr($rating); ?>" />
                            <p class="description"><?php esc_html_e('点击星星给物品评分（1-5颗星）', 'wp-stuff'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label><?php esc_html_e('描述图标', 'wp-stuff'); ?></label></th>
                        <td>
                            <div class="wp-stuff-image-container">
                                <div class="wp-stuff-image-preview">
                                    <?php if ($brand_image_url) : ?>
                                        <img src="<?php echo esc_url($brand_image_url); ?>" id="wp-stuff-brand-image-preview" />
                                    <?php else : ?>
                                        <div class="wp-stuff-no-image-placeholder"><?php esc_html_e('无图标', 'wp-stuff'); ?></div>
                                        <img src="" id="wp-stuff-brand-image-preview" style="display:none;" />
                                    <?php endif; ?>
                                </div>
                                <div class="wp-stuff-image-buttons">
                                    <input type="hidden" id="wp_stuff_brand_image_id" name="wp_stuff_brand_image_id" value="<?php echo esc_attr($brand_image_id); ?>" />
                                    <button type="button" id="wp-stuff-brand-image-upload-button" class="button"><?php esc_html_e('选择图标', 'wp-stuff'); ?></button>
                                    <button type="button" id="wp-stuff-brand-image-remove-button" class="button" <?php echo empty($brand_image_url) ? 'style="display:none;"' : ''; ?>><?php esc_html_e('移除', 'wp-stuff'); ?></button>
                                </div>
                            </div>
                            <p class="description"><?php esc_html_e('上传一个小图标，用于描述物品品牌或类型', 'wp-stuff'); ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <div class="wp-stuff-tips postbox">
                <h3 class="hndle"><span><?php esc_html_e('提示', 'wp-stuff'); ?></span></h3>
                <div class="inside">
                    <ul>
                        <li><?php esc_html_e('标题：请在上方文本框中填写物品名称', 'wp-stuff'); ?></li>
                        <li><?php esc_html_e('详情：可在内容编辑框中填写详细介绍（可选）', 'wp-stuff'); ?></li>
                        <li><?php esc_html_e('图片：请在右侧"特色图片"面板中添加主图', 'wp-stuff'); ?></li>
                        <li><?php esc_html_e('分类：在右侧"好物分类"中选择合适分类', 'wp-stuff'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * 保存元数据框数据
     */
    public function save_meta_box_data($post_id) {
        // 检查nonce字段
        if (!isset($_POST['wp_stuff_meta_box_nonce']) || !wp_verify_nonce($_POST['wp_stuff_meta_box_nonce'], 'wp_stuff_meta_box')) {
            return;
        }

        // 如果这是自动保存，我们不想做任何事
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // 检查用户权限
        if (isset($_POST['post_type']) && 'wp_stuff' === $_POST['post_type']) {
            if (!current_user_can('edit_post', $post_id)) {
                return;
            }
        }

        // 保存排序值
        if (isset($_POST['wp_stuff_sort_order'])) {
            $sort_order = intval($_POST['wp_stuff_sort_order']);
            if ($sort_order >= 0) {
                update_post_meta($post_id, '_wp_stuff_sort_order', $sort_order);
            }
        }

        // 保存品牌（描述）
        if (isset($_POST['wp_stuff_brand'])) {
            update_post_meta($post_id, '_wp_stuff_brand', sanitize_text_field($_POST['wp_stuff_brand']));
        }

        // 保存品牌图片ID
        if (isset($_POST['wp_stuff_brand_image_id'])) {
            update_post_meta($post_id, '_wp_stuff_brand_image_id', absint($_POST['wp_stuff_brand_image_id']));
        }

        // 保存价格
        if (isset($_POST['wp_stuff_price'])) {
            update_post_meta($post_id, '_wp_stuff_price', sanitize_text_field($_POST['wp_stuff_price']));
        }

        // 保存购买日期
        if (isset($_POST['wp_stuff_purchase_date'])) {
            update_post_meta($post_id, '_wp_stuff_purchase_date', sanitize_text_field($_POST['wp_stuff_purchase_date']));
        }

        // 保存链接
        if (isset($_POST['wp_stuff_link'])) {
            update_post_meta($post_id, '_wp_stuff_link', esc_url_raw($_POST['wp_stuff_link']));
        }

        // 保存评分
        if (isset($_POST['wp_stuff_rating'])) {
            $rating = intval($_POST['wp_stuff_rating']);
            if ($rating >= 1 && $rating <= 5) {
                update_post_meta($post_id, '_wp_stuff_rating', $rating);
            }
        }
    }

    /**
     * 注册短代码
     * 提供显示好物列表和单个好物的功能
     */
    public function register_shortcodes() {
        add_shortcode('wp_stuff_all', array($this, 'shortcode_all_items'));
        add_shortcode('wp_stuff_item', array($this, 'shortcode_single_item'));
    }

    /**
     * 所有物品的短代码
     */
    public function shortcode_all_items($atts) {
        $atts = shortcode_atts(array(
            'category' => '',
            'limit' => -1,
            'orderby' => 'custom', // 默认使用自定义排序
            'order' => 'DESC',
        ), $atts, 'wp_stuff_all');

        // 检查URL参数中是否有分类信息
        if (empty($atts['category']) && isset($_GET['stuff_category'])) {
            $atts['category'] = sanitize_text_field($_GET['stuff_category']);
        }

        // 基本查询参数
        $args = array(
            'post_type' => 'wp_stuff',
            'posts_per_page' => $atts['limit'],
            'post_status' => 'publish',
        );

        // 如果指定了分类
        if (!empty($atts['category'])) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'stuff_category',
                    'field'    => 'slug',
                    'terms'    => $atts['category'],
                ),
            );
        }

        // 设置排序参数
        if ($atts['orderby'] === 'custom') {
            // 使用自定义排序
            $args['meta_key'] = '_wp_stuff_sort_order';
            $args['orderby'] = array(
                'meta_value_num' => 'ASC',
                'date' => 'DESC'
            );
        } else {
            // 使用指定的排序方式
            $args['orderby'] = $atts['orderby'];
            $args['order'] = $atts['order'];
        }

        $query = new WP_Query($args);
        
        ob_start();
        
        if ($query->have_posts()) {
            ?>
            <div class="wp-stuff-grid">
                <?php while ($query->have_posts()) : $query->the_post(); ?>
                    <?php $this->render_item_card(get_the_ID()); ?>
                <?php endwhile; ?>
            </div>
            <?php
        } else {
            echo '<div class="wp-stuff-no-items">' . __('没有找到相关好物。', 'wp-stuff') . '</div>';
        }
        
        wp_reset_postdata();
        
        return ob_get_clean();
    }

    /**
     * 单个物品的短代码
     */
    public function shortcode_single_item($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
        ), $atts, 'wp_stuff_item');

        if (empty($atts['id'])) {
            return '<p>' . __('请指定好物ID。', 'wp-stuff') . '</p>';
        }

        $post = get_post($atts['id']);
        
        if (!$post || 'wp_stuff' !== $post->post_type) {
            return '<p>' . __('指定的好物不存在。', 'wp-stuff') . '</p>';
        }
        
        ob_start();
        
        $this->render_item_card($post->ID);
        
        return ob_get_clean();
    }

    /**
     * 渲染单个物品卡片
     */
    private function render_item_card($post_id) {
        // 获取物品元数据
        $price = get_post_meta($post_id, '_wp_stuff_price', true);
        $link = get_post_meta($post_id, '_wp_stuff_link', true);
        $rating = get_post_meta($post_id, '_wp_stuff_rating', true);
        $brand = get_post_meta($post_id, '_wp_stuff_brand', true);
        $brand_image_id = get_post_meta($post_id, '_wp_stuff_brand_image_id', true);
        $purchase_date = get_post_meta($post_id, '_wp_stuff_purchase_date', true);
        
        $brand_image_url = '';
        if ($brand_image_id) {
            $brand_image_data = wp_get_attachment_image_src($brand_image_id, 'thumbnail');
            if ($brand_image_data) {
                $brand_image_url = $brand_image_data[0];
            }
        }
        
        // 检查是否有外部链接
        $has_link = !empty($link);
        $item_class = $has_link ? 'wp-stuff-item' : 'wp-stuff-item no-link';
        
        // 输出HTML结构
        ?>
        <div class="<?php echo esc_attr($item_class); ?>">
            <?php if ($has_link) : ?>
            <a href="<?php echo esc_url($link); ?>" class="wp-stuff-item-link" target="_blank">
            <?php else: ?>
            <div class="wp-stuff-item-link">
            <?php endif; ?>
                <div class="wp-stuff-item-image-wrapper">
                    <?php if (has_post_thumbnail($post_id)) : ?>
                        <?php echo get_the_post_thumbnail($post_id, 'medium'); ?>
                    <?php else: ?>
                        <div class="wp-stuff-no-image"></div>
                    <?php endif; ?>
                </div>
                
                <div class="wp-stuff-item-content">
                    <div class="wp-stuff-item-title"><?php echo esc_html(get_the_title($post_id)); ?></div>
                    
                    <?php if (!empty($brand) || !empty($brand_image_url)) : ?>
                    <div class="wp-stuff-item-description">
                        <?php if (!empty($brand_image_url)) : ?>
                            <img src="<?php echo esc_url($brand_image_url); ?>" alt="" class="brand-logo">
                        <?php endif; ?>
                        
                        <?php if (!empty($brand)) : ?>
                            <span class="description-text"><?php echo esc_html($brand); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($rating)) : ?>
                    <div class="wp-stuff-item-rating">
                        <?php 
                        for ($i = 1; $i <= 5; $i++) {
                            $class = $i <= $rating ? 'star filled' : 'star';
                            echo '<span class="' . $class . '">' . ($i <= $rating ? '★' : '☆') . '</span>';
                        }
                        ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="wp-stuff-item-info">
                        <?php if (!empty($price)) : ?>
                        <div class="wp-stuff-item-price">¥<?php echo esc_html($price); ?></div>
                        <?php endif; ?>
                        
                        <?php if (!empty($purchase_date)) : ?>
                        <div class="wp-stuff-item-purchase-date"><?php echo esc_html(date_i18n('Y年m月d日', strtotime($purchase_date))); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php if ($has_link) : ?>
            </a>
            <?php else: ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * 加载前端资源
     */
    public function enqueue_frontend_assets() {
        wp_enqueue_style(
            'wp-stuff-styles',
            WP_STUFF_PLUGIN_URL . 'assets/css/wp-stuff.css',
            array(),
            WP_STUFF_VERSION
        );

        wp_enqueue_script(
            'wp-stuff-scripts',
            WP_STUFF_PLUGIN_URL . 'assets/js/wp-stuff.js',
            array('jquery'),
            WP_STUFF_VERSION,
            true
        );
        
        // 添加本地化脚本
        wp_localize_script(
            'wp-stuff-scripts',
            'wp_stuff_vars',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wp_stuff_ajax_nonce'),
            )
        );
    }

    /**
     * 加载后台资源
     */
    public function enqueue_admin_assets($hook) {
        // 只在编辑好物页面加载
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }

        $screen = get_current_screen();
        if (!$screen || 'wp_stuff' !== $screen->post_type) {
            return;
        }

        wp_enqueue_style(
            'wp-stuff-admin-styles',
            WP_STUFF_PLUGIN_URL . 'assets/css/wp-stuff-admin.css',
            array(),
            WP_STUFF_VERSION
        );

        wp_enqueue_script(
            'wp-stuff-admin-scripts',
            WP_STUFF_PLUGIN_URL . 'assets/js/wp-stuff-admin.js',
            array('jquery'),
            WP_STUFF_VERSION,
            true
        );
    }

    /**
     * 添加管理菜单
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=wp_stuff',
            __('设置', 'wp-stuff'),
            __('设置', 'wp-stuff'),
            'manage_options',
            'wp-stuff-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * 渲染设置页面
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <div class="wp-stuff-settings-info">
                <h2><?php _e('可用短代码', 'wp-stuff'); ?></h2>
                <ul>
                    <li><code>[wp_stuff_all]</code> - <?php _e('显示所有好物（默认按排序值排序）', 'wp-stuff'); ?></li>
                    <li><code>[wp_stuff_all category="分类名称" limit="10"]</code> - <?php _e('显示指定分类的好物', 'wp-stuff'); ?></li>
                    <li><code>[wp_stuff_all orderby="date" order="DESC"]</code> - <?php _e('按日期排序显示好物', 'wp-stuff'); ?></li>
                    <li><code>[wp_stuff_item id="123"]</code> - <?php _e('显示指定ID的好物', 'wp-stuff'); ?></li>
                </ul>
                <h3><?php _e('排序说明', 'wp-stuff'); ?></h3>
                <p><?php _e('物品默认按照后台设置的排序值从小到大排序。排序值相同时，会按添加日期从新到旧排序。', 'wp-stuff'); ?></p>
                <p><?php _e('如需按其他方式排序，可使用 orderby 参数，例如：<code>orderby="date"</code>（按日期）、<code>orderby="title"</code>（按标题）等。', 'wp-stuff'); ?></p>
            </div>
        </div>
        <?php
    }

    /**
     * 自定义编辑屏幕
     */
    public function customize_edit_screen() {
        global $post_type, $pagenow;
        
        // 只在好物编辑页面应用
        if ('wp_stuff' !== $post_type) {
            return;
        }
        
        // 如果是列表页面，只添加短代码模态窗口
        if ('edit.php' === $pagenow) {
            ?>
            <div id="wp-stuff-shortcode-modal" style="display:none;" class="wp-stuff-modal">
                <div class="wp-stuff-modal-content">
                    <span class="wp-stuff-modal-close">&times;</span>
                    <h3><?php _e('复制简码', 'wp-stuff'); ?></h3>
                    <div class="wp-stuff-modal-body">
                        <p><?php _e('点击下方简码复制到剪贴板：', 'wp-stuff'); ?></p>
                        <div class="wp-stuff-modal-shortcode-wrap">
                            <input type="text" id="wp-stuff-modal-shortcode" readonly class="wp-stuff-modal-shortcode-field" />
                            <div class="wp-stuff-copied-message"><?php _e('已复制!', 'wp-stuff'); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <style>
                /* 模态窗口样式 */
                .wp-stuff-modal {
                    position: fixed;
                    z-index: 100000;
                    left: 0;
                    top: 0;
                    width: 100%;
                    height: 100%;
                    background-color: rgba(0,0,0,0.4);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                
                .wp-stuff-modal-content {
                    background-color: #fefefe;
                    padding: 20px;
                    border-radius: 5px;
                    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
                    width: 90%;
                    max-width: 500px;
                    position: relative;
                }
                
                .wp-stuff-modal-close {
                    position: absolute;
                    top: 10px;
                    right: 15px;
                    color: #aaa;
                    font-size: 24px;
                    font-weight: bold;
                    cursor: pointer;
                }
                
                .wp-stuff-modal-close:hover,
                .wp-stuff-modal-close:focus {
                    color: #000;
                    text-decoration: none;
                }
                
                .wp-stuff-modal-body {
                    margin-top: 15px;
                }
                
                .wp-stuff-modal-shortcode-wrap {
                    position: relative;
                    margin-top: 10px;
                }
                
                .wp-stuff-modal-shortcode-field {
                    width: 100%;
                    padding: 8px 10px;
                    font-family: monospace;
                    border-radius: 4px;
                    border: 1px solid #ddd;
                    font-size: 14px;
                    background-color: #f7f7f7;
                    cursor: pointer;
                }
                
                .wp-stuff-copied-message {
                    position: absolute;
                    top: -30px;
                    left: 50%;
                    transform: translateX(-50%);
                    background-color: #46b450;
                    color: white;
                    padding: 5px 10px;
                    border-radius: 3px;
                    font-size: 12px;
                    display: none;
                    animation: fadeinout 1.5s ease-in-out;
                }
                
                @keyframes fadeinout {
                    0% { opacity: 0; }
                    20% { opacity: 1; }
                    80% { opacity: 1; }
                    100% { opacity: 0; }
                }
                
                @media (max-width: 782px) {
                    .wp-stuff-modal-content {
                        padding: 15px;
                        width: 95%;
                    }
                    
                    .wp-stuff-modal-shortcode-field {
                        font-size: 13px;
                        padding: 6px 8px;
                    }
                }
                
                /* 确保在深色模式下模态窗口仍然可见 */
                .admin-color-modern .wp-stuff-modal-content,
                .admin-color-light .wp-stuff-modal-content,
                .admin-color-midnight .wp-stuff-modal-content {
                    background-color: #222;
                    color: #f0f0f1;
                }
                
                .admin-color-modern .wp-stuff-modal-shortcode-field,
                .admin-color-light .wp-stuff-modal-shortcode-field,
                .admin-color-midnight .wp-stuff-modal-shortcode-field {
                    background-color: #2c2c2c;
                    border-color: #3c3c3c;
                    color: #f0f0f1;
                }
            </style>
            <script>
                jQuery(document).ready(function($) {
                    // 短代码模态窗口功能
                    var modal = document.getElementById('wp-stuff-shortcode-modal');
                    var modalShortcode = document.getElementById('wp-stuff-modal-shortcode');
                    var copiedMessage = document.querySelector('.wp-stuff-copied-message');
                    
                    // 显示模态窗口
                    $(document).on('click', '.wp-stuff-view-shortcode', function() {
                        var shortcode = $(this).data('shortcode');
                        $(modalShortcode).val(shortcode);
                        $(modal).fadeIn(200);
                    });
                    
                    // 关闭模态窗口
                    $('.wp-stuff-modal-close').on('click', function() {
                        $(modal).fadeOut(200);
                    });
                    
                    // 点击外部区域关闭模态窗口
                    $(window).on('click', function(event) {
                        if (event.target == modal) {
                            $(modal).fadeOut(200);
                        }
                    });
                    
                    // 复制短代码
                    $(modalShortcode).on('click', function() {
                        $(this).select();
                        document.execCommand('copy');
                        
                        // 显示复制成功消息
                        $(copiedMessage).fadeIn(200).delay(1000).fadeOut(200);
                    });
                });
            </script>
            <?php
        }
        
        // 编辑页面隐藏编辑器
        if (in_array($pagenow, array('post.php', 'post-new.php'))) {
            ?>
            <style type="text/css">
                /* 完全隐藏内容编辑区域 */
                #postdivrich, 
                .wp-editor-expand,
                .wp-core-ui .quicktags-toolbar,
                #wp-content-editor-tools,
                #after_title-sortables,
                #postdiv,
                #wp-content-wrap,
                #content-tmce,
                #content-html,
                .wp-editor-tabs,
                .wp-editor-container {
                    display: none !important;
                }
            </style>
            <?php
        }
    }

    /**
     * 添加自定义列
     */
    public function add_custom_columns($columns) {
        $new_columns = array();
        
        // 使用WordPress默认的列布局，只添加ID和简码列
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];
        $new_columns['shortcode_btn'] = __('简码', 'wp-stuff');
        $new_columns['taxonomy-stuff_category'] = __('分类', 'wp-stuff');
        $new_columns['date'] = $columns['date'];
        
        return $new_columns;
    }
    
    /**
     * 显示自定义列内容
     */
    public function display_custom_column_content($column, $post_id) {
        switch ($column) {
            case 'shortcode_btn':
                // 短代码按钮
                $shortcode = '[wp_stuff_item id="' . esc_attr($post_id) . '"]';
                echo '<button type="button" class="button button-small wp-stuff-view-shortcode" data-shortcode="' . esc_attr($shortcode) . '">' . 
                    __('查看简码', 'wp-stuff') . 
                    '</button>';
                break;
        }
    }

    // 添加品牌字段
    public function add_brand_field( $post ) {
        $brand_name = get_post_meta( $post->ID, 'wp_stuff_brand_name', true );
        $brand_image_id = get_post_meta( $post->ID, 'wp_stuff_brand_image_id', true );
        $brand_image_url = '';
        
        if ( $brand_image_id ) {
            $brand_image_url = wp_get_attachment_url( $brand_image_id );
        }
        
        ?>
        <div class="wp-stuff-field wp-stuff-brand-field">
            <label for="wp_stuff_brand_name">描述</label>
            <input type="text" id="wp_stuff_brand_name" name="wp_stuff_brand_name" value="<?php echo esc_attr( $brand_name ); ?>" placeholder="输入简短描述" />
        </div>
        
        <div class="wp-stuff-field wp-stuff-brand-image-field">
            <label>描述图标</label>
            <div class="wp-stuff-image-preview-container">
                <img id="wp-stuff-brand-image-preview" src="<?php echo esc_url( $brand_image_url ); ?>" <?php echo empty( $brand_image_url ) ? 'style="display:none;"' : ''; ?> />
            </div>
            <input type="hidden" id="wp_stuff_brand_image_id" name="wp_stuff_brand_image_id" value="<?php echo esc_attr( $brand_image_id ); ?>" />
            <button id="wp-stuff-brand-image-upload-button" class="button">选择图片</button>
            <button id="wp-stuff-brand-image-remove-button" class="button" <?php echo empty( $brand_image_url ) ? 'style="display:none;"' : ''; ?>>移除图片</button>
            <p class="description">可选。上传描述相关的图标</p>
        </div>
        <?php
    }

    /**
     * AJAX加载分类物品
     */
    public function ajax_load_category_items() {
        // 检查安全性
        check_ajax_referer('wp_stuff_ajax_nonce', 'security');
        
        // 获取分类
        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
        $orderby = isset($_POST['orderby']) ? sanitize_text_field($_POST['orderby']) : 'custom';
        
        // 查询物品
        $args = array(
            'post_type' => 'wp_stuff',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        );
        
        // 如果不是"全部"分类，则添加分类查询
        if (!empty($category) && $category !== 'all') {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'stuff_category',
                    'field'    => 'slug',
                    'terms'    => $category,
                ),
            );
        }
        
        // 设置排序参数
        if ($orderby === 'custom') {
            // 默认使用自定义排序
            $args['meta_key'] = '_wp_stuff_sort_order';
            $args['orderby'] = array(
                'meta_value_num' => 'ASC',
                'date' => 'DESC'
            );
        } else {
            // 使用其他指定的排序方式
            $args['orderby'] = $orderby;
            $args['order'] = 'DESC';
        }
        
        $query = new WP_Query($args);
        
        ob_start();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $this->render_item_card(get_the_ID());
            }
        } else {
            echo '<div class="wp-stuff-no-items">该分类下暂无物品</div>';
        }
        
        wp_reset_postdata();
        
        $html = ob_get_clean();
        
        wp_send_json_success($html);
    }

    /**
     * 移除编辑器支持
     */
    public function remove_editor_support() {
        remove_post_type_support('wp_stuff', 'editor');
    }
    
    /**
     * 自定义标题输入提示
     */
    public function custom_enter_title($title) {
        $screen = get_current_screen();
        if ($screen && 'wp_stuff' === $screen->post_type) {
            $title = '请输入物品名称（必填）';
        }
        return $title;
    }
} 