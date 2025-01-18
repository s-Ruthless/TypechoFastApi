<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

// 调试信息
error_reporting(E_ALL);
ini_set('display_errors', 1);

class TypechoFastApi_Controller extends Typecho_Widget
{
    /**
     * @var Typecho_Config
     */
    private $config;
    
    /**
     * 构造函数
     */
    public function __construct($request, $response, $params = NULL)
    {
        parent::__construct($request, $response, $params);
        $this->config = Helper::options()->plugin('TypechoFastApi');
        
        // 处理跨域
        if ($this->config->cors) {
            $origin = $this->request->getHeader('Origin');
            $allowOrigins = explode(',', $this->config->allowOrigin);
            
            if ($this->config->allowOrigin === '*') {
                $this->response->setHeader('Access-Control-Allow-Origin', '*');
            } else if (in_array($origin, $allowOrigins)) {
                $this->response->setHeader('Access-Control-Allow-Origin', $origin);
            }
            
            $this->response->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
            $this->response->setHeader('Access-Control-Allow-Headers', 'Content-Type, api_key');
        }
        
        // 检查API密钥
        $this->checkApiKey();
    }
    
    /**
     * 检查API密钥
     */
    private function checkApiKey()
    {
        $apiKey = $this->request->get('api_key');
        if (empty($apiKey) || $apiKey !== $this->config->apiKey) {
            $this->response->throwJson([
                'code' => 401,
                'message' => 'Unauthorized'
            ]);
        }
    }
    
    /**
     * 获取文章列表
     */
    public function posts()
    {
        $page = $this->request->get('page');        // 获取页码
        $pageSize = $this->request->get('pageSize'); // 获取每页条数
        
        // 如果开启了缓存，先尝试从缓存获取
        if ($this->config->cache) {
            $cacheKey = TypechoFastApi_Cache::PREFIX_POSTS . ($page ? $page : 'all') . '_' . ($pageSize ? $pageSize : 'all');
            $cached = TypechoFastApi_Cache::get($cacheKey);
            if ($cached !== false) {
                $this->response->throwJson($cached);
            }
        }
        
        $db = Typecho_Db::get();
        
        // 获取文章总数
        $total = $db->fetchObject($db->select(array('COUNT(cid)' => 'total'))
            ->from('table.contents')
            ->where('type = ?', 'post')
            ->where('status = ?', 'publish'))->total;
            
        // 获取文章列表
        $select = $db->select('cid', 'title', 'created', 'modified', 'text', 'authorId', 'slug', 'commentsNum')
            ->from('table.contents')
            ->where('type = ?', 'post')
            ->where('status = ?', 'publish')
            ->order('created', Typecho_Db::SORT_DESC);
            
        // 只有当page和pageSize都有值时才进行分页
        if (!empty($page) && !empty($pageSize)) {
            $select->page($page, $pageSize);
        }
        
        $posts = $db->fetchAll($select);
        
        $result = array_map(function($post) {
            return [
                'id' => $post['cid'],
                'title' => $post['title'],
                'slug' => $post['slug'],
                'created' => date('c', $post['created']),
                'modified' => date('c', $post['modified']),
                'summary' => Typecho_Common::subStr(strip_tags($post['text']), 0, $this->config->summaryLength, '...'),
                'authorId' => $post['authorId'],
                'commentsNum' => $post['commentsNum']
            ];
        }, $posts);
        
        $response = [
            'code' => 200,
            'message' => 'success',
            'data' => [
                'list' => $result,
                'pagination' => [
                    'current' => empty($page) ? 1 : (int)$page,
                    'pageSize' => empty($pageSize) ? $total : (int)$pageSize,
                    'total' => (int)$total,
                    'totalPage' => empty($pageSize) ? 1 : ceil($total / $pageSize)
                ]
            ]
        ];
        
        // 如果开启了缓存，保存到缓存
        if ($this->config->cache) {
            TypechoFastApi_Cache::set($cacheKey, $response);
        }
        
        $this->response->throwJson($response);
    }
    
