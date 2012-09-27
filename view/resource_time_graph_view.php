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
$resource_time_graph->getView()->setHStepsize(.5);
$resource_time_graph->getView()->setMinHAxis(11);
$resource_time_graph->getView()->setMaxHAxis(24);
$resource_time_graph->getStruct()->setHeaderRow(array("ID","Day","Resource","Type","# Users"));
$resource_time_graph->getStruct()->setRowModifier(function($row){
  return array(
    "",
    floatval(date("H", $row['timestamp'])+((date("i", $row['timestamp'])/60))),
    intval($row['linkId']),
    $row['linkType'],
    intval($row['count'])
  );
});

$resource_time_filter = $resource_time_graph->getFilterComponent('resource_time_filter');
$resource_list->getSource()->setFilter($resource_time_filter);

if(isset($_POST)){
  $resource_time_filter->process();
}

$resource_time_filter->setValue("period", "1336453200-1336539599");

$tabs = new TabbedContainer();
$tabs->addComponent($resource_time_graph, 'Graph');
$tabs->addComponent($resource_time_filter, 'Filter');
$tabs->addComponent($resource_list, 'Resources list');

?>
<html>
  <head>
    <link rel='stylesheet' type='text/css' href='style/main.css' />
    <script type="text/javascript" src="https://www.google.com/jsapi"></script>
  </head>
  <body>
    <?php echo $tabs->display(); ?>
  </body>
</html>
