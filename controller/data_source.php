<?php
abstract class DataSource {

  protected $selections = array();
  protected $filters = array();
  protected $orderings = array();
  protected $groupings = array();
  protected $limit = null;

  protected $filterComponent = null;

  abstract public function fetchAll($table);

  public function clear(){
    $this->clearSelections();
    $this->clearFilters();
    $this->clearOrderings();
    $this->clearGroupings();
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

  protected function applyFilterByFieldValue($field, $op, $value, $type = "string"){
    $this->filters[$this->escape($field,'field')] = array(
      "op" => $op, 
      "value" => $this->escape($value,$type)
    );
  }
  
  protected function applyFilterByFieldBetweenValues($field, $a, $b, $type){
    $this->filters[$this->escape($field,'field')] = array(
      "op" => "BETWEEN", 
      "value" => $this->escape($a, $type)." AND ".$this->escape($b, $type)
    );
  }

  public function setFilter($filter){
    $this->filterComponent = $filter;
  }

  protected function getFilter(){
    if($this->filterComponent != null){
      return $this->filterComponent;
    }else{
      return new FilterComponent('dummy',null,array());
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
      case "field":
        break;
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
  
  protected function group($group){
    $this->groupings[] = $group;
  }

  public function clearGroupings(){
    $this->groupings = array();
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
        "SELECT %s FROM %s %s %s %s %s",
        $this->createSelectionString(),
        $table,
        $this->createFilterString(),
        $this->createGroupingString(),
        $this->createOrderingString(),
        $this->createLimitString()
      ));
      $db_result = $this->dbh->raw_query($this->query);
      $this->clearSelections();
      $this->clearGroupings();
      $this->clearFilters();
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
        if($value[0] != '`'){
          if(strpos($value, '.') !== false){
            $parts = explode('.', $value);
            $value = $this->escape($parts[0], "field")."."
              .$this->escape($parts[1], "field");
          }else{
            $value = '`'.$this->dbh->escape_string($value).'`';
          }
        }
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
  
  private function createGroupingString(){
    $str = "";
    foreach($this->groupings as $group){
      $str .= ($str == "" ? "" : ", ").$group;
    }
    return ($str==""?"":"GROUP BY ".$str);
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
  private $course = null;

  public function getUsers(){
    if($this->getFilter()->isFiltered("course")){
      $this->applyFilterByFieldValue("course", "=", $this->getFilter()->getValue("course"));
    }
    return $this->fetchAll("user");
  }

  public function getLinks(){
    if($this->getFilter()->isFiltered("course")){
      $this->applyFilterByFieldValue("course", "=", $this->getFilter()->getValue("course"));
    }
    return $this->fetchAll("links");
  }

  public function getStats(){
    $this->select(array(
      "CONCAT(`user`.`surname`,', ',`user`.`firstname`,' (',`user`.`username`,')')"=>"`user`",
      "FROM_UNIXTIME(`stats`.`timestamp`,'%d-%m-%Y %H:%i:%s')"=>"`timestamp`",
      "`links`.`title`"=>"`link`"
    ));
    if($this->getFilter()->isFiltered("course")){
      $this->applyFilterByFieldValue("stats.course", "=", $this->getFilter()->getValue("course"));
    }
    if($this->getFilter()->isFiltered("users")){
      $this->applyFilterByFieldValue("stats.user", "IN", $this->getFilter()->getValue("users"),"list");
    }
    if($this->getFilter()->isFiltered("resources")){
      $this->applyFilterByFieldValue("links.id", "IN", $this->getFilter()->getValue("resources"),"list");
    }
    if($this->getFilter()->isFiltered("period")){
      $date = explode("-", $this->getFilter()->getValue("period"));
      $this->applyFilterByFieldBetweenValues("stats.timestamp",$date[0],$date[1], "int");
    }
    return $this->fetchAll(
      "(".
        "`stats` ".
        "LEFT JOIN (`links`,`user`) ".
        "ON (`stats`.`link` = `links`.`url` AND `stats`.`user` = `user`.`id`)".
      ")"
    );
  }

  public function getAggregatedResourceStats(){
    $this->select(array(
      "UNIX_TIMESTAMP(FROM_UNIXTIME(`stats`.`timestamp`,'%Y-%m-%d 00:00:00'))"=>"`timestamp`",
      "FROM_UNIXTIME(`stats`.`timestamp`,'%Y-%m-%d')"=>"`date`",
      "`links`.`title`"=>"`link`",
      "`links`.`type`"=>"`linkType`",
      "`links`.`id`"=>"`linkId`",
      "COUNT(DISTINCT `user`, FROM_UNIXTIME(`timestamp` , '%d-%m-%Y'))"=>"`count`"
    ));
    $this->group("`links`.`id`");
    $this->group("`date`");
    if($this->getFilter()->isFiltered("course")){
      $this->applyFilterByFieldValue("stats.course", "=", $this->getFilter()->getValue("course"));
    }
    if($this->getFilter()->isFiltered("users")){
      $this->applyFilterByFieldValue("stats.user", "IN", $this->getFilter()->getValue("users"),"list");
    }
    if($this->getFilter()->isFiltered("resources")){
      $this->applyFilterByFieldValue("links.id", "IN", $this->getFilter()->getValue("resources"),"list");
    }
    if($this->getFilter()->isFiltered("period")){
      $date = explode("-", $this->getFilter()->getValue("period"));
      $this->applyFilterByFieldBetweenValues("stats.timestamp",$date[0],$date[1], "int");
    }
    return $this->fetchAll(
      "(".
        "`stats` ".
        "LEFT JOIN `links` ".
        "ON `stats`.`link` = `links`.`url`".
      ")"
    );
  }
  
  public function getAggregatedUserStats(){
    $this->select(array(
      "UNIX_TIMESTAMP(FROM_UNIXTIME(`stats`.`timestamp`,'%Y-%m-%d 00:00:00'))"=>"`timestamp`",
      "FROM_UNIXTIME(`stats`.`timestamp`,'%Y-%m-%d')"=>"`date`",
      "`stats`.`user`"=>"`user`",
      "`grades`.`grade`"=>"grade",
      "COUNT(DISTINCT `link`, FROM_UNIXTIME(`timestamp` , '%d-%m-%Y'))"=>"`count`"
    ));
    $this->group("`stats`.`user`");
    $this->group("`date`");
    if($this->getFilter()->isFiltered("course")){
      $this->applyFilterByFieldValue("stats.course", "=", $this->getFilter()->getValue("course"));
    }
    if($this->getFilter()->isFiltered("users")){
      $this->applyFilterByFieldValue("stats.user", "IN", $this->getFilter()->getValue("users"),"list");
    }
    if($this->getFilter()->isFiltered("resources")){
      $this->applyFilterByFieldValue("links.id", "IN", $this->getFilter()->getValue("resources"),"list");
    }
    if($this->getFilter()->isFiltered("period")){
      $date = explode("-", $this->getFilter()->getValue("period"));
      $this->applyFilterByFieldBetweenValues("stats.timestamp",$date[0],$date[1], "int");
    }
    return $this->fetchAll(
      "(".
        "`stats` ".
        "LEFT JOIN (`links`,`grades`) ".
        "ON (`stats`.`link` = `links`.`url` AND `stats`.`user` = `grades`.`student`)".
      ")"
    );
  }

  public function getPathUserStats($path_begin, $path_end){
    
  }
}
?>
