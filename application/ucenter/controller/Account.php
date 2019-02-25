<?php
/**
 * Created by PhpStorm.
 * User: hiliq
 * Date: 2019/2/25
 * Time: 15:55
 */

namespace app\ucenter\controller;


use think\Controller;

class Account extends Controller
{
    public function register(){
        $site_name = config('site.site_name');
        $this->assign('site_name',$site_name);
        return view();
    }
}