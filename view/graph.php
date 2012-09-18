<?php
$doc_bubble_matrix = new GoogleGraphMatrix($db);
$doc_bubble_matrix->getStruct()->setHeaderRow(array("ID","Day","Document","Type","# Students"));
$doc_bubble_matrix->getStruct()->setRowModifier(function($row){
  return array(
    ("#".$row['count']),
    intval(date("d", $row['timestamp'])),
    intval($row['linkId']),
    "Link",
    intval($row['count'])
  );
});
//$doc_bubble_matrix->debug();

$bubble_filter = $doc_bubble_matrix->getFilterComponent('bubble_filter');

if(isset($_POST)){
  $bubble_filter->process();
}
?>
<html>
  <head>
    <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script type="text/javascript">
      google.load("visualization", "1", {packages:["corechart"]});
      google.setOnLoadCallback(drawChart);
      function drawChart() {
        var data = google.visualization.arrayToDataTable(
          <?php echo $doc_bubble_matrix->display(); ?>
        );

        var options = {
          title: 'Correlation between life expectancy, fertility rate and population of some world countries (2010)',
          hAxis: {title: 'Day'},
          vAxis: {title: 'Document'},
          chartArea: {left: 100, top: 10 },
          bubble: {textStyle: {fontSize: 11}}
        };

        var chart = new google.visualization.BubbleChart(document.getElementById('chart_div'));
        chart.draw(data, options);
      }
    </script>
  </head>
  <body>
    <?php echo $bubble_filter->display(); ?>
    <div id="chart_div" style="width: 1800px; height: 1000px;"></div>
  </body>
</html>
