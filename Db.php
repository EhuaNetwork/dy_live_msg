<?php

namespace Db;
require_once "config.php";
/**
 *  单例模式
 **/
class Db
{
    //保存全局实例
    private static $instance;
    //数据库连接句柄
    private $_db;
    public $_configs = [
        // 数据库连接地址
        'hostname' => "127.0.0.1",
        // 数据库名称
        'dbname'   => "",
        // 数据库账户
        'username' => "",
        // 数据库密码
        'password' => "",
        // 数据库表前缀
        'prefix'     => "",
        'resultset_type' => false,
    ];//数据库配置
    public $_getlatsql = [];//最后一次sql

    /*需要每次使用表名都重置的*/
    private $_table = null;//表名
    private $_join = null;//where条件
    private $_where = null;//where条件
    private $_order = null;//order排序
    private $_limit = null;//limit限定查询
    private $_group = null;//group分组
    private $_alias = null;//alias定义别名
    private $_field = "*";//field定义字段
    private $_fetchSql=false;
    private $_failException=false;
    //数据库连接参数

    //私有化构造函数，防止外界实例化对象
    private function __construct()
    {

        $this->_configs = get_config()['database'];

        $link = $this->_db;
        //$configs=include_once($databasepath.'database.php');
        // var_dump($configs);
        // echo "<br>";
        $this->_configs=empty($configs)?$this->_configs:$configs;
        //  var_dump($this->_configs);die;
        if(!$link){
            $db = mysqli_connect($this->_configs['hostname'],$this->_configs['username'],$this->_configs['password'],$this->_configs['dbname']);
            mysqli_query($db,"set names utf8");
            if(!$db){
                $this->ShowException("错误信息".mysqli_connect_error());
            }
            $this->_db = $db;
        }
    }
    //私有化克隆函数，防止外界克隆对象
    private function __clone()
    {
    }



    /**
     * 获取所有数据
     *
     * @param      <type>   $table  The table
     *
     * @return     boolean  All.
     */
    public function getAll($table=null){
        $link = $this->_db;
        if(!$link)return false;
        $sql = "SELECT * FROM {$table}";
        $array=$this->execute($sql);
        if(is_string($array)||$this->_failException||$this->_configs['resultset_type']){
            return $array;
        }
        $data = mysqli_fetch_all($array,MYSQLI_ASSOC);
        return $data;
    }
    /*

    */
    public function reset(){


        $this->_failException=false;
        $this->_fetchSql=false;
        $this->_join = null;//where条件
        $this->_where = null;//where条件
        $this->_order = null;//order排序
        $this->_limit = null;//limit限定查询
        $this->_group = null;//group分组
        $this->_alias = null;//alias定义别名
        $this->_field = "*";//field定义字段
    }
    /*启动事务*/
    public static  function startTrans($table){
        return  mysqli_query($this->_db, "SET AUTOCOMMIT=0"); // 设置为不自动提交，因为MYSQL默认立即执行

    }
    /*回滚事务*/
    public static  function rollback($table){

        return  mysqli_query($this->_db, "ROLLBACK");     // 判断当执行失败时回滚

    }
    /*提交事务*/
    public static  function commit($table){
        return mysqli_commit($this->_db);            //执行事务
    }
    public static  function table($table){

        if(!(self::$instance instanceof self))
        {
            self::$instance = new self();
        }

        self::$instance->_table("$table");
        return self::$instance;

    }
    public  function _table($table){


        $this->reset();
        $this->_table = $table;
        return $this;
    }
    public static  function name($table){

        if(!(self::$instance instanceof self))
        {
            self::$instance = new self();
        }
        self::$instance->_name("$table");
        return self::$instance;

    }
    public  function _name($table){

        $this->reset();
        $this->_table = $this->_configs['prefix'].$table;
        return $this;
    }


    public function alias($as){
        $this->_alias = ' as '. $as;
        return $this;
    }
    /**
     * where条件
     *
     * @param      string  $where  The where
     *
     * @return     <type>  ( description_of_the_return_value )
     */
    public function field($field='*'){
        $fieldStr = '';
        $link = $this->_db;
        if(!$link)return $link;
        if(is_array($field)){
            foreach ($field as $key => $value) {
                $fieldStr .=  "'".$value."'";

            }
            $fieldStr =  $fieldStr;
        }elseif(is_string($field)&&!empty($field)){

            $fieldStr = $field;
        }

        $this->_field = $fieldStr;
        return $this;
    }