    /**
     * 获取单篇文章
     */
    public function post()
    {
        $cid = $this->request->get('id');
        
        // 如果开启了缓存，先尝试从缓存获取
        if ($this->config->cache) {
            $cacheKey = TypechoFastApi_Cache::PREFIX_POST . $cid;
            $cached = TypechoFastApi_Cache::get($cacheKey);
            if ($cached !== false) {
                $this->response->throwJson($cached);
            }
        }
        
        $db = Typecho_Db::get();
        $post = $db->fetchRow($db->select()
            ->from('table.contents')
            ->where('cid = ?', $cid)
            ->where('type = ?', 'post')
            ->where('status = ?', 'publish')
            ->limit(1));
            
        if (empty($post)) {
            $this->response->throwJson([
                'code' => 404,
                'message' => 'Post not found'
            ]);
        }
        
        $result = [
            'id' => $post['cid'],
            'title' => $post['title'],
            'created' => date('c', $post['created']),
            'modified' => date('c', $post['modified']),
            'content' => $post['text'],
            'authorId' => $post['authorId']
        ];
        
        $this->response->throwJson([
            'code' => 200,
            'message' => 'success',
            'data' => $result
        ]);
    }
    
    /**
     * 获取分类列表
     */
    public function categories()
    {
        // 如果开启了缓存，先尝试从缓存获取
        if ($this->config->cache) {
            $cacheKey = TypechoFastApi_Cache::PREFIX_CATEGORY . 'list';
            $cached = TypechoFastApi_Cache::get($cacheKey);
            if ($cached !== false) {
                $this->response->throwJson($cached);
            }
        }
        
        $db = Typecho_Db::get();
        $categories = $db->fetchAll($db->select()
            ->from('table.metas')
            ->where('type = ?', 'category')
            ->order('order', Typecho_Db::SORT_ASC));
            
        $result = array_map(function($category) {
            return [
                'id' => $category['mid'],
                'name' => $category['name'],
                'slug' => $category['slug'],
                'description' => $category['description']
            ];
        }, $categories);
        
        $response = [
            'code' => 200,
            'message' => 'success',
            'data' => $result
        ];
        
        // 如果开启了缓存，保存到缓存
        if ($this->config->cache) {
            TypechoFastApi_Cache::set($cacheKey, $response);
        }
        
        $this->response->throwJson($response);
    }
    
    /**
     * 获取标签列表
     */
    public function tags()
    {
        // 如果开启了缓存，先尝试从缓存获取
        if ($this->config->cache) {
            $cacheKey = TypechoFastApi_Cache::PREFIX_TAG . 'list';
            $cached = TypechoFastApi_Cache::get($cacheKey);
            if ($cached !== false) {
                $this->response->throwJson($cached);
            }
        }
        
        $db = Typecho_Db::get();
        $tags = $db->fetchAll($db->select()
            ->from('table.metas')
            ->where('type = ?', 'tag')
            ->order('mid', Typecho_Db::SORT_DESC));
            
        $result = array_map(function($tag) {
            return [
                'id' => $tag['mid'],
                'name' => $tag['name'],
                'slug' => $tag['slug']
            ];
        }, $tags);
        
        $response = [
            'code' => 200,
            'message' => 'success',
            'data' => $result
        ];
        
        // 如果开启了缓存，保存到缓存
        if ($this->config->cache) {
            TypechoFastApi_Cache::set($cacheKey, $response);
        }
        
        $this->response->throwJson($response);
    }
    
