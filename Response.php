<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

class TypechoFastApi_Response
{
    /**
     * 成功响应
     */
    public static function success($data = null, $message = 'success')
    {
        return self::json(200, $message, $data);
    }
    
    /**
     * 错误响应
     */
    public static function error($code = 400, $message = 'error', $data = null)
    {
        return self::json($code, $message, $data);
    }
    
    /**
     * JSON响应
     */
    private static function json($code, $message, $data)
    {
        return [
            'code' => $code,
            'message' => $message,
            'data' => $data
        ];
    }
} 