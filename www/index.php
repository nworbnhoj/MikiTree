	<?php

define("WT_ID_REGEX", "^[A-Z][\w-]{1,30}-\d{1,6}$");
define("DEPTH_DEFAULT", 4);
define("SHOW_DEFAULT", "Lfm");
define("SHOW_ALL", array(
	'b' => "birth year",
	'c' => "birth location",
	'd' => "death year",
	'e' => "death location",
	'f' => "first name",
	'F' => "first initial",
	'l' => "last name",
	'L' => "<b>last name</b>",
	'm' => "middle name",
	'M' => "middle initial",
	'p' => "photo",
	'r' => "relationship",
));
define("BRAIL", array(0 => '⠀', 1 => '⠂', 2 => '⠤', 3 => '⠦', 4 => '⠶', 5 => '⠷', 6 => '⠿', 7 => '⡿', 8 => '⣿'));

// Get parameters and constrain
$root_key = isset($_GET['key']) ? $_GET['key'] : null;
$root_key = mb_ereg(WT_ID_REGEX, $root_key) ? $root_key : null;
$depth = isset($_GET['depth']) ? intval($_GET['depth']) : DEPTH_DEFAULT;
$depth = max(min($depth, 10), 0);
$show = isset($_GET['show']) ? $_GET['show'] : SHOW_DEFAULT;
define("DEPTH", $depth);
define("SHOW", $show);

// list of photos to be fetched after html returned to client
$fetch = array();

if (isset($root_key)) {
	$people = fetchFamily($root_key, $depth);
	$root_id = findId($people, $root_key);
}

if ($root_id) {
	$root = $people[$root_id];
	$head = head($root_key);
	$ancestors = fractal($root_id, $people, 0, $depth);
	$photo = img($root, 'photo');
	$bdm = bdm_div($root, $people);
	$descendants = branch($root, $people);
	$bio = tidyHtml($root['bioHTML']);
} else {
	$head = head("MikiTree");
	$ancestors = "";
	$photo = "";
	$bdm = "";
	$descendants = "";
	$bio = "";
}

$body = body($root_key, $ancestors, $descendants, $photo, $bdm, $bio);

echo "<!DOCTYPE html>
	<html lang='en'>
	$head
	$body
	</html>";

respondOK();

array_map('fetchPhoto', $fetch);

///////////////////////////////////////////////////////////////////////////////

function head($key) {
	return "<head>
	    <meta charset='UTF-8'>
	    <meta name='viewport' content='width=device-width, initial-scale=1'>
	    <meta name='theme-color' content='#AAAAFF'/>
	    <title>$key</title>
	    <link rel='manifest' type='text/json' href='./manifest.json'>
	    <link rel='stylesheet' type='text/css' href='tree.css'>
	    <script src='levenshtein.js'></script>
	    <script src='tree.js'></script>
	    <link rel='apple-touch-icon' href='/img/mikitree.png'>
	</head>";
}

