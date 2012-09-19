<?php
abstract class Component {
  protected $debug = false;

  public function debug($bool=true){
    $this->debug=$bool;
  }

  abstract public function display();
}

abstract class DataComponent extends Component {
  private $filter = null;

  public function filter($filter){
    $fields = $this->getFilterFields();
    if($fields != null){
      $this->filter = array();
      foreach($fields as $field){
        if(isset($filter[$field['name']])){
          $this->filter[$field['name']] = $filter[$field['name']];
        }
      }
    }
  }

  public function isFiltered($field){
    return (
      $this->filter != null &&
      isset($this->filter[$field]) &&
      $this->filter[$field] != null
    );
  }

  public function getFilterValue($field){
    return $this->filter[$field];
  }

  protected function getFilterFields(){
    return null;
  }
  
  public function getFilterComponent($id){
    $fields = $this->getFilterFields();
    if($fields != null){
      return new FilterComponent($id, $this, $fields);
    }else{
      return null;
    }
  }
}

class FilterComponent extends Component {
  private $identifier;
  private $component;
  private $fields;
  private $values;

  const TextFilterType = 0;
  const SelectFilterType = 1;
  const RadioFilterType = 2;

  public function __construct($id, $component, $fields){
    $this->identifier = $id;
    $this->component = $component;
    $this->fields = $fields;
  }

  public function getComponent(){
    return $this->component;
  }

  public function getIdentifier(){
    return $this->identifier;
  }

  public function getFilterFields(){
    return $this->fields;
  }

  public function display(){
    $fields = $this->getFilterFields();
    $html = "<form method='post'>";
    $html .= "<input type='hidden' name='filter' value='".$this->getIdentifier()."' />";
    foreach($fields as $field){
      switch($field['type']){
        case FilterComponent::TextFilterType:
          $html .= "<label for='".$field['name']."'>".$field['label']."</label>";
          $html .= "<input type='text' name='".$field['name']."' ";
          if(isset($this->values[$field['name']])){
            $html .= "value='".$this->values[$field['name']]."' ";
          }
          $html .= "/>";
        break;
        case FilterComponent::SelectFilterType:
        case FilterComponent::RadioFilterType:
        break;
      }
    }
    $html .= "<input type='submit' value='Submit!' />";
    $html .= "</form>";
    return $html;
  }

  public function process(){
    if(isset($_POST["filter"]) && $_POST["filter"] == $this->getIdentifier()){
      $filter = array();
      foreach($_POST as $name=>$value){
        if($name == "filter") continue;
        if($value == ""){
          $filter[$name] = null;
        }else{
          $filter[$name] = $value;
          $this->values[$name] = $value;
        }
      }
      $this->getComponent()->filter($filter);
    }
  }
}

abstract class ListComponent extends DataComponent{
  protected $source = null;
  protected $struct = null;
  protected $view = null;
  
  public function getSource(){
    return $this->source;
  }

  public function getStruct(){
    return $this->struct;
  }

  public function getView(){
    return $this->view;
  }
}

class VisitedDocumentList extends ListComponent {

  public function __construct($dbh){
    $this->source = new LAProxyDataSource($dbh);
    $struct = new MatrixDataStructure();
    $struct->setYField("timestamp");
    $struct->setXField("user");
    $struct->setValueField("link");
    $struct->setMatrixLayout(MatrixDataStructure::YXLayout);
    $struct->setEmptyValue("0");
    $this->struct = $struct;
    $view = new TableView();
    $this->view = $view;
  }

  public function display(){
    $data = $this->source->getStats();
    if($this->debug) echo $this->source->getQuery();
    $this->struct->loadData($data);
    return $this->view->display($this->struct);
  }
}

class AggregatedDocumentList extends ListComponent {
  private $filter = array(
    "end_date" => null
  );

  public function __construct($dbh){
    $source = new LAProxyDataSource($dbh);
    $this->source = $source;
    $struct = new MatrixDataStructure();
    $struct->setYField("link");
    $struct->setXField("timestamp");
    $struct->setValueField("count");
    $struct->setMatrixLayout(MatrixDataStructure::YXLayout);
    $struct->setEmptyValue("0");
    $this->struct = $struct;
    $view = new TableView();
    $view->setColumnLabelModifier(function($timestamp){ return date("D d/M", $timestamp); });
    $this->view = $view;
  }

  public function display(){
    if(
      $this->isFiltered("begin_date") ||
      $this->isFiltered("end_date")
    ){
      $this->source->filterByDate(
        ($this->isFiltered("begin_date")?$this->getFilterValue("begin_date"):0),
        ($this->isFiltered("end_date")?$this->getFilterValue("end_date"):PHP_INT_MAX)
      );
    }
    $data = $this->source->getAggregatedStats();
    if($this->debug) echo $this->source->getQuery();
    $this->struct->loadData($data);
    return $this->view->display($this->struct);
  }

  protected function getFilterFields(){
    return array(
      array( "name" =>  "begin_date", "label" => "Begin timestamp", "type" => FilterComponent::TextFilterType),
      array( "name" =>  "end_date", "label" => "End timestamp", "type" => FilterComponent::TextFilterType)
    );
  }
}

class ResourceList extends ListComponent {
  public function __construct($dbh){
    $this->source = new LAProxyDataSource($dbh);
    $this->struct = new RowDataStructure();
    $this->view = new GTableView();
  }

  public function display(){
    $data = $this->source->getLinks();
    $this->struct->loadData($data);
    return $this->view->display($this->struct->getStructure());
  }
}

class ResourceTimeGraph extends ListComponent {
  
  public function __construct($dbh){
    $this->source = new LAProxyDataSource($dbh);
    $this->struct = new RowDataStructure();
    $this->view = new BubbleGraphView();
  }

  public function display(){
    if($this->isFiltered("users")){
      $this->source->filterByUsers(explode(",", $this->getFilterValue("users")));
    }
    if(
      $this->isFiltered("begin_date") ||
      $this->isFiltered("end_date")
    ){
      $this->source->filterByDate(
        ($this->isFiltered("begin_date")?$this->getFilterValue("begin_date"):0),
        ($this->isFiltered("end_date")?$this->getFilterValue("end_date"):PHP_INT_MAX)
      );
    }
    $data = $this->source->getAggregatedStats();
    $this->struct->loadData($data);
    return $this->view->display($this->struct);
  }
  
  protected function getFilterFields(){
    return array(
      array( "name" =>  "begin_date", "label" => "Begin timestamp", "type" => FilterComponent::TextFilterType),
      array( "name" =>  "end_date", "label" => "End timestamp", "type" => FilterComponent::TextFilterType),
      array( "name" =>  "users", "label" => "Users", "type" => FilterComponent::TextFilterType)
    );
  }
}

?>
