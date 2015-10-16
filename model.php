<?php 
require_once('vendor/autoload.php');

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
        'comments' => array(self::HAS_MANY, 'Comment', 'post_id'),
        'author' => array(self::BELONGS_TO, 'User', 'user_id'),
    );
    public function url(){ return '/post/'. $this->id . '/view'; }
    public function showTime(){ return date('M, d Y', $this->time); }
    public function commentCount(){ return '1 comments';}
    public function summary(){ return strlen($this->content) > 300?substr($this->content, 0, 300). '...': $this->content;}
    public function getTags(){
        return array_map(function($tag){
            return $tag->tag->name;
        }, $this->tags);
    }
    function updateCategory(){
        $category = (new Category)->eq('id', $this->category_id)->find();
        $category->count = $category->count + 1;
        $category->update();
        return $this;
        //(new Category)->set('count', 'count+1')->eq('id', $this->id)->update();
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

class Comment extends ActiveRecord{
    public $table = 'comments';
    public $relations = array(
        'post' => array(self::BELONGS_TO, 'Post', 'post_id'),
    );
    public function url(){ return '/post/'. $this->post_id. '/view#comment-'. $this->id; }
    public function posturl(){ return '/post/'. $this->post_id. '/view'; }
    public function sumarry(){ return $this->content; }
}
class Category extends ActiveRecord{
    public $table = 'category';
    public $relations = array(
        'posts' => array(self::HAS_MANY, 'Post', 'category_id'),
    );
    public function url(){ return '/category/'. $this->id. '/post'; }
}
class Tag extends ActiveRecord{
    public $table = 'tag';
    public $relations = array(
        'tags' => array(self::HAS_MANY, 'Post2Tag', 'tag_id'),
    );
    public function url(){ return '/tag/'. $this->id. '/post'; }
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

