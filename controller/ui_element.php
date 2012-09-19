<?php
abstract class UIElement {
  private $components = array();

  public function addComponent($component, $label=null){
    if($label == null){
      $this->components[] = $component;
    }else{
      $this->components[$label] = $component;
    }
  }

  public function clear(){
    $this->components = array();
  }

  public function numberOfComponents(){
    return sizeof($this->components);
  }

  public function isEmpty(){
    return ($this->numberOfComponents()==0);
  }

  protected function getComponents(){
    return $this->components;
  }

  abstract public function display();
}

class TabbedContainer extends UIElement {
  private $unique_id;

  public function __construct(){
    $this->unique_id = rand().time();
  }

  protected function displayScript(){
    $deactivatestr = "";
    for($i = 0; $i < $this->numberOfComponents(); $i++){
      $deactivatestr .= "deactivate_".$this->unique_id."('container_".$i."_".$this->unique_id."');\n";
    }
    $html = <<<EOT
      <style>
        .show$this->unique_id {
          display: block;
          width: 100%;
          position: absolute;
          top: 35px;
        }

        .hide$this->unique_id {
          display: none;
          width: 100%;
          position: absolute;
          top: 35px;
        }

        .tabbed_container$this->unique_id {
          position: relative;
          width: 100%;
        }

        .tab$this->unique_id{
          width: 33%;
          height: 20px;
          font-size: 16px;
          font-weight: bold;
          padding: 5px 0px 5px 0px;
          text-align: center;
          cursor: hand;
          background: #eee;
          color: black;
          float: left;
        }
      </style>

      <script type='text/javascript'>
        function activate_$this->unique_id(container){
          $deactivatestr
          document.getElementById(container).setAttribute('class','show$this->unique_id');
        }
        
        function deactivate_$this->unique_id(container){
          document.getElementById(container).setAttribute('class','hide$this->unique_id');
        }
      </script>
EOT;
    return $html;
  }

  protected function displayContainers(){
    $ui = $this->unique_id;
    $html = "";
    $label_html = "";
    $index = 0;
    foreach($this->getComponents() as $label=>$component){
      $label_html .= "<a onClick='activate_$ui(\"container_${index}_$ui\")'".
        " class='tab$ui' href='#'>$label</a>";
      $html .= "<div id='container_${index}_$ui' class='"
        .($html==""?"show":"hide")."$ui'>";
      $html .= $component->display();
      $html .= "</div>";
      $index++;
    }
    return "<div class='tabbed_container$ui'>\n$label_html\n$html\n</div>";
  }

  public function display(){
    return $this->displayScript()."\n".$this->displayContainers();
  }
}
