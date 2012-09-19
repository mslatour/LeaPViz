<?php
abstract class DataView {
  private $classname = "";
  public function display($data){
    if(
      is_object($data) && 
      $data instanceof DataStructure
    ){
      return $this->displayStructure($data);
    }else if(
      is_array($data) && 
      sizeof($data) > 0 &&
      is_array($data[0])
    ){
      return $this->display2DMatrix($data);
    }else{
      return false;
    }
  }
  abstract protected function displayStructure($data);
  abstract protected function display2DMatrix($data);
}

abstract class HtmlDataView extends DataView {
  public function setCSSClass($class){
    $this->classname = $class;
  }
  public function getCSSClass(){
    return $this->classname;
  }
}

abstract class GoogleTableView extends DataView {

}

abstract class GoogleGraphView extends DataView {
  private $vStepsize = 10;
  private $maxVAxis = 100;
  private $hStepsize = 10;
  private $maxHAxis = 100;

  public function setVStepSize($stepsize){
    $this->vStepsize = $stepsize;
  }

  protected function getVStepSize(){
    return $this->vStepsize;
  }

  public function setHStepSize($stepsize){
    $this->hStepsize = $stepsize;
  }

  protected function getHStepSize(){
    return $this->hStepsize;
  }

  public function setMaxVAxis($max){
    $this->maxVAxis = $max;
  }

  protected function getMaxVAxis(){
    return $this->maxVAxis;
  }

  public function setMaxHAxis($max){
    $this->maxHAxis = $max;
  }

  protected function getMaxHAxis(){
    return $this->maxHAxis;
  }
}

class JSONView extends DataView {

  protected function displayStructure($struct){
    if($struct instanceof MatrixDataStructure){
      if(!$struct->is_empty()){
        $struct->setMatrixLayout(MatrixDataStructure::YXLayout);
        $matrix = $struct->getStructure();
        return $this->display2DMatrix($matrix);
      }
    }else{
      return false;
    }
  }

  protected function display2DMatrix($matrix){
    $json = "";
    foreach($matrix as $row){
      $json .= ($json==""?"":",\n");
      $rowstr = "";
      foreach($row as $column){
        $rowstr .= ($rowstr==""?"":", ").json_encode($column);
      }
      $json .= "[".$rowstr."]";
    }
    return ($json==""?"":"[".$json."]");
  }
}

class TableView extends HtmlDataView {
  private $c_mod;
  private $r_mod;
  private $v_mod;
  
  public function __construct(){
    $this->c_mod = (function($c){ return $c; });
    $this->r_mod = (function($r){ return $r; });
    $this->v_mod = (function($v){ return $v; });
  }

  public function setColumnLabelModifier($mod){
    $this->c_mod = $mod;
  }

  protected function getColumnLabelModifier(){
    return $this->c_mod;
  }
  
  public function setRowLabelModifier($mod){
    $this->r_mod = $mod;
  }
  
  public function getRowLabelModifier(){
    return $this->r_mod;
  }
  
  public function setValueModifier($mod){
    $this->v_mod = $mod;
  }
  
  public function getValueModifier(){
    return $this->v_mod;
  }

  protected function displayStructure($struct){
    if($struct instanceof MatrixDataStructure){
      if(!$struct->is_empty()){
        $struct->setMatrixLayout(MatrixDataStructure::YXLayout);
        $matrix = $struct->getStructure();
        return $this->display2DMatrix($matrix);
      }
    }else{
      return false;
    }
  }

