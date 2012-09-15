<?php
abstract class DataSource {

  protected $selections = array();
  protected $filters = array();
  protected $orderings = array();
  protected $limit = null;

  abstract public function fetchAll($table);

  public function clear(){
    $this->clearSelections();
    $this->clearFilters();
    $this->clearOrderings();
    $this->clearLimit();
  }
  
  protected function select($fields,$alias=""){
    if(is_array($fields)){
      foreach($fields as $field=>$alias){
        $this->select($field, $alias);
      }
    }else{
      $this->selections[$fields] = $alias;
    }
  }

  public function clearSelections(){
    $this->selections = array();
  }

  protected function filterByFieldValue($field, $op, $value, $type = "string"){
    $this->filters[$this->escape($field,'field')] = array(
      "op" => $op, 
      "value" => $this->escape($value,$type)
    );
  }

  protected function escape($value, $type = "string"){
    switch($type){
      case "int":
        $value = intval($value);
        break;
      case "real":
      case "float":
      case "double":
        $value = floatval($value);
        break;
      case "field":
        $value = '`'.$value.'`';
      default:
      case "string":
        $value = '"'.$value.'"';
    }
    return $value;
  }
  
  public function clearFilters(){
    $this->filters = array();
  }

  protected function orderByField($field, $order){
    $this->orderings[$field] = $order;
  }

  public function clearOrderings(){
    $this->orderings = array();
  }

  public function limit($from, $to){
    $this->limit = array("from" => intval($from), "to" => intval($to));
  }

  public function clearLimit(){
    $this->limit = null;
  }

}

class MySQLiDataSource extends DataSource {

  private $dbh = null;
  private $query = "";

  public function __construct($dbh = null){
    $this->dbh = $dbh;
  }

  public function connect($host,$user,$pass,$db){
    $this->dhb = new MySQLiHandler($host,$user,$pass,$db);
  }

  // $table is insecure
  public function fetchAll($table){
    if($this->dbh != null){
      $this->query = trim(sprintf(
        "SELECT %s FROM %s %s %s %s",
        $this->createSelectionString(),
        $table,
        $this->createFilterString(),
        $this->createOrderingString(),
        $this->createLimitString()
      ));
      $db_result = $this->dbh->raw_query($this->query);
      if($db_result){
        $result = array();
        while($row = $db_result->fetch_array(MYSQLI_ASSOC)){
          $result[] = $row;
        }
        return $result;
      }else{
        return array();
      }
    }else{
      return array();
    }
  }

  protected function escape($value, $type = "string"){
    switch($type){
      case "int":
        $value = intval($value);
        break;
      case "real":
      case "float":
      case "double":
        $value = floatval($value);
        break;
      case "list":
        $str = "";
        foreach($value as $v){
          $str .= ($str==""?"":",").$this->escape($v);
        }
        $value = "(".$str.")";
        break;
      case "field":
        $value = '`'.$this->dbh->escape_string($value).'`';
        break;
      default:
      case "string":
        $value = '"'.$this->dbh->escape_string($value).'"';
        break;
    }
    return $value;
  }

  private function createSelectionString(){
    $str = "";
    foreach($this->selections as $field=>$alias){
      $str .= ($str == "" ? "" : ", ").$field.($alias==""?"":" AS ".$alias);
    }
    return ($str==""?"*":$str);
  }

  private function createFilterString(){
    $str = "";
    foreach($this->filters as $field=>$filter){
      $op = $filter['op']; $value = $filter['value'];
      $str .= ($str==""?"":" AND ").$field." ".$op." ".$value;
    }
    return ($str==""?"":"WHERE ".$str);
  }

  private function createOrderingString(){
    $str = "";
    foreach($this->orderings as $field=>$order){
      $str .= ($str == "" ? "" : ", ")."`".$field."` ".$order;
    }
    return ($str==""?"":"ORDER BY ".$str);
  }

  private function createLimitString(){
    if($this->limit != null){
      return "LIMIT ".$this->limit["from"].",".$this->limit["to"];
    }else{
      return "";
    }
  }

  public function getQuery(){
    return $this->query;
  }
}

class LAProxyDataSource extends MySQLiDataSource {

  public function getUsers(){
    return $this->fetchAll("user");
  }

  public function getLinks(){
    return $this->fetchAll("links");
  }

  public function getStats(){
    $this->clearSelections();
    $this->select(array(
      "CONCAT(`user`.`surname`,', ',`user`.`firstname`,' (',`user`.`username`,')')"=>"`user`",
      "FROM_UNIXTIME(`stats`.`timestamp`,'%d-%m-%Y %H:%i:%s')"=>"`timestamp`",
      "`links`.`title`"=>"`link`"
    ));
    return $this->fetchAll(
      "(".
        "`stats` ".
        "LEFT JOIN (`links`,`user`) ".
        "ON (`stats`.`link` = `links`.`url` AND `stats`.`user` = `user`.`id`)".
      ")"
    );
  }

  public function filterByUser($user){
    $this->filterByFieldValue("user","=",$user,"int");
  }

  public function filterByUsers($users){
    $this->filterByFieldValue("user","IN",$users,"list");
  }

}
?>
