<?php

// this is a simple relay to temporarily work around CORS

if (isset($_GET['action'])) {
	$params = http_build_query($_GET);
	$url = "https://api.wikitree.com/api.php?$params";
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US)");
	$response = curl_exec($ch);
	curl_close($ch);
	echo $response;
} else {
	echo "search";
}

?>