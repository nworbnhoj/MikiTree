<?php
	if (isset($_GET['key'])){
		$root_key = $_GET['key'];
		$relatives = fetchRelatives($root_key);
		$siblings = $relatives['siblings'];
		$children = $relatives['children'];
		$ancestors = fetchAncestors($root_key);
	    $people = array_merge($children, $siblings, $ancestors);
	    $people = rekey($people, "Id");
		$people = inferGender($people);
	    $root_id = findId($people, $root_key);	    
		$root = $people[$root_id];
		$photo = fetchPhoto($root['PhotoData']['path'], $root['Photo']);
		$bio = tidyHtml($root['bioHTML']);
	} else {
		init();
        $people = HELP_ANCESTORS;
        $siblings = HELP_SIBLINGS;
        $children = HELP_CHILDREN;
        $root_id = 0;
		$root_key = $people[$root_id];
        $bio = HELP_BIO;
        $photo = "";
	}
	$fractal = fractal($people, $root_id, 0, 5, $siblings, $children);
?>

<!DOCTYPE html>

<html lang='[lang]'>
<head>
    <meta charset="UTF-8">    
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo($root_key) ?></title>
    <link href='https://fonts.googleapis.com/css?family=Pontano+Sans' rel='stylesheet' type='text/css'>
    <link rel="stylesheet" type="text/css" href="mikitree.css">
    <script>
		function toggleHelp(event) {
		 	var help = document.querySelectorAll('.help');
		 	var name = document.querySelectorAll('.name');
		 	for (let i = 0; i < help.length; i++) {
                help[i].classList.toggle('hide');
            }
		 	for (let i = 0; i < help.length; i++) {
                name[i].classList.toggle('hide');
            }
		}
		function load(event) {
            var div = event.currentTarget;
            div.textContent = '';
            div.className += ' spin';
            window.location.href="index.php?key="+div.id;
		}
    </script>
</head>

<body>
	<div>	<?php echo $fractal; ?>    </div>
	<?php echo $photo; ?>
	<div class='bio'>
		<br>
		<br>
		<?php echo($bio); ?>
	</div>

	
</body>

</html>




<?php

function tidyHtml($html){
	$dom = new DOMDocument('1.0', 'UTF-8');
	$internalErrors = libxml_use_internal_errors(true);
	$dom->loadHTML($html);
	$dom = removeNodes($dom, "table");
	$dom = removeNodes($dom, "div");
	$html = $dom->saveHTML();
	$html = preg_replace("{href=\"\/wiki\/(\w*-\d*)\"}", "href='/index.php?key=$1'", $html);
	return $html;
}


function removeNodes($dom, $name){	
    $nodes = $dom->getElementsByTagName($name);
    while ($nodes->length > 0)
    {
        $node = $nodes->item(0);
        $node->parentNode->removeChild($node);
    }
    return $dom;
}


function wiki($key){
	return "<div class='wiki'>
	    <a class='wiki' href='https://www.wikitree.com/wiki/$key' target='_blank'>WikiTree</a>
			<form class='wiki' action='/index.php'>
			   <input class='wiki' type='text' placeholder='WikiTree ID' name='key' pattern='\w{2,20}-\d{1,6}'>
			   <input type='submit' value='Go'>
			</form>
	</div>";
}


function findId($people, $name){	
	$id = 0;
	foreach ($people as $key => $person) {
		// find the root_id corresponding to the root_key
		if (strcmp($person['Name'], $name) == 0){
			$id = $key;
			break;
		}
	}
	return $id;
}


function fetchPhoto($path, $name){
	$save_as = "photo/$name";

	if (!file_exists($save_as)){

		$image_url = "https://www.wikitree.com$path";

		$ch = curl_init($image_url);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US)");
		$raw_data = curl_exec($ch);
		curl_close($ch);
		$fp = fopen($save_as, 'w');
		fwrite($fp, $raw_data);
		fclose($fp);
	}
	return "<img class='photo' src='$save_as'>";
}



function fractal($people, $id, $gen, $depth=3, $siblings=array(), $children=array()){
	if (!isset($people[$id])) {
		return "<div class='person'><br><br></div>";
	}

    $person = $people[$id];
    $key = $person['Name'];
    $name_family = $person['LastNameAtBirth'];
    $name_first = isset($person['RealName']) ? $person['RealName'] : "" ;
    $gender = isset($person['Gender']) ? strtolower($person['Gender']) : "" ;
    $is_living = $person['IsLiving'];
    $year_birth = isset($person['BirthDate']) ? substr($person['BirthDate'], 0, 4) : "" ;
    $year_death = isset($person['DeathDate']) ? substr($person['DeathDate'], 0, 4) : "" ;
    $years = $is_living ? $person['BirthDateDecade'] :  "$year_birth-$year_death" ;
    $onclick = "onclick=load(event)";
    $wiki = "";
    $root = "";
    $p = "<span class='upper'>$name_family</span>";
    $p = $gen < 6 ? "$p $name_first" : $p ;
    $p = $gen < 3 ? "$p<br>$years" : $p ;
    if ($gen < 1) {
    	$root='root';
		$s = links($siblings);
		$c = links($children);
		$p = "$s$p$c";
		$wiki = wiki($key);
            $onclick = "";
    }

    $role = $gender == "male" ? "father" : "mother" ; 

	$tab = str_repeat("\t", $gen);
    $div_person =
"$tab<div class='person $root $gender g$gen' id='$key' $onclick>
$tab\t<div class='name'>$p</div>
$tab\t$wiki
$tab</div>";


	if ($gen >= $depth){
		return $div_person;
	}

    $id_father = $person['Father'];
	$div_father = fractal($people, $id_father, $gen+1, $depth);

    $id_mother = $person['Mother'];
	$div_mother = fractal($people, $id_mother, $gen+1, $depth);

	$orientation = $gen % 2 == 0 ?  "horizontal" : "vertical" ;
    $div = 
"$tab<div class='family $orientation'>
$div_father
$div_person
$div_mother
$tab</div>";
    return $div;
}


