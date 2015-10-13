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
    public function getTags(){
        return array_map(function($tag){
            return $tag->tag->name;
        }, $this->tags);
    }
    function updateTag($tags){
        $tags = array_map(function($t){ return trim($t); }, explode(',', $tags));
        $tags = array_filter($tags, function($t){ return strlen($t)>0; });
        foreach($this->tags as $i=>$tag){
            $key = array_search($tag->tag->name, $tags);
            if (false === $key){
                $tag->tag->count = $tag->tag->count - 1;
                if ($tag->tag->count > 0)
                    $tag->tag->update();
                else
                    $tag->tag->delete();
                $tag->delete();
            } else unset($tags[$key]);//do not change tag
        }
        foreach($tags as $i=>$t){
            $tag = new Tag();
            $post2tag = new Post2Tag();
            $tag->reset()->eq('name', $t)->find();
            if (!$tag->id){
                $tag->name = $t;
                $tag->count = 1;
                $tag->insert();
            }else{
                $tag->count = $tag->count + 1;
                $tag->update();
            }
            $post2tag->tag_id = $tag->id;
            $post2tag->post_id = $this->id;
            $post2tag->insert();
        }
        return $this;
    }
}

class Tag extends ActiveRecord{
    public $table = 'tag';
}

class Post2Tag extends ActiveRecord{
    public $table = 'post_tag';
    public $relations = array(
        'post' => array(self::BELONGS_TO, 'Post', 'post_id'),
        'tag' => array(self::BELONGS_TO, 'Tag', 'tag_id'),
    );
}

function get_post($id=null){
    return (new Post())->eq('id', (int)($id?$id:1))->find();
}
function get_user($id=null){
    return (new User())->eq('id', (int)($id?$id:1))->find();
}
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
    $router->error(302, '/posts');
})
->get('/tags', function(){
    MicroTpl::render('list.html', array('tags'=>(new Tag())->orderby('count desc')->findAll()));
})
->get('/user/:id/post', function($id){
    MicroTpl::render('list.html', array('posts'=>get_user($id)->posts));
})
->get('/tag/:id/post', function($id){
    $tags = (new Post2Tag())->eq('tag_id', (int)$id)->findAll();
    MicroTpl::render('list.html', array('posts'=>array_map(function($t){ return $t->post; }, $tags)));
})
->get('/posts', function(){
    MicroTpl::render('list.html', array('posts'=>(new Post())->orderby('time desc')->findAll()));
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
    MicroTpl::render('post.html', array('post'=>get_post($id)));
})
->execute(array());


