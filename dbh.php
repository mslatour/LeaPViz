<?php
class MySQLiHandler {
  private $host;
  private $username;
  private $password;
  private $database;

  private $handler = null;

  function __construct($host, $username, $password, $database){
    $this->host = $host;
    $this->username = $username;
    $this->password = $password;
    $this->database = $database;
  }

  private function getHandler($ping = false){
    if(!$this->handler){
      $this->handler = new mysqli(
        $this->host, 
        $this->username, 
        $this->password, 
        $this->database
      );
    }
    if($ping){
      $this->handler->ping();
    }
    return $this->handler;
  }

  public function escape_string($str){
    return $this->getHandler()->escape_string($str);
  }
 
  public function raw_query($query){
    return $this->getHandler(true)->query($query);
  }

  public function query($query, $params){
    $handler = $this->getHandler(true);
    foreach($params as $key => $value){
      $params[$key] = $handler->escape_string($value);
    }
    return $handler->query(vsprintf($query, $params));
  }

  public function insert($table, $data){
    if(sizeof($data) > 0){
      $fields = "";
      $params = array();
      foreach($data as $field => $value){
        $fields .= ($fields==""?"":", ")."`$field` = '%s'";
        $params[] = $value;
      }
      $query = sprintf("INSERT INTO `%s` SET %s", $table, $fields);
      if($this->query($query, $params)){
        return $this->getHandler()->insert_id;
      }else{
        return false;
      }
    }
    return false;
  }

  public function update($table, $data, $conditions){
    if(sizeof($data) > 0){
      $fields = "";
      $params = array();
      foreach($data as $field => $value){
        $fields .= ($fields==""?"":", ")."`$field` = '%s'";
        $params[] = $value;
      }
      $where = "";
      foreach($conditions as $field => $value){
        $where .= ($where==""?"":", ")."`$field` = '";
        $where .= $this->getHandler()->escape_string($value)."'";
      }

      $query = sprintf("UPDATE `%s` SET %s WHERE %s", $table, $fields, $where);
      if($this->query($query, $params)){
        return $this->getHandler()->affected_rows;
      }else{
        return false;
      }
    }
    return false;
  }
}
?>
