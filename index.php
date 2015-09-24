<?php 
require_once('vendor/autoload.php');

ActiveRecord::setDb(new PDO('sqlite:blog.db'));
MicroTpl::$debug = true;

class User extends ActiveRecord{
    public $table = 'user';
    public $relations = array(
        'posts' => array(self::HAS_MANY, 'Post', 'user_id'),
    );
}

class Post extends ActiveRecord{
    public $table = 'post';
    public $relations = array(
        'tags' => array(self::HAS_MANY, 'Post2Tag', 'post_id'),
        'author' => array(self::BELONGS_TO, 'User', 'user_id'),
    );
}

class Tag extends ActiveRecord{
    public $table = 'tag';
}

class Post2Tag extends ActiveRecord{
    public $table = 'post_tag';
    public $relations = array(
        'post' => array(self::HAS_ONE, 'Post', 'post_id'),
        'tag' => array(self::HAS_ONE, 'Tag', 'tag_id'),
    );
}

map('GET', '/install', function(){
    ActiveRecord::execute("CREATE TABLE IF NOT EXISTS user (id INTEGER PRIMARY KEY, name TEXT, email TEXT, password TEXT);");
    ActiveRecord::execute("CREATE TABLE IF NOT EXISTS post (id INTEGER PRIMARY KEY, user_id INTEGER, title TEXT,content TEXT, time INTEGER);");
    ActiveRecord::execute("CREATE TABLE IF NOT EXISTS tag (id INTEGER PRIMARY KEY, name TEXT);");
    ActiveRecord::execute("CREATE TABLE IF NOT EXISTS post_tag (id INTEGER PRIMARY KEY, post_id INTEGER, tag_id INTEGER);");
    echo 'Success to create tables.<br />';
    $user = new User();
    $user->name = 'admin';
    $user->email = 'admin@example.com';
    $user->password = md5('admin');
    $user->insert();
    echo 'Success to create user.';
});
function get_post($id=null){
    $post = new Post();
    return $post->eq('id', (int)($id?$id:1))->find();
}
function get_user($id=null){
    $user = new User();
    return $user->eq('id', (int)($id?$id:1))->find();
}
map('GET', '/posts', function(){
    $post = new Post();
    MicroTpl::render('list.html', array('posts'=>$post->orderby('time desc')->findAll()));
});
map('GET', '/post/create', function(){
    MicroTpl::render('post.html', array('user'=>get_user()));
});
map('POST', '/post/create', function(){
    $uid = (int)($_POST['user_id']);
    $post = new Post();
    $post->user_id = $uid;
    $post->title = $_POST['title'];
    $post->content = $_POST['content'];
    $post->time = time();
    $post->insert();
    redirect('/post/'. $post->id);
});
map('GET', '/post/<id:\d+>', function($p){
    $post = get_post($p['id']);
    MicroTpl::render('post.html', array('post'=>$post));
});
map('GET', '/post/<id:\d+>/delete', function($p){
    $post = get_post($p['id']);
    $post->delete();
    redirect('/posts');
});
map('GET', '/post/<id:\d+>/edit', function($p){
    $post = get_post($p['id']);
    MicroTpl::render('post.html', array('user'=>$post->author, 'post'=>$post));
});
map('POST', '/post/<id:\d+>/edit', function($p){
    $post = get_post($p['id']);
    $post->title = $_POST['title'];
    $post->content = $_POST['content'];
    $post->update();
    redirect('/post/'. $post->id);
});
dispatch();


