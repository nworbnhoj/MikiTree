	<?php
if (isset($_GET['key'])) {
	$root_key = $_GET['key'];
	$people = fetchFamily($root_key);
	$root_id = findId($people, $root_key);
	if ($root_id) {
		$root = $people[$root_id];
		$photo = isset($root['PhotoData'], $root['PhotoData']['path']) ? fetchPhoto($root['PhotoData']['path'], $root['Photo']) : "";
		$bio = tidyHtml($root['bioHTML']);
	}
}

if (!isset($root)) {
	init();
	$people = HELP_FAMILY;
	$root_id = 0;
	$root = $people[$root_id];
	$bio = HELP_BIO;
	$photo = fetchPhoto("", $root['Photo']);
}
$ancestors = fractal($root_id, $people, 0, 6);
$descendants = branch($root, $people);
?>

	<!DOCTYPE html>

	<html lang='[lang]'>
	<head>
	    <meta charset="UTF-8">
	    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	    <title><?php echo ($root_key) ?></title>
	    <link href='https://fonts.googleapis.com/css?family=Pontano+Sans' rel='stylesheet' type='text/css'>
	    <link rel="stylesheet" type="text/css" href="tree.css">
	    <script src="tree.js"></script>
	</head>

	<body  onresize='resize_chutes(event)'>
		<div id='ancestors'>	<?php echo $ancestors; ?>    </div>
		<?php echo $photo; ?>
		<div id='descendants'>	<?php echo $descendants; ?>    </div>
		<div id='bio' class='bio'>
			<?php echo ($bio); ?>
		</div>
	</body>

	</html>




	<?php

//https://stackoverflow.com/questions/3523409/domdocument-encoding-problems-characters-transformed
//https://www.php.net/manual/en/domdocument.savehtml.php
function tidyHtml($html) {
	if (empty($html)) {
		return "";
	}
	$dom = new DOMDocument('1.0', 'UTF-8');
	$html = '<?xml version="1.0" encoding="UTF-8"?>' . $html;
	$dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
	$dom = removeNodes($dom, "table");
	$dom = removeNodes($dom, "div");
	$body = $dom->saveHTML();
	$body = preg_replace("{href=\"\/wiki\/(\w*-\d*)\"}", "href='/index.php?key=$1'", $body);
	return $body;
}

function removeNodes($dom, $name) {
	$nodes = $dom->getElementsByTagName($name);
	while ($nodes->length > 0) {
		$node = $nodes->item(0);
		$node->parentNode->removeChild($node);
	}
	return $dom;
}

function wiki($key) {
	return
		"<div class='wiki'>
			All data drawn from the superb <a class='wiki' href='https://www.wikitree.com/wiki/$key' target='_blank'>WikiTree</a>
			<form class='wiki' action='/index.php'>
			   <input class='wiki' type='text' placeholder='WikiTree ID' name='key' pattern='\w{2,20}-\d{1,6}'>
			   <input type='submit' value='Go'>
			</form>
		</div>";
}

function findId($people, $key) {
	foreach ($people as $id => $person) {
		$k = isset($person['Name']) ? $person['Name'] : false;
		if ($k == $key) {
			return $id;
		}
	}
}

function fetchPhoto($path, $name) {
	$save_as = "photo/$name";

	if (!file_exists($save_as)) {

		$image_url = "https://www.wikitree.com$path";

		$ch = curl_init($image_url);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US)");
		$raw_data = curl_exec($ch);
		curl_close($ch);
		$fp = fopen($save_as, 'wb') or die(print_r(error_get_last(), true));
		fwrite($fp, $raw_data);
		fclose($fp);
	}
	return "<img class='photo' src='$save_as'>";
}

function fractal($id, $people, $gen, $depth = 3) {
	if (empty($id) || !isset($people[$id])) {
		return "<div class='missing person'><br><br></div>";
	}

	$person = $people[$id];
	switch ($gen) {
	case '5':$div_person = person_div($person, "l", "g5");
		break;
	case '4':$div_person = person_div($person, "fl", "g4");
		break;
	case '3':$div_person = person_div($person, "fil", "g3");
		break;
	case '2':$div_person = person_div($person, "fmlyv", "g2");
		break;
	case '1':$div_person = person_div($person, "fmly", "g1");
		break;
	case '0':$div_person = root_div($person, $people);
		break;
	default:$div_person = person_div($person, "l", "g$gen");
		break;
	}

	// unwind recursion
	if ($gen >= $depth) {
		return $div_person;
	}

	$id_father = isset($person['Father']) ? $person['Father'] : null;
	$div_father = fractal($id_father, $people, $gen + 1, $depth);

	$id_mother = isset($person['Mother']) ? $person['Mother'] : null;
	$div_mother = fractal($id_mother, $people, $gen + 1, $depth);

	$orientation = $gen % 2 == 0 ? "fractal_h" : "fractal_v";
	$div =
		"<div class='grid $orientation'>
			$div_father
			$div_person
			$div_mother
		</div>";
	return $div;
}

