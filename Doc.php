<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

class TypechoFastApi_Doc extends Typecho_Widget
{
    public function render()
    {
        // 开启错误显示
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        
        // 获取插件配置
        $config = Helper::options()->plugin('TypechoFastApi');
        
        // 获取站点URL
        $siteUrl = Helper::options()->siteUrl;
        
        // API基础URL
        $baseUrl = rtrim($siteUrl, '/') . '/api/v1';
        
        // 检查模板文件是否存在
        $templateFile = dirname(__FILE__) . '/template/doc.php';
        if (!file_exists($templateFile)) {
            die('Template file not found: ' . $templateFile);
        }
        
        // API文档数据
        $apiDocs = [
            'title' => 'TypechoFastApi 接口文档',
            'baseUrl' => $baseUrl,
            'description' => '这是一个为 Typecho 博客系统提供 RESTful API 的插件。',
            'auth' => [
                'type' => 'apiKey',
                'name' => 'api_key',
                'in' => 'query'
            ],
            'apis' => [
                [
                    'group' => '文章相关接口',
                    'endpoints' => [
                        [
                            'name' => '获取文章列表',
                            'method' => 'GET',
                            'path' => '/posts',
                            'params' => [
                                ['name' => 'page', 'type' => 'integer', 'required' => false, 'description' => '页码（可选，为空时返回全部）'],
                                ['name' => 'pageSize', 'type' => 'integer', 'required' => false, 'description' => '每页条数（可选，为空时返回全部）']
                            ],
                            'example' => $baseUrl . '/posts?api_key=your-api-key&page=1&pageSize=10'
                        ],
                        [
                            'name' => '获取文章详情',
                            'method' => 'GET',
                            'path' => '/posts/{id}',
                            'params' => [
                                ['name' => 'id', 'type' => 'integer', 'required' => true, 'description' => '文章ID']
                            ],
                            'example' => $baseUrl . '/posts/1?api_key=your-api-key'
                        ],
                        [
                            'name' => '搜索文章',
                            'method' => 'GET',
                            'path' => '/posts/search',
                            'params' => [
                                ['name' => 'keyword', 'type' => 'string', 'required' => true, 'description' => '搜索关键词'],
                                ['name' => 'page', 'type' => 'integer', 'required' => false, 'description' => '页码（默认1）'],
                                ['name' => 'pageSize', 'type' => 'integer', 'required' => false, 'description' => '每页条数（默认10）']
                            ],
                            'example' => $baseUrl . '/posts/search?api_key=your-api-key&keyword=test&page=1&pageSize=10'
                        ],
                        [
                            'name' => '获取文章归档',
                            'method' => 'GET',
                            'path' => '/posts/archive',
                            'example' => $baseUrl . '/posts/archive?api_key=your-api-key'
                        ]
                    ]
                ],
                [
                    'group' => '分类相关接口',
                    'endpoints' => [
                        [
                            'name' => '获取分类列表',
                            'method' => 'GET',
                            'path' => '/categories',
                            'example' => $baseUrl . '/categories?api_key=your-api-key'
                        ],
                        [
                            'name' => '获取分类下的文章',
                            'method' => 'GET',
                            'path' => '/categories/{mid}/posts',
                            'params' => [
                                ['name' => 'mid', 'type' => 'integer', 'required' => true, 'description' => '分类ID'],
                                ['name' => 'page', 'type' => 'integer', 'required' => false, 'description' => '页码（默认1）'],
                                ['name' => 'pageSize', 'type' => 'integer', 'required' => false, 'description' => '每页条数（默认10）']
                            ],
                            'example' => $baseUrl . '/categories/1/posts?api_key=your-api-key&page=1&pageSize=10'
                        ]
                    ]
                ],
                [
                    'group' => '标签相关接口',
                    'endpoints' => [
                        [
                            'name' => '获取标签列表',
                            'method' => 'GET',
                            'path' => '/tags',
                            'example' => $baseUrl . '/tags?api_key=your-api-key'
                        ],
                        [
                            'name' => '获取标签下的文章',
                            'method' => 'GET',
                            'path' => '/tags/{mid}/posts',
                            'params' => [
                                ['name' => 'mid', 'type' => 'integer', 'required' => true, 'description' => '标签ID'],
                                ['name' => 'page', 'type' => 'integer', 'required' => false, 'description' => '页码（默认1）'],
                                ['name' => 'pageSize', 'type' => 'integer', 'required' => false, 'description' => '每页条数（默认10）']
                            ],
                            'example' => $baseUrl . '/tags/1/posts?api_key=your-api-key&page=1&pageSize=10'
                        ]
                    ]
                ],
                [
                    'group' => '评论相关接口',
                    'endpoints' => [
                        [
                            'name' => '获取评论列表',
                            'method' => 'GET',
                            'path' => '/comments',
                            'params' => [
                                ['name' => 'page', 'type' => 'integer', 'required' => false, 'description' => '页码（默认1）'],
                                ['name' => 'pageSize', 'type' => 'integer', 'required' => false, 'description' => '每页条数（默认10）']
                            ],
                            'example' => $baseUrl . '/comments?api_key=your-api-key&page=1&pageSize=10'
                        ],
                        [
                            'name' => '获取文章评论',
                            'method' => 'GET',
                            'path' => '/posts/{cid}/comments',
                            'params' => [
                                ['name' => 'cid', 'type' => 'integer', 'required' => true, 'description' => '文章ID'],
                                ['name' => 'page', 'type' => 'integer', 'required' => false, 'description' => '页码（默认1）'],
                                ['name' => 'pageSize', 'type' => 'integer', 'required' => false, 'description' => '每页条数（默认10）']
                            ],
                            'example' => $baseUrl . '/posts/1/comments?api_key=your-api-key&page=1&pageSize=10'
                        ],
                        [
                            'name' => '发表评论',
                            'method' => 'POST',
                            'path' => '/comments/add',
                            'params' => [
                                ['name' => 'cid', 'type' => 'integer', 'required' => true, 'description' => '文章ID'],
                                ['name' => 'author', 'type' => 'string', 'required' => true, 'description' => '评论作者'],
                                ['name' => 'mail', 'type' => 'string', 'required' => true, 'description' => '评论者邮箱'],
                                ['name' => 'url', 'type' => 'string', 'required' => false, 'description' => '评论者网站'],
                                ['name' => 'content', 'type' => 'string', 'required' => true, 'description' => '评论内容'],
                                ['name' => 'parent', 'type' => 'integer', 'required' => false, 'description' => '父评论ID（默认0）']
                            ],
                            'example' => $baseUrl . '/comments/add?api_key=your-api-key'
                        ]
                    ]
                ],
                [
                    'group' => '页面相关接口',
                    'endpoints' => [
                        [
                            'name' => '获取页面列表',
                            'method' => 'GET',
                            'path' => '/pages',
                            'example' => $baseUrl . '/pages?api_key=your-api-key'
                        ],
                        [
                            'name' => '获取页面详情',
                            'method' => 'GET',
                            'path' => '/pages/{id}',
                            'params' => [
                                ['name' => 'id', 'type' => 'integer', 'required' => true, 'description' => '页面ID']
                            ],
                            'example' => $baseUrl . '/pages/1?api_key=your-api-key'
                        ]
                    ]
                ],
                [
                    'group' => '用户相关接口',
                    'endpoints' => [
                        [
                            'name' => '获取用户信息',
                            'method' => 'GET',
                            'path' => '/users/{uid}',
                            'params' => [
                                ['name' => 'uid', 'type' => 'integer', 'required' => true, 'description' => '用户ID']
                            ],
                            'example' => $baseUrl . '/users/1?api_key=your-api-key'
                        ],
                        [
                            'name' => '获取用户文章列表',
                            'method' => 'GET',
                            'path' => '/users/{uid}/posts',
                            'params' => [
                                ['name' => 'uid', 'type' => 'integer', 'required' => true, 'description' => '用户ID'],
                                ['name' => 'page', 'type' => 'integer', 'required' => false, 'description' => '页码（默认1）'],
                                ['name' => 'pageSize', 'type' => 'integer', 'required' => false, 'description' => '每页条数（默认10）']
                            ],
                            'example' => $baseUrl . '/users/1/posts?api_key=your-api-key&page=1&pageSize=10'
                        ]
                    ]
                ],
                [
                    'group' => '站点相关接口',
                    'endpoints' => [
                        [
                            'name' => '获取站点统计信息',
                            'method' => 'GET',
                            'path' => '/site/stats',
                            'example' => $baseUrl . '/site/stats?api_key=your-api-key'
                        ]
                    ]
                ]
            ]
        ];
        
        try {
            // 输出HTML文档
            include $templateFile;
        } catch (Exception $e) {
            die('Error loading template: ' . $e->getMessage());
        }
    }
} 