    /**
     * 实现查询操作
     *
     * @param      string   $fields  The fields
     *
     * @return     boolean  ( description_of_the_return_value )
     */
    public function select($fields=""){
        $fieldsStr = '';
        $link = $this->_db;
        if(!$link)return false;
        if(is_array($fields)){
            $fieldsStr = implode(',', $fields);
        }elseif(is_string($fields)&&!empty($fields)){
            $fieldsStr = $fields;
        }
        if(empty($fields)){
            $fields= $this->_field;
        }
        $sql = "SELECT {$fields} FROM {$this->_table} {$this->_alias} {$this->_join} {$this->_where} {$this->_order} {$this->_limit}";
// 	echo($sql."<br/>");
        $array=$this->execute($sql);
        if(is_string($array)||$this->_failException||$this->_configs['resultset_type']){
            return $array;
        }
        $data = mysqli_fetch_all($array,MYSQLI_ASSOC);

        return $data;
    }
    /**.$this->_alias
     * 实现查询操作
     *
     * @param      string   $fields  The fields
     *
     * @return     boolean  ( description_of_the_return_value )
     */
    public function count($fields="*"){
        //  return  $this->aggregation($fields,"count");
        $fieldsStr = '';
        $link = $this->_db;
        if(!$link)return false;
        if(is_array($fields)){
            $fieldsStr = implode(',', $fields);
        }elseif(is_string($fields)&&!empty($fields)){
            $fieldsStr = $fields;
        }
        $sql = "SELECT count({$fields}) FROM {$this->_table} {$this->_alias} {$this->_join} {$this->_where} {$this->_order} {$this->_limit}";
// 	echo($this->_where."<br/>");
// 	echo($sql."<br/>");
        $array=$this->execute($sql);
        if(is_string($array)||$this->_failException||$this->_configs['resultset_type']){
            return $array;
        }
        $data = mysqli_fetch_all($array,MYSQLI_ASSOC);
// 		var_dump($data);die;
        return $data[0]["count({$fields})"];
    }

    public function aggregation($fields,$type){
        $fieldsArr = [];
        $fieldsArr2=[];
        $link = $this->_db;
        if(!$link)return false;
        if(is_array($fields)){
            $fieldsArr =  $fields ;
        }elseif(is_string($fields)&&!empty($fields)){
            $fieldsArr = explode(',', $fields);
        }
        foreach($fieldsArr as $key=>$val){
            $fieldsArr2[]=$type."({$val})";
        }
        $fieldsStr=implode(',', $fieldsArr2);
        $sql = "SELECT $fieldsStr   FROM {$this->_table} {$this->_alias} {$this->_join} {$this->_where} {$this->_order} {$this->_limit}";
        $array=$this->execute($sql);
        if(is_string($array)||$this->_failException||$this->_configs['resultset_type']){
            return $array;
        }
        $data = mysqli_fetch_all($array,MYSQLI_ASSOC);
        return $data[0];
    }
    public function max($fields){
        return  $this->aggregation($fields,"max");
    }
    public function min($fields){
        return  $this->aggregation($fields,"min");
    }
    public function sum($fields){
        return  $this->aggregation($fields,"sum");
    }
    public function avg($fields){
        return  $this->aggregation($fields,"avg");
    }