function branch($head, $people, $gen = 0, $branch_index = 0) {
	$head_id = $head['Id'];
	$head_div = person_div($head);

	$union_divs = "";
	$svgs = "";
	$next_gen = $gen + 1;
	$next_gen_divs = "";
	$spouses = isset($head["Spouses"]) ? $head["Spouses"] : array();
	foreach ($spouses as $spouse_id => $child_ids) {
		if ($spouse_id == $head_id) {
			continue;
		}

		$union_divs = $union_divs . union_div($head_id, $spouse_id, $people, $gen);

		foreach ($child_ids as $index => $child_id) {
			$child = $people[$child_id];
			$branch = branch($child, $people, $next_gen, $index);
			$next_gen_divs = $next_gen_divs . $branch;

			$gender = isset($child['Gender']) ? strtolower($child['Gender']) : "";
			$svg = "<svg class='chute hide' branch='$index' xmlns='http://www.w3.org/2000/svg' width='100% ' height='6em'>
				    <polygon class='$gender' points='0,0 10,0 10,20 0,20' />
				</svg>";

			$svgs = $svgs . $svg;
		}
	}

	$hide = $gen > 0 ? "hide" : "";
	$branch =
		"<div class='grid generation $hide' gen='$gen' branch='$branch_index'>
	        <div class='grid generation_h'unions> $union_divs </div>
            <div class='grid generation_h chutes'> $svgs </div>
	        <div class='grid generation_h next_gen' gen='$next_gen'> $next_gen_divs </div>
	    </div>";

	return $branch;
}

function union_div($head_id, $spouse_id, $people, $gen) {
	$spouse = $spouse_id != "unknown" ? $people[$spouse_id] : array("LastNameAtBirth" => "unknown");
	$name_family = $spouse['LastNameAtBirth'];
	$name_first = isset($spouse['RealName']) ? $spouse['RealName'] : "";
	$gender = isset($spouse['Gender']) ? strtolower($spouse['Gender']) : "";
	$spouse_div = person_div($spouse);

	$siblings_div = "";
	$children = $spouse["Spouses"][$head_id];
	foreach ($children as $index => $child_id) {
		$siblings_div = $siblings_div . child_div($child_id, $people, $gen, $index);
	}
	return
		"<div class='grid union'>
		    $spouse_div
		    <div class='grid siblings'>$siblings_div</div>
		</div>";
}

function person_div($person, $flags = "fily", $class = "") {
	$key = isset($person['Name']) ? $person['Name'] : "";
	$gender = isset($person['Gender']) ? strtolower($person['Gender']) : "";
	$name_div = name_div($person, $flags);
	return
		"<div class='person $gender $class' id='$key' onclick='load(event)'>
	        $name_div
	    </div>";
}

function child_div($child_id, $people, $gen, $index, $flags = "filyv") {
	$child = $people[$child_id];
	$key = isset($child['Name']) ? $child['Name'] : "";
	$gender = isset($child['Gender']) ? strtolower($child['Gender']) : "";

	$name_div = name_div($child, $flags);

	$radio_button = radio_button($child, $people);

	$spouses_div = "";
	$spouses = isset($child['Spouses']) ? $child['Spouses'] : array();
	foreach ($spouses as $spouse_id => $grand_children) {
		$spouse = $people[$spouse_id];
		$spouses_div = $spouses_div . spouse_div($spouse, $people, $gen, $index);
	}
	$spouses_div = "<div class='grid spouses'>$spouses_div</div>";
	return
		"<div class='person child $gender' branch='$index' id='$key' onclick='load(event)'>
	        $name_div
	        $radio_button
	        $spouses_div
	    </div>";
}

