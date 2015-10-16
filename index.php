<?php 
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
->error(302, function($path, $halt=false){
    header("Location: {$path}", true, 302);
    $halt && exit();
})
->error(405, function($message){
    header("Location: /posts", true, 302);
    die('aaa');
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
->get('/tags', function(){
    MicroTpl::render('web/list.html', array('tags'=>(new Tag())->orderby('count desc')->findAll()), 'web/layout.html');
})
->get('/user/:id/post', function($id){
    MicroTpl::render('web/list.html', array('posts'=>get_user($id)->posts), 'web/layout.html');
})
->get('/tag/:tagid/post', array(new PostController, 'listall'))
->get('/category/:categoryid/post', array(new PostController, 'listall'))
->get('/posts', array(new PostController(), 'listall'))
->get('/post/create', function(){
    MicroTpl::render('post.html', array('user'=>get_user()));
})
->post('/post/create', function($router, $user_id, $title, $content, $tag){
    // another way to init model.
    $post = new Post(array('user_id'=>(int)($user_id), 'title'=>$title, 'content'=>$content, 'time'=>time()));
    $post->insert();
    $router->error(302, '/post/'. $post->updateTag($tag)->id. '/view');
})
->get('/post/:id/delete', function($id, $router){
    $post = get_post($id);
    $post->updateTag('');
    $post->delete();
    $router->error(302, '/posts');
})
->get('/post/:id/edit', function($id){
    $post = get_post($id);
    MicroTpl::render('post.html', array('user'=>$post->author, 'post'=>$post));
})
->post('/post/:id/edit', function($id, $router, $title, $content, $tag){
    $post = get_post($id);
    $post->title = $title;
    $post->content = $content;
    $post->update();
    $post->updateTag($tag);
    $router->error(302, '/post/'. $post->id. '/view');
})
->get('/post/:id/view', array(new PostController, 'view'))
->execute(array());


