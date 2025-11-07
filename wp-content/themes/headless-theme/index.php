<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Headless WordPress - API Only</title>
    <?php wp_head(); ?>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .api-info {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .endpoint {
            background: #f8f9fa;
            padding: 10px;
            margin: 10px 0;
            border-left: 4px solid #007cba;
            font-family: monospace;
        }
        .method {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 12px;
        }
        .get { background: #28a745; color: white; }
        .post { background: #007bff; color: white; }
        .put { background: #ffc107; color: black; }
        .delete { background: #dc3545; color: white; }
    </style>
</head>
<body>
    <div class="api-info">
        <h1>🚀 Headless WordPress with WooCommerce</h1>
        <p>Your headless WordPress installation is ready! This page is only visible when accessing the site directly. Your frontend application should consume the REST API endpoints below.</p>
        
        <h2>📡 Available API Endpoints</h2>
        
        <h3>WordPress Core</h3>
        <div class="endpoint">
            <span class="method get">GET</span> <?php echo rest_url('wp/v2/posts'); ?><br>
            <small>Get all posts</small>
        </div>
        <div class="endpoint">
            <span class="method get">GET</span> <?php echo rest_url('wp/v2/pages'); ?><br>
            <small>Get all pages</small>
        </div>
        <div class="endpoint">
            <span class="method get">GET</span> <?php echo rest_url('wp/v2/media'); ?><br>
            <small>Get media files</small>
        </div>
        
        <?php if (class_exists('WooCommerce')): ?>
        <h3>🛒 WooCommerce</h3>
        <div class="endpoint">
            <span class="method get">GET</span> <?php echo rest_url('wc/v3/products'); ?><br>
            <small>Get all products</small>
        </div>
        <div class="endpoint">
            <span class="method get">GET</span> <?php echo rest_url('wc/v3/orders'); ?><br>
            <small>Get orders (requires authentication)</small>
        </div>
        <div class="endpoint">
            <span class="method get">GET</span> <?php echo rest_url('wc/v3/customers'); ?><br>
            <small>Get customers (requires authentication)</small>
        </div>
        <div class="endpoint">
            <span class="method get">GET</span> <?php echo rest_url('wc/v3/product_categories'); ?><br>
            <small>Get product categories</small>
        </div>
        <?php endif; ?>
        
        <h3>🔧 Custom Endpoints</h3>
        <div class="endpoint">
            <span class="method get">GET</span> <?php echo rest_url('headless/v1/site-info'); ?><br>
            <small>Get site information</small>
        </div>
        <div class="endpoint">
            <span class="method get">GET</span> <?php echo rest_url('headless/v1/store-info'); ?><br>
            <small>Get store information</small>
        </div>
        
        <h3>🔐 Authentication</h3>
        <div class="endpoint">
            <span class="method post">POST</span> <?php echo rest_url('jwt-auth/v1/token'); ?><br>
            <small>Get JWT token</small>
        </div>
        
        <h2>📚 Documentation</h2>
        <ul>
            <li><a href="https://developer.wordpress.org/rest-api/" target="_blank">WordPress REST API Docs</a></li>
            <li><a href="https://woocommerce.github.io/woocommerce-rest-api-docs/" target="_blank">WooCommerce REST API Docs</a></li>
        </ul>
        
        <h2>⚙️ Next Steps</h2>
        <ol>
            <li>Complete WordPress installation via <a href="<?php echo admin_url(); ?>" target="_blank">admin panel</a></li>
            <li>Activate plugins (WooCommerce, JWT Authentication, Headless Config)</li>
            <li>Configure WooCommerce settings</li>
            <li>Create your frontend application</li>
            <li>Test API endpoints</li>
        </ol>
    </div>
    <?php wp_footer(); ?>
</body>
</html>