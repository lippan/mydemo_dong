<?php

/**
 * 上传控制器
 */
namespace App\Http\Controllers\App;
use App\Http\Controllers\BaseController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class UploadController extends  BaseController
{

    private $business       = "business";
    /**
     * 上传excel文档
     */
    public function excel(Request $request)
    {
        set_time_limit(0);
        //获取表单上传文件
        $file    = $request->file('file');

        if($file && $file->isValid())
        {
            require_once app_path().'/Libraries/phpexcel/PHPExcel.php';


            $extension      = $file->getClientOriginalExtension();
            if(!in_array($extension,["xlsx","xls","csv"]))
                $this->failed("文件类型不允许");

            $destinationPath = storage_path('/rundata/excel/');

            // 如果目标目录不存在，则创建之
            if (!file_exists($destinationPath))
                mkdir($destinationPath);

            $filename = "customer_excel_".time();
            // 保存文件到目标目录
            $file->move($destinationPath,$filename);

            $path_file_name = $destinationPath . DIRECTORY_SEPARATOR . $filename;

            $objReader =  $extension == "xlsx" ? \PHPExcel_IOFactory::createReader('Excel2007'):\PHPExcel_IOFactory::createReader('Excel5');

            $obj_PHPExcel =$objReader->load($path_file_name, $encode = 'utf-8');  //加载文件内容,编码utf-8

            $excel_array=$obj_PHPExcel->getsheet(0)->toArray();   //转换为数组格式
            array_shift($excel_array);  //删除第一个数组(标题);
            //array_shift($excel_array);  //删除第二个数组(标题);
            $data = [];
            $i=0;

            $time = date("Y-m-d H:i:s");
            foreach($excel_array as $k=>$val) {
                //id、标题、英文内容、中文内容、关键词、文件、排序

                $num = $k + 1;

                if(empty($val[0]))
                    $this->failed("第{$num}行1不能为空");

                if(empty($val[1]))
                    $this->failed("第{$num}行2不能为空");
                if(empty($val[2]))
                    $this->failed("第{$num}行3不能为空");

                if(empty($val[3]))
                    $this->failed("第{$num}行4不能为空");

                if(empty($val[5]))
                    $this->failed("第{$num}行6不能为空");
                if(empty($val[6]))
                    $this->failed("第{$num}行7不能为空");

                if(empty($val[7]))
                    $this->failed("第{$num}行8不能为空");



                if(strlen($val[5])>15)
                {

                    $arr = explode('/',$val[5]);

                    if(strpos($val[8],",") !== false)
                    {
                        $str = explode(',',$val[8]);
                    }

                    foreach($arr as $v)
                    {
                        $data[$k]['name']           = $val[1];
                        $data[$k]['city']           = $val[2];
                        $data[$k]['position']       = $val[3];
                        $data[$k]['telphone']       = $val[4];
                        $data[$k]['mobile']         = $v;
                        $data[$k]['source']         = $val[6];
                        $data[$k]['shop_area']      = $val[7];
                        if(isset($str))
                        {
                            $data[$k]['longitude']      = $str[0];
                            $data[$k]['latitude']       = $str[1];
                        }
                    }

                    $newdata['name']           = $val[1];
                    $newdata['city']           = $val[2];
                    $newdata['position']       = $val[3];
                    $newdata['telphone']       = $val[4];
                    $newdata['mobile']         = $arr[0];
                    $newdata['source']         = $val[6];
                    $newdata['shop_area']      = $val[7];
                    if(isset($str))
                    {
                        $newdata['longitude']      = $str[0];
                        $newdata['latitude']       = $str[1];
                    }

                    DB::table($this->business)->insertOrIgnore($newdata);
                }
                else
                {
                    $data[$k]['name']           = $val[1];
                    $data[$k]['city']           = $val[2];
                    $data[$k]['position']       = $val[3];
                    $data[$k]['telphone']       = $val[4];
                    $data[$k]['mobile']         = $val[5];
                    $data[$k]['source']         = $val[6];
                    $data[$k]['shop_area']      = $val[7];

                    if(strpos($val[8],",") !== false)
                    {
                        $str = explode(',',$val[8]);
                        $data[$k]['longitude']      = $str[0];
                        $data[$k]['latitude']      = $str[1];
                    }
                }

                $i++;
            }

            $success= DB::table($this->business)->insertOrIgnore($data); //批量插入数据

            $error  = $i - $success;
            $this->success("总{$i}条，成功{$success}条，失败{$error}条。");

        }else{
            // 上传失败获取错误信息
            $this->failed($file->getError());
        }
    }


}
