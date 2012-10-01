<?php
$user_list = new UserList($db);
$user_list->getStruct()->setRowModifier(function($row){
  return array(
    "Id"=>intval($row['id']),
    "Username"=>$row['username'],
    "Firstname"=>$row['firstname'],
    "Surname"=>$row['surname'],
    "Student"=>($row['student']==1)
  );
});

$user_time_graph = new UserTimeGraph($db);
$user_time_graph->getView()->setVStepsize(2);
$user_time_graph->getView()->setMaxVAxis(702);
$user_time_graph->getView()->setHStepsize(1);
$user_time_graph->getView()->setMaxHAxis(8);
$user_time_graph->getStruct()->setHeaderRow(array("ID","Day","Student","Type","# Resources"));
$user_time_graph->getStruct()->setRowModifier(function($row){
  return array(
    "",
    intval(date("N", $row['timestamp'])),
    intval($row['user']),
    ($row['student']==1?"Student":"Teacher"),
    intval($row['count'])
  );
});

$user_time_filter = $user_time_graph->getFilterComponent('user_time_filter');
$user_list->getSource()->setFilter($user_time_filter);


if(isset($_POST)){
  $user_time_filter->process();
}
$user_time_filter->setValue("period","1318827600-1319432399");

$user_time_graph->display();

$tabs = new TabbedContainer();
$tabs->addComponent($user_time_graph, 'Graph');
$tabs->addComponent($user_time_filter, 'Filter');
$tabs->addComponent($user_list, 'Users list');

?>
<html>
  <head>
    <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <link rel='stylesheet' type='text/css' href='style/main.css' />
  </head>
  <body>
    <?php echo $tabs->display(); ?>
  </body>
</html>
