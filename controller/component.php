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
  private $filterId = 'filter';
  private $filterComponent = null;

  public function setFilterId($id){
    $this->filterId = $id;
  }

  public function getFilterId(){
    return $this->filterId;
  }

  public function isFiltered($field){
    return $this->getFilterComponent()->isFiltered($field);
  }

  public function getFilterValue($field){
    return $this->getFilterComponent()->getValue($field);
  }

  public function getFilterFields(){
    return null;
  }
  
  public function initFilterComponent(){
    $id = $this->getFilterId();
    $this->filterComponent = new FilterComponent($id, $this);
  }

  public function getFilterComponent(){
    if($this->filterComponent == null){
      $this->initFilterComponent();
    }
    return $this->filterComponent;
  }
}

class FilterComponent extends Component {
  private $identifier;
  private $component;
  private $fields;
  private $values = array();

  const TextFilterType = 0;
  const SelectFilterType = 1;
  const RadioFilterType = 2;

  public function __construct($id, $component){
    $this->identifier = $id;
    $this->component = $component;
  }

  public function getComponent(){
    return $this->component;
  }

  public function getIdentifier(){
    return $this->identifier;
  }

  public function getFilterFields(){
    $fields = $this->component->getFilterFields();
    if($fields == null) $fields = array();
    return $fields;
  }

  public function setValue($field, $value){
    $this->values[$field] = $value;
  }

  public function getValue($field){
    return $this->values[$field];
  }

  public function isFiltered($field){
    return (
      isset($this->values[$field]) &&
      $this->values[$field] !== null
    );
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
              ."style='height: 400px;'>";
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
      foreach($_POST as $name=>$value){
        if($name == "filter") continue;
        if(substr($name, -2) == "[]") $name = substr($name, 0, -2);
        if($value == ""){
          $this->values[$name] = null;
        }else{
          $this->values[$name] = $value;
        }
      }
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
    $source->setFilter($this->getFilterComponent());
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
    $data = $this->source->getAggregatedStats();
    $this->struct->loadData($data);
    return $this->view->display($this->struct);
  }

  public function getFilterFields(){
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
      )
    );
  }
}

