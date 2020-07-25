<?php
/*
 * mongodb记录日志文件
 */

namespace  App\Helpers;

use Illuminate\Support\Facades\DB;

class MongoLog{

    public function __construct($collection='mongoLog'){

        $this->mongodb = DB::connection('mongodb')->collection($collection);

    }

    public function add($log){

        if(!is_array($log))
            return false;

        $this->mongodb->insert($log);

    }


}