  protected function display2DMatrix($matrix){
    $rowstr = "";
    $headerstr = "";
    $headers = array("");

    $rmod = $this->getRowLabelModifier();
    $cmod = $this->getColumnLabelModifier();
    $vmod = $this->getValueModifier();
    
    foreach($matrix as $row=>$t){
      $rowstr .= "\t<tr>\n\t\t<td>".$rmod($row)."</td>\n";
      foreach($t as $col=>$value){
        if($headerstr == "") $headers[] = $cmod($col);
        $rowstr .= "\t\t<td>".$vmod($value)."</td>\n";
      }
      if($headerstr == ""){
        foreach($headers as $header){
          $headerstr .= "\t\t<td>".$header."</td>\n";
        }
      }
      $rowstr .= "\t</tr>\n";
    }
    $c = $this->getCSSClass();
    $html = "<table".($c==""?"":" class='".$c."'").">\n";
    $html .= "\t<tr>\n".$headerstr."\t</tr>\n";
    $html .= $rowstr;
    $html .= "</table>\n";
    return $html;
  }
}


class BubbleGraphView extends GoogleGraphView {
  public function displayStructure($struct){
    if($struct instanceof RowDataStructure){
      if(!$struct->is_empty()){
        $matrix = $struct->getStructure();
        return $this->display2DMatrix($matrix);
      }
    }else{
      return false;
    }

  }

  public function display2DMatrix($matrix){
    $inner_view = new JSONView();
    $google_matrix = $inner_view->display($matrix);
    $vStepsize = $this->getVStepSize();
    $maxVAxis = $this->getMaxVAxis();
    $hStepsize = $this->getHStepSize();
    $maxHAxis =$this->getMaxHAxis();
    $gridlinesHAxis = ($maxHAxis/$hStepsize)+1;
    $gridlinesVAxis = ($maxVAxis/$vStepsize)+1;
    $unique_id = "bubble_chart".time().rand();
    $html = <<<EOT
      <script type="text/javascript">
        google.load("visualization", "1", {packages:["corechart"]});
        google.setOnLoadCallback(drawChart_$unique_id);
        function drawChart_$unique_id() {
          var data = google.visualization.arrayToDataTable(
            $google_matrix
          );

          var options = {
            hAxis: {
              title: 'Day of month',
              minValue: 0,
              maxValue: $maxHAxis,
              gridlines: {count: $gridlinesHAxis}
            },
            vAxis: {
              title: 'Resource',
              direction: -1,
              minValue: 0,
              maxValue: $maxVAxis,
              gridlines: {count: $gridlinesVAxis},
              minorGridlines: {count: 1}
            },
            sizeAxis : {maxValue: 148},
            legend: {position: 'top'},
            chartArea: {width: '90%', height: '90%'},
            bubble: {textStyle: {fontSize: 11}}

          };

          var chart = new google.visualization.BubbleChart(document.getElementById('$unique_id'));
          chart.draw(data, options);
        }
      </script>
      <div id="$unique_id" style="width: 1200px; height: 2000px;"></div>
EOT;
    return $html;
  }
}

class GTableView extends GoogleTableView {
  public function displayStructure($struct){
    if($struct instanceof RowDataStructure){
      if(!$struct->is_empty()){
        $matrix = $struct->getStructure();
        return $this->display2DMatrix($matrix);
      }
    }else{
      return false;
    }

  }

  protected function getColumnString(){
    $columns = array('Id'=>'number', 'Title'=>'string', 'Type'=>'string');
    $columnstr = "";
    foreach($columns as $column=>$type){
      $columnstr .= "data.addColumn('$type', '$column');";
    }
    return $columnstr;
  }

  public function display2DMatrix($matrix){
    $inner_view = new JSONView();
    $google_matrix = $inner_view->display($matrix);
    $unique_id = "table_chart".time().rand();
    $columnstr = $this->getColumnString();
    $html = <<<EOT
      <script type="text/javascript">
        google.load("visualization", "1", {packages:["table"]});
        google.setOnLoadCallback(drawTable_$unique_id);
        function drawTable_$unique_id() {
          var data = new google.visualization.DataTable();
          $columnstr
          data.addRows(
            $google_matrix
          );

           var table = new google.visualization.Table(document.getElementById('$unique_id'));
          table.draw(data, {showRowNumber: false});
        }
      </script>
      <div id="$unique_id"></div>
EOT;
    return $html;
  }
}
?>
