<?php

/*******************************************************************************

    Alfanous Web Interface for Mobiles, uses Alfanous JSON service.
    Copyright (C) 2011  Alfanous Team <http://www.alfanous.org>

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as
    published by the Free Software Foundation, either version 3 of the
    License, or (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.

*******************************************************************************/

/*
    Output should be valid for Mobile Web Standards (ie: XHTML1.1 Basic + CSS1)
    There is only one exception concerning RTL support
    <http://validator.w3.org/mobile/> helps much to check standards
*/

# HTTP header
header("Content-Type: application/xhtml+xml; charset=UTF-8");
header("Content-Language: ar");

header("Cache-Control: max-age=3600, must-revalidate");
header(sprintf("Last-Modified: %s", date("r", filemtime($_SERVER['SCRIPT_FILENAME']))));

# Check GET parameters
	$search = isset($_GET["search"]) ? $_GET["search"] : "";
	$page = isset($_GET["page"]) ? $_GET["page"] : 1;

if ($search) {

	# Hidden parameters
	$sortedby="mushaf";
	$recitation="Mishary Rashid Alafasy";
	$translation="None";
	$highlight="css";
	#$fuzzy="yes"; not stable yet

	# Encode JSON query URL
	$query_site = "http://www.alfanous.org/json?";
	$query_search = "search=" . urlencode($search);
	$query_page = "&page=" . urlencode($page);
	$query_string =  "&sortedby=" . urlencode($sortedby)
		. "&recitation=" . urlencode($recitation)
		. "&translation=" . urlencode($translation)
		. "&highlight=" . urlencode($highlight);
		#. "&fuzzy=" . urlencode($fuzzy);

	# JSON query
	$handle = fopen($query_site . $query_search . $query_page . $query_string,
		"rb");
	$contents = stream_get_contents($handle);
	fclose($handle);

	$json = json_decode($contents);
};

# XHTML header
print('
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML Basic 1.1//EN"
    "http://www.w3.org/TR/xhtml-basic/xhtml-basic11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>

	<title>Alfanous</title>

	<meta http-equiv="Content-Type" content="application/xhtml+xml; 
charset=UTF-8" />
	<link rel="shortcut icon" type="image/gif" href="icon.gif" />
');

# CSS1 style
$css = file_get_contents('basic.css');
printf ('
<style type="text/css">
%s
</style>', $css);

# Body + Search form
print('
</head>
<body dir="rtl">
');

/*
if ($search) {
	# debug:
	# print raw json
	printf('<!-- DEBUG --><pre>%s</pre>', htmlspecialchars($contents));
	# dump json decoded object
	print('<pre>');
	var_dump($json);
	print('</pre>');
};
*/

# Search form template
$search_form='
	<form id="form_%1$s" class="form" method="get" action="index.php">
		<div id="search_form_%1$s" class="search_form">
			<img src="icon.gif" width="32" height="32" alt="logo" />
			<span>الفانوس</span>
			<input id="search_box_%1$s" class="search_box" type="text" 
name="search" title="search" inputmode="arabic" />
			<input id="submit_%1$s" class="submit" type="submit" value="بحث" />
		</div>
	</form>
';

# Top search form
printf($search_form,1);

if ($search and $json and $json->interval->total) {
	# case: some results

	# Pages control
	$nb_pages = floor(($json->interval->total- 1) / 10)+ 1;
	if ($nb_pages) {
		$page_nb = floor(($json->interval->start- 1) / 10)+ 1;
		# Alfanous JSON service doesn't serve more than 100 pages.
		$hit_page_limit = False;
		if ($nb_pages > 100) {
			$hit_page_limit = True;
			$nb_pages = 100;
		};

		$results_pages = sprintf("<div class='pages'>\nالنتائج: %s-%s\\%s الصفحات:"
			,$json->interval->start
			,$json->interval->end
			,$json->interval->total);
		for ($i = 1; $i <= $nb_pages; $i++) {
			if ($i == $page_nb) {
				$results_pages .= " ". $i;
			}
			else {
				$results_pages .= sprintf(" <a href='%s'>%s</a>",
					htmlspecialchars("index.php?" . $query_search . "&page=" . $i),
					$i);
			};
		};
		# show a sign for pages limit '...', a part of pages are not listed
		if ($hit_page_limit) $results_pages .= " ...";
		$results_pages .= "</div>\n";
		print($results_pages);

		# Results listing
		$results = "<div id='search_result'>";
		for( $i = $json->interval->start; $i <= $json->interval->end; $i++) {
			$results .= sprintf ("
			<div class='result_item'>
				<div><span class='item_number'>%s </span>
					<span class='aya_info'> (%s %s) </span></div>
				<div class='quran'> [ %s ] </div>
				<div class='aya_details'>
		‎			الكلمات %s\\%s - الأحرف %s\\%s - ألفاظ الجلالة %s\\%s
					<br /> الحزب %s - الصفحة %s</div>
			</div>"
			,$i
			,$json->ayas->$i->sura->name,$json->ayas->$i->aya->id
			,$json->ayas->$i->aya->text
			,$json->ayas->$i->stat->words,$json->ayas->$i->sura->stat->words
			,$json->ayas->$i->stat->letters,$json->ayas->$i->sura->stat->letters
			,$json->ayas->$i->stat->godnames,$json->ayas->$i->sura->stat->godnames
			,$json->ayas->$i->position->hizb,$json->ayas->$i->position->page
			);
		};
		$results .= "</div>";
		print($results);

		# Pages control again
		print($results_pages);

		printf($search_form,2);
	};
}
elseif ($search) {
	# case: no results
	print('
	<hr />
	<p>:/ لم يعثر الفانوس على أي أيات تنطبق عليها معطيات البحث.</p>
	');
}
else{
	# case: no query
	# tobe replaced with Arabic paragraph
	print('
	<hr />
	<p class="align-left" dir="ltr">Alfanous is a Quranic search engine offers 
	simple and advanced search services in the whole information that Holy 
	Qur’an contains. it is based on the modern approach of information 
	retrieval to get a good-stability and a high-speed search. We want 
	implement some additional features like Highlight, Suggestions, Scoring 
	…etc.</p>
	');
};

# Close body
print('
</body>
</html>
');

?>

