<?php
$resource_list = new ResourceList($db);
$resource_list->getStruct()->setRowModifier(function($row){
  return array(
    "Id"=>intval($row['id']),
    "Title"=>$row['title'],
    "Type"=>$row['type']
  );
});

$user_time_graph = new UserTimeGraph($db);
$user_time_graph->getView()->setVStepsize(2);
$user_time_graph->getView()->setMaxVAxis(156);
$user_time_graph->getView()->setHStepsize(1);
$user_time_graph->getView()->setMaxHAxis(32);
$user_time_graph->getStruct()->setHeaderRow(array("ID","Day","Student","Type","# Resources"));
$user_time_graph->getStruct()->setRowModifier(function($row){
  return array(
    "",
    intval(date("d", $row['timestamp'])),
    intval($row['user']),
    ($row['user']>1&&$row['user']<149?"Student":"Teacher"),
    intval($row['count'])
  );
});

$user_time_filter = $user_time_graph->getFilterComponent('user_time_filter');

if(isset($_POST)){
  $user_time_filter->process();
}

?>
<html>
  <head>
    <script type="text/javascript" src="https://www.google.com/jsapi"></script>
  </head>
  <body>
    <?php echo $user_time_filter->display(); ?>
    <?php echo $user_time_graph->display(); ?>
    <?php //echo $resource_list->display(); ?>
  </body>
</html>
