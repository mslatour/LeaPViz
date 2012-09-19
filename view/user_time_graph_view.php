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
