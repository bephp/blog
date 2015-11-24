<?php 
error_reporting(1);
include('model.php');
include('controller.php');

ActiveRecord::setDb(new PDO('sqlite:blog.db', null, null, array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION)));
MicroTpl::$debug = true;

/**
 * PRODUCTION
 * after compiled code to "index.inc", just need to include the source code and execute it with parameters.
 */
/*
$router = include('index.inc');
$router->execute();
 */

/**
 * DEV
 * using CRouter to compile plain array source code into "index.inc"
 */
(new CRouter('index.inc', true))
->hook('after', function(){
    echo ob_get_clean();
})
->hook('before', function($params){
    $key = md5(var_export(path(), true));
    if (($html = cache($key)) && $params['REQUEST_METHOD'] == 'GET') die($html);
    ob_start(function($str) use ($key) {echo cache($key, $str, 10);});
    return $params;
})
->error(302, function($path, $halt=false){
    header("Location: {$path}", true, 302);
    $halt && exit();
})
->error(405, function($message){
    header("Location: /posts", true, 302);
    die('405');
})
->delete('/uninstall', function($router){
    @unlink('blog.db');
    $router->error(302, '/install');
})
->get('/install', function($router){
    ActiveRecord::execute("CREATE TABLE IF NOT EXISTS user (id INTEGER PRIMARY KEY, name TEXT, email TEXT, password TEXT);");
    ActiveRecord::execute("CREATE TABLE IF NOT EXISTS category (id INTEGER PRIMARY KEY, name TEXT, count INTEGER);");
    ActiveRecord::execute("CREATE TABLE IF NOT EXISTS post (id INTEGER PRIMARY KEY, user_id INTEGER, category_id INTEGER, title TEXT,content TEXT, time INTEGER);");
    ActiveRecord::execute("CREATE TABLE IF NOT EXISTS comments (id INTEGER PRIMARY KEY, name TEXT, post_id INTEGER, comment_id INTEGER,content TEXT, time INTEGER);");
    ActiveRecord::execute("CREATE TABLE IF NOT EXISTS tag (id INTEGER PRIMARY KEY, name TEXT, count INTEGER);");
    ActiveRecord::execute("CREATE TABLE IF NOT EXISTS post_tag (id INTEGER PRIMARY KEY, post_id INTEGER, tag_id INTEGER);");
    $user = new User();
    $user->name = 'admin';
    $user->email = 'admin@example.com';
    $user->password = md5('admin');
    $user->insert();
    $category1 = (new Category(array('name'=>'Blog','count'=>0)))->insert();
    $category2 = (new Category(array('name'=>'PHP','count'=>0)))->insert();
    $post = (new Post(array('title'=>'REACT BASE FIDDLE (JSX)','content'=>'REACT BASE FIDDLE (JSX)', 'category_id'=>$category1->id, 'time'=>time())))->insert();
    $post->updateTag('PHP,Blog')->updateCategory();
    (new Comment(array('name'=>'admin', 'post_id'=>$post->id, 'comment_id'=>0, 'content'=>'Test Comment', 'time'=>time())))->insert();

    $router->error(302, '/posts', true);
})
->get('/user/:userid/post', array(new PostController, 'listall'))
->get('/tag/:tagid/post', array(new PostController, 'listall'))
->get('/category/:categoryid/post', array(new PostController, 'listall'))
->get('/', array(new PostController(), 'listall'))
->get('/posts', array(new PostController(), 'listall'))
->get('/post/create', array(new PostController(), 'create'), 'auth')
->post('/post/create', array(new PostController(), 'create'), 'auth')
->get('/post/:id/delete', array(new PostController(), 'delete'), 'auth')
->get('/post/:id/edit', array(new PostController, 'edit'), 'auth')
->post('/post/:id/edit', array(new PostController, 'edit'), 'auth')
->get('/post/:id/view', array(new PostController, 'view'))
->get('/:page', function($page){MicroTpl::render('web/'. $page. '.html', array('page'=>$page), 'web/layout.html');})
->execute(array());


