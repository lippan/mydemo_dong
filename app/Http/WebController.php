<?php

/**
 * 管理后台基础控制器
 */
namespace App\Http\Controllers;

use Curl\Curl;
use Exception;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;


class WebController  extends BaseController
{

    public function __construct(Request $request)
    {
        parent::__construct($request);
    }


}
