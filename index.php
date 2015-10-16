<?php 
include('model.php');

ActiveRecord::setDb(new PDO('sqlite:blog.db'));
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
(new CRouter('index.inc'))
->error(302, function($path, $halt=false){
    header("Location: {$path}", true, 302);
    $halt && exit();
})
->error(405, function($message){
    header("Location: /posts", true, 302);
})
->delete('/uninstall', function($router){
    @unlink('blog.db');
    $router->error(302, '/install');
})
->get('/install', function($router){
    ActiveRecord::execute("CREATE TABLE IF NOT EXISTS user (id INTEGER PRIMARY KEY, name TEXT, email TEXT, password TEXT);");
    ActiveRecord::execute("CREATE TABLE IF NOT EXISTS post (id INTEGER PRIMARY KEY, user_id INTEGER, title TEXT,content TEXT, time INTEGER);");
    ActiveRecord::execute("CREATE TABLE IF NOT EXISTS tag (id INTEGER PRIMARY KEY, name TEXT, count INTEGER);");
    ActiveRecord::execute("CREATE TABLE IF NOT EXISTS post_tag (id INTEGER PRIMARY KEY, post_id INTEGER, tag_id INTEGER);");
    $user = new User();
    $user->name = 'admin';
    $user->email = 'admin@example.com';
    $user->password = md5('admin');
    $user->insert();
    //$router->error(302, '/posts');
    MicroTpl::render('web/list.html', array(), 'web/layout.html');
})
->get('/tags', function(){
    MicroTpl::render('web/list.html', array('tags'=>(new Tag())->orderby('count desc')->findAll()), 'web/layout.html');
})
->get('/user/:id/post', function($id){
    MicroTpl::render('web/list.html', array('posts'=>get_user($id)->posts), 'web/layout.html');
})
->get('/tag/:id/post', function($id){
    $tags = (new Post2Tag())->eq('tag_id', (int)$id)->findAll();
    MicroTpl::render('web/list.html', array('posts'=>array_map(function($t){ return $t->post; }, $tags)), 'web/layout.html');
})
->get('/posts', function(){
    MicroTpl::render('web/list.html', array('title'=>'Blog Name', 'posts'=>(new Post())->orderby('time desc')->findAll()), 'web/layout.html');
})
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
->get('/post/:id/view', function($id){
    $post = get_post($id);
    MicroTpl::render('web/post.html', array('title'=>$post->title, 'post'=>$post), 'web/layout.html');
})
->execute(array());