function radio_button($child, $people) {
	if (!isset($child['Children']) || sizeof($child['Children']) == 0) {
		return "";
	}
	$kids = str_repeat("▯", sizeof($child['Children']));
	return "<button class='radio' gen='$gen' onclick='showBranch(event)'>$kids</button>";
}

function spouse_div($person, $gen, $index) {
	$key = isset($person['Name']) ? $person['Name'] : "";
	$gender = isset($person['Gender']) ? strtolower($person['Gender']) : "";
	$name_div = name_div($person, "l");
	return
		"<div class='person spouse $gender' id='$key' onclick='load(event)'>
	        $name_div
	    </div>";
}

function root_div($root, $people, $flags = "fmlyv") {
	$key = isset($root['Name']) ? $root['Name'] : "";
	$gender = isset($root['Gender']) ? strtolower($root['Gender']) : "";
	$siblings = isset($root['Siblings']) ? links($root['Siblings'], $people) : "";
	$name_div = name_div($root, $flags);
	$checked = $root['Id'] == "0" ? "checked" : "";

	return
		"<div class='person root $gender g0' id='$key'>
			<div class='wiki'>
				All data drawn from the superb <a class='wiki' href='https://www.wikitree.com/wiki/$key' target='_blank'>WikiTree</a>
				<form class='wiki' action='/index.php'>
				   <input class='wiki' type='text' placeholder='WikiTree ID' name='key' pattern='.{2,20}-\d{1,6}'>
				   <input type='submit' value='Go'>
				</form>
			</div>
			$name_div
			$siblings
			<button class='help $checked' onclick='help(event)'>HELP</button>
		</div>";
}

function name_div($person, $flags = "fily") {
	$name_family = isset($person['LastNameAtBirth']) ? $person['LastNameAtBirth'] : "?";
	$name_first = isset($person['RealName']) ? $person['RealName'] : "";
	$initial = isset($person['MiddleInitial']) ? $person['MiddleInitial'] : "";
	$initial = ($initial == ".") ? "" : $initial;
	$gender = isset($person['Gender']) ? strtolower($person['Gender']) : "";
	$privacy = isset($person['Privacy']) ? intval($person['Privacy']) : 60;
	$name_family = $privacy < 30 ? "🔒" : $name_family;

	$first = strpos($flags, 'f') === false ? "" : (isset($person['RealName']) ? $person['RealName'] : "");
	$middle = strpos($flags, 'i') === false ? "" : $initial;
	$middle = strpos($flags, 'm') === false ? $middle : (isset($person['MiddleName']) ? $person['MiddleName'] : $middle);
	$last = strpos($flags, 'l') === false ? "" : $name_family;
	$vertical = strpos($flags, 'v') !== false;

	$years = "";
	if (strpos($flags, 'y') !== false) {
		$year_birth = $person['BirthYear'] > 0 ? $person['BirthYear'] : "";
		$year_death = $person['DeathYear'] > 0 ? $person['DeathYear'] : "";
		$is_living = isset($person['IsLiving']) ? boolval($person['IsLiving']) : true;
		if ($is_living) {
			$decade = isset($person['BirthDateDecade']) ? $person['BirthDateDecade'] : false;
			$years = $decade ? "($decade)" : "";
		} else if ($vertical) {
			$years = "b.$year_birth<br>d.$year_death";
		} else {
			$years = "<span class='nowrap'>($year_birth-$year_death)</span>";
		}
		$years = $privacy < 30 ? "" : $years;
	}

	return $vertical ?
	"<div class='name'>$first $middle<br><b>$last</b><br><small>$years</small></div>" :
	"<div class='name'>$first $middle <b>$last</b> <small>$years</small></div>";
}

function links($ids, $people) {
	$links = "";
	foreach ($ids as $id) {
		$person = $people[$id];
		$name = $person['RealName'];
		$key = isset($person['Name']) ? $person['Name'] : "";
		$links = "$links <a href='index.php?key=$key'>$name</a>";
	}
	return "<div class='links'>$links</div>";
}

function rekey($person, $newKey) {
	$rekeyed = array();
	foreach ($person as $key => $val) {
		$rekeyed[$val[$newKey]] = $val;
	}
	return $rekeyed;
}

