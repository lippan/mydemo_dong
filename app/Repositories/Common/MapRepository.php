<?php

/**
 *  地图资源
 */
namespace App\Repositories\Common;
use App\Libraries\GdMap\GdMap;

class MapRepository
{
    use \App\Helpers\ApiResponse;

    /**
     * 获取周边
     */
    public function getAround($params,$type="gd")
    {
        $location   = $params["location"];
        $keywords   = isset($params["keywords"]) ? $params["keywords"]:null;
        $types      = isset($params["types"]) ? $params["types"]:null;
        $city       = isset($params["city"]) ? $params["city"]:null;
        $radius     = isset($params["radius"]) ? $params["radius"]:1000;
        $sortrule   = isset($params["sortrule"]) ? $params["sortrule"]:null;
        $offset     = isset($params["offset"]) ? $params["offset"]:null;
        $page       = isset($params["page"]) ? $params["page"]:null;

        $result = GdMap::getInstance()->getAround($location,$keywords,$types,$city,$radius,$sortrule,$offset,$page);

        return $result;
    }


}