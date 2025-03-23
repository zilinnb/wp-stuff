/**
 * WP-Stuff 前端脚本 - 简约博客风格
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // 分类切换功能
        function initCategoryFilter() {
            $('.wp-stuff-category-item').on('click', function(e) {
                e.preventDefault();
                
                var $this = $(this);
                var href = $this.attr('href');
                
                // 更新URL但不刷新页面
                window.history.pushState({}, '', href);
                
                // 高亮当前分类
                $('.wp-stuff-category-item').removeClass('active');
                $this.addClass('active');
                
                // 获取分类slug
                var categorySlug = $this.data('category');
                
                // 如果是"所有"分类
                if (categorySlug === 'all') {
                    // 显示所有物品
                    $('.wp-stuff-grid .wp-stuff-item').show();
                    animateItems();
                } else {
                    // AJAX加载该分类的物品
                    loadCategoryItems(categorySlug);
                }
                
                return false;
            });
        }
        
        // 加载分类物品
        function loadCategoryItems(category) {
            var $grid = $('.wp-stuff-grid');
            
            // 添加加载状态并淡出当前物品
            $grid.addClass('loading');
            $grid.css('opacity', '0.6');
            
            $.ajax({
                url: wp_stuff_vars.ajax_url,
                type: 'post',
                data: {
                    action: 'load_category_items',
                    category: category,
                    orderby: 'custom', // 默认使用自定义排序
                    security: wp_stuff_vars.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // 替换物品并动画显示
                        $grid.html(response.data);
                        $grid.css('opacity', '1');
                        animateItems();
                    } else {
                        console.error('加载失败');
                        $grid.css('opacity', '1');
                    }
                },
                error: function() {
                    console.error('AJAX请求失败');
                    $grid.css('opacity', '1');
                },
                complete: function() {
                    $grid.removeClass('loading');
                }
            });
        }
        
        // 动画显示物品
        function animateItems() {
            $('.wp-stuff-grid .wp-stuff-item').each(function(index) {
                var $item = $(this);
                
                // 添加初始化类以便应用动画效果
                $item.addClass('js-init');
                
                // 强制回流
                $item[0].offsetHeight;
                
                // 延迟添加动画类
                setTimeout(function() {
                    $item.addClass('animated');
                    // 动画完成后移除初始化类以防止再次触发动画
                    setTimeout(function() {
                        $item.removeClass('js-init');
                    }, 500); // 动画持续时间后移除
                }, index * 40);
            });
        }
        
        // 物品展示增强
        function enhanceItemDisplay() {
            // 添加鼠标悬停效果
            $('.wp-stuff-item').hover(
                function() {
                    $(this).addClass('hover');
                },
                function() {
                    $(this).removeClass('hover');
                }
            );
            
            // 防止点击详情区域触发物品链接
            $('.wp-stuff-item-details').on('click', function(e) {
                e.stopPropagation();
            });
            
            // 处理无链接物品
            $('.wp-stuff-item.no-link').on('click', function(e) {
                e.preventDefault();
                return false;
            });
        }
        
        // 检测暗模式
        function detectDarkMode() {
            // 检查系统暗模式
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                $('body').addClass('wp-stuff-dark-mode');
                $('html').addClass('wp-stuff-dark-mode');
            }
            
            // 监听系统暗模式变化
            try {
                window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
                    if (e.matches) {
                        $('body').addClass('wp-stuff-dark-mode');
                        $('html').addClass('wp-stuff-dark-mode');
                    } else {
                        $('body').removeClass('wp-stuff-dark-mode');
                        $('html').removeClass('wp-stuff-dark-mode');
                    }
                });
            } catch (error) {
                // 老浏览器不支持addEventListener方法，尝试用addListener
                try {
                    window.matchMedia('(prefers-color-scheme: dark)').addListener(function(e) {
                        if (e.matches) {
                            $('body').addClass('wp-stuff-dark-mode');
                            $('html').addClass('wp-stuff-dark-mode');
                        } else {
                            $('body').removeClass('wp-stuff-dark-mode');
                            $('html').removeClass('wp-stuff-dark-mode');
                        }
                    });
                } catch (err) {
                    console.log('您的浏览器不支持暗色模式自动检测');
                }
            }
            
            // 检测常见WordPress主题的暗模式类
            var darkModeClasses = [
                '.dark-mode', '.dark-theme', '.night-mode', '.dark', 
                '.nightmode', '.dark-style', '.scheme-dark', '.color-scheme-dark',
                '.theme-dark', '.darkmode'
            ];
            
            var darkModeAttrs = [
                'data-theme="dark"', 'theme-mode="dark"', 'color-theme="dark"',
                'data-color-scheme="dark"', 'data-color-mode="dark"',
                'data-bs-theme="dark"', 'color-mode="dark"'
            ];
            
            // 检查类
            for (var i = 0; i < darkModeClasses.length; i++) {
                if ($(darkModeClasses[i]).length > 0 || 
                    $('body').hasClass(darkModeClasses[i].substring(1)) || 
                    $('html').hasClass(darkModeClasses[i].substring(1))) {
                    $('body').addClass('wp-stuff-dark-mode');
                    $('html').addClass('wp-stuff-dark-mode');
                    break;
                }
            }
            
            // 检查属性
            for (var j = 0; j < darkModeAttrs.length; j++) {
                var attrParts = darkModeAttrs[j].split('=');
                var attrName = attrParts[0];
                var attrValue = attrParts[1].replace(/"/g, '');
                
                if ($('html').attr(attrName) === attrValue || 
                    $('body').attr(attrName) === attrValue) {
                    $('body').addClass('wp-stuff-dark-mode');
                    $('html').addClass('wp-stuff-dark-mode');
                    break;
                }
            }
            
            // 额外检查特定主题的深色模式标识 - 通过背景色
            var bodyBgColor = $('body').css('background-color');
            var htmlBgColor = $('html').css('background-color');
            
            function isDarkColor(color) {
                // 提取RGB值，转为亮度计算
                var rgb = color.match(/\d+/g);
                if (rgb && rgb.length >= 3) {
                    // 计算亮度 (0.299*R + 0.587*G + 0.114*B)
                    var brightness = (0.299 * parseInt(rgb[0]) + 0.587 * parseInt(rgb[1]) + 0.114 * parseInt(rgb[2]));
                    // 亮度低于128认为是暗色
                    return brightness < 128;
                }
                return false;
            }
            
            if (isDarkColor(bodyBgColor) || isDarkColor(htmlBgColor)) {
                $('body').addClass('wp-stuff-dark-mode');
                $('html').addClass('wp-stuff-dark-mode');
            }
            
            // 设置定期检查以捕获动态变化的主题
            setInterval(function() {
                var currentBodyBgColor = $('body').css('background-color');
                if (isDarkColor(currentBodyBgColor) && !$('body').hasClass('wp-stuff-dark-mode')) {
                    $('body').addClass('wp-stuff-dark-mode');
                    $('html').addClass('wp-stuff-dark-mode');
                }
                
                // 检查是否有新的暗模式类添加
                for (var i = 0; i < darkModeClasses.length; i++) {
                    if ($(darkModeClasses[i]).length > 0 || 
                        $('body').hasClass(darkModeClasses[i].substring(1)) || 
                        $('html').hasClass(darkModeClasses[i].substring(1))) {
                        if (!$('body').hasClass('wp-stuff-dark-mode')) {
                            $('body').addClass('wp-stuff-dark-mode');
                            $('html').addClass('wp-stuff-dark-mode');
                        }
                        break;
                    }
                }
            }, 1000);
        }
        
        // 添加页面加载效果
        function addLoadEffect() {
            // 如果页面已经加载完毕，立即应用动画
            if (document.readyState === 'complete' || document.readyState === 'interactive') {
                setTimeout(function() {
                    animateItems();
                }, 100); // 短暂延迟以确保DOM已经完全处理
            } else {
                // 页面加载完成后显示物品
                $(window).on('load', function() {
                    animateItems();
                });
            }
        }
        
        // 初始化所有功能
        function init() {
            // 确保物品显示
            enhanceItemDisplay();
            // 尝试应用动画效果，但不影响物品的基本显示
            addLoadEffect();
            initCategoryFilter();
            detectDarkMode();
            
            console.log('WP-Stuff 前端功能已初始化');
        }
        
        // 启动初始化
        init();
    });

})(jQuery); 