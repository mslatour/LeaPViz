<?php
abstract class Component {
  protected $debug = false;

  public function debug($bool=true){
    $this->debug=$bool;
  }

  abstract public function display();
}

class VisitedDocumentList extends Component {
  private $filtered_users = array();
  private $dbh = null;

  public function __construct($dbh){
    $this->dbh = $dbh;
  }

  public function filterByUsers($users){
    $this->filtered_users = $users;
  }

  public function clearUserFilter(){
    $this->filtered_users = array();
  }

  public function display(){
    $source = new LAProxyDataSource($this->dbh);
    if(sizeof($this->filtered_users) > 0){
      $source->filterByUsers($this->filtered_users);
    }
    $data = $source->getStats();
    if($this->debug) echo $source->getQuery();
    $struct = new MatrixDataStructure();
    $struct->setYField("timestamp");
    $struct->setXField("user");
    $struct->setValueField("link");
    $struct->setMatrixLayout(MatrixDataStructure::YXLayout);
    $struct->loadData($data);
    $view = new TableView();
    $view->setCSSClass("tabular");
    $table = $view->display($struct);
    return $table;
  }
}

class AggregatedDocumentList extends Component {
  private $filtered_users = array();
  private $dbh = null;

  public function __construct($dbh){
    $this->dbh = $dbh;
  }

  public function filterByUsers($users){
    $this->filtered_users = $users;
  }

  public function clearUserFilter(){
    $this->filtered_users = array();
  }

  public function display(){
    $source = new LAProxyDataSource($this->dbh);
    if(sizeof($this->filtered_users) > 0){
      $source->filterByUsers($this->filtered_users);
    }
    $data = $source->getAggregatedStats();
    if($this->debug) echo $source->getQuery();
    $struct = new MatrixDataStructure();
    $struct->setYField("link");
    $struct->setXField("date");
    $struct->setValueField("count");
    $struct->setMatrixLayout(MatrixDataStructure::YXLayout);
    $struct->setEmptyValue("0");
    $struct->loadData($data);
    $view = new TableView();
    $view->setColumnLabelModifier(function($date){ return date("d/M", $date); });
    $view->setCSSClass("tabular");
    $table = $view->display($struct);
    return $table;
  }
}
?>
