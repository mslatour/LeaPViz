<?php

include("settings.php");
include("controller/dbh.php");
include("controller/data_source.php");
include("controller/data_structure.php");
include("controller/data_view.php");
include("controller/component.php");
include("controller/ui_element.php");

$db = new MySQLiHandler(
  $SETTING_DB_HOST,
  $SETTING_DB_USER,
  $SETTING_DB_PASS,
  $SETTING_DB_DATABASE
);

switch($_GET['v']){
  case 'list':
    include("view/list.php");
    break;
  case 'resource_time_graph':
    include("view/resource_time_graph_view.php");
    break;
  default:
  case 'user_time_graph':
  include("view/user_time_graph_view.php");
  break;
}
?>
