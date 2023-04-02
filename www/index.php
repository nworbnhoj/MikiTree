	<?php

define("WT_ID_REGEX", "^[A-Z][\w-]{1,20}-\d{1,6}$");

// Get parameters and constrain
$root_key = isset($_GET['key']) ? $_GET['key'] : null;
$regex = "{" . WT_ID_REGEX . "}";
$root_key = preg_match($regex, $root_key) ? $root_key : null;
$depth = isset($_GET['depth']) ? intval($_GET['depth']) : 6;
$depth = max(min($depth, 10), 0);

if (isset($root_key)) {
	$people = fetchFamily($root_key, $depth);
	$root_id = findId($people, $root_key);
	if ($root_id) {
		$root = $people[$root_id];
		$photo = isset($root['PhotoData'], $root['PhotoData']['path']) ? fetchPhoto($root['PhotoData']['path'], $root['Photo']) : "";
		$bdm = bdm_div($root, $people);
		$bio = tidyHtml($root['bioHTML']);
	}
} else {
	$root_key = "MikiTree";
}

if (!isset($root)) {
	init();
	$depth = 4;
	$people = HELP_FAMILY;
	$root_id = "root";
	$root = $people[$root_id];
	$photo = fetchPhoto("", $root['Photo']);
	$bdm = HELP_BIO;
}
$ancestors = fractal($root_id, $people, 0, $depth);
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

	<body  onload='pack()' onresize='resize(event)'>
		<div id='ancestors'>	<?php echo $ancestors; ?>    </div>
		<div id='profile' class='grid'>
		    <?php
echo $photo;
echo $bdm;
?>
        </div>
		<div id='descendants'>	<?php echo $descendants; ?>    </div>
		<div id='bio'>
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

function bdm_div($person, $people) {

	$key = $person['Name'];
	$wiki = "Go to <a class='wiki' href='https://www.wikitree.com/wiki/$key' target='_blank'>WikiTree</a> to edit details.";

	$name_prefix = isset($person['Prefix']) ? $person['Prefix'] : "";
	$name_first = isset($person['RealName']) ? $person['RealName'] : "";
	$name_middle = isset($person['MiddleName']) ? $person['MiddleName'] : "";
	$name_family = isset($person['LastNameAtBirth']) ? $person['LastNameAtBirth'] : "?";
	$name_suffix = isset($person['Suffix']) ? $person['Suffix'] : "";
	$name = "<h1>$name_prefix $name_first $name_middle $name_family $name_suffix</h1>";

	$is_living = isset($person['IsLiving']) ? boolval($person['IsLiving']) : true;
	if ($is_living) {
		$birth_date = isset($person['BirthDateDecade']) ? $person['BirthDateDecade'] : "";
		$birth_location = isset($person['BirthLocation']) ? $person['BirthLocation'] : "";
		$birth = "<h2>birth:</h2>$birth_date<br>$birth_location";
		$death = "";
	} else {
		$birth_date = isset($person['BirthDate']) ? $person['BirthDate'] : "";
		$birth_location = isset($person['BirthLocation']) ? $person['BirthLocation'] : "";
		$birth = "<h2>birth</h2>$birth_date<br>$birth_location";
		$death_date = isset($person['DeathDate']) ? $person['DeathDate'] : "";
		$death_location = isset($person['DeathLocation']) ? $person['DeathLocation'] : "";
		$death = "<h2>death</h2>$death_date<br>$death_location";
	}

	$list = "";
	foreach ($person['Spouses'] as $spouse_id => $union) {
		if (isset($union['Marriage'])) {
			$spouse = $people[$spouse_id];
			$first = isset($spouse['RealName']) ? $spouse['RealName'] : "";
			$last = isset($spouse['LastNameAtBirth']) ? $spouse['LastNameAtBirth'] : "";
			$union = $union['Marriage'];
			$date = isset($union['date']) ? $union['date'] : "";
			$location = isset($union['location']) ? $union['location'] : "";
			$list .= "<li class='marriage'>$date $first $last<br>$location</li>";
		}
	}
	$marriage = "<h2>marriage</h2><ul class='marriage'>$list</ul>";

	return "
    <div id='bdm'>
        $name
        $wiki
        $birth
        $marriage
        $death
    </div>";
}

