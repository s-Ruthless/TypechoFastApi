<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * Typecho REST API 插件
 * 
 * @package TypechoFastApi
 * @author YourName
 * @version 1.0.0
 * @link https://github.com/yourusername/TypechoFastApi
 */
class TypechoFastApi_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     */
    public static function activate()
    {
        // 添加自动加载
        Typecho_Plugin::factory('index.php')->begin = array('TypechoFastApi_Plugin', 'autoload');
        self::autoload(); // 立即加载类
        
        // 文章相关路由
        Helper::addRoute('api_posts', '/api/v1/posts', 'TypechoFastApi_Controller', 'posts');
        Helper::addRoute('api_post', '/api/v1/posts/[id:digital]', 'TypechoFastApi_Controller', 'post');
        Helper::addRoute('api_search', '/api/v1/posts/search', 'TypechoFastApi_Controller', 'search');
        Helper::addRoute('api_archive', '/api/v1/posts/archive', 'TypechoFastApi_Controller', 'archive');
        
        // 分类相关路由
        Helper::addRoute('api_categories', '/api/v1/categories', 'TypechoFastApi_Controller', 'categories');
        Helper::addRoute('api_category_posts', '/api/v1/categories/[mid:digital]/posts', 'TypechoFastApi_Controller', 'categoryPosts');
        
        // 标签相关路由
        Helper::addRoute('api_tags', '/api/v1/tags', 'TypechoFastApi_Controller', 'tags');
        Helper::addRoute('api_tag_posts', '/api/v1/tags/[mid:digital]/posts', 'TypechoFastApi_Controller', 'tagPosts');
        
        // 评论相关路由
        Helper::addRoute('api_comments', '/api/v1/comments', 'TypechoFastApi_Controller', 'comments');
        Helper::addRoute('api_post_comments', '/api/v1/posts/[cid:digital]/comments', 'TypechoFastApi_Controller', 'postComments');
        Helper::addRoute('api_add_comment', '/api/v1/comments/add', 'TypechoFastApi_Controller', 'addComment');
        
        // 页面相关路由
        Helper::addRoute('api_pages', '/api/v1/pages', 'TypechoFastApi_Controller', 'pages');
        Helper::addRoute('api_page', '/api/v1/pages/[id:digital]', 'TypechoFastApi_Controller', 'page');
        
        // 用户相关路由
        Helper::addRoute('api_user', '/api/v1/users/[uid:digital]', 'TypechoFastApi_Controller', 'user');
        Helper::addRoute('api_user_posts', '/api/v1/users/[uid:digital]/posts', 'TypechoFastApi_Controller', 'userPosts');
        
        // 站点相关路由
        Helper::addRoute('api_site_stats', '/api/v1/site/stats', 'TypechoFastApi_Controller', 'siteStats');
        
        // 添加文档路由
        Helper::addRoute('api_doc', '/api/doc', 'TypechoFastApi_Doc', 'render');
        
        // 创建缓存目录
        $cacheDir = __TYPECHO_ROOT_DIR__ . '/usr/plugins/TypechoFastApi/cache/';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }
        
        // 注册文章更新时的回调
        Typecho_Plugin::factory('Widget_Contents_Post_Edit')->finishPublish = array('TypechoFastApi_Plugin', 'clearCache');
        Typecho_Plugin::factory('Widget_Contents_Post_Edit')->finishDelete = array('TypechoFastApi_Plugin', 'clearCache');
        
        // 注册分类更新时的回调
        Typecho_Plugin::factory('Widget_Metas_Category_Edit')->finishSave = array('TypechoFastApi_Plugin', 'clearCache');
        Typecho_Plugin::factory('Widget_Metas_Category_Edit')->finishDelete = array('TypechoFastApi_Plugin', 'clearCache');
        
        // 注册标签更新时的回调
        Typecho_Plugin::factory('Widget_Metas_Tag_Edit')->finishSave = array('TypechoFastApi_Plugin', 'clearCache');
        Typecho_Plugin::factory('Widget_Metas_Tag_Edit')->finishDelete = array('TypechoFastApi_Plugin', 'clearCache');
        
        return _t('插件已经激活');
    }
    
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     */
    public static function deactivate()
    {
        // 删除所有路由
        Helper::removeRoute('api_posts');
        Helper::removeRoute('api_post');
        Helper::removeRoute('api_search');
        Helper::removeRoute('api_archive');
        Helper::removeRoute('api_categories');
        Helper::removeRoute('api_category_posts');
        Helper::removeRoute('api_tags');
        Helper::removeRoute('api_tag_posts');
        Helper::removeRoute('api_comments');
        Helper::removeRoute('api_post_comments');
        Helper::removeRoute('api_add_comment');
        Helper::removeRoute('api_pages');
        Helper::removeRoute('api_page');
        Helper::removeRoute('api_user');
        Helper::removeRoute('api_user_posts');
        Helper::removeRoute('api_site_stats');
        Helper::removeRoute('api_doc');
        
        // 清理缓存
        TypechoFastApi_Cache::clear();
        
        return _t('插件已被禁用');
    }
    
    /**
     * 获取插件配置面板
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        // API密钥
        $apiKey = new Typecho_Widget_Helper_Form_Element_Text(
            'apiKey',
            null,
            '',
            _t('API密钥'),
            _t('请设置API密钥，用于接口认证')
        );
        $form->addInput($apiKey);
        
        // 是否开启缓存
        $cache = new Typecho_Widget_Helper_Form_Element_Radio(
            'cache',
            array(
                '0' => _t('关闭'),
                '1' => _t('开启')
            ),
            '0',
            _t('是否开启缓存'),
            _t('开启缓存可以提高接口响应速度')
        );
        $form->addInput($cache);
        
        // 缓存时间
        $cacheTime = new Typecho_Widget_Helper_Form_Element_Text(
            'cacheTime',
            null,
            '3600',
            _t('缓存时间(秒)'),
            _t('缓存的过期时间，默认3600秒(1小时)')
        );
        $form->addInput($cacheTime);
        
        // 是否开启跨域
        $cors = new Typecho_Widget_Helper_Form_Element_Radio(
            'cors',
            array(
                '0' => _t('关闭'),
                '1' => _t('开启')
            ),
            '0',
            _t('是否开启跨域(CORS)'),
            _t('开启后允许跨域访问API')
        );
        $form->addInput($cors);
        
        // 允许的域名
        $allowOrigin = new Typecho_Widget_Helper_Form_Element_Text(
            'allowOrigin',
            null,
            '*',
            _t('允许的域名'),
            _t('允许跨域访问的域名，多个域名用英文逗号分隔，*表示允许所有域名')
        );
        $form->addInput($allowOrigin);
        
        // 文章摘要长度
        $summaryLength = new Typecho_Widget_Helper_Form_Element_Text(
            'summaryLength',
            null,
            '100',
            _t('文章摘要长度'),
            _t('文章列表中摘要的字符长度')
        );
        $form->addInput($summaryLength);
    }
    
    /**
     * 个人用户的配置面板
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
    }
    
    /**
     * 清理缓存的回调方法
     * 
     * @param mixed $contents 内容数据
     * @param mixed $class 触发清理的类
     * @return mixed
     */
    public static function clearCache($contents, $class)
    {
        // 获取插件配置
        $config = Helper::options()->plugin('TypechoFastApi');
        
        // 如果开启了缓存，则清理
        if ($config->cache) {
            // 清理站点统计缓存（因为几乎所有操作都会影响统计数据）
            TypechoFastApi_Cache::clear(TypechoFastApi_Cache::PREFIX_SITE);
            
            // 根据不同的操作类型清理不同的缓存
            if (strpos(get_class($class), 'Contents_Post') !== false) {
                // 文章更新，清理文章相关缓存
                TypechoFastApi_Cache::clear(TypechoFastApi_Cache::PREFIX_POSTS);
                if (!empty($contents['cid'])) {
                    TypechoFastApi_Cache::clear(TypechoFastApi_Cache::PREFIX_POST . $contents['cid']);
                }
                // 清理归档缓存
                TypechoFastApi_Cache::clear(TypechoFastApi_Cache::PREFIX_POSTS . 'archive');
            } 
            else if (strpos(get_class($class), 'Contents_Page') !== false) {
                // 页面更新，清理页面相关缓存
                TypechoFastApi_Cache::clear(TypechoFastApi_Cache::PREFIX_PAGE);
                if (!empty($contents['cid'])) {
                    TypechoFastApi_Cache::clear(TypechoFastApi_Cache::PREFIX_PAGE . $contents['cid']);
                }
            }
            else if (strpos(get_class($class), 'Metas_Category') !== false) {
                // 分类更新，清理分类缓存和文章列表缓存
                TypechoFastApi_Cache::clear(TypechoFastApi_Cache::PREFIX_CATEGORY);
                TypechoFastApi_Cache::clear(TypechoFastApi_Cache::PREFIX_POSTS);
            }
            else if (strpos(get_class($class), 'Metas_Tag') !== false) {
                // 标签更新，清理标签缓存和文章列表缓存
                TypechoFastApi_Cache::clear(TypechoFastApi_Cache::PREFIX_TAG);
                TypechoFastApi_Cache::clear(TypechoFastApi_Cache::PREFIX_POSTS);
            }
            else if (strpos(get_class($class), 'Comments') !== false) {
                // 评论更新，清理评论缓存和相关文章缓存
                TypechoFastApi_Cache::clear(TypechoFastApi_Cache::PREFIX_COMMENTS);
                if (!empty($contents['cid'])) {
                    TypechoFastApi_Cache::clear(TypechoFastApi_Cache::PREFIX_POST . $contents['cid']);
                }
            }
            else if (strpos(get_class($class), 'Users') !== false) {
                // 用户更新，清理用户缓存
                TypechoFastApi_Cache::clear(TypechoFastApi_Cache::PREFIX_USER);
            }
        }
        
        return $contents;
    }
    
    /**
     * 自动加载类
     */
    public static function autoload()
    {
        if (!class_exists('TypechoFastApi_Controller')) {
            require_once dirname(__FILE__) . '/Controller.php';
        }
        if (!class_exists('TypechoFastApi_Cache')) {
            require_once dirname(__FILE__) . '/Cache.php';
        }
    }
} 