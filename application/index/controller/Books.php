<?php

namespace app\index\controller;

use app\model\Book;
use think\Db;
use think\facade\Cache;
use think\Request;

class Books extends Base
{
    protected $bookService;

    public function initialize()
    {
        cookie('nav_switch', 'booklist'); //设置导航菜单active
        $this->bookService = new \app\service\BookService();
    }

    public function index(Request $request)
    {
        $id = $request->param('id');
        $book = cache('book' . $id);
        $tags = cache('book' . $id . 'tags');
        if ($book ==false) {
            $book = Book::with('chapters,author')->find($id);
            $tags = explode('|', $book->tags);
            cache('book' . $id, $book);
            cache('book' . $id . 'tags', $tags);
        }

        $redis = new \Redis();
        $redis->connect(config('site.redis_host'), config('site.redis_port'));
        if (!empty(config('site.redis_auth'))){
            $redis->auth(config('site.redis_auth'));
        }
        $redis->zIncrBy('hot_books',1,json_encode([
            'id' => $book->id,
            'book_name' => $book->book_name,
            'cover_url' => $book->cover_url,
            'update_time' => $book->update_time,
            'chapter_count' => count($book->chapters),
            'summary' => $book->summary
        ]));
        $recommand = $this->bookService->getRandBooks();
        $start = cache('book_start' . $id);
        if ($start == false) {
            $db = Db::query('SELECT id FROM '.$this->prefix.'chapter WHERE book_id = ' . $request->param('id') . ' ORDER BY id LIMIT 1');
            $start = $db ? $db[0]['id'] : -1;
            cache('book_start' . $id, $start);
        }

        $this->assign([
            'book' => $book,
            'tags' => $tags,
            'recommand' => $recommand,
            'start' => $start,
        ]);
        if (!isMobile()){
            $updates = $this->bookService->getBooks('update_time',[],10);
            $this->assign('updates',$updates);
        }
        return view($this->tpl);

    }

    public function booklist(Request $request)
    {
        $tag = $request->param('tag');
        if (is_null($tag)){
            $tag = '全部';
            $books = $this->bookService->getPagedBooks('update_time',[],28);
        }else{
            $map[] = ['tags', 'like', '%' . $tag . '%'];
            $books = $this->bookService->getPagedBooks('update_time', $map, 28);
        }

        $this->assign([
            'books' => $books,
            'tag' => $tag
        ]);
        if (!isMobile()){
            $tags = \app\model\Tags::all();
            $this->assign([
                'tags' => $tags,
                'param' => $tag
            ]);
        }
        return view($this->tpl);
    }
}
