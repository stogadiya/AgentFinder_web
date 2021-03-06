<?php
	require_once('config.php');
?>
<html>
<head>
<style>
	.btn {
	-moz-box-shadow: inset 0px 1px 0px 0px #bbdaf7;
	-webkit-box-shadow: inset 0px 1px 0px 0px #bbdaf7;
	box-shadow: inset 0px 1px 0px 0px #bbdaf7;
	background-color: #FFFFFF;
	-moz-border-radius: 2px;
	-webkit-border-radius: 2px;
	border-radius: 2px;
	border: 1px solid #84bbf3;
	display: inline-block;
	color: #ffffff;
	font-family: arial;
	font-size: 15px;
	font-weight: bold;
	padding: 6px 24px;
	text-decoration: none;
	text-shadow: 1px 1px 0px #528ecc;
	}
	.btn:hover {
		background:-webkit-gradient( linear, left top, left bottom, color-stop(0.05, #990000), color-stop(1, #CC0000) );
		background:-moz-linear-gradient( center top, #990000 5%, #CC0000 100% );
		filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#990000', endColorstr='#CC0000');
		background-color:#990000;
	}
	.btn:active {
		position:relative;
		top:1px;
	}
	/* This imageless css button was generated by CSSButtonGenerator.com */

	.notifs {
	color: #FF0000;
	padding: 8px 35px 8px 14px;
	margin-bottom: 20px;
	text-shadow: 0 1px 0 rgba(255, 255, 255, 0.5);
	background-color: #fcf8e3;
	border: 1px solid #fbeed5;
	-webkit-border-radius: 4px;
	-moz-border-radius: 4px;
	border-radius: 4px;
	background-color: #FFFFFF;
	border-color: #FFCCCC;
		}	
	.notifsholder {
		display: block;
		margin: 0px auto;
		width: 95%;
		padding:0;
		}
</style>
</head>
<body style="font-family: Helvetica, Arial, sans serif">
<div class='notifsholder'>
	<div class="notifs">
		<p>Get directions to the nearest Agent from your current location using the buttons below: </p>	
	</div>
</div>

<?php

GLOBAL $flat, $flng, $n1lat, $n1lng, $n2lat, $n2lng;

function get_handle(){
	GLOBAL $flat, $flng, $n1lat, $n1lng, $n2lat, $n2lng;
	
	$cry = $_SERVER['SERVER_NAME'];
	$conn = @mysql_connect($db_host, $db_username, $db_password);
	if ( !is_resource($conn) ){
		echo $cry."\n\n";
		return false;
		}	
	mysql_select_db($db_name);
	return $conn;
}

if (isset($_GET['prev'])){
	echo  "<p>Agent Finder used the last GPS position you were on. If you have moved, press Back and use the Refresh Agents button</p>";
	}

$conn = get_handle(); //mysql_connect("localhost", "root", "");
if (!is_resource($conn)){
	echo "This isn't good, we are experiencing difficulties. Try again later.";
	exit();
	
	}
mysql_select_db($db_name, $conn);

$lat = @$_GET['latitude'];
$long = @$_GET['longitude'];
$flat = $lat;
$flng = $long;
$loc = array("latitude"=>$lat, "longitude"=>$long);

$res = find_closest($loc, $conn);

if ( ($lat == 0) AND ($long == 0)){
	echo "<p><i>Sorry but your phone's GPS says you're at 0' 0'. Somewhere in the Atlantic Ocean. This happens usually when there's an interruption of sorts when getting coordinates from the GPS satellites. Refresh GPS maybe?</i>";
	}

print_locs($res);



function logic_distance($distkm){
	$out = "";
	if ($distkm < 1){
		$nudist = round($distkm, 3);
		$nudist *= 100;
		$out = $nudist." metres";
		}
		
	else{
		$nudist = round($distkm, 1);
		$out = $nudist." km";
		}
		
	return $out;
	}

function print_locs($locs){
	GLOBAL $flat, $flng, $n1lat, $n1lng, $n2lat, $n2lng;
	$maps_url = "http://agentfinder.co/directions.php"; 
	$mapsq1 = $maps_url."?flat=".urlencode($flat)."&flng=".urlencode($flng)."&tlat=".urlencode($n1lat)."&tlng=".urlencode($n1lng);
	$mapsq2 = $maps_url."?flat=".urlencode($flat)."&flng=".urlencode($flng)."&tlat=".urlencode($n2lat)."&tlng=".urlencode($n2lng);
	//var_dump($locs);
	$str1 = "<p><hr style='width: 100%; border: 1px solid gray;' /><h3>Closest Agent is ".$locs[0]['name']."</h3></p>";
	$str1 .= "<p><b><a class='btn' href='".$mapsq1."'>Get directions on Google Maps</a></b></p>";
	$str1 .= "<p><b>How far? (Bird flight distance)</b><br/>About ".logic_distance($locs[0]['distance'])." from you</p>";
	$str1 .= "<p><b>Address</b><br/>".$locs[0]['address']."</p>";
	$str1 .= "<p><b>Type: </b><br/>".$locs[0]['type']."</p>";			
	
	$str2 = "<p><hr style='width: 100%; border: 1px solid gray;' /><h3>Second closest agent is ".$locs[1]['name']."</h3></p>";
	$str2 .= "<p><a class='btn' href='".$mapsq2."'>Get directions on Google Maps</b></a></p>";
	$str2 .= "<p><b>How far? (Bird flight distance)</b><br/>About ".logic_distance($locs[1]['distance'])." from you</p>";
	$str2 .= "<p><b>Address</b><br/>".$locs[1]['address']."</p>";
	$str2 .= "<p><b>Type</b><br/>".$locs[1]['type']."</p>";
	$str2 .= "<p><b>Agent Type</b><br/>".$locs[1]['agent_type_id']."</p>";
	
	
	echo $str1;
	echo $str2;
	
	}

function find_closest($fr, $conn, $debug = false){
	GLOBAL $flat, $flng, $n1lat, $n1lng, $n2lat, $n2lng;
	if ($debug) echo '<p>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>></p>';
	$q = "SELECT * FROM markers";
	$nearest1d = -1;
	$nearest1 = -1;
	$nearest2d = -1;
	$nearest2 = -1;
	$res = mysql_query($q, $conn);
	while($r = mysql_fetch_assoc($res)){
		
		$diff = getDistance($fr['latitude'], $fr['longitude'], $r['lat'], $r['lng']);
		
		if ($nearest1d == -1){
			$nearest1d = $diff;
			$nearest1 = $r['id'];
			}
		else{
				if ($nearest2d == -1){
					$nearest2d = $diff;
					$nearest2 = $r['id'];
					}
				
			}
		
		if ($diff < $nearest1d){
			$nearest2d = $nearest1d;
			$nearest2 = $nearest1;
			$nearest1d = $diff;
			$nearest1 = $r['id'];
			if ($debug) echo "<br/>Nearest 1 take over ".$diff."<br/>";
			}
		else{
			if ($diff < $nearest2d){
				$nearest2d = $diff;
				$nearest2 = $r['id'];
				if ($debug) echo "<br/>Nearest 2 takeover ".$diff."<br/>";
				}
			}
	}	
	
	if ($debug) echo '<p>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>></p>';
	
	$out = array();
	
	$n1q = "SELECT * FROM markers WHERE id='".$nearest1."' LIMIT 1";
	$n1r = mysql_query($n1q, $conn);
	$n1 = mysql_fetch_assoc($n1r);
	$n1['distance'] = $nearest1d;
	$near1 = $n1;
	$n1lat = $near1['lat'];
	$n1lng= $near1['lng'];
		
	$n2q = "SELECT * FROM markers WHERE id='".$nearest2."' LIMIT 1";
	$n2r = mysql_query($n2q, $conn);
	$n2 = mysql_fetch_assoc($n2r);
	$n2['distance'] = $nearest2d;
	$near2 = $n2;
	$n2lat = $near2['lat'];
	$n2lng= $near2['lng'];
	
	
	$out[] = $near1;
	$out[] = $near2;
	return $out;
	
	
}


function getDistance($lt1, $ln1, $lt2, $ln2){
	$r = 6371;
	$dLat = deg2rad($lt2 - $lt1);
	$dLon = deg2rad($ln2 - $ln1);
	$a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lt1)) * cos(deg2rad($lt2)) * sin($dLon/2) * sin($dLon/2);
	$c = 2 * asin(sqrt($a));
	$d = $r * $c;
	return $d;
}
?>
</body></html>