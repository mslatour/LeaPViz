<?php

include("settings.php");
include("dbh.php");
include("data_source.php");
include("data_structure.php");
include("data_view.php");

$db = new MySQLiHandler(
  $SETTING_DB_HOST,
  $SETTING_DB_USER,
  $SETTING_DB_PASS,
  $SETTING_DB_DATABASE
);

$source = new LAProxyDataSource($db);
$source->filterByUsers(array(139,150));
$data = $source->getStats();
echo $source->getQuery();
$struct = new MatrixDataStructure();
$struct->setYField("timestamp");
$struct->setXField("user");
$struct->setValueField("link");
$struct->setMatrixLayout(MatrixDataStructure::YXLayout);
$struct->loadData($data);
$view = new TableView();
$view->setCSSClass("tabular");
$table = $view->display($struct);
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
<?php echo $table; ?>
</body>
</html>
