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
    $html = "<form method='post'>\n";
    $html .= "<input type='hidden' name='filter' value='".$this->getIdentifier()."' />\n";
    $html .= "<input type='submit' value='Apply Filter!' />";
    foreach($fields as $field){
      switch($field['type']){
        case FilterComponent::TextFilterType:
          $html .= "<label for='".$field['name']."'>".$field['label']."</label>\n";
          $html .= "<input type='text' name='".$field['name']."' ";
          if(isset($this->values[$field['name']])){
            $html .= "value='".$this->values[$field['name']]."' ";
          }
          $html .= "/>\n";
        break;
        case FilterComponent::SelectFilterType:
          $html .= "<label for='".$field['name']."'>".$field['label']."</label>\n";
          if(isset($field['multiple']) && $field['multiple'] == true){
            $html .= "<select name='".$field['name']."[]' multiple='multiple' "
              ."style='height: ".(30+sizeof($field['options'])*10)."px;'>";
          }else{
            $html .= "<select name='".$field['name']."'>\n";
          }
          foreach($field['options'] as $id=>$value){
            if(
              isset($this->values[$field['name']]) &&
              (
                (
                  is_array($this->values[$field['name']]) &&
                  in_array($id, $this->values[$field['name']])
                )
              ||
                $this->values[$field['name']] == $id
              )
            ){
              $html .= "<option value='$id' selected='selected'>$value</option>\n";
            }else{
              $html .= "<option value='$id'>$value</option>\n";
            }
          }
          $html .= "</select>";
        case FilterComponent::RadioFilterType:
        break;
      }
    }
    $html .= "</form>";
    return $html;
  }

  public function process(){
    if(isset($_POST["filter"]) && $_POST["filter"] == $this->getIdentifier()){
      $filter = array();
      foreach($_POST as $name=>$value){
        if($name == "filter") continue;
        if(substr($name, -2) == "[]") $name = substr($name, 0, -2);
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
    $this->view->setColumns(array(
      'Id'=>'number', 
      'Title'=>'string', 
      'Type'=>'string')
    );
  }

  public function display(){
    $data = $this->source->getLinks();
    $this->struct->loadData($data);
    return $this->view->display($this->struct->getStructure());
  }
}

class UserList extends ListComponent {
  public function __construct($dbh){
    $this->source = new LAProxyDataSource($dbh);
    $this->struct = new RowDataStructure();
    $this->view = new GTableView();
    $this->view->setColumns(array(
      'Id'=>'number', 
      'Username'=>'string', 
      'Firstname'=>'string',
      'Surname'=>'string',
      'Student'=>'boolean'
      )
    );
  }

  public function display(){
    $data = $this->source->getUsers();
    $this->struct->loadData($data);
    return $this->view->display($this->struct->getStructure());
  }
}

class ResourceTimeGraph extends ListComponent {
  
  public function __construct($dbh){
    $this->source = new LAProxyDataSource($dbh);
    $this->struct = new RowDataStructure();
    $this->view = new BubbleGraphView();
    $this->view->setVAxisTitle("Resource");
    $this->view->setHAxisTitle("Day of month");
  }

  public function display(){
    if($this->isFiltered("users")){
      if(is_array($this->getFilterValue("users"))){
        $this->source->filterByUsers($this->getFilterValue("users"));
      }else{
        $this->source->filterByUsers(explode(",", $this->getFilterValue("users")));
      }
    }
    if(
      $this->isFiltered("period")
    ){
      $period = explode("-", $this->getFilterValue("period"));
      $this->source->filterByDate($period[0], $period[1]);
    }
    $data = $this->source->getAggregatedResourceStats();
    $this->struct->loadData($data);
    return $this->view->display($this->struct);
  }
  
  protected function getFilterFields(){
    $users = $this->source->getUsers();
    $user_options = array();
    foreach($users as $user){
      $user_options[$user['id']] = $user['surname'].", ".$user['firstname']." (".$user['username'].")";
    }
    return array(
      array( 
        "name" => "period", 
        "label" => "Period",
        "type" => FilterComponent::SelectFilterType,
        "options" => array(
          "" => "No filter",
          "1335848400-1338526799" => "1/05 - 31/05",
          "1338526800-1341118799" => "1/06 - 30/06",
          "1341118800-1343797199" => "1/07 - 31/07"
        )
      ),
      array(
        "name" => "users", 
        "label" => "Users",
        "type" => FilterComponent::SelectFilterType,
        "options" => $user_options,
        "multiple" => true
      )
    );
  }
}

class UserTimeGraph extends ListComponent {
  
  public function __construct($dbh){
    $this->source = new LAProxyDataSource($dbh);
    $this->struct = new RowDataStructure();
    $this->view = new BubbleGraphView();
    $this->view->setVAxisTitle("Student");
    $this->view->setHAxisTitle("Day of month");
  }

  public function display(){
    if($this->isFiltered("resources")){
      if(is_array($this->getFilterValue("resources"))){
        $this->source->filterByResources($this->getFilterValue("resources"));
      }else{
        $this->source->filterByResources(explode(",", $this->getFilterValue("resources")));
      }
    }
    if(
      $this->isFiltered("period")
    ){
      $period = explode("-", $this->getFilterValue("period"));
      $this->source->filterByDate($period[0], $period[1]);
    }
    $data = $this->source->getAggregatedUserStats();
    $this->struct->loadData($data);
    return $this->view->display($this->struct);
  }
  
  protected function getFilterFields(){
    $resources = $this->source->getLinks();
    $resource_options = array();
    foreach($resources as $resource){
      $resource_options[$resource['id']] = $resource['title'];
    }
    return array(
      array( 
        "name" => "period", 
        "label" => "Period",
        "type" => FilterComponent::SelectFilterType,
        "options" => array(
          "" => "No filter",
          "1335848400-1338526799" => "1/05 - 31/05",
          "1338526800-1341118799" => "1/06 - 30/06",
          "1341118800-1343797199" => "1/07 - 31/07"
        )
      ),
      array(
        "name" => "resources", 
        "label" => "Resources",
        "type" => FilterComponent::SelectFilterType,
        "options" => $resource_options,
        "multiple" => true
      )
    );
  }
}

?>
