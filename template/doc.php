<?php
// 调试信息
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $apiDocs['title']; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/themes/prism-tomorrow.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2ecc71;
            --dark-color: #2c3e50;
            --light-color: #ecf0f1;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: var(--dark-color);
            background-color: #f8f9fa;
        }
        
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: 250px;
            background: white;
            border-right: 1px solid #dee2e6;
            overflow-y: auto;
            padding: 1.5rem;
            z-index: 1000;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 2rem;
            max-width: 1200px;
        }
        
        .api-group {
            margin-bottom: 3rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            padding: 2rem;
        }
        
        .group-title {
            color: var(--primary-color);
            font-weight: 600;
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--primary-color);
        }
        
        .endpoint {
            margin-bottom: 2rem;
            padding: 1.5rem;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            background: #fff;
            transition: all 0.3s ease;
        }
        
        .endpoint:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .method {
            display: inline-block;
            padding: 0.35rem 0.8rem;
            border-radius: 4px;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .method.get {
            background: #e3f2fd;
            color: #1565c0;
        }
        
        .method.post {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .endpoint-path {
            font-family: 'SF Mono', 'Segoe UI Mono', 'Roboto Mono', Menlo, Courier, monospace;
            font-size: 0.95rem;
            color: var(--dark-color);
            padding: 0.5rem 1rem;
            background: #f8f9fa;
            border-radius: 4px;
            margin-left: 1rem;
        }
        
        .endpoint-name {
            font-size: 1.1rem;
            font-weight: 500;
            color: var(--dark-color);
            margin: 1rem 0;
        }
        
        .params-table {
            margin-top: 1.5rem;
            width: 100%;
        }
        
        .params-table th {
            background: #f8f9fa;
            font-weight: 600;
            padding: 0.75rem;
        }
        
        .params-table td {
            padding: 0.75rem;
            border-top: 1px solid #dee2e6;
        }
        
        .param-required {
            display: inline-block;
            padding: 0.2rem 0.5rem;
            border-radius: 3px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .param-required.yes {
            background: #ffebee;
            color: #c62828;
        }
        
        .param-required.no {
            background: #f5f5f5;
            color: #616161;
        }
        
        .param-type {
            display: inline-block;
            padding: 0.2rem 0.5rem;
            background: #e3f2fd;
            color: #1565c0;
            border-radius: 3px;
            font-size: 0.85rem;
        }
        
        .nav-link {
            color: var(--dark-color);
            padding: 0.5rem 0;
            transition: all 0.2s ease;
        }
        
        .nav-link:hover {
            color: var(--primary-color);
            text-decoration: none;
            padding-left: 0.5rem;
        }
        
        .api-example {
            margin-top: 1rem;
            background: #2d2d2d;
            border-radius: 4px;
            padding: 1rem;
        }
        
        .example-title {
            color: #e0e0e0;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        
        .example-code {
            color: #fff;
            font-family: 'SF Mono', 'Segoe UI Mono', 'Roboto Mono', Menlo, Courier, monospace;
            font-size: 0.9rem;
            word-break: break-all;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
        }
        
        .top-bar {
            background: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }
        
        .description {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <!-- 侧边导航 -->
    <div class="sidebar">
        <h5 class="mb-3">目录</h5>
        <nav class="nav flex-column">
            <a class="nav-link" href="#auth">认证说明</a>
            <?php foreach ($apiDocs['apis'] as $index => $group): ?>
            <a class="nav-link" href="#group-<?php echo $index; ?>"><?php echo $group['group']; ?></a>
            <?php endforeach; ?>
        </nav>
    </div>

    <!-- 主要内容 -->
    <div class="main-content">
        <div class="top-bar">
            <h1><?php echo $apiDocs['title']; ?></h1>
            <p class="description"><?php echo $apiDocs['description']; ?></p>
        </div>
        
        <!-- 基础信息 -->
        <div class="api-group" id="auth">
            <h2 class="group-title">基础信息</h2>
            <div class="mb-4">
                <h5>接口地址</h5>
                <code class="endpoint-path"><?php echo $apiDocs['baseUrl']; ?></code>
            </div>
            <div class="mb-4">
                <h5>认证方式</h5>
                <p>所有接口都需要在请求参数中携带 <code>api_key</code></p>
                <div class="api-example">
                    <div class="example-title">示例</div>
                    <div class="example-code"><?php echo $apiDocs['baseUrl']; ?>/posts?api_key=your-api-key</div>
                </div>
            </div>
        </div>

        <!-- API列表 -->
        <?php foreach ($apiDocs['apis'] as $index => $group): ?>
        <div class="api-group" id="group-<?php echo $index; ?>">
            <h2 class="group-title"><?php echo $group['group']; ?></h2>
            
            <?php foreach ($group['endpoints'] as $api): ?>
            <div class="endpoint">
                <div class="d-flex align-items-center">
                    <span class="method <?php echo strtolower($api['method']); ?>"><?php echo $api['method']; ?></span>
                    <code class="endpoint-path"><?php echo $api['path']; ?></code>
                </div>
                <div class="endpoint-name"><?php echo $api['name']; ?></div>
                
                <?php if (!empty($api['params'])): ?>
                <table class="params-table">
                    <thead>
                        <tr>
                            <th>参数名</th>
                            <th>类型</th>
                            <th>是否必须</th>
                            <th>说明</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($api['params'] as $param): ?>
                        <tr>
                            <td><code><?php echo $param['name']; ?></code></td>
                            <td><span class="param-type"><?php echo $param['type']; ?></span></td>
                            <td>
                                <span class="param-required <?php echo $param['required'] ? 'yes' : 'no'; ?>">
                                    <?php echo $param['required'] ? '必须' : '可选'; ?>
                                </span>
                            </td>
                            <td><?php echo $param['description']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>

                <?php if (!empty($api['example'])): ?>
                <div class="api-example">
                    <div class="example-title">请求示例</div>
                    <div class="example-code"><?php echo $api['example']; ?></div>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <script>
        // 平滑滚动
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html> 