<?php
/**
 *  地址控制器
 */
namespace App\Http\Controllers\Common;

use App\Http\Controllers\BaseController;
use App\Repositories\Common\AddressRepository;
use Illuminate\Http\Request;

class AddressController extends BaseController
{


    /**
     * 获取省份
     * @param Request $request
     * @param AddressRepository $addressRepository
     */
    public function getProvince(Request $request, AddressRepository $addressRepository)
    {
        $id   = $request->post("province_id");
        $list = $addressRepository->getProvince($id);
        $this->success($list);
    }


    /**
     * 获取城市
     * @param Request $request
     * @param AddressRepository $addressRepository
     */
    public function getCity(Request $request, AddressRepository $addressRepository)
    {
        $id  = $request->post("province_id");
        if(empty($id))
            $this->failed("省份信息不能为空");

        $list = $addressRepository->getCity($id);
        $this->success($list);
    }


    /**
     * 获取地区
     * @param Request $request
     * @param AddressRepository $addressRepository
     */
    public function getArea(Request $request, AddressRepository $addressRepository)
    {
        $id  = $request->post("city_id");
        if(empty($id))
            $this->failed("城市信息不能为空");


        $list = $addressRepository->getArea($id);
        $this->success($list);
    }


}
