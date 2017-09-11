<!DOCTYPE html>
<html>
<body>
<?php
$xml=simplexml_load_file("portiqo.xml") or die("Error: Cannot create object");
// echo "<pre>";
// print_r($xml);
$i=1;
foreach ($xml as $key => $value) {
	echo "$i &nbsp;&nbsp;&nbsp;&nbsp; <a target='_blank' href='".$value->loc."'>".$value->loc."</a>" ;
	echo "<br>";
	$i++;
}
echo "<br><br><br><br><br>";
$xml=simplexml_load_file("dev_portiqo.xml") or die("Error: Cannot create object");
$i=1;
foreach ($xml as $key => $value) {
	echo "$i &nbsp;&nbsp;&nbsp;&nbsp; <a target='_blank' href='".$value->loc."'>".$value->loc."</a>" ;
	echo "<br>";
	$i++;
}
?>
</body>
</html>