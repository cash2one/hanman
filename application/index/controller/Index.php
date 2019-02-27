<?php
namespace app\index\controller;

use app\model\Banner;
use app\model\Author;
use app\model\Book;

class Index extends Base
{
    protected $bookService;
    protected function initialize()
    {
        $this->bookService = new \app\service\BookService();
    }

    public function index()
    {
        $banners = cache('banners_homepage');
        if (!$banners){
            $banners = Banner::limit(5)->order('id','desc')->select();
            cache('banners_homepage',$banners,null,'redis');
        }
        $redis = new_redis();
        $hots = $redis->zRevRange('hot_books',0,5,true);
        $hot_books = array();
        foreach ($hots as $k => $v){
            $hot_books[] = json_decode($k,true);
        }
        $newest = cache('newest_homepage_pc');
        if (!$newest){
            $newest = $this->bookService->getBooks('create_time','1=1',10);
            cache('newest_homepage_pc',$newest,null,'redis');
        }

        $ends = cache('ends_homepage_pc');
        if (!$ends){
            $ends = $this->bookService->getBooks('update_time',[['end','=','1']],10);
            cache('ends',$ends,null,'redis');
        }
        $rands = $this->bookService->getRandBooks();
        $this->assign([
            'banners' => $banners,
            'banners_count' => count($banners),
            'newest' => $newest,
            'hot' => $hot_books,
            'ends' => $ends,
            'rands' => $rands
        ]);
        if (!$this->request->isMobile()){
            $tags = \app\model\Tags::all();
            $this->assign('tags',$tags);
        }

        return view($this->tpl);
    }

    public function search(){
        $keyword = input('keyword');
        $books = cache('searchresult'.$keyword);
        if (!$books){
            $books = $this->bookService->search($keyword);
            cache('searchresult'.$keyword,$books,null,'redis');
        }
        foreach ($books as &$book){
            $author = Author::get($book['author_id']);
            $book['author'] = $author;
        }
        $this->assign([
            'books' => $books,
            'header_title' =>'搜索：'. $keyword,
            'count' => count($books),
        ]);
        return view($this->tpl);
    }

    public function bookshelf(){
        return view($this->tpl);
    }
}