    //
    /**
     * 实现查询操作
     *
     * @param      string   $fields  The fields
     *
     * @return     boolean  ( description_of_the_return_value )
     */
    public function find($fields=""){
        $fieldsStr = '';
        $link = $this->_db;
        if(!$link)return false;
        if(is_array($fields)){
            $fieldsStr = implode(',', $fields);
        }elseif(is_string($fields)&&!empty($fields)){
            $fieldsStr = $fields;
        }
        if(empty($fields)){
            $fields= $this->_field;
        }
        $sql = "SELECT {$fields} FROM {$this->_table} {$this->_alias} {$this->_join} {$this->_where} {$this->_order} {$this->_limit}";
// 	echo($sql."<br/>");
        $array=$this->execute($sql);
        if(is_string($array)||$this->_failException||$this->_configs['resultset_type']){
            return $array;
        }
        $data = mysqli_fetch_all($array,MYSQLI_ASSOC);

        return $data[0];
    }
    /**
     * order排序
     *
     * @param      string   $order  The order
     *
     * @return     boolean  ( description_of_the_return_value )
     */
    public function order($order=''){
        $orderStr = '';
        $link = $this->_db;
        if(!$link)return false;
        if(is_string($order)&&!empty($order)){
            $orderStr = "ORDER BY ".$order;
        }
        $this->_order = $orderStr;
        return $this;
    }
    /**
     * JOIN
     *
     * @param      string  $where  The where
     *
     * @return     <type>  ( description_of_the_return_value )
     */
    public function JOIN($JOIN=''){
        $JOINStr = '';
        $link = $this->_db;
        if(!$link)return $link;
        if(is_string($JOIN)&&!empty($JOIN)){
            $JOINStr = " join ".$JOIN;
        }
        $this->_join = $JOINStr;
        return $this;
    }
    /**
     * LEFTJOIN
     *
     * @param      string  $where  The where
     *
     * @return     <type>  ( description_of_the_return_value )
     */
    public function LEFTJOIN($JOIN=''){
        $JOINStr = '';
        $link = $this->_db;
        if(!$link)return $link;
        if(is_string($JOIN)&&!empty($JOIN)){
            $JOINStr = " LEFT JOIN ".$JOIN;
        }
        $this->_join = $JOINStr;
        return $this;
    }
    /**
     * LEFTJOIN
     *
     * @param      string  $where  The where
     *
     * @return     <type>  ( description_of_the_return_value )
     */
    public function RIGHTJOIN($JOIN=''){
        $JOINStr = '';
        $link = $this->_db;
        if(!$link)return $link;
        if(is_string($JOIN)&&!empty($JOIN)){
            $JOINStr = " RIGHT JOIN ".$JOIN;
        }
        $this->_join = $JOINStr;
        return $this;
    }
    /**
     * where条件
     *
     * @param      string  $where  The where
     *
     * @return     <type>  ( description_of_the_return_value )
     */
    public function where($where=''){
        $whereStr = '';
        $link = $this->_db;
        if(!$link)return $link;
        if(is_array($where)){
            foreach ($where as $key => $value) {
                if(is_array($value)){
                    if($value == end($where)){
                        $whereStr .= "`".$key."` ".$value[0]." '".$value[1]."'";
                    }else{
                        $whereStr .= "`".$key."` ".$value[0]." '".$value[1].""."' AND ";
                    }
                }else if($value == end($where)){
                    $whereStr .= "`".$key."` = '".$value."'";
                }else{
                    $whereStr .= "`".$key."` = '".$value."' AND ";
                }
            }
            $whereStr = "WHERE ".$whereStr;
        }elseif(is_string($where)&&!empty($where)){
            $whereStr = "WHERE ".$where;
        }
        $this->_where = $whereStr;
        return $this;
    }


    /**
     * group分组
     *
     * @param      string   $group  The group
     *
     * @return     boolean  ( description_of_the_return_value )
     */
    public function group($group=''){
        $groupStr = '';
        $link = $this->_db;
        if(!$link)return false;
        if(is_array($group)){
            $groupStr = "GROUP BY ".implode(',',$group);
        }elseif(is_string($group)&&!empty($group)){
            $groupStr = "GROUP BY ".$group;
        }
        $this->_group = $groupStr;
        return $this;
    }
    /**
     *page 分页计算，限定查询
     *
     * @param      string  $limit  The limit
     *
     * @return     <type>  ( description_of_the_return_value )
     */
    public function page($page='',$limit=''){
        $limitStr = '';
        $link = $this->_db;
        if(!$link)return $link;
        if(is_array($page)){
            list($page,$limit) = $page;
        }else if((is_string($page)||!empty($page))&&empty($limit)){
            list($page,$limit) = explode(",",$page);
        }
        if($page<1){
            $page=1;
        }

        if(empty($limit)&&!empty($page)){
            $limitStr = "LIMIT ".$page;
        }else{
            $limitStr = "LIMIT ".($page-1)*$limit.",".$limit;
        }
        /*Start    limit*/
        $this->_limit = $limitStr;
        return $this;
    }
    /**
     * limit限定查询
     *
     * @param      string  $limit  The limit
     *
     * @return     <type>  ( description_of_the_return_value )
     */
    public function limit($slimit='',$limit=''){
        $limitStr = '';
        $link = $this->_db;
        if(!$link)return $link;

        if(is_array($slimit)){
            list($slimit,$limit) = $slimit;
        }else if((is_string($slimit)||!empty($slimit))&&empty($limit)){
            list($slimit,$limit) = explode(",",$slimit);
        }
        if($slimit>0){
            $slimit = $slimit-1;
        }else{
            $slimit=0;
        }
        if(empty($limit)&&!empty($slimit)){
            $limitStr = "LIMIT ".$slimit;
        }else{
            $limitStr = "LIMIT ".$slimit.",".$limit;
        }
        //var_dump($limitStr);die;
        /*Start    limit*/
        $this->_limit = $limitStr;
        return $this;
    }

