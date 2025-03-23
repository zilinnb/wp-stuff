/**
 * WP-Stuff 管理界面脚本
 */
(function($) {
    'use strict';

    // 当文档准备就绪时
    $(document).ready(function() {
        // 媒体上传器
        var mediaUploader;
        var brandImageUploader;
        
        // 初始化图片上传器
        function initMediaUploader() {
            $('#wp_stuff_upload_image_button').on('click', function(e) {
                e.preventDefault();
                
                // 如果已经存在上传器，则重用
                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }
                
                // 创建媒体上传器
                mediaUploader = wp.media({
                    title: '选择或上传图片',
                    button: {
                        text: '使用此图片'
                    },
                    multiple: false
                });
                
                // 当选择媒体时
                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#wp_stuff_image_id').val(attachment.id);
                    $('#wp_stuff_image_preview').attr('src', attachment.url).show();
                    $('#wp_stuff_remove_image_button').show();
                });
                
                // 打开媒体上传器
                mediaUploader.open();
            });
            
            // 移除图片
            $('#wp_stuff_remove_image_button').on('click', function(e) {
                e.preventDefault();
                $('#wp_stuff_image_id').val('');
                $('#wp_stuff_image_preview').attr('src', '').hide();
                $(this).hide();
            });
        }
        
        // 初始化品牌图片上传器
        function initBrandImageUploader() {
            // 打开媒体上传窗口
            $('#wp-stuff-brand-image-upload-button').on('click', function(e) {
                e.preventDefault();
                
                // 如果已经存在上传器，则重用
                if (brandImageUploader) {
                    brandImageUploader.open();
                    return;
                }
                
                // 创建媒体上传器
                brandImageUploader = wp.media({
                    title: '选择品牌图片',
                    button: {
                        text: '使用此图片'
                    },
                    multiple: false
                });
                
                // 当选择媒体时
                brandImageUploader.on('select', function() {
                    var attachment = brandImageUploader.state().get('selection').first().toJSON();
                    $('#wp_stuff_brand_image_id').val(attachment.id);
                    $('#wp-stuff-brand-image-preview').attr('src', attachment.url).show();
                    $('#wp-stuff-brand-image-remove-button').show();
                });
                
                // 打开媒体上传器
                brandImageUploader.open();
            });
            
            // 移除品牌图片
            $('#wp-stuff-brand-image-remove-button').on('click', function(e) {
                e.preventDefault();
                $('#wp_stuff_brand_image_id').val('');
                $('#wp-stuff-brand-image-preview').attr('src', '').hide();
                $(this).hide();
            });
        }
        
        // 初始化评分选择器
        function initRatingSelector() {
            // 评分星星交互
            $('.wp-stuff-rating-stars span').on('click', function() {
                var rating = $(this).data('rating');
                $('#wp_stuff_rating').val(rating);
                updateRatingStars(rating);
            });
            
            // 鼠标悬停效果
            $('.wp-stuff-rating-stars span').on('mouseenter', function() {
                var hoverRating = $(this).data('rating');
                previewRatingStars(hoverRating);
            });
            
            // 鼠标离开恢复原状
            $('.wp-stuff-rating-stars').on('mouseleave', function() {
                var currentRating = $('#wp_stuff_rating').val() || 0;
                updateRatingStars(currentRating);
            });
            
            // 更新评分星星显示
            function updateRatingStars(rating) {
                $('.wp-stuff-rating-stars span').each(function() {
                    var starRating = $(this).data('rating');
                    if (starRating <= rating) {
                        $(this).addClass('active').text('★');
                    } else {
                        $(this).removeClass('active').text('☆');
                    }
                });
            }
            
            // 预览星星状态
            function previewRatingStars(rating) {
                $('.wp-stuff-rating-stars span').each(function() {
                    var starRating = $(this).data('rating');
                    if (starRating <= rating) {
                        $(this).text('★');
                    } else {
                        $(this).text('☆');
                    }
                });
            }
            
            // 初始化显示
            var initialRating = $('#wp_stuff_rating').val();
            if (initialRating) {
                updateRatingStars(initialRating);
            }
        }
        
        // 初始化表单验证
        function initFormValidation() {
            $('form#post').on('submit', function() {
                var isValid = true;
                
                // 验证价格
                var price = $('#wp_stuff_price').val();
                if (price && !/^[0-9]+(\.[0-9]{1,2})?$/.test(price)) {
                    alert('请输入有效的价格格式');
                    $('#wp_stuff_price').focus();
                    isValid = false;
                }
                
                // 验证链接
                var link = $('#wp_stuff_link').val();
                if (link && !/^https?:\/\//.test(link)) {
                    alert('请输入有效的URL，以http://或https://开头');
                    $('#wp_stuff_link').focus();
                    isValid = false;
                }
                
                return isValid;
            });
        }
        
        // 简化编辑界面
        function simplifyEditInterface() {
            // 显示设置
            $('.wp-stuff-meta-box').show();
            $('#titlediv').show();
            
            // 隐藏内容编辑器
            $('#postdivrich').hide();
            $('#normal-sortables').show();
            
            // 优化界面
            $('#title').attr('placeholder', '输入物品名称');
            
            // 隐藏不需要的元素
            $('#edit-slug-box').hide();
            $('.page-title-action').hide();
            $('#minor-publishing').hide();
            
            // 调整标题字段样式
            $('#titlediv').css({
                'margin-bottom': '15px',
                'padding': '10px',
                'background': '#f9f9f9',
                'border-radius': '4px'
            });
            
            // 添加提示文字
            if (!$('.title-instruction').length) {
                $('#titlewrap').after('<p class="title-instruction" style="margin-top: 5px; color: #666;">请输入物品名称（必填）</p>');
            }
            
            // 设置必填字段
            $('#title').attr('required', 'required');
            
            // 重新标记特色图片为产品图片
            $('#postimagediv h2').html('产品图片');
            $('#set-post-thumbnail').text('设置产品图片');
            $('.thickbox.upload-post-thumbnail').text('设置产品图片');
            $('#remove-post-thumbnail').text('移除产品图片');
            
            // 发布按钮文字修改
            $('#publish').val('保存物品');
            
            // 如果未发布，修改为"添加物品"
            if ($('#original_post_status').val() === 'auto-draft' || $('#original_post_status').val() === 'draft') {
                $('#publish').val('添加物品');
            }
            
            // 检测是否为手机设备并优化界面
            if (window.innerWidth <= 782) {
                optimizeMobileInterface();
            }
        }
        
        // 手机界面专门优化
        function optimizeMobileInterface() {
            // 设置发布框固定在底部
            $('#submitdiv').css({
                'position': 'sticky',
                'bottom': '0',
                'z-index': '100',
                'margin-bottom': '0',
                'box-shadow': '0 -2px 5px rgba(0,0,0,0.1)'
            });
            
            // 优化保存按钮
            $('#publish').css({
                'width': '100%',
                'text-align': 'center',
                'height': '42px',
                'line-height': '40px',
                'font-size': '15px'
            });
            
            // 简化表单布局
            $('.wp-stuff-form .form-table th').css({
                'padding-bottom': '5px',
                'padding-top': '15px'
            });
            
            $('.wp-stuff-form .form-table td').css({
                'padding-top': '5px',
                'padding-bottom': '15px'
            });
        }
        
        // 执行初始化
        function init() {
            if ($('#wp_stuff_meta_box').length) {
                initMediaUploader();
                initBrandImageUploader();
                initRatingSelector();
                initFormValidation();
                simplifyEditInterface();
                
                // 添加窗口大小变化监听
                $(window).on('resize', function() {
                    if (window.innerWidth <= 782) {
                        optimizeMobileInterface();
                    }
                });
                
                // 自动设置标题焦点
                setTimeout(function() {
                    $('#title').focus();
                }, 300);
            }
        }
        
        // 如果在管理页面
        if ($('#wp_stuff_meta_box').length) {
            init();
        }

        /**
         * 短代码复制功能
         */
        $(document).on('click', '.wp-stuff-code-style', function() {
            $(this).select();
            document.execCommand('copy');
            
            var parent = $(this).parent();
            parent.addClass('copied');
            
            setTimeout(function() {
                parent.removeClass('copied');
            }, 1000);
        });
    });

})(jQuery); 