function body($key, $ancestors, $descendants, $photo, $bdm, $bio) {
	$welcome = $key ? 'hide' : '';
	$checked = $key ? '' : 'checked';
	$regex = WT_ID_REGEX;
	$show_fieldset = show_fieldset();
	$show = SHOW;
	$depth = DEPTH;
	$c4 = $depth == 4 ? 'checked' : '';
	$c6 = $depth == 6 ? 'checked' : '';
	$c8 = $depth == 8 ? 'checked' : '';
	$c10 = $depth == 10 ? 'checked' : '';
	return "<body  onload='onload()' onresize='resize(event)' onclick='spin(false)'>
	    <div id='get' class='hide' depth='$depth' show='$show'></div>
		<div id='ancestors'>$ancestors</div>
	    <div id='banner' class='help $welcome'>Explore your WikiTree.<br>
	    Enter a <b>WikiTree-ID</b> or <b>Search</b> for an ancestor.</div>
        <div id='control' class='wiki'>
			All data drawn from the superb <a class='wiki' href='https://www.wikitree.com/wiki/$key' target='_blank'>WikiTree</a>
			<form class='wiki'>
			   <input class='wiki' type='text' placeholder='WikiTree ID' name='key' pattern='$regex' title='A WikiTree ID is case sensitive and something like Brown-126635'>
			   <button id='go_button' type='button' onclick='load_new(event)'>Go</button>
			</form>
			<span class='nowrap'>
				<button id='search_toggle' type='button' class='toggle $checked' onclick='search_show(event)'>🔎︎</button>
				<button id='help_button' type='button' class='toggle' onclick='help(event)'>HELP</button>
				<button id='settings_toggle' type='button' class='toggle' onclick='settings(event)'>⚙</button>
			</span>
		</div>
		<div id='help' class='row help hide'>
		    <div id='help_txt'>
		    <p><b>Ancestors</b> are displayed <b>above</b> in a fractal tree. Fathers are shown to the left or above, and Mothers are shown to the right or below.</p>
		    <P>Click on any profile to bring it to the centre.</p>
			<p><b>Descendents</b> are displayed <b>below</b> in rows - one per generation. Each row of siblings will have their parent above, and spouses below. Click an <b>inlaw</b> to show the next generation (click the <b>brail</b> to hide).</p>
			<p><b>Search</b> for the WikiTree-ID of a deceased ancestor <b>below</b>. You must at least provide a first or last name. Any of the other fields will help narrow it down - even if inaccurate.</p>
            </div>
			<div id='photo'><img class='help' src='photo/help.webp'></div>
		</div>
		<div id='settings' class='hide'>
			<fieldset key='$key' onchange='depth_changed(event)'>
			    <legend>fractal depth</legend>
			    <input type='radio' id='4' name='depth' value='4' $c4>
                <label for='4'><strong>4</strong> (default)</label><br>
			    <input type='radio' id='6' name='depth' value='6' $c6>
                <label for='6'><strong>6</strong> (nice)</label><br>
			    <input type='radio' id='8' name='depth' value='8' $c8>
                <label for='8'><strong>8</strong> (slow)</label><br>
			    <input type='radio' id='10' name='depth' value='10' $c10>
                <label for='10'><strong>10</strong> (nuts)</label><br>
                </fieldset>
			$show_fieldset
			<fieldset>
			    <legend>experimental</legend>
			    <button type='button' onclick='show_all_descendants(event)'>show all descendants</button><br>
			    <button type='button' onclick='full_monty()'>full monty</button>
            </fieldset>
            <fieldset>
			    <legend>about</legend>
			    <a href='https://github.com/nworbnhoj/MikiTree'>code</a> by <a href='index.php?key=Brown-107675&depth=4&show=LfM'>nhoj</a>
            </fieldset>
		</div>
		<div id='search_div' class='$welcome'>
			<div id='search_terms' onchange='search_change(event)'>
				<fieldset >
				    <legend>Deceased Ancestor</legend>
				    <input class='search hilite' type='text' placeholder='First Name' id='child_first' >
				    <input class='search hilite' type='text' placeholder='Last Name' id='child_last' ><br>
				    <input class='search' type='number' placeholder='birth year' id='child_birth_date' >
				    <input class='search' type='number' placeholder='death year' id='child_death_date' ><br>
	                <input class='search' type='text' placeholder='birth location' id='child_birth_location' >
				    <input class='search' type='text' placeholder='death location' id='child_death_location' >
				</fieldset>
				<div >
					<fieldset >
						<legend>Father</legend>
					    <input class='search' type='text' placeholder='First Name' id='father_first' >
					    <input class='search' type='text' placeholder='Last Name' id='father_last' >
					</fieldset>
					<br>
					<fieldset >
						<legend>Mother</legend>
					    <input class='search' type='text' placeholder='First Name' id='mother_first' >
					    <input class='search' type='text' placeholder='Last Name' id='mother_last' >
					</fieldset>
					<br>
					<button id='search_button' type='button' onclick='search_click(event)' disabled>Search</button>
				</div>
			</div>
			<div id='results_div' class='hide'>
				<fieldset id='results_fieldset'>
					<legend>Results (<span id='results_count'>0</span>)</legend>
					<div id='results_msg'>WikiTree privacy limits searches to deceased ancestors.</div>
					<table id='results_table'></table>
				</fieldset>
			</div>
		</div>
		<div id='profile'  class='profile'>
			<div id='photo'>$photo</div>
		    <div>$bdm</div>
	    </div>
		<div id='descendants'>$descendants</div>
		<div id='bio'>$bio</div>
	</body>";
}

