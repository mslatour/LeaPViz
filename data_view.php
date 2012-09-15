<?php

class TableView extends DataView {

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
    // Display headers
    foreach($matrix as $row=>$t){
      $rowstr .= "\t<tr>\n\t\t<td>$row</td>\n";
      foreach($t as $col=>$value){
        if($headerstr == "") $headers[] = $col;
        $rowstr .= "\t\t<td>".$value."</td>\n";
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
  public function setCSSClass($class){
    $this->classname = $class;
  }
  public function getCSSClass(){
    return $this->classname;
  }
  abstract protected function displayStructure($data);
  abstract protected function display2DMatrix($data);
}

?>
