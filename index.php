<?php

include("settings.php");
include("controller/dbh.php");
include("controller/data_source.php");
include("controller/data_structure.php");
include("controller/data_view.php");
include("controller/component.php");

$db = new MySQLiHandler(
  $SETTING_DB_HOST,
  $SETTING_DB_USER,
  $SETTING_DB_PASS,
  $SETTING_DB_DATABASE
);

include("view/list.php");
?>
