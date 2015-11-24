<?php 

class BlogTest extends \PHPUnit_Framework_TestCase{
    // test in build-in server
    // see the server side code in "index.php" 
    public function getRequest(){
        return new \Simplon\Request\Request();
    }
    /**
     * @requires PHP 5.4
     */
    public function testInstall(){
        @unlink('blog.db');
        $response = $this->getRequest()->get('http://127.0.0.1:8889/install');
        $this->assertEquals(302, $response->getHttpCode());
        $this->assertEmpty($response->getContent());
        $this->assertEquals('/posts', $response->getHeader()->getLocation());
    }
    /**
     * @requires PHP 5.4
     * @depends testInstall
     */
    public function testPostCreate(){
        $response = $this->getRequest()->post('http://127.0.0.1:8889/post/create', array('title'=>'test title', 'content' => 'test content', 'tag' => 'test,title,content', 'user_id'=>1, 'category_id'=>1));
        $this->assertEquals(302, $response->getHttpCode());
        $this->assertEmpty($response->getContent());
        $this->assertRegExp('@/post/([0-9]+)/view@', $response->getHeader()->getLocation());
        $this->assertEquals(1, preg_match('@/post/([0-9]+)/view@', $response->getHeader()->getLocation(), $match));
        return $match[1];
    }
    /**
     * @requires PHP 5.4
     * @depends testPostCreate
     */
    public function testPostEdit($postId){
        $response = $this->getRequest()->post('http://127.0.0.1:8889/post/'. $postId. '/edit', array('id' => $postId, 'title'=>'title', 'content' => 'content', 'tag' => 'test,title,content,edit', 'user_id'=>1, 'category_id'=>1));
        $this->assertEquals(302, $response->getHttpCode());
        $this->assertEmpty($response->getContent());
        $this->assertRegExp('@/post/([0-9]+)/view@', $response->getHeader()->getLocation());
        $this->assertEquals(1, preg_match('@/post/([0-9]+)/view@', $response->getHeader()->getLocation(), $match));
        return $match[1];
    }
    /**
     * @requires PHP 5.4
     * @depends testPostEdit
     */
    public function testPostView($postId){
        $response = $this->getRequest()->get('http://127.0.0.1:8889/post/'. $postId. '/view');
        $this->assertEquals(200, $response->getHttpCode());
        //$this->assertRegExp('@<h1>title</h1>@', $response->getContent());
        $this->assertRegExp('@<p id="post-content">content</p>@', $response->getContent());
        $this->assertEquals(6, preg_match_all('@/tag/([0-9]+)/post@', $response->getContent(), $match));
        return $match[1];
    }
    /**
     * @requires PHP 5.4
     * @depends testPostView
     */
    public function testPostBelongsTag($tagIds){
        foreach($tagIds as $tagId){
            $response = $this->getRequest()->get('http://127.0.0.1:8889/tag/'. $tagId. '/post');
            $this->assertEquals(200, $response->getHttpCode());
            $this->assertRegExp('@title</a>@', $response->getContent());
            $this->assertRegExp('@<p class="post-summary">(.*)</p>@', $response->getContent());
        }
    }
    /**
     * @requires PHP 5.4
     * @depends testPostCreate
     */
    public function testPosts(){
        $response = $this->getRequest()->get('http://127.0.0.1:8889/posts/');
        $this->assertEquals(200, $response->getHttpCode());
        $this->assertRegExp('@title</a>@', $response->getContent());
        $this->assertRegExp('@<p class="post-summary">(.*)</p>@', $response->getContent());
    }
    /**
     * @requires PHP 5.4
     * @depends testPostEdit
     */
    public function testPostDelete($postId){
        $response = $this->getRequest()->get('http://127.0.0.1:8889/post/'. $postId. '/delete');
        $this->assertEquals(302, $response->getHttpCode());
        $this->assertEmpty($response->getContent());
        $this->assertEquals('/posts', $response->getHeader()->getLocation());
    }
}