function show_fieldset() {
	$checkboxes = "";
	$show = SHOW;
	foreach (str_split($show) as $s) {
		$label = SHOW_ALL[$s];
		$checkboxes .= "<div>
		    <input type='checkbox' id='$s' name='show' value='$s' checked>
		    <label for='$s'>$label</label>
		</div>";
	}
	foreach (SHOW_ALL as $s => $label) {
		if (strpos($show, $s) === false) {
			$checkboxes .= "<div>
			    <input type='checkbox' id='$s' name='show' value='$s'>
			    <label for='$s'>$label</label>
			</div>";
		}
	}
	return "<fieldset id='show_fieldset' onchange='show_changed(event)'>
	    <legend>show priority</legend>
	    $checkboxes
	</fieldset>";

}

//https://stackoverflow.com/questions/3523409/domdocument-encoding-problems-characters-transformed
//https://www.php.net/manual/en/domdocument.savehtml.php
function tidyHtml($html) {
	if (empty($html)) {
		return "";
	}
	$dom = new DOMDocument('1.0', 'UTF-8');
	$html = '<?xml version="1.0" encoding="UTF-8"?>' . $html;
	libxml_use_internal_errors(true);
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
		$k = key_fix($person);
		if ($k == $key) {
			return $id;
		}
	}
	return null;
}

function img($person, $klass, $delay = false) {
	$path = false;
	$name = false;
	if (isset($person['PhotoData']) && is_array($person['PhotoData'])) {
		switch ($klass) {
		case 'photo':
			$path = isset($person['PhotoData']['path']) ? $person['PhotoData']['path'] : false;
			$name = isset($person['Photo']) ? $person['Photo'] : false;
			break;
		case 'thumb':
			$path = isset($person['PhotoData']['url']) ? $person['PhotoData']['url'] : false;
			$name = isset($person['PhotoData']['file']) ? $person['PhotoData']['file'] : false;
			break;
		default:
			$path = false;
			$name = false;
			break;
		}
	}
	if (!($path && $name)) {
		return "";
	}

	$save_as = "$klass/$name";
	if (!file_exists($save_as)) {
		$param = array($path, $save_as);
		if ($delay) {
			$GLOBALS['fetch'][] = $param;
		} else {
			fetchPhoto($param);
		}
	}
	$src = $delay ? 'src-delay' : 'src';
	return "<img class='$klass' $src='$save_as'>";
}

