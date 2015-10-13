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
    public function testUnInstall(){
        $response = $this->getRequest()->delete('http://127.0.0.1:8889/uninstall');
        $this->assertEquals(302, $response->getHttpCode());
        $this->assertEmpty($response->getContent());
        $this->assertEquals('/install', $response->getHeader()->getLocation());
    }
    /**
     * @requires PHP 5.4
     * @depends testUnInstall
     */
    public function testInstall(){
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
        $response = $this->getRequest()->post('http://127.0.0.1:8889/post/create', array('title'=>'test title', 'content' => 'test content', 'tag' => 'test,title,content'));
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
        $response = $this->getRequest()->post('http://127.0.0.1:8889/post/'. $postId. '/edit', array('id' => $postId, 'title'=>'title', 'content' => 'content', 'tag' => 'test,title,content,edit'));
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
        $this->assertRegExp('@<h1>title</h1>@', $response->getContent());
        $this->assertRegExp('@<pre>content</pre>@', $response->getContent());
        $this->assertEquals(4, preg_match_all('@/tag/([0-9]+)/post@', $response->getContent(), $match));
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
            $this->assertRegExp('@<h2>title</h2>@', $response->getContent());
            $this->assertRegExp('@<pre>content</pre>@', $response->getContent());
        }
    }
    /**
     * @requires PHP 5.4
     * @depends testPostCreate
     */
    public function testPosts(){
        $response = $this->getRequest()->get('http://127.0.0.1:8889/posts/');
        $this->assertEquals(200, $response->getHttpCode());
        $this->assertRegExp('@<h2>title</h2>@', $response->getContent());
        $this->assertRegExp('@<pre>content</pre>@', $response->getContent());
    }
    /**
     * @requires PHP 5.4
     * @depends testPostCreate
     */
    public function testTags(){
        $response = $this->getRequest()->get('http://127.0.0.1:8889/tags');
        $this->assertEquals(200, $response->getHttpCode());
        $this->assertEquals(4, preg_match_all('@/tag/([0-9]+)/post@', $response->getContent(), $match));
    }
    /**
     * @requires PHP 5.4
     * @depends testPostEdit
     */
    public function testPostDelete($postId){
        $response = $this->getRequest()->get('http://127.0.0.1:8889/post/'. $postId. '/delete');
        $this->assertEquals(302, $response->getHttpCode());
        $this->assertEquals(302, $response->getHttpCode());
        $this->assertEmpty($response->getContent());
        $this->assertEquals('/posts', $response->getHeader()->getLocation());
    }
}