    public function fetchSql($str){
        $this->_fetchSql=$str;
        return $this;
    }

    public function failException($str){
        $this->_failException=$str;
        return $this;
    }

    /**
     * 执行sql语句
     *
     * @param      <type>   $sql    The sql
     *
     * @return     boolean  ( description_of_the_return_value )
     */
    public function execute($sql=null){
        $link = $this->_db;
        if(!$link)return false;
        $this->_getlatsql=[$this->_table=>$sql];
        if($this->_fetchSql){
            return $sql;
        }
        $res = mysqli_query($this->_db,$sql);
        //		var_dump($sql);die;
        //		var_dump($res["current_field"]);
        if(!$res){
            if($this->_failException){
                return null;
            }else{
                $errors = mysqli_error_list($this->_db);
                $this->ShowException("报错啦！<br/>错误号：".$errors[0]['errno']."<br/>SQL错误状态：".$errors[0]['sqlstate']."<br/>错误信息：".$errors[0]['error']);
                die();
            }

        }


        return $res;
    }
    /**
     * 执行sql语句
     *
     * @param      <type>   $sql    The sql
     *
     * @return     boolean  ( description_of_the_return_value )
     */
    public function getlatsql(){
        return $this->_getlatsql[$this->_table];
    }
    /**
     * 插入数据
     *
     * @param      <type>   $data   The data
     *
     * @return     boolean  ( description_of_the_return_value )
     */
    public function insert($data){
        $link = $this->_db;
        if(!$link)return false;
        if(is_array($data)){
            $keys = '';
            $values = '';
            foreach ($data as $key => $value) {
                $keys .= "`".$key."`,";
                $values .= "'".$value."',";
            }
            $keys = rtrim($keys,',');
            $values = rtrim($values,',');
        }
        $sql = "INSERT INTO `{$this->_table}`({$keys}) VALUES({$values})";
        // var_dump( $sql);die;
        mysqli_query($this->_db,$sql);
        $insertId = mysqli_insert_id($this->_db);
        return $insertId;
    }

    /**
     * 更新数据
     *
     * @param      <type>  $data   The data
     *
     * @return     <type>  ( description_of_the_return_value )
     */
    public function update($data){
        $link = $this->_db;
        if(!$link)return $link;
        if(is_array($data)){
            $dataStr = '';
            foreach ($data as $key => $value) {
                $dataStr .= "`".$key."`='".$value."',";
            }
            $dataStr = rtrim($dataStr,',');
        }
        $sql = "UPDATE `{$this->_table}` SET {$dataStr} {$this->_where} {$this->_order} {$this->_limit}";
        $res = $this->execute($sql);
        return $res;
    }

    /**
     * 删除数据
     *
     * @return     <type>  ( description_of_the_return_value )
     */
    public function delete(){
        $link = $this->_db;
        if(!$link)return $link;
        $sql = "DELETE FROM `{$this->_table}` {$this->_where}";
        $res = $this->execute($sql);
        return $res;
    }

    /**
     * 异常信息输出
     *
     * @param      <type>  $var    The variable
     */
    private function ShowException($var){
        if(is_bool($var)){
            var_dump($var);
        }else if(is_null($var)){
            var_dump(NULL);
        }else{
            echo "<pre style='position:relative;z-index:1000;padding:10px;border-radius:5px;background:#F5F5F5;border:1px solid #aaa;font-size:14px;line-height:18px;opacity:0.9;'>".print_r($var,true)."</pre>";
        }
    }

}

