<?php 
/* extends ActiveRecord.php Base class*/
class BaseController extends Base{
    public function render($view, $params=array(), $layout='layout.html', $templatePath='web/', $return=false){
        if ($return) ob_start();
        MicroTpl::render($templatePath. $view, array_merge($params, array('controller'=>$this), $this->data), $layout?$templatePath. $layout:false);
        if ($return) return ob_get_clean();
    }
}
/* widgets */
class RecentPost extends BaseController{
    public function __toString(){
        return $this->render('recent_post.html', array('posts'=>(new Post)->orderby('time desc')->limit(0, 5)->findAll()), '', 'web/', true);
    }
}
class Tags extends BaseController{
    public function __toString(){
        return $this->render('tags.html', array('tags'=>(new Tag)->orderby('count desc')->findAll()), '', 'web/', true);
    }
}
class Categories extends BaseController{
    public function __toString(){
        return $this->render('categories.html', array('categories'=>(new Category)->orderby('count desc')->findAll()), '', 'web/', true);
    }
}
class RecentComment extends BaseController{
    public function __toString(){
        return $this->render('recent_comment.html', array('comments'=>(new Comment)->orderby('time desc')->limit(0, 5)->findAll()), '', 'web/', true);
    }
}
class Archives extends BaseController{
    public function __toString(){
        return '';
    }
}

class Controller extends BaseController {
    public function initSilder(){
        $this->tags = new Tags();
        $this->archives = new Archives();
        $this->categories = new Categories();
        $this->recentPost = new RecentPost();
        $this->recentComment = new RecentComment();
    }
}
class PostController extends Controller{
    public function listall($tagid=null, $categoryid=null, $userid){
        if ($categoryid){
            $category = (new Category())->eq('id', intval($categoryid))->find();
            $this->title = $category->name;
            $this->posts = $category->posts;
        }elseif ($userid){
            $user = (new user())->eq('id', intval($userid))->find();
            $this->title = $user->name;
            $this->posts = $user->posts;
        }elseif ($tagid){
            $tag = (new Tag())->eq('id', intval($tagid))->find();
            $this->title = $tag->tag->name;
            $tags = (new Post2Tag())->eq('tag_id', intval($tagid))->findAll();
            $this->posts = array_map(function($t){ return $t->post; }, $tags);
        }else{
            $this->title = 'Blog Name';
            $this->posts = (new Post())->orderby('time desc')->findAll();
        }
        $this->initSilder();
        $this->render('list.html');
    }
    public function view($id){
        $this->post = (new Post())->find(intval($id));
        $this->initSilder();
        $this->render('post.html');
    }
    public function create($router, $user_id, $category_id, $category, $title, $content, $tag){
        if ($user_id){
            if ($category){
                $cate = (new Category)->eq('name', $category)->find();
                if (!$cate->id) {
                    $cate->name = $category;
                    $cate->count = 0;
                    $cate->insert();
                }
                $category_id = $cate->id;
            }
            $post = new Post(array('user_id'=>(int)($user_id), 'category_id'=>intval($category_id), 'title'=>$title, 'content'=>$content, 'time'=>time()));
            $post->insert()->updateTag($tag)->updateCategory();
            $router->error(302, '/post/'. $post->id. '/view', true);
        }
        $this->initSilder();
        $this->cates = (new Category)->findAll();
        $this->user = (new User)->find(1);
        $this->render('post.html');
    }
    public function edit($id, $router, $user_id, $category_id, $category, $title, $content, $tag){
        $this->post = $post = (new Post())->find(intval($id));
        if ($user_id){
            if ($category){
                $cate = (new Category)->eq('name', $category)->find();
                if (!$cate->id) {
                    $cate->name = $category;
                    $cate->count = 0;
                    $cate->insert();
                }
                if ($category_id != $cate->id){
                    (new Category)->set('count=count-1')->update();
                    $category_id = $cate->id;
                    $post->updateCategory();
                }
            }
            $post->dirty(array('user_id'=>(int)($user_id), 'category_id'=>intval($category_id), 'title'=>$title, 'content'=>$content, 'time'=>time()));
            $post->update()->updateTag($tag);
            $router->error(302, '/post/'. $post->id. '/view', true);
        }
        $this->initSilder();
        $this->cates = (new Category)->findAll();
        $this->user = $this->post->author;
        $this->render('post.html');
    }
}