function onclick($key){
	return "onclick='window.location.href=\"index.php?key=$key\"'";
}



function links($people){
	$links = "";
	foreach($people as $key => $person){
        $name = $person['RealName'];
	    $key = $person['Name'];
		$links = "$links <a href='index.php?key=$key'>$name</a>";
	}
	return "<div class='links'>$links</div>";
}


function rekey($person, $newKey){
	$rekeyed = array();
    foreach ($person as $key => $val) {
        $rekeyed[$val[$newKey]] = $val;
	}
	return $rekeyed;
}


function fetchAncestors($key){
	$ancestors = array();
    $fields = "Id,Name,LastNameAtBirth,RealName,MiddleName,MiddleInitial,Father,Mother,BirthDate,DeathDate,BirthDateDecade,BirthLocation,DeathLocation,Gender,Photo,PhotoData,Bio,IsLiving";
	$url = "https://api.wikitree.com/api.php?action=getAncestors&bioFormat=both&key=$key&fields=$fields";
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	$response = curl_exec($curl);
    if($e = curl_error($curl)) {
	    echo $e;
	} else {
	    $json_data = json_decode($response, true);
	    $ancestors = $json_data[0]['ancestors'];
	}
	curl_close($curl);
	return $ancestors;
} 


function fetchRelatives($key){
	$relatives = array();
    $fields = "Id,Name,LastNameAtBirth,RealName,MiddleName,MiddleInitial,Father,Mother,BirthDate,DeathDate,BirthDateDecade,BirthLocation,DeathLocation,Gender,Photo,PhotoData,IsLiving,Bio";
	$url = "https://api.wikitree.com/api.php?action=getRelatives&keys=$key&getParents=1&getSiblings=1&getChildren=1&getSpouses=1&fields=$fields";
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	$response = curl_exec($curl);
    if($e = curl_error($curl)) {
	    echo $e;
	} else {    
	    $json_data = json_decode($response, true);
	    $data = $json_data[0]['items'][0]['person'];
        $relatives['siblings'] = $data['Siblings'];
	    $relatives['children'] = $data['Children'];
	}
	curl_close($curl);
	return $relatives;
} 


function inferGender($people){
    // infer private fathers are male and mothers are female
	foreach ($people as $key => $person) {		
		$father_id = $person['Father'];
		if (isset($father_id)) {
			if (isset($people[$father_id])) {
		        $people[$father_id]['Gender'] = 'Male';
			}
		}		
		$mother_id = $person['Mother'];
		if (isset($mother_id)) {
			if (isset($people[$mother_id])) {
		        $people[$mother_id]['Gender'] = 'Female';
			}
		}
	}
	return $people;
}


function init() {
	define ("HELP_ANCESTORS", [
	    "0" => array ("RealName" => "WikiTree Profile", "Father"=>"1", "Mother"=>"2", "Gender"=>"Male"),
	    "1" => array ("RealName" => "Father", "Father"=>"3", "Mother"=>"4", "Gender"=>"Male"),
	    "2" => array ("RealName" => "Mother", "Father"=>"3", "Mother"=>"4", "Gender"=>"Female"),
	    "3" => array ("RealName" => "Grand Father", "Father"=>"5", "Mother"=>"6", "Gender"=>"Male"),
	    "4" => array ("RealName" => "Grand Mother", "Father"=>"5", "Mother"=>"6", "Gender"=>"Female"),
	    "5" => array ("RealName" => "Great Grand Father", "Father"=>"7", "Mother"=>"8", "Gender"=>"Male"),
	    "6" => array ("RealName" => "Great Grand Mother", "Father"=>"7", "Mother"=>"8", "Gender"=>"Female"),
	    "7" => array ("RealName" => "GGGF", "Father"=>"9", "Mother"=>"10", "Gender"=>"Male"),
	    "8" => array ("RealName" => "GGGM", "Father"=>"9", "Mother"=>"10", "Gender"=>"Female"),
	    "9" => array ("RealName" => "GGGGF", "Father"=>"11", "Mother"=>"12", "Gender"=>"Male"),
	    "10" => array ("RealName" => "GGGGM", "Father"=>"11", "Mother"=>"12", "Gender"=>"Female"),
	    "11" => array ("RealName" => "GGGGGF", "Father"=>"13", "Mother"=>"14", "Gender"=>"Male"),
	    "12" => array ("RealName" => "GGGGGM", "Father"=>"13", "Mother"=>"14", "Gender"=>"Female"),
	]);


	define ("HELP_SIBLINGS", [
	    array ("RealName" => "siblings"),
	    array ("RealName" => "brother"),
	    array ("RealName" => "sister"),
	]);


	define ("HELP_CHILDREN", [
	   ["RealName" => "children"],
	   ["RealName" => "son"],
	   ["RealName" => "daughter"],
	]);


	define ("HELP_BIO", "</p>Enter a valid WikiTree ID to begin exploring.</p>
		<p>On the fractal family tree: <br>fathers are shown to the left or above<br>mothers are shown to the right or below</p>");
}

?>
