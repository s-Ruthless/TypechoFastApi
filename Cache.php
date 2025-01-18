<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

class TypechoFastApi_Cache
{
    /**
     * 缓存目录
     */
    private static $cacheDir = __TYPECHO_ROOT_DIR__ . '/usr/plugins/TypechoFastApi/cache/';
    
    /**
     * 缓存时间(秒)
     */
    private static $cacheTime = 3600; // 1小时
    
    /**
     * 缓存前缀
     */
    const PREFIX_POST = 'post_';      // 文章缓存前缀
    const PREFIX_POSTS = 'posts_';     // 文章列表缓存前缀
    const PREFIX_CATEGORY = 'category_'; // 分类缓存前缀
    const PREFIX_TAG = 'tag_';        // 标签缓存前缀
    const PREFIX_COMMENTS = 'comments_'; // 评论缓存前缀
    const PREFIX_PAGE = 'page_';      // 页面缓存前缀
    const PREFIX_USER = 'user_';      // 用户缓存前缀
    const PREFIX_SITE = 'site_';      // 站点缓存前缀
    
    /**
     * 获取缓存
     */
    public static function get($key)
    {
        $file = self::getCacheFile($key);
        if (!file_exists($file)) {
            return false;
        }
        
        // 检查缓存是否过期
        if (time() - filemtime($file) > self::$cacheTime) {
            @unlink($file);
            return false;
        }
        
        return json_decode(file_get_contents($file), true);
    }
    
    /**
     * 设置缓存
     */
    public static function set($key, $value)
    {
        if (!is_dir(self::$cacheDir)) {
            mkdir(self::$cacheDir, 0777, true);
        }
        
        $file = self::getCacheFile($key);
        return file_put_contents($file, json_encode($value));
    }
    
    /**
     * 清除缓存
     * @param string $prefix 缓存前缀，用于清理特定类型的缓存
     */
    public static function clear($prefix = null)
    {
        if ($prefix === null) {
            // 清除所有缓存
            $files = glob(self::$cacheDir . '*');
            foreach ($files as $file) {
                @unlink($file);
            }
            return true;
        }
        
        // 清理指定前缀的缓存
        $files = glob(self::$cacheDir . md5($prefix) . '*');
        foreach ($files as $file) {
            @unlink($file);
        }
        return true;
    }
    
    /**
     * 获取缓存文件路径
     */
    private static function getCacheFile($key)
    {
        return self::$cacheDir . md5($key) . '.cache';
    }
} 