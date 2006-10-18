<?php

define('ROOT', '../../..');
//require ROOT . '/lib/include.php';
require ROOT . '/lib/config.php';
include_once ROOT . '/config.php';
require ROOT . '/lib/database.php';

include ("src/jpgraph.php");
include ("src/jpgraph_scatter.php");
include ("src/jpgraph_line.php");

if ((isset($_REQUEST['owner'])) && is_numeric($_REQUEST['owner'])) {
	$owner = intval($_REQUEST['owner']);
}

requireComponent('Tattertools.Model.Statistics');
$row = Statistics::getWeeklyStatistics();

$row = array_reverse($row);

// Y�� �迭
$pos = 0;
for ($i = 7; $i >= 0; $i--) {
    $week = strtotime("-".$i." day");
    $xdata[] = date('d', $week);
	if ( !isset($row[$pos]) || (date('d', $week) != substr($row[$pos]["date"], -2))) {
        $ydata[] = 0;
    } else {
		$ydata[] = $row[$pos++]["visits"];
    }
}

// Create the graph. These two calls are always required
$graph = new Graph(175,120,"auto"); //�׷����� ũ�⸦ ����
$graph->img->SetAntiAliasing();
$graph->SetMargin(0,10,5,0);
$graph->SetFrame(false);
$graph->SetMarginColor('white');
$graph->SetScale("textlin");
$graph->xaxis->SetTickLabels($xdata);
$graph->xaxis->SetColor("gray7");
$graph->xaxis->title->Set(date('Y-m-d H:i:s', strtotime("now")));
$graph->xaxis->title->SetColor("gray7");
$graph->xaxis->title->SetFont(FF_FONT0);
$graph->xaxis->SetFont(FF_FONT0);
$graph->xaxis->HideTicks();
$graph->yaxis->title->Set("Hits");
$graph->yaxis->title->SetColor("gray7");
$graph->yaxis->title->SetFont(FF_FONT0);
$graph->yaxis->HideZeroLabel();
$graph->ygrid->SetFill(true,'white','#F7F7F7');
$graph->xgrid->Show();
$graph->yaxis->SetColor("white");
$graph->yaxis->SetFont(FF_FONT0);

// Create the linear plot
$lineplot = new LinePlot($ydata);
$lineplot->SetColor("gray7");
$lineplot->value->SetColor("gray5");
$lineplot->value->Show();
$lineplot->value->SetFormat("%d");
$lineplot->value->SetFont(FF_FONT0);
$lineplot->value->SetAlign("left");
$lineplot->mark->SetColor("red");
$lineplot->mark->SetWidth(1);
$lineplot->mark->SetTYPE(MARK_FILLEDCIRCLE,1);
$lineplot->SetCenter();

// Add the plot to the graph
$graph->Add($lineplot);

// Display the graph
$graph->Stroke();

?>