function fractal($id, $people, $gen = 0, $depth = 3) {
	if (empty($id) || !isset($people[$id])) {
		return "<div class='missing person'><br><br></div>";
	}
	$person = $people[$id];
	$div_person = $gen == 0 ? root_div($person, $people) : person_div($person, $gen);

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
	$next_branch = 0;
	$spouses = isset($head["Spouses"]) ? $head["Spouses"] : array();
	foreach ($spouses as $spouse_id => $child_ids) {
		if ($spouse_id == $head_id) {
			continue;
		}

		$union_divs = $union_divs . union_div($head_id, $spouse_id, $people, $gen, $next_branch);

		foreach ($child_ids as $index => $child_id) {
			if (!is_int($index)) {continue;}
			$child = $people[$child_id];
			$branch = branch($child, $people, $next_gen, $next_branch);
			$next_gen_divs = $next_gen_divs . $branch;

			$gender = isset($child['Gender']) ? strtolower($child['Gender']) : "";
			$svg = "<svg class='chute hide' branch='$next_branch' xmlns='http://www.w3.org/2000/svg' width='100% ' height='6em'>
				    <polygon class='$gender' points='0,0 10,0 10,20 0,20' />
				</svg>";

			$svgs = $svgs . $svg;
			$next_branch++;
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

function union_div($head_id, $spouse_id, $people, $gen, $branch) {
	$spouse = $spouse_id != "unknown" ? $people[$spouse_id] : array("LastNameAtBirth" => "unknown");
	$name_family = isset($spouse['LastNameAtBirth']) ? $spouse['LastNameAtBirth'] : "";
	$name_first = isset($spouse['RealName']) ? $spouse['RealName'] : "";
	$gender = isset($spouse['Gender']) ? strtolower($spouse['Gender']) : "";
	$spouse_div = person_div($spouse, 1);

	$siblings_div = "";
	$children = $spouse["Spouses"][$head_id];
	foreach ($children as $index => $child_id) {
		$siblings_div = $siblings_div . child_div($child_id, $people, $gen, $branch);
		$branch++;
	}
	return
		"<div class='grid union'>
		    $spouse_div
		    <div class='grid siblings'>$siblings_div</div>
		</div>";
}

function person_div($person, $gen = 0) {
	$key = isset($person['Name']) ? $person['Name'] : "";
	$gender = isset($person['Gender']) ? strtolower($person['Gender']) : "";
	$flags = $gen % 2 == 0 ? "lfymv" : "lfym";
	$name_div = name_div($person, $flags);
	return
		"<div class='person $gender' id='$key' gen='$gen' onclick='load(event)'>
	        $name_div
	    </div>";
}

function child_div($child_id, $people, $gen, $index) {
	$child = $people[$child_id];
	$key = isset($child['Name']) ? $child['Name'] : "";
	$gender = isset($child['Gender']) ? strtolower($child['Gender']) : "";

	$name_div = name_div($child, 'fmlyv');

	$radio_button = radio_button($child, $people, $gen);

	$spouses_div = "";
	$spouses = isset($child['Spouses']) ? $child['Spouses'] : array();
	foreach ($spouses as $spouse_id => $grand_children) {
		$spouse = $people[$spouse_id];
		$spouses_div = $spouses_div . spouse_div($spouse, $gen);
	}
	$spouses_div = "<div class='grid spouses'>$spouses_div</div>";
	return
		"<div class='person child $gender' branch='$index' id='$key' gen='$gen' onclick='load(event)'>
	        $name_div
	        $radio_button
	        $spouses_div
	    </div>";
}

function radio_button($child, $people, $gen) {
	if (!isset($child['Children']) || sizeof($child['Children']) == 0) {
		return "";
	}
	$kids = str_repeat("▯", sizeof($child['Children']));
	return "<button class='radio' gen='$gen' onclick='showBranch(event)'>$kids</button>";
}

function spouse_div($person, $gen) {
	$key = isset($person['Name']) ? $person['Name'] : "";
	$gender = isset($person['Gender']) ? strtolower($person['Gender']) : "";
	$name_div = name_div($person, "l");
	return
		"<div class='person spouse $gender' id='$key' gen='$gen' onclick='load(event)'>
	        $name_div
	    </div>";
}

function root_div($root, $people) {
	$key = isset($root['Name']) ? $root['Name'] : "";
	$gender = isset($root['Gender']) ? strtolower($root['Gender']) : "";
	$siblings = isset($root['Siblings']) ? links($root['Siblings'], $people) : "";
	$name_div = name_div($root, "fmlyv");
	$checked = $root['Id'] == "root" ? "checked" : "";
	$regex = WT_ID_REGEX;
	$help = "A WikiTree ID is case sensitive and something like Brown-126635";

	return
		"<div class='person root $gender' id='$key' gen='0'>
			<div class='wiki'>
				All data drawn from the superb <a class='wiki' href='https://www.wikitree.com/wiki/$key' target='_blank'>WikiTree</a>
				<form class='wiki' action='/index.php'>
				   <input class='wiki' type='text' placeholder='WikiTree ID' name='key' pattern='$regex' title='$help'>
				   <input type='submit' value='Go'>
				</form>
			</div>
			$name_div
			$siblings
			<button class='help $checked' onclick='help(event)'>HELP</button>
		</div>";
}

function name_div($person, $flags = "lfymv") {

	$ff = strpos($flags, 'f') !== false; // first name
	$fi = strpos($flags, 'i') !== false; // initial
	$fm = strpos($flags, 'm') !== false; // middle name
	$fl = strpos($flags, 'l') !== false; // last name
	$fy = strpos($flags, 'y') !== false; // year
	$fb = strpos($flags, 'b') !== false; // birth date
	$fd = strpos($flags, 'd') !== false; // death date
	$fh = strpos($flags, 'h') !== false; // hilite last name
	$fv = strpos($flags, 'v') !== false; // vertical orientation

	$name_family = isset($person['LastNameAtBirth']) ? $person['LastNameAtBirth'] : "?";
	$name_first = isset($person['RealName']) ? $person['RealName'] : "";
	$initial = isset($person['MiddleInitial']) ? $person['MiddleInitial'] : "";
	$initial = ($initial == ".") ? "" : $initial;
	$gender = isset($person['Gender']) ? strtolower($person['Gender']) : "";
	$privacy = isset($person['Privacy']) ? intval($person['Privacy']) : 60;
	$name_family = $privacy < 30 ? "🔒" : $name_family;

	$first = isset($person['RealName']) ? $person['RealName'] : "";
	$middle = isset($person['MiddleName']) ? $person['MiddleName'] : $initial;
	$last = $fh ? $name_family : "<b>$name_family</b>";

	$years = "";
	$is_living = isset($person['IsLiving']) ? boolval($person['IsLiving']) : true;
	if ($is_living) {
		$decade = isset($person['BirthDateDecade']) ? $person['BirthDateDecade'] : false;
		$years = $decade ? "($decade)" : "";
	} else {
		$year_birth = isset($person['BirthYear']) && $person['BirthYear'] > 0 ? $person['BirthYear'] : "";
		$year_death = isset($person['DeathYear']) && $person['DeathYear'] > 0 ? $person['DeathYear'] : "";
		if ($fv) {
			$years = "b.$year_birth<br>d.$year_death";
		} else {
			$years = "<span class='nowrap'>($year_birth-$year_death)</span>";
		}
	}
	$years = $privacy < 30 ? "" : $years;
	$first = $ff ? "<span class='X' p='f'>$first</span>" : "";
	$middle = $fm ? "<span class='X' p='m'>$middle</span>" : "";
	$last = $fl ? "<span class='X' p='l'>$last</span>" : "";
	$years = $fy ? "<span class='X' p='y'>$years</span>" : "";

	return $fv ?
	"<div class='name'>$first $middle<br>$last<br><small>$years</small></div>" :
	"<div class='name'>$first $middle $last <small>$years</small></div>";
}

function links($ids, $people) {
	$links = "";
	foreach ($ids as $id) {
		$person = $people[$id];
		if (isset($person['RealName'])) {
			$name = $person['RealName'];
			if (isset($person['Name'])) {
				$key = isset($person['Name']) ? $person['Name'] : "";
				$links = $links . " <a href='index.php?key=$key'>$name</a>";
			} else {
				$links = $links . " $name";
			}
		}
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

function fetchFamily($key, $depth) {
	$people = array();
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

	// get ancestors and descendants from WikiTree
	$fields = "Id,Name,LastNameAtBirth,RealName,MiddleName,MiddleInitial,Father,Mother,BirthDate,DeathDate,BirthDateDecade,BirthLocation,DeathLocation,Gender,IsLiving,HasChildren,NoChildren,Privacy";
	$url = "https://api.wikitree.com/api.php?action=getPeople&ancestors=$depth&descendants=$depth&keys=$key&nuclear=1&fields=$fields";

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
	$fields = "Id,Name,LongName,Bio,Photo,PhotoData,Prefix,Suffix";
	$url = "https://api.wikitree.com/api.php?action=getProfile&bioFormat=both&key=$key&fields=$fields";
	curl_setopt($curl, CURLOPT_URL, $url);
	$response = curl_exec($curl);
	if ($e = curl_error($curl)) {
		echo $e;
		curl_close($curl);
		return $people;
	}
	$json_data = json_decode($response, true);
	$root = $json_data[0]['profile'];
	$root_id = $root["Id"];

	$people = inferYears($people);
	usort($people, "byBirth");
	$people = rekey($people, 'Id');

	$people[$root_id]['Prefix'] = isset($root['Prefix']) ? $root['Prefix'] : "";
	$people[$root_id]['Suffix'] = isset($root['Suffix']) ? $root['Suffix'] : "";
	$people[$root_id]['Photo'] = isset($root['Photo']) ? $root['Photo'] : "";
	$people[$root_id]['PhotoData'] = isset($root['PhotoData']) ? $root['PhotoData'] : "";
	$people[$root_id]['bioHTML'] = isset($root['bioHTML']) ? $root['bioHTML'] : "";

	// get root Spouses from WikiTree
	$fields = "Id,Name,Spouses";
	$url = "https://api.wikitree.com/api.php?action=getRelatives&getSpouses=1&keys=$key&fields=$fields";
	curl_setopt($curl, CURLOPT_URL, $url);
	$response = curl_exec($curl);
	if ($e = curl_error($curl)) {
		echo $e;
		curl_close($curl);
		return $people;
	}
	$json_data = json_decode($response, true);
	$spouses = $json_data[0]['items'][0]['person']['Spouses'];
	foreach ($spouses as $spouse_id => $spouse) {
		$marriage = array();
		$marriage['date'] = $spouse['marriage_date'];
		$marriage['location'] = $spouse['marriage_location'];
		//$marriage['end_date'] = $spouse['end_date'];
		$people[$root_id]['Spouses'][$spouse_id]['Marriage'] = $marriage;
	}

	curl_close($curl);

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
		"7" => array("Id" => "7", "LastNameAtBirth" => "Son-in-law-2", "Father" => "", "Mother" => "", "Gender" => "Male", "Spouses" => ["4" => [8]], "Children" => [8]),
		"6" => array("Id" => "6", "LastNameAtBirth" => "Son-in-law-1", "Father" => "", "Mother" => "", "Gender" => "Male", "Spouses" => ["4" => [9, 10]], "Children" => [9, 10]),
		"5" => array("Id" => "5", "LastNameAtBirth" => "Daughter-in-law", "Father" => "", "Mother" => "", "Gender" => "Female", "Spouses" => ["2" => []]),
		"4" => array("Id" => "4", "LastNameAtBirth" => "Daughter", "Father" => "root", "Mother" => "1", "Gender" => "Female", "Siblings" => [2, 3], "Spouses" => ["6" => [9, 10], "7" => [8]], "Children" => [8, 9, 10]),

		"3" => array("Id" => "3", "LastNameAtBirth" => "Son", "Father" => "root", "Mother" => "1", "Gender" => "Male", "Siblings" => [2, 4]),
		"2" => array("Id" => "2", "LastNameAtBirth" => "Son", "Father" => "root", "Mother" => "1", "Gender" => "Male", "Siblings" => [3, 4], "Spouses" => ["5" => []]),
		"1" => array("Id" => "1", "LastNameAtBirth" => "Wife", "Father" => "", "Mother" => "", "Gender" => "Female", "Children" => [2, 3, 4], "Spouses" => ["root" => [2, 3, 4]]),
		"root" => array("Id" => "root", "LastNameAtBirth" => "WikiTree Profile", "Father" => "-1", "Mother" => "-2", "Gender" => "Male", "Children" => [2, 3, 4], "Spouses" => ["1" => [2, 3, 4]], "Siblings" => [-20, -21, -22], "Photo" => "help.webp"),
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

	define("HELP_BIO", "<div id='bdm'><p>Enter a valid <b>WikiTree ID</b> to begin exploring.</p>
			<p><b>Ancestors</b> are displayed in a fractal tree:
			<ul>
			<li>fathers are shown to the left or above</li>
			<li>mothers are shown to the right or below</li>
			</ul></p>
			<p><b>Descendents</b> are in rows of siblings, 1st cousins, 2nd cousins etc.
			<ul>
			<li>Click ▯▯▯ to show the next generation (click ▯▯▯ again to hide).</li>
			<li>Parents are above siblings, and spouses are below.</li>
			</ul></p></div>");
}

?>

