<?php
abstract class Component {
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
    //echo $source->getQuery();
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
?>
