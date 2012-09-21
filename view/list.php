<?php
$visited_docs = new AggregatedDocumentList($db);
// Show the query that was performed
//$visited_docs->debug();
// Only show the range of users representing students.
$visited_docs->getFilterComponent()->setValue("users",range(2,148));
// Clear empty cells
$visited_docs->getStruct()->setEmptyValue("");

$visited_docs->getView()->setCSSClass("tabular");
$visited_docs->getView()->setRowLabelModifier(
  function($url){
    if(
      substr($url, 0, 7) == "BW1019_" && 
      substr($url, -5) == ".html"
    ){
      $url = "Ch. ".substr($url, 7,-5);
    }else if(
      substr($url, 0, 32) == "applets/geogebra/versie4/BW1019_" && 
      substr($url, -5) == ".html"
    ){
      $url = "Applet ".substr($url, 32,-5);
    }else if(
      substr($url, 0, 21) == "applets/Excel/BW1019_" && 
      substr($url, -5) == ".xlsx"
    ){
      $url = "Excel Applet ".substr($url, 21,-5);
    }else if(
      substr($url, 0, 16) == "pencasts/BW1019_" && 
      substr($url, -4) == ".pdf"
    ){
      $url = "Pencast ".substr($url, 16,-4);
    }
    return $url;
  }
);
$visited_docs_filter = $visited_docs->getFilterComponent("visited_docs_filter");
$visited_docs_filter->debug();
if(isset($_POST)){
  $visited_docs_filter->process();
}

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
<?php echo $visited_docs_filter->display(); ?>
<br /><br />
<?php echo $visited_docs->display(); ?>

<!--<textarea cols='50' rows='100'>
<?php //echo $tmp->display($visited_docs->getStruct()); ?>
</textarea>-->
</body>
</html>
