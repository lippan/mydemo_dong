<?php

namespace App\Repositories;


trait BaseRepository
{

    protected $model;


    public function setThisModel($model)
    {
         $this->model=$model;
    }


    public function getThisModel()
    {
        return $this->model;
    }


    /**
     * Store a new record.
     *
     * @param  $input
     * @return User
     */
    public function store($input)
    {
        return $this->save($this->model, $input);
    }

    /**
     * Save the input's data.
     *
     * @param  $model
     * @param  $input
     * @return mixed
     */
    public function save($model, $input)
    {
        $model->fill($input);

        $model->save();

        return $model;
    }

    /**
     * Get one record without draft scope
     *
     * @param $id
     * @return mixed
     */
    public function getById($id)
    {
        return $this->model->find($id);
    }

    public function getByKey($id)
    {
        return $this->model->first($id);
    }

    public function getLastDate($primkey){

        return $this->model->orderBy($primkey, 'desc')->first();
    }

    /**
     * Delete the draft article.
     *
     * @param int $id
     * @return boolean
     */
    public function destroy($id)
    {
        return $this->getById($id)->delete();
    }

    /**
     * @param $field
     * @return mixed
     */
    public function getAllData($field = "*", $needToArray = true)
    {
        if ($needToArray) {
            return $this->model->select($field)->get()->toArray();
        } else {
            return $this->model->select($field)->get();
        }
    }


    public function getAllDataByWhere($where,$needToArray = true,$field = "*")
    {
        if ($needToArray) {
            return $this->model->where($where)->select($field)->get()->toArray();
        } else {
            return $this->model->where($where)->select($field)->get();
        }
    }


    /**
     * To judge the record is existence in you table
     *
     * @param $where
     */
    public function getFirstRecordByWhere($where)
    {
        return $this->model->where($where)->first();
    }

    /**
     * @param $id
     * @param $input
     * @return mixed
     */
    public function update($id, $input)
    {
        $this->model = $this->getById($id);

        return $this->save($this->model, $input);
    }

    /**
     * return  paginate list
     *
     * @param int $pagesize
     * @param string $sort
     * @param string $sortColumn
     * @return mixed
     */
    public function page($where = false, $pagesize = 20, $sortColumn = false, $sort = 'asc')
    {
        if ($where) {

            if(!empty($sortColumn)){
                return $this->model->where($where)->orderBy($sortColumn, $sort)->paginate($pagesize)->toArray();
            }else{
                return $this->model->where($where)->paginate($pagesize)->toArray();
            }


        } else {

            if(!empty($sortColumn)){
                return $this->model->orderBy($sortColumn, $sort)->paginate($pagesize)->toArray();
            }else{
                return $this->model->paginate($pagesize)->toArray();
            }


        }
    }


    public function page_api($where = false,$nums=10, $page = 1, $sortColumn = false, $sort = 'asc',$field = "*",$needToArray = false)
    {


        $ret=$this->model;

        $where&&$ret=$ret->where($where);

        !empty($sortColumn)&&$ret=$ret->orderBy($sortColumn, $sort);

        $ret=$ret->select($field)->offset(($page-1)*$nums)->limit($nums)->get();


       if ($needToArray)
            return $ret->toArray();

        return $ret;


    }


    /**
     * @param bool $where
     * @return mixed
     */

    public function count($where=false){

        if($where){
            return $this->model->where($where)->count();

        }else{
            return $this->model->count();
        }

    }

    /**
     * Get all the records
     *
     * @return array User
     */
    public function all()
    {
        return $this->model->get();
    }
}