class ResourceList extends ListComponent {
  public function __construct($dbh){
    $this->source = new LAProxyDataSource($dbh);
    $this->source->setFilter($this->getFilterComponent());
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
  
  public function getFilterFields(){
    array();
  }
}

class UserList extends ListComponent {
  public function __construct($dbh){
    $this->source = new LAProxyDataSource($dbh);
    $this->source->setFilter($this->getFilterComponent());
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
  
  public function getFilterFields(){
    $users = $this->source->getUsers();
    $user_options = array();
    foreach($users as $user){
      $user_options[$user['id']] = $user['surname'].", ".$user['firstname']." (".$user['username'].")";
    }
    return array(
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

class ResourceTimeGraph extends ListComponent {
  
  public function __construct($dbh){
    $this->source = new LAProxyDataSource($dbh);
    $this->getFilterComponent()->setValue("course", "UVA_BW1019_2012");
    $this->source->setFilter($this->getFilterComponent());
    $this->struct = new RowDataStructure();
    $this->view = new BubbleGraphView();
    $this->view->setVAxisTitle("Resource");
    $this->view->setHAxisTitle("Hour of Day");
  }

  public function display(){
    $data = $this->source->getAggregatedResourceStats();
    $this->struct->loadData($data);
    return $this->view->display($this->struct);
  }
  
  public function getFilterFields(){
    $users = $this->source->getUsers();
    $user_options = array();
    foreach($users as $user){
      $user_options[$user['id']] = $user['surname'].", ".$user['firstname']." (".$user['username'].")";
    }
    // Generate period list
    $periods = array("" => "Any period");
    $year = intval(date("Y"));
    for($i = 1; $i <= 12; $i++){
      $from = mktime(00,00,00,$i,1,$year-1);
      $till = mktime(00,00,00,$i+1,1,$year-1)-1;
      $periods[$from."-".$till] = "1/$i - ".date("d",$till)."/$i/".($year-1);
    }
    for($i = 1; $i <= 12; $i++){
      $from = mktime(00,00,00,$i,1,$year);
      $till = mktime(00,00,00,$i+1,1,$year)-1;
      $periods[$from."-".$till] = "1/$i - ".date("d",$till)."/$i/$year";
    }
    return array(
      array(
        "name" => "course",
        "label" => "Course",
        "type" => FilterComponent::SelectFilterType,
        "options" => array(
          "" => "Any course",
          "UVA_BW1019_2012" => "Universiteit van Amsterdam, BW1019, 2012",
          "VU_KNO_2012" => "Vrije Universiteit, KNO, 2012"
        )
      ),
      array( 
        "name" => "period", 
        "label" => "Period",
        "type" => FilterComponent::SelectFilterType,
        "options" => $periods
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
    $this->getFilterComponent()->setValue("course", "UVA_BW1019_2012");
    $this->source->setFilter($this->getFilterComponent());
    $this->struct = new RowDataStructure();
    $this->view = new BubbleGraphView();
    $this->view->setVAxisTitle("Student");
    $this->view->setHAxisTitle("Day of month");
  }

  public function display(){
    $data = $this->source->getAggregatedUserStats();
    $this->struct->loadData($data);
    return $this->view->display($this->struct);
  }
  
  public function getFilterFields(){
    // Generate resource list
    $resources = $this->source->getLinks();
    $resource_options = array();
    foreach($resources as $resource){
      $resource_options[$resource['id']] = $resource['title'];
    }
    // Generate period list
    $periods = array("" => "Any period");
    $year = intval(date("Y"));
    for($i = 1; $i <= 12; $i++){
      $from = mktime(00,00,00,$i,1,$year-1);
      $till = mktime(00,00,00,$i+1,1,$year-1)-1;
      $periods[$from."-".$till] = "1/$i - ".date("d",$till)."/$i/".($year-1);
    }
    for($i = 1; $i <= 12; $i++){
      $from = mktime(00,00,00,$i,1,$year);
      $till = mktime(00,00,00,$i+1,1,$year)-1;
      $periods[$from."-".$till] = "1/$i - ".date("d",$till)."/$i/$year";
    }
    // Generate users
    $users = $this->source->getUsers();
    $user_options = array();
    foreach($users as $user){
      $user_options[$user['id']] = $user['surname'].", ".$user['firstname']." (".$user['username'].")";
    }
    return array(
      array(
        "name" => "course",
        "label" => "Course",
        "type" => FilterComponent::SelectFilterType,
        "options" => array(
          "" => "Any course",
          "UVA_BW1019_2012" => "Universiteit van Amsterdam, BW1019, 2012",
          "VU_KNO_2012" => "Vrije Universiteit, KNO, 2012"
        )
      ),
      array( 
        "name" => "period", 
        "label" => "Period",
        "type" => FilterComponent::SelectFilterType,
        "options" => $periods
      ),
      array(
        "name" => "resources", 
        "label" => "Resources",
        "type" => FilterComponent::SelectFilterType,
        "options" => $resource_options,
        "multiple" => true
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


class PathTimeGraph extends ListComponent {
  
  public function __construct($dbh){
    $this->source = new LAProxyDataSource($dbh);
    $this->getFilterComponent()->setValue("course", "UVA_BW1019_2012");
    $this->source->setFilter($this->getFilterComponent());
    $this->struct = new RowDataStructure();
    $this->view = new BubbleGraphView();
    $this->view->setVAxisTitle("Student");
    $this->view->setHAxisTitle("Day of month");
  }

  public function display(){
    $data = $this->source->getAggregatedUserStats();
    $this->struct->loadData($data);
    return $this->view->display($this->struct);
  }
  
  public function getFilterFields(){
    // Generate resource list
    $resources = $this->source->getLinks();
    $resource_options = array();
    foreach($resources as $resource){
      $resource_options[$resource['id']] = $resource['title'];
    }
    // Generate period list
    $periods = array("" => "Any period");
    $year = intval(date("Y"));
    for($i = 1; $i <= 12; $i++){
      $from = mktime(00,00,00,$i,1,$year-1);
      $till = mktime(00,00,00,$i+1,1,$year-1)-1;
      $periods[$from."-".$till] = "1/$i - ".date("d",$till)."/$i/".($year-1);
    }
    for($i = 1; $i <= 12; $i++){
      $from = mktime(00,00,00,$i,1,$year);
      $till = mktime(00,00,00,$i+1,1,$year)-1;
      $periods[$from."-".$till] = "1/$i - ".date("d",$till)."/$i/$year";
    }
    return array(
      array(
        "name" => "course",
        "label" => "Course",
        "type" => FilterComponent::SelectFilterType,
        "options" => array(
          "" => "Any course",
          "UVA_BW1019_2012" => "Universiteit van Amsterdam, BW1019, 2012",
          "VU_KNO_2012" => "Vrije Universiteit, KNO, 2012"
        )
      ),
      array( 
        "name" => "period", 
        "label" => "Period",
        "type" => FilterComponent::SelectFilterType,
        "options" => $periods
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
