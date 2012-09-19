<?php
$resource_list = new ResourceList($db);
$resource_list->getStruct()->setRowModifier(function($row){
  return array(
    "Id"=>intval($row['id']),
    "Title"=>$row['title'],
    "Type"=>$row['type']
  );
});

$resource_time_graph = new ResourceTimeGraph($db);
$resource_time_graph->getView()->setVStepsize(2);
$resource_time_graph->getView()->setMaxVAxis(120);
$resource_time_graph->getView()->setHStepsize(1);
$resource_time_graph->getView()->setMaxHAxis(32);
$resource_time_graph->getStruct()->setHeaderRow(array("ID","Day","Resource","Type","# Students"));
$resource_time_graph->getStruct()->setRowModifier(function($row){
  return array(
    "",
    intval(date("d", $row['timestamp'])),
    intval($row['linkId']),
    $row['linkType'],
    intval($row['count'])
  );
});

$resource_time_filter = $resource_time_graph->getFilterComponent('resource_time_filter');

if(isset($_POST)){
  $resource_time_filter->process();
}

?>
<html>
  <head>
    <script type="text/javascript" src="https://www.google.com/jsapi"></script>
  </head>
  <body>
    <?php echo $resource_time_filter->display(); ?>
    <?php echo $resource_time_graph->display(); ?>
    <?php echo $resource_list->display(); ?>
  </body>
</html>