function fetchPhoto($foto) {
	$path = $foto[0];
	$save_as = $foto[1];
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

function bdm_div($person, $people) {

	$key = $person['Name'];
	$wiki = "Go to <a class='wiki' href='https://www.wikitree.com/wiki/$key' target='_blank'>WikiTree</a> to edit details.";

	$name_prefix = isset($person['Prefix']) ? $person['Prefix'] : "";
	$name_first = isset($person['RealName']) ? $person['RealName'] : "";
	$name_middle_initial = isset($person['MiddleInitial']) ? $person['MiddleInitial'] : "";
	$name_middle = isset($person['MiddleName']) ? $person['MiddleName'] : $name_middle_initial;
	$name_family = isset($person['LastNameAtBirth']) ? $person['LastNameAtBirth'] : "?";
	$name_suffix = isset($person['Suffix']) ? $person['Suffix'] : "";
	$name = "<h1>$name_prefix $name_first $name_middle $name_family $name_suffix</h1>";

	$is_living = isset($person['IsLiving']) ? boolval($person['IsLiving']) : true;
	$birth_date = isset($person['BirthDate']) ? $person['BirthDate'] : "";
	$birth_date = str_replace("-00", "", $birth_date);
	$birth_date = str_replace("0000", "", $birth_date);
	$birth_location = isset($person['BirthLocation']) ? $person['BirthLocation'] : "";
	$birth = "<h2>birth</h2>$birth_date<br>$birth_location";
	$death_date = isset($person['DeathDate']) ? $person['DeathDate'] : "";
	$death_date = str_replace("-00", "", $death_date);
	$death_date = str_replace("0000", "", $death_date);
	$death_location = isset($person['DeathLocation']) ? $person['DeathLocation'] : "";
	$death = $is_living ? "" : "<h2>death</h2>$death_date<br>$death_location";

	$list = "";
	foreach ($person['Spouses'] as $spouse_id => $union) {
		if (isset($union['Marriage'])) {
			$spouse = $people[$spouse_id];
			$first = isset($spouse['RealName']) ? $spouse['RealName'] : "";
			$last = isset($spouse['LastNameAtBirth']) ? $spouse['LastNameAtBirth'] : "";
			$union = $union['Marriage'];
			$date = isset($union['date']) ? $union['date'] : "";
			$date = str_replace("-00", "", $date);
			$date = str_replace("0000", "", $date);
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

function fractal_gap($gen, $depth, $g_gap) {
	$div_person = "<div class='gap $g_gap'></div>";
	// unwind recursion
	if ($gen >= $depth) {
		return $div_person;
	}
	$orient = $gen % 2 == 0 ? "frac_gap_h" : "frac_gap_v";
	$div_father = fractal_gap($gen + 1, $depth, 'm_gap');
	$div_mother = fractal_gap($gen + 1, $depth, 'f_gap');
	return "<div class='grid $orient'>
			$div_father
			$div_person
			$div_mother
		</div>";
}

function fractal($id, $people, $gen = 0, $depth = 4) {
	if (empty($id) || !isset($people[$id])) {
		$g_gap = $gen % 2 ? 'm_gap' : 'f_gap';
		return fractal_gap($gen, $depth, $g_gap);
	} else {
		$person = $people[$id];
		$orient = $gen % 2 == 0 ? "vertical" : "horizontal";
		$div_person = $gen == 0 ? root_div($person, $people) : person_div($person, $gen, $orient);

		// unwind recursion
		if ($gen >= $depth) {
			return $div_person;
		}

		$id_father = isset($person['Father']) ? $person['Father'] : null;
		$div_father = fractal($id_father, $people, $gen + 1, $depth);

		$id_mother = isset($person['Mother']) ? $person['Mother'] : null;
		$div_mother = fractal($id_mother, $people, $gen + 1, $depth);
	}

	$orient = $gen % 2 == 0 ? "fractal_h" : "fractal_v";
	$div =
		"<div class='grid $orient'>
			$div_father
			$div_person
			$div_mother
		</div>";
	return $div;
}

function branch($head, $people, $gen = 1, $branch_index = 0) {
	if ($gen > 10) {return "";}
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
			$svg = "<svg class='chute hide' branch='$next_branch' xmlns='http://www.w3.org/2000/svg' width='100% ' height='6em' >
				    <polygon class='$gender' points='0,0 10,0 10,20 0,20' />
				</svg>";

			$svgs = $svgs . $svg;
			$next_branch++;
		}
	}

	$hide = $gen > 1 ? "hide" : "";
	$branch =
		"<div class='generation $hide' gen='$gen' branch='$branch_index'>
	        <div class='generation_h unions'> $union_divs </div>
            <div class='generation_h chutes'> $svgs </div>
	        <div class='generation_h next_gen' gen='$next_gen'> $next_gen_divs </div>
	    </div>";

	return $branch;
}

function union_div($head_id, $spouse_id, $people, $gen, $branch) {
	$spouse = $spouse_id != "unknown" ? $people[$spouse_id] : array("LastNameAtBirth" => "unknown", "Spouses" => array());
	$name_family = isset($spouse['LastNameAtBirth']) ? $spouse['LastNameAtBirth'] : "";
	$name_first = isset($spouse['RealName']) ? $spouse['RealName'] : "";
	$gender = isset($spouse['Gender']) ? strtolower($spouse['Gender']) : "";
	$spouse_div = spouse_div($spouse, $gen - 1);

	$siblings_div = "";
	$children = isset($spouse["Spouses"][$head_id]) ? $spouse["Spouses"][$head_id] : array();
	foreach ($children as $index => $child_id) {
		$siblings_div = $siblings_div . child_div($child_id, $people, $gen, $branch);
		$branch++;
	}
	return
		"<div class='union'>
		    $spouse_div
		    <div class='grid siblings'>$siblings_div</div>
		</div>";
}

// wikitree bug??
function key_fix($person) {
	return isset($person['Name']) ? str_replace(" ", "_", $person['Name']) : '';
}

function relationship($generation, $gender, $inlaw = '-inlaw') {
	$rel = '';
	$abs_gen = abs($generation);
	switch ($abs_gen) {
	case 0:return $gender === 'male' ? 'husband' : 'wife';
	case 1:$rel = '';
		break;
	case 2:$rel = 'grand-';
		break;
	case 3:$rel = 'great-grand-';
		break;
	default:$rel = str_repeat('g', $abs_gen - 2) . "-G";
	}

	if ($generation > 3) {
		$rel .= $gender === 'male' ? 'M' : 'F';
	} elseif ($generation > 0) {
		$rel .= $gender === 'male' ? 'father' : 'mother';
	} elseif ($generation == 0) {
		$rel .= $gender === 'male' ? 'husband' : 'wife';
	} elseif ($generation < 0) {
		switch ($gender) {
		case 'male':$rel .= 'son';
			break;
		case 'female':$rel .= 'daughter';
			break;
		default:$rel .= 'child';
		}
	}
	$rel .= $inlaw;
	return $rel;
}

function person_div($person, $gen = 0, $orient = '') {
	$key = key_fix($person);
	$gender = isset($person['Gender']) ? strtolower($person['Gender']) : "";

	$name_div = name_div($person, "bcdefFmMlLr", relationship($gen, $gender, ''));
	$thumb = img($person, 'thumb', true);
	return
		"<div class='person $gender $orient' key='$key' gen='$gen' onclick='load_profile(event)'>
		    <div class='fill'></div>
		    <span class='thumb X' p='p'>$thumb</span>
	        $name_div
		    <div class='fill'></div>
	    </div>";
}

function child_div($child_id, $people, $gen, $index) {
	$child = $people[$child_id];
	$key = key_fix($child);
	$gender = isset($child['Gender']) ? strtolower($child['Gender']) : "";

	$name_div = name_div($child, "bdfFmMlLr", relationship(-1 * $gen, $gender, ''));

	$spouses_div = "";
	$spouses = isset($child['Spouses']) ? $child['Spouses'] : array();
	$child_count = 0;
	foreach ($spouses as $spouse_id => $grand_children) {
		$spouse = $people[$spouse_id];
		$gc_count = sizeof($grand_children);
		$spouses_div = $spouses_div . spouse_icon($spouse, $gen, $gc_count);
		$child_count += $gc_count;
	}
	$brail = brail($child_count);
	$spouses_div = "<div class='grid spouses'>$spouses_div</div>";
	$thumb = img($child, 'thumb', true);
	return
		"<div class='person child $gender' branch='$index' key='$key' gen='$gen' onclick='load_profile(event)'>
	        $name_div
		    <span class='thumb X' p='p'>$thumb</span>
		    <div class='fill'></div>
	        <button type='button' class='brail hide' onclick='hide_branch(event)'>$brail</button>
	        $spouses_div
	    </div>";
}

function brail($n) {
	$brail = '';
	while ($n > 0) {
		$brail .= $n > 8 ? BRAIL[8] : BRAIL[$n];
		$n = $n - 8;
	}
	return $brail;
}

function spouse_div($person, $gen = 0) {
	$key = key_fix($person);
	$gender = isset($person['Gender']) ? strtolower($person['Gender']) : "";

	$name_div = name_div($person, "bcdefFmMlLr", relationship(-$gen, $gender));
	$thumb = img($person, 'thumb', true);
	return
		"<div class='person $gender' key='$key' gen='$gen' onclick='load_profile(event)'>
		    <div class='fill'></div>
		    <span class='thumb X' p='p'>$thumb</span>
	        $name_div
		    <div class='fill'></div>
	    </div>";
}

function spouse_icon($person, $gen, $child_count) {
	$key = key_fix($person);
	$gender = isset($person['Gender']) ? strtolower($person['Gender']) : "";
	$name_div = name_div($person, "lLr", relationship(-$gen, $gender));
	$brail = brail($child_count);
	return
		"<div class='person spouse $gender' key='$key' gen='$gen' onclick='show_branch(event)'>
	        $name_div
	        <div class='brail'>$brail</div>
	    </div>";
}

function root_div($root, $people) {
	$key = key_fix($root);
	$gender = isset($root['Gender']) ? strtolower($root['Gender']) : "";
	$siblings = isset($root['Siblings']) ? links($root['Siblings'], $people) : "";
	$name_div = name_div($root, "bcdefFmMlLr", "WikiTree profile");
	$thumb = img($root, 'thumb', true);
	return
		"<div class='person root $gender' key='$key' gen='0'>
		    <span class='thumb X' p='p'>$thumb</span>
			$name_div
		    <div class='fill'></div>
			$siblings
		</div>";
}

function name_div($person, $flags, $relationship = '') {
	$fb = strpos($flags, 'b') !== false; // birth date
	$fc = strpos($flags, 'c') !== false; // birth location
	$fd = strpos($flags, 'd') !== false; // death date
	$fe = strpos($flags, 'e') !== false; // death location
	$ff = strpos($flags, 'f') !== false; // first name
	$fF = strpos($flags, 'F') !== false; // first initial
	$fl = strpos($flags, 'l') !== false; // last name
	$fL = strpos($flags, 'L') !== false; // last name bold
	$fm = strpos($flags, 'm') !== false; // middle name
	$fM = strpos($flags, 'M') !== false; // middle initial
	$fr = strpos($flags, 'r') !== false; // relationship
	$fy = strpos($flags, 'y') !== false; // year

	$gender = isset($person['Gender']) ? strtolower($person['Gender']) : false;
	$privacy = isset($person['Privacy']) ? intval($person['Privacy']) : 60;

	$first = isset($person['RealName']) ? $person['RealName'] : false;
	$first_initial = $first ? mb_substr($first, 0, 1) . '.' : false;
	$middle_initial = isset($person['MiddleInitial']) ? $person['MiddleInitial'] : false;
	$middle = isset($person['MiddleName']) ? $person['MiddleName'] : $middle_initial;
	$middle_initial = $middle_initial ? $middle_initial : mb_substr($middle, 0, 1) . '.';
	$middle_initial = $middle_initial == "." ? false : $middle_initial;
	$last = isset($person['LastNameAtBirth']) ? $person['LastNameAtBirth'] : " "; // space is important for help
	$last = $privacy < 30 ? "🔒" : $last;

	$birth_year = isset($person['BirthDateDecade']) ? $person['BirthDateDecade'] : false;
	$birth_year = isset($person['BirthYear']) && $person['BirthYear'] > 0 ? $person['BirthYear'] : $birth_year;
	$death_year = isset($person['DeathYear']) && $person['DeathYear'] > 0 ? $person['DeathYear'] : false;
	$birth_location = isset($person['BirthLocation']) ? explode(',', $person['BirthLocation']) : false;
	$death_location = isset($person['DeathLocation']) ? explode(',', $person['DeathLocation']) : false;
	$birth_location = is_array($birth_location) ? end($birth_location) : false;
	$death_location = is_array($death_location) ? end($death_location) : false;

	$b = $fb && $birth_year ? "<span class='X' p='b'>b.$birth_year</span>" : "";
	$c = $fc && $birth_location ? "<span class='X' p='c'>b.$birth_location</span>" : "";
	$d = $fd && $death_year ? "<span class='X' p='d'>d.$death_year</span>" : "";
	$e = $fe && $death_location ? "<span class='X' p='e'>d.$death_location</span>" : "";
	$f = $ff && $first ? "<span class='X' p='f'>$first</span>" : "";
	$F = $fF && $first_initial ? "<span class='upper X' p='F'>$first_initial</span>" : "";
	$l = $fl && $last ? "<span class='X' p='l'>$last</span>" : "";
	$L = $fL && $last ? "<span class='X' p='L'><b>$last</b></span>" : "";
	$m = $fm && $middle ? "<span class='X' p='m'>$middle</span>" : "";
	$M = $fM && $middle_initial ? "<span class='upper X' p='M'>$middle_initial</span>" : "";
	$r = $fr && $relationship ? "<br><span class='X' p='r'><i>$relationship</i></span>" : "";

	if (!($b || $c || $d || $e || $f || $F || $l || $L || $m || $M)) {return "<br>";}

	return "<div class='name'>$f$F $m$M $l$L<small>$b $d $c $e</small>$r</div>";
}

function links($ids, $people) {
	$links = "";
	foreach ($ids as $id) {
		$person = $people[$id];
		if (isset($person['RealName'])) {
			$name = $person['RealName'];
			if (isset($person['Name'])) {
				$key = key_fix($person);
				$links = $links . " <a href='index.php?key=$key'>$name</a>";
			} else {
				$links = $links . " $name";
			}
		}
	}
	return "<div id='root_siblings' class='links'>$links</div>";
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
	$fields = "Id,Name,LastNameAtBirth,RealName,MiddleName,MiddleInitial,Father,Mother,BirthDate,DeathDate,BirthDateDecade,BirthLocation,DeathLocation,Gender,IsLiving,HasChildren,NoChildren,Privacy,Photo,PhotoData";
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
	$spouses = isset($json_data[0]['items'][0]['person']['Spouses']) ? $json_data[0]['items'][0]['person']['Spouses'] : array();
	$people[$root_id]['Spouses'] = array();
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

/**
 * respondOK.
 * https://stackoverflow.com/questions/15273570/ continue-processing-php-after-sending-http-response
 * licence: CC BY-SA 3.0. https://creativecommons.org/licenses/by-sa/3.0/
 */
function respondOK() {
	// check if fastcgi_finish_request is callable
	if (is_callable('fastcgi_finish_request')) {
		/*
	         * This works in Nginx but the next approach not
*/
		session_write_close();
		fastcgi_finish_request();

		return;
	}

	ignore_user_abort(true);

	ob_start();
	$serverProtocole = filter_input(INPUT_SERVER, 'SERVER_PROTOCOL', FILTER_SANITIZE_STRING);
	header($serverProtocole . ' 200 OK');
	header('Content-Encoding: none');
	header('Content-Length: ' . ob_get_length());
	header('Connection: close');

	ob_end_flush();
	ob_flush();
	flush();
}

?>