function fetchFamily($key) {
	$people = array();
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

	// get ancestors and descendants from WikiTree
	$fields = "Id,Name,LastNameAtBirth,RealName,MiddleName,MiddleInitial,Father,Mother,BirthDate,DeathDate,BirthDateDecade,BirthLocation,DeathLocation,Gender,IsLiving,HasChildren,NoChildren,Privacy";
	$url = "https://api.wikitree.com/api.php?action=getPeople&ancestors=6&descendants=5&keys=$key&nuclear=1&fields=$fields";

	curl_setopt($curl, CURLOPT_URL, $url);
	$response = curl_exec($curl);
	if ($e = curl_error($curl)) {
		echo $e;
		curl_close($curl);
		return $people;
	}
	$json_data = json_decode($response, true);
	$people = $json_data[0]['people'];

	// get root bio and photo from WikiTree
	$fields = "Id,Name,Bio,Photo,PhotoData";
	$url = "https://api.wikitree.com/api.php?action=getPeople&bioFormat=both&keys=$key&fields=$fields";
	curl_setopt($curl, CURLOPT_URL, $url);
	$response = curl_exec($curl);
	if ($e = curl_error($curl)) {
		echo $e;
		curl_close($curl);
		return $people;
	}
	curl_close($curl);

	$json_data = json_decode($response, true);
	$people_array = $json_data[0]['people'];
	$root_id = array_key_first($people_array);
	$root_person = $people_array[$root_id];

	$people = inferYears($people);
	usort($people, "byBirth");
	$people = rekey($people, 'Id');

	$people[$root_id]['Photo'] = isset($root_person['Photo']) ? $root_person['Photo'] : "";
	$people[$root_id]['PhotoData'] = isset($root_person['PhotoData']) ? $root_person['PhotoData'] : "";
	$people[$root_id]['bioHTML'] = isset($root_person['bioHTML']) ? $root_person['bioHTML'] : "";

	$people = inferChildren($people);
	$people = inferSiblings($people);
	$people = inferSpouses($people);

	return $people;
}

function byBirth($a, $b) {
	return $a["BirthYear"] > $b["BirthYear"];
}

function inferYears($people) {
	foreach ($people as &$person) {
		$person['BirthYear'] = isset($person['BirthDate']) ? intval(substr($person['BirthDate'], 0, 4)) : 0;
		$person['DeathYear'] = isset($person['DeathDate']) ? intval(substr($person['DeathDate'], 0, 4)) : 0;
	}
	return $people;
}

function inferChildren($people) {
	foreach ($people as $child_id => $child) {
		$father_id = isset($child['Father']) ? $child['Father'] : null;
		if (isset($people[$father_id])) {
			$people[$father_id]['Gender'] = 'Male';
			$people[$father_id]['Children'][$child_id] = $child_id;
		}
		$mother_id = isset($child['Mother']) ? $child['Mother'] : null;
		if (isset($people[$mother_id])) {
			$people[$mother_id]['Gender'] = 'Female';
			$people[$mother_id]['Children'][$child_id] = $child_id;
		}
	}
	return $people;
}

function inferSiblings($people) {
	foreach ($people as $parent_id => $parent) {
		if (isset($parent['Children'])) {
			foreach ($parent['Children'] as $child_id) {
				$people[$child_id]['Siblings'] = $parent['Children'];
				unset($people[$child_id]['Siblings'][$child_id]);
			}
		}
	}
	return $people;
}

function inferSpouses($people) {
	foreach ($people as $child_id => $child) {
		$father_id = isset($child['Father']) ? $child['Father'] : "unknown";
		$mother_id = isset($child['Mother']) ? $child['Mother'] : "unknown";
		$people[$father_id]['Spouses'][$mother_id][] = $child_id;
		$people[$mother_id]['Spouses'][$father_id][] = $child_id;
	}
	return $people;
}

