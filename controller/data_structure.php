<?php

abstract class DataStructure {
  abstract public function loadData($data);
  abstract public function getStructure();
  abstract public function clear();
  abstract public function is_empty();
}


class MatrixDataStructure extends DataStructure {
  const XYLayout = 0;
  const YXLayout = 1;

  private $empty_value = "";

  private $x_field = null;
  private $y_field = null;
  private $value_field = null;

  private $matrix = null;

  private $layout = MatrixDataStructure::XYLayout;

  public function setEmptyValue($value){
    $this->empty_value = $value;
  }

  public function setXField($field){
    $this->x_field = $field;
  }

  public function setYField($field){
    $this->y_field = $field;
  }

  public function setValueField($field){
    $this->value_field = $field;
  }

  public function setMatrixLayout($layout){
    if($this->layout != $layout){
      $this->layoutMatrix($layout);
      $this->layout = $layout;
    }
  }

  public function loadData($data){
    $this->matrix = $this->extractMatrix($data);
    return $this->matrix;
  }

  public function getStructure(){
    return $this->matrix;
  }

  protected function layoutMatrix($layout){
    $matrix = $this->getStructure();
    if($matrix != null){
      $new_matrix = array();
      if(
        $this->layout == MatrixDataStructure::XYLayout &&
        $layout == MatrixDataStructure::YXLayout
      ){
        foreach($matrix as $x=>$t){
          foreach($t as $y=>$v){
            $new_matrix[$y][$x] = $v;
          }
        }
      }else if(
        $this->layout == MatrixDataStructure::YXLayout &&
        $layout == MatrixDataStructure::XYLayout
      ){
        foreach($matrix as $y=>$t){
          foreach($t as $x=>$v){
            $new_matrix[$x][$y] = $v;
          }
        }
      }
      $this->matrix = $new_matrix;
    }
  }

  protected function extractMatrix($data){
    $x = $this->x_field;
    $y = $this->y_field;
    $v = $this->value_field;
    $matrix = array();
    if(
      $x != null && 
      $y != null && 
      $v != null &&
      is_array($data) &&
      sizeof($data) > 0 &&
      is_array($data[0]) &&
      array_key_exists($x, $data[0]) &&
      array_key_exists($y, $data[0]) &&
      array_key_exists($v, $data[0])
    ){
      $innerset = array();
      foreach($data as $entry){
        if($this->layout == MatrixDataStructure::YXLayout){
          $matrix[$entry[$y]][$entry[$x]] = $entry[$v];
          $innerset[$entry[$x]] = $this->empty_value;
        }else{
          $matrix[$entry[$x]][$entry[$y]] = $entry[$v];
        }
      }
      foreach($matrix as $outer=>$vector){
        $matrix[$outer] = $vector + $innerset;
        ksort($matrix[$outer]);
      }
      ksort($matrix);
    }
    return $matrix;
  }

  public function is_empty(){
    return ($this->matrix == null);
  }

  public function clear(){
    $this->x_field = null;
    $this->y_field = null;
    $this->value_field = null;
    $this->matrix = null;
  }
}

class RowDataStructure extends DataStructure {
  private $matrix = null;
  private $rmod = null;
  private $header = null;

  public function setRowModifier($mod){
    $this->rmod = $mod;
  }

  public function setHeaderRow($row){
    $this->header = $row;
  }

  public function getRowCount(){
    if($this->matrix != null){
      return sizeof($this->matrix);
    }else{
      return 0;
    }
  }
  
  public function loadData($data){
    $this->matrix = $this->extractMatrix($data);
    return $this->matrix;
  }

  protected function extractMatrix($data){
    $rmod = $this->rmod;
    $matrix = array();
    if($this->header != null){
      $matrix[] = $this->header;
    }
    if($rmod == null){
      $matrix = $data;
    }else{
      foreach($data as $row){
        $matrix[] = $rmod($row);
      }
    }
    return $matrix;
  }

  public function getStructure(){
    return $this->matrix;
  }
  
  public function is_empty(){
    return ($this->matrix == null);
  }

  public function clear(){
    $this->matrix = null;
    $this->rmod = null;
    $this->header = null;
  }
}
?>
