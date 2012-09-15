<?php

include("settings.php");
include("dbh.php");
include("data_source.php");
include("data_structure.php");
include("data_view.php");
include("component.php");

$db = new MySQLiHandler(
  $SETTING_DB_HOST,
  $SETTING_DB_USER,
  $SETTING_DB_PASS,
  $SETTING_DB_DATABASE
);

$visited_docs = new VisitedDocumentList($db);
$visited_docs->filterByUsers(array(139,150));
?>
<!DOCTYPE html>
<html>
<head><title>Test</title></head>
<body>
<style>
.tabular table,tr,td{
  border: thin solid black;
}
</style>
<?php echo $visited_docs->display(); ?>
</body>
</html>