    /**
     * 搜索文章
     */
    public function search()
    {
        $keyword = $this->request->get('keyword', '');
        $page = $this->request->get('page', 1);
        $pageSize = $this->request->get('pageSize', 10);
        
        if (empty($keyword)) {
            $this->response->throwJson([
                'code' => 400,
                'message' => 'Keyword is required'
            ]);
        }
        
        $db = Typecho_Db::get();
        
        // 获取搜索结果总数
        $total = $db->fetchObject($db->select(array('COUNT(cid)' => 'total'))
            ->from('table.contents')
            ->where('type = ? AND status = ? AND (title LIKE ? OR text LIKE ?)', 
                'post', 'publish', 
                '%' . $keyword . '%', 
                '%' . $keyword . '%')
            )->total;
            
        // 获取搜索结果
        $posts = $db->fetchAll($db->select()
            ->from('table.contents')
            ->where('type = ? AND status = ? AND (title LIKE ? OR text LIKE ?)', 
                'post', 'publish', 
                '%' . $keyword . '%', 
                '%' . $keyword . '%')
            ->order('created', Typecho_Db::SORT_DESC)
            ->page($page, $pageSize));
            
        $result = array_map(function($post) {
            return [
                'id' => $post['cid'],
                'title' => $post['title'],
                'slug' => $post['slug'],
                'created' => date('c', $post['created']),
                'summary' => Typecho_Common::subStr(strip_tags($post['text']), 0, $this->config->summaryLength, '...')
            ];
        }, $posts);
        
        $this->response->throwJson([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'list' => $result,
                'pagination' => [
                    'current' => (int)$page,
                    'pageSize' => (int)$pageSize,
                    'total' => (int)$total,
                    'totalPage' => ceil($total / $pageSize)
                ]
            ]
        ]);
    }
    
    /**
     * 获取文章归档
     */
    public function archive()
    {
        if ($this->config->cache) {
            $cacheKey = TypechoFastApi_Cache::PREFIX_POSTS . 'archive';
            $cached = TypechoFastApi_Cache::get($cacheKey);
            if ($cached !== false) {
                $this->response->throwJson($cached);
            }
        }
        
        $db = Typecho_Db::get();
        $posts = $db->fetchAll($db->select('created', 'cid', 'title', 'slug')
            ->from('table.contents')
            ->where('type = ? AND status = ?', 'post', 'publish')
            ->order('created', Typecho_Db::SORT_DESC));
            
        $archives = [];
        foreach ($posts as $post) {
            $year = date('Y', $post['created']);
            $month = date('m', $post['created']);
            $archives[$year][$month][] = [
                'id' => $post['cid'],
                'title' => $post['title'],
                'slug' => $post['slug'],
                'created' => date('c', $post['created'])
            ];
        }
        
        $response = [
            'code' => 200,
            'message' => 'success',
            'data' => $archives
        ];
        
        if ($this->config->cache) {
            TypechoFastApi_Cache::set($cacheKey, $response);
        }
        
        $this->response->throwJson($response);
    }
    
    /**
     * 获取分类下的文章
     */
    public function categoryPosts()
    {
        $mid = $this->request->get('mid');
        $page = $this->request->get('page', 1);
        $pageSize = $this->request->get('pageSize', 10);
        
        $db = Typecho_Db::get();
        
        // 获取分类信息
        $category = $db->fetchRow($db->select()
            ->from('table.metas')
            ->where('mid = ? AND type = ?', $mid, 'category'));
            
        if (empty($category)) {
            $this->response->throwJson([
                'code' => 404,
                'message' => 'Category not found'
            ]);
        }
        
        // 获取分类下的文章
        $posts = $db->fetchAll($db->select('table.contents.*')
            ->from('table.contents')
            ->join('table.relationships', 'table.relationships.cid = table.contents.cid')
            ->where('table.relationships.mid = ?', $mid)
            ->where('table.contents.type = ? AND table.contents.status = ?', 'post', 'publish')
            ->order('table.contents.created', Typecho_Db::SORT_DESC)
            ->page($page, $pageSize));
            
        $result = array_map(function($post) {
            return [
                'id' => $post['cid'],
                'title' => $post['title'],
                'slug' => $post['slug'],
                'created' => date('c', $post['created']),
                'summary' => Typecho_Common::subStr(strip_tags($post['text']), 0, $this->config->summaryLength, '...')
            ];
        }, $posts);
        
        $this->response->throwJson([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'category' => [
                    'id' => $category['mid'],
                    'name' => $category['name'],
                    'slug' => $category['slug']
                ],
                'posts' => $result
            ]
        ]);
    }
    
    /**
     * 获取标签下的文章
     */
    public function tagPosts()
    {
        $mid = $this->request->get('mid');
        $page = $this->request->get('page', 1);
        $pageSize = $this->request->get('pageSize', 10);
        
        // 如果开启了缓存，先尝试从缓存获取
        if ($this->config->cache) {
            $cacheKey = TypechoFastApi_Cache::PREFIX_TAG . $mid . '_posts_' . $page . '_' . $pageSize;
            $cached = TypechoFastApi_Cache::get($cacheKey);
            if ($cached !== false) {
                $this->response->throwJson($cached);
            }
        }
        
        $db = Typecho_Db::get();
        
        // 获取标签信息
        $tag = $db->fetchRow($db->select()
            ->from('table.metas')
            ->where('mid = ? AND type = ?', $mid, 'tag'));
            
        if (empty($tag)) {
            $this->response->throwJson([
                'code' => 404,
                'message' => 'Tag not found'
            ]);
        }
        
        // 获取标签下的文章总数
        $total = $db->fetchObject($db->select(array('COUNT(DISTINCT table.contents.cid)' => 'total'))
            ->from('table.contents')
            ->join('table.relationships', 'table.relationships.cid = table.contents.cid')
            ->where('table.relationships.mid = ?', $mid)
            ->where('table.contents.type = ? AND table.contents.status = ?', 'post', 'publish')
        )->total;
        
        // 获取标签下的文章
        $posts = $db->fetchAll($db->select('DISTINCT table.contents.*')
            ->from('table.contents')
            ->join('table.relationships', 'table.relationships.cid = table.contents.cid')
            ->where('table.relationships.mid = ?', $mid)
            ->where('table.contents.type = ? AND table.contents.status = ?', 'post', 'publish')
            ->order('table.contents.created', Typecho_Db::SORT_DESC)
            ->page($page, $pageSize));
            
        $result = array_map(function($post) {
            return [
                'id' => $post['cid'],
                'title' => $post['title'],
                'slug' => $post['slug'],
                'created' => date('c', $post['created']),
                'modified' => date('c', $post['modified']),
                'summary' => Typecho_Common::subStr(strip_tags($post['text']), 0, $this->config->summaryLength, '...'),
                'authorId' => $post['authorId'],
                'commentsNum' => $post['commentsNum']
            ];
        }, $posts);
        
        $response = [
            'code' => 200,
            'message' => 'success',
            'data' => [
                'tag' => [
                    'id' => $tag['mid'],
                    'name' => $tag['name'],
                    'slug' => $tag['slug']
                ],
                'list' => $result,
                'pagination' => [
                    'current' => (int)$page,
                    'pageSize' => (int)$pageSize,
                    'total' => (int)$total,
                    'totalPage' => ceil($total / $pageSize)
                ]
            ]
        ];
        
        // 如果开启了缓存，保存到缓存
        if ($this->config->cache) {
            TypechoFastApi_Cache::set($cacheKey, $response);
        }
        
        $this->response->throwJson($response);
    }
    
    /**
     * 获取评论列表
     */
    public function comments()
    {
        $page = $this->request->get('page', 1);
        $pageSize = $this->request->get('pageSize', 10);
        
        // 如果开启了缓存，先尝试从缓存获取
        if ($this->config->cache) {
            $cacheKey = TypechoFastApi_Cache::PREFIX_COMMENTS . 'list_' . $page . '_' . $pageSize;
            $cached = TypechoFastApi_Cache::get($cacheKey);
            if ($cached !== false) {
                $this->response->throwJson($cached);
            }
        }
        
        $db = Typecho_Db::get();
        
        // 获取评论总数
        $total = $db->fetchObject($db->select(array('COUNT(coid)' => 'total'))
            ->from('table.comments')
            ->where('status = ?', 'approved'))->total;
            
        // 获取评论列表
        $comments = $db->fetchAll($db->select()
            ->from('table.comments')
            ->where('status = ?', 'approved')
            ->order('created', Typecho_Db::SORT_DESC)
            ->page($page, $pageSize));
            
        $result = array_map(function($comment) {
            return [
                'id' => $comment['coid'],
                'postId' => $comment['cid'],
                'author' => $comment['author'],
                'authorId' => $comment['authorId'],
                'mail' => $comment['mail'],
                'url' => $comment['url'],
                'content' => $comment['text'],
                'parent' => $comment['parent'],
                'created' => date('c', $comment['created'])
            ];
        }, $comments);
        
        $response = [
            'code' => 200,
            'message' => 'success',
            'data' => [
                'list' => $result,
                'pagination' => [
                    'current' => (int)$page,
                    'pageSize' => (int)$pageSize,
                    'total' => (int)$total,
                    'totalPage' => ceil($total / $pageSize)
                ]
            ]
        ];
        
        // 如果开启了缓存，保存到缓存
        if ($this->config->cache) {
            TypechoFastApi_Cache::set($cacheKey, $response);
        }
        
        $this->response->throwJson($response);
    }
    
    /**
     * 获取文章评论
     */
    public function postComments()
    {
        $cid = $this->request->get('cid');
        $page = $this->request->get('page', 1);
        $pageSize = $this->request->get('pageSize', 10);
        
        // 如果开启了缓存，先尝试从缓存获取
        if ($this->config->cache) {
            $cacheKey = TypechoFastApi_Cache::PREFIX_COMMENTS . 'post_' . $cid . '_' . $page . '_' . $pageSize;
            $cached = TypechoFastApi_Cache::get($cacheKey);
            if ($cached !== false) {
                $this->response->throwJson($cached);
            }
        }
        
        $db = Typecho_Db::get();
        
        // 获取文章评论总数
        $total = $db->fetchObject($db->select(array('COUNT(coid)' => 'total'))
            ->from('table.comments')
            ->where('cid = ? AND status = ?', $cid, 'approved'))->total;
            
        // 获取文章评论列表
        $comments = $db->fetchAll($db->select()
            ->from('table.comments')
            ->where('cid = ? AND status = ?', $cid, 'approved')
            ->order('created', Typecho_Db::SORT_ASC)
            ->page($page, $pageSize));
            
        $result = array_map(function($comment) {
            return [
                'id' => $comment['coid'],
                'author' => $comment['author'],
                'authorId' => $comment['authorId'],
                'mail' => $comment['mail'],
                'url' => $comment['url'],
                'content' => $comment['text'],
                'parent' => $comment['parent'],
                'created' => date('c', $comment['created'])
            ];
        }, $comments);
        
        $response = [
            'code' => 200,
            'message' => 'success',
            'data' => [
                'list' => $result,
                'pagination' => [
                    'current' => (int)$page,
                    'pageSize' => (int)$pageSize,
                    'total' => (int)$total,
                    'totalPage' => ceil($total / $pageSize)
                ]
            ]
        ];
        
        // 如果开启了缓存，保存到缓存
        if ($this->config->cache) {
            TypechoFastApi_Cache::set($cacheKey, $response);
        }
        
        $this->response->throwJson($response);
    }
    
    /**
     * 发表评论
     */
    public function addComment()
    {
        // 评论功能不使用缓存
        $cid = $this->request->get('cid');
        $parent = $this->request->get('parent', 0);
        $author = $this->request->get('author');
        $mail = $this->request->get('mail');
        $url = $this->request->get('url', '');
        $content = $this->request->get('content');
        
        // 验证必填字段
        if (empty($cid) || empty($author) || empty($mail) || empty($content)) {
            $this->response->throwJson([
                'code' => 400,
                'message' => 'Missing required fields'
            ]);
        }
        
        // 验证邮箱格式
        if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
            $this->response->throwJson([
                'code' => 400,
                'message' => 'Invalid email format'
            ]);
        }
        
        $db = Typecho_Db::get();
        
        // 检查文章是否存在
        $post = $db->fetchRow($db->select()
            ->from('table.contents')
            ->where('cid = ? AND type = ? AND status = ?', $cid, 'post', 'publish'));
            
        if (empty($post)) {
            $this->response->throwJson([
                'code' => 404,
                'message' => 'Post not found'
            ]);
        }
        
        // 如果有父评论，检查父评论是否存在
        if ($parent > 0) {
            $parentComment = $db->fetchRow($db->select()
                ->from('table.comments')
                ->where('coid = ? AND status = ?', $parent, 'approved'));
                
            if (empty($parentComment)) {
                $this->response->throwJson([
                    'code' => 404,
                    'message' => 'Parent comment not found'
                ]);
            }
        }
        
        // 插入评论
        $comment = [
            'cid' => $cid,
            'created' => time(),
            'author' => $author,
            'authorId' => '0',
            'ownerId' => $post['authorId'],
            'mail' => $mail,
            'url' => $url,
            'ip' => $this->request->getIp(),
            'agent' => $this->request->getAgent(),
            'text' => $content,
            'type' => 'comment',
            'status' => 'approved',
            'parent' => $parent
        ];
        
        $result = $db->query($db->insert('table.comments')->rows($comment));
        
        if ($result) {
            // 更新文章评论数
            $db->query($db->update('table.contents')
                ->rows(['commentsNum' => (int)$post['commentsNum'] + 1])
                ->where('cid = ?', $cid));
                
            // 清理相关缓存
            if ($this->config->cache) {
                TypechoFastApi_Cache::clear(TypechoFastApi_Cache::PREFIX_COMMENTS);
                TypechoFastApi_Cache::clear(TypechoFastApi_Cache::PREFIX_POST . $cid);
            }
            
            $this->response->throwJson([
                'code' => 200,
                'message' => 'Comment added successfully'
            ]);
        } else {
            $this->response->throwJson([
                'code' => 500,
                'message' => 'Failed to add comment'
            ]);
        }
    }
    
    /**
     * 获取独立页面列表
     */
    public function pages()
    {
        // 如果开启了缓存，先尝试从缓存获取
        if ($this->config->cache) {
            $cacheKey = TypechoFastApi_Cache::PREFIX_PAGE . 'list';
            $cached = TypechoFastApi_Cache::get($cacheKey);
            if ($cached !== false) {
                $this->response->throwJson($cached);
            }
        }
        
        $db = Typecho_Db::get();
        $pages = $db->fetchAll($db->select('cid', 'title', 'slug', 'created', 'modified', 'text', 'authorId', 'template')
            ->from('table.contents')
            ->where('type = ? AND status = ?', 'page', 'publish')
            ->order('order', Typecho_Db::SORT_ASC));
            
        $result = array_map(function($page) {
            return [
                'id' => $page['cid'],
                'title' => $page['title'],
                'slug' => $page['slug'],
                'created' => date('c', $page['created']),
                'modified' => date('c', $page['modified']),
                'summary' => Typecho_Common::subStr(strip_tags($page['text']), 0, $this->config->summaryLength, '...'),
                'authorId' => $page['authorId'],
                'template' => $page['template']
            ];
        }, $pages);
        
        $response = [
            'code' => 200,
            'message' => 'success',
            'data' => $result
        ];
        
        // 如果开启了缓存，保存到缓存
        if ($this->config->cache) {
            TypechoFastApi_Cache::set($cacheKey, $response);
        }
        
        $this->response->throwJson($response);
    }
    
    /**
     * 获取单个独立页面
     */
    public function page()
    {
        $cid = $this->request->get('id');
        
        // 如果开启了缓存，先尝试从缓存获取
        if ($this->config->cache) {
            $cacheKey = TypechoFastApi_Cache::PREFIX_PAGE . $cid;
            $cached = TypechoFastApi_Cache::get($cacheKey);
            if ($cached !== false) {
                $this->response->throwJson($cached);
            }
        }
        
        $db = Typecho_Db::get();
        $page = $db->fetchRow($db->select()
            ->from('table.contents')
            ->where('cid = ? AND type = ? AND status = ?', $cid, 'page', 'publish')
            ->limit(1));
            
        if (empty($page)) {
            $this->response->throwJson([
                'code' => 404,
                'message' => 'Page not found'
            ]);
        }
        
        // 获取页面的字段
        $fields = [];
        $rows = $db->fetchAll($db->select()
            ->from('table.fields')
            ->where('cid = ?', $cid));
            
        foreach ($rows as $row) {
            $fields[$row['name']] = $row['str_value'] ?: $row['int_value'];
        }
        
        $result = [
            'id' => $page['cid'],
            'title' => $page['title'],
            'slug' => $page['slug'],
            'created' => date('c', $page['created']),
            'modified' => date('c', $page['modified']),
            'content' => $page['text'],
            'authorId' => $page['authorId'],
            'template' => $page['template'],
            'fields' => $fields,
            'commentsNum' => $page['commentsNum']
        ];
        
        $response = [
            'code' => 200,
            'message' => 'success',
            'data' => $result
        ];
        
        // 如果开启了缓存，保存到缓存
        if ($this->config->cache) {
            TypechoFastApi_Cache::set($cacheKey, $response);
        }
        
        $this->response->throwJson($response);
    }
    
    /**
     * 获取用户信息
     */
    public function user()
    {
        $uid = $this->request->get('uid');
        
        if ($this->config->cache) {
            $cacheKey = TypechoFastApi_Cache::PREFIX_USER . $uid;
            $cached = TypechoFastApi_Cache::get($cacheKey);
            if ($cached !== false) {
                $this->response->throwJson($cached);
            }
        }
        
        $db = Typecho_Db::get();
        $user = $db->fetchRow($db->select()
            ->from('table.users')
            ->where('uid = ?', $uid));
            
        if (empty($user)) {
            $this->response->throwJson([
                'code' => 404,
                'message' => 'User not found'
            ]);
        }
        
        $result = [
            'id' => $user['uid'],
            'name' => $user['name'],
            'screenName' => $user['screenName'],
            'mail' => $user['mail'],
            'url' => $user['url'],
            'created' => date('c', $user['created'])
        ];
        
        $response = [
            'code' => 200,
            'message' => 'success',
            'data' => $result
        ];
        
        if ($this->config->cache) {
            TypechoFastApi_Cache::set($cacheKey, $response);
        }
        
        $this->response->throwJson($response);
    }
    
    /**
     * 获取用户文章列表
     */
    public function userPosts()
    {
        $uid = $this->request->get('uid');
        $page = $this->request->get('page', 1);
        $pageSize = $this->request->get('pageSize', 10);
        
        if ($this->config->cache) {
            $cacheKey = TypechoFastApi_Cache::PREFIX_USER . $uid . '_posts_' . $page . '_' . $pageSize;
            $cached = TypechoFastApi_Cache::get($cacheKey);
            if ($cached !== false) {
                $this->response->throwJson($cached);
            }
        }
        
        $db = Typecho_Db::get();
        
        // 获取用户文章总数
        $total = $db->fetchObject($db->select(array('COUNT(cid)' => 'total'))
            ->from('table.contents')
            ->where('authorId = ? AND type = ? AND status = ?', $uid, 'post', 'publish')
        )->total;
        
        // 获取用户文章列表
        $posts = $db->fetchAll($db->select()
            ->from('table.contents')
            ->where('authorId = ? AND type = ? AND status = ?', $uid, 'post', 'publish')
            ->order('created', Typecho_Db::SORT_DESC)
            ->page($page, $pageSize));
            
        $result = array_map(function($post) {
            return [
                'id' => $post['cid'],
                'title' => $post['title'],
                'slug' => $post['slug'],
                'created' => date('c', $post['created']),
                'modified' => date('c', $post['modified']),
                'summary' => Typecho_Common::subStr(strip_tags($post['text']), 0, $this->config->summaryLength, '...'),
                'commentsNum' => $post['commentsNum']
            ];
        }, $posts);
        
        $response = [
            'code' => 200,
            'message' => 'success',
            'data' => [
                'list' => $result,
                'pagination' => [
                    'current' => (int)$page,
                    'pageSize' => (int)$pageSize,
                    'total' => (int)$total,
                    'totalPage' => ceil($total / $pageSize)
                ]
            ]
        ];
        
        if ($this->config->cache) {
            TypechoFastApi_Cache::set($cacheKey, $response);
        }
        
        $this->response->throwJson($response);
    }
    
    /**
     * 获取站点统计信息
     */
    public function siteStats()
    {
        // 如果开启了缓存，先尝试从缓存获取
        if ($this->config->cache) {
            $cacheKey = TypechoFastApi_Cache::PREFIX_SITE . 'stats';
            $cached = TypechoFastApi_Cache::get($cacheKey);
            if ($cached !== false) {
                $this->response->throwJson($cached);
            }
        }
        
        $db = Typecho_Db::get();
        
        // 获取文章统计
        $posts = $db->fetchObject($db->select(array(
            'COUNT(cid)' => 'total',
            'SUM(commentsNum)' => 'commentsCount'
        ))->from('table.contents')
            ->where('type = ? AND status = ?', 'post', 'publish'));
            
        // 获取分类统计
        $categories = $db->fetchObject($db->select(array('COUNT(mid)' => 'total'))
            ->from('table.metas')
            ->where('type = ?', 'category'));
            
        // 获取标签统计
        $tags = $db->fetchObject($db->select(array('COUNT(mid)' => 'total'))
            ->from('table.metas')
            ->where('type = ?', 'tag'));
            
        // 获取页面统计
        $pages = $db->fetchObject($db->select(array('COUNT(cid)' => 'total'))
            ->from('table.contents')
            ->where('type = ? AND status = ?', 'page', 'publish'));
            
        // 获取评论统计
        $comments = $db->fetchObject($db->select(array('COUNT(coid)' => 'total'))
            ->from('table.comments')
            ->where('status = ?', 'approved'));
            
        // 获取用户统计
        $users = $db->fetchObject($db->select(array('COUNT(uid)' => 'total'))
            ->from('table.users'));
            
        // 获取最近更新时间
        $lastPost = $db->fetchObject($db->select('created')
            ->from('table.contents')
            ->where('type = ? AND status = ?', 'post', 'publish')
            ->order('created', Typecho_Db::SORT_DESC)
            ->limit(1));
            
        $result = [
            'posts' => [
                'total' => (int)$posts->total,
                'commentsCount' => (int)$posts->commentsCount
            ],
            'categories' => [
                'total' => (int)$categories->total
            ],
            'tags' => [
                'total' => (int)$tags->total
            ],
            'pages' => [
                'total' => (int)$pages->total
            ],
            'comments' => [
                'total' => (int)$comments->total
            ],
            'users' => [
                'total' => (int)$users->total
            ],
            'lastUpdate' => $lastPost ? date('c', $lastPost->created) : null
        ];
        
        $response = [
            'code' => 200,
            'message' => 'success',
            'data' => $result
        ];
        
        // 如果开启了缓存，保存到缓存
        if ($this->config->cache) {
            TypechoFastApi_Cache::set($cacheKey, $response);
        }
        
        $this->response->throwJson($response);
    }
} 