function init() {
	define("HELP_FAMILY", [
		"10" => array("Id" => "10", "LastNameAtBirth" => "Grand-Son", "Father" => "6", "Mother" => "4", "Gender" => "Male", "Siblings" => [8, 9]),
		"9" => array("Id" => "9", "LastNameAtBirth" => "Grand-Daughter", "Father" => "6", "Mother" => "4", "Gender" => "Female", "Siblings" => [8, 10]),
		"8" => array("Id" => "8", "LastNameAtBirth" => "Grand-Daughter", "Father" => "7", "Mother" => "4", "Gender" => "Female", "Siblings" => [9, 10]),
		"7" => array("Id" => "7", "LastNameAtBirth" => "Son-in-law-2", "Father" => "", "Mother" => "", "Gender" => "Male", "Spouses" => [4], "Children" => [8]),
		"6" => array("Id" => "6", "LastNameAtBirth" => "Son-in-law-1", "Father" => "", "Mother" => "", "Gender" => "Male", "Spouses" => [4], "Children" => [9]),
		"5" => array("Id" => "5", "LastNameAtBirth" => "Daughter-in-law", "Father" => "", "Mother" => "", "Gender" => "Female", "Spouses" => [2]),
		"4" => array("Id" => "4", "LastNameAtBirth" => "Daughter", "Father" => "0", "Mother" => "1", "Gender" => "Female", "Siblings" => [2, 3], "Spouses" => [6, 7], "Children" => [8, 9, 10]),

		"3" => array("Id" => "10", "LastNameAtBirth" => "Son", "Father" => "0", "Mother" => "1", "Gender" => "Male", "Siblings" => [2, 4]),
		"2" => array("Id" => "2", "LastNameAtBirth" => "Son", "Father" => "0", "Mother" => "1", "Gender" => "Male", "Siblings" => [3, 4], "Spouses" => [5]),
		"1" => array("Id" => "1", "LastNameAtBirth" => "Wife", "Father" => "", "Mother" => "", "Gender" => "Female", "Children" => [2, 3, 4], "Spouses" => [0]),
		"0" => array("Id" => "0", "LastNameAtBirth" => "WikiTree Profile", "Father" => "-1", "Mother" => "-2", "Gender" => "Male", "Children" => [2, 3, 4], "Spouses" => [1], "Siblings" => [-20, -21, -22], "Photo" => "help.webp"),
		"-1" => array("Id" => "-1", "LastNameAtBirth" => "Father", "Father" => "-3", "Mother" => "-4", "Gender" => "Male"),
		"-2" => array("Id" => "-2", "LastNameAtBirth" => "Mother", "Father" => "-3", "Mother" => "-4", "Gender" => "Female"),
		"-3" => array("Id" => "-3", "LastNameAtBirth" => "Grand Father", "Father" => "-5", "Mother" => "-6", "Gender" => "Male"),
		"-4" => array("Id" => "-4", "LastNameAtBirth" => "Grand Mother", "Father" => "-5", "Mother" => "-6", "Gender" => "Female"),
		"-5" => array("Id" => "-5", "LastNameAtBirth" => "Great Grand Father", "Father" => "-7", "Mother" => "-8", "Gender" => "Male"),
		"-6" => array("Id" => "-6", "LastNameAtBirth" => "Great Grand Mother", "Father" => "-7", "Mother" => "-8", "Gender" => "Female"),
		"-7" => array("Id" => "-7", "LastNameAtBirth" => "GGGF", "Father" => "-9", "Mother" => "-10", "Gender" => "Male"),
		"-8" => array("Id" => "-8", "LastNameAtBirth" => "GGGM", "Father" => "-9", "Mother" => "-10", "Gender" => "Female"),
		"-9" => array("Id" => "-9", "LastNameAtBirth" => "GGGGF", "Father" => "-11", "Mother" => "-12", "Gender" => "Male"),
		"-10" => array("Id" => "-10", "LastNameAtBirth" => "GGGGM", "Father" => "-11", "Mother" => "-12", "Gender" => "Female"),
		"-11" => array("Id" => "-11", "LastNameAtBirth" => "GGGGGF", "Father" => "-13", "Mother" => "-14", "Gender" => "Male"),
		"-12" => array("Id" => "-12", "LastNameAtBirth" => "GGGGGM", "Father" => "-13", "Mother" => "-14", "Gender" => "Female"),
		"-20" => array("Id" => "-20", "LastNameAtBirth" => "siblings", "Father" => "", "Mother" => "", "Gender" => ""),
		"-21" => array("Id" => "-21", "LastNameAtBirth" => "brother", "Father" => "", "Mother" => "", "Gender" => "Male"),
		"-22" => array("Id" => "-22", "LastNameAtBirth" => "sister", "Father" => "", "Mother" => "", "Gender" => "Female"),
	]);

	define("HELP_BIO", "<p>Enter a valid <b>WikiTree ID</b> to begin exploring.</p>
			<p><b>Ancestors</b> are displayed in a fractal tree: <br>fathers are shown to the left or above<br>mothers are shown to the right or below</p>
			<p><b>Descendents</b> are grouped into generations, with parents above a row of children.<br>Click CHILDREN or SPOUSES to show/hide subsequent descendants.</p>");
}

?>

