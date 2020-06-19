<?php
include "SpellCorrector.php";
ini_set('memory_limit', '-1');
ini_set('max_execution_time', 300);

// make sure browsers see this page as utf-8 encoded HTML
header('Content-Type: text/html; charset=utf-8');

$correct_query="";
$actual_query = "";
$page_rank = "";
$limit = 10;
$query = isset($_REQUEST['q']) ? $_REQUEST['q'] : false;
$results = false;
$flag = false;
$idToURLMap = array();


$file = fopen("URLtoHTML_fox_news.csv", "r");
if($file !== false) {
	while($row = fgetcsv($file, 10000, ",")) {
		$idToURLMap[$row['0']] = $row['1'];
	}
	fclose($file);
}

if ($query)
{
  // The Apache Solr Client library should be on the include path
  // which is usually most easily accomplished by placing in the
  // same directory as this script ( . or current directory is a default
  // php include path entry in the php.ini)
  require_once('solr-php-client/Apache/Solr/Service.php');

  // create a new solr service instance - host, port, and webapp
  // path (all defaults in this example)
  $solr = new Apache_Solr_Service('localhost', 8080, '/solr/IRHW4_FoxNews/query');
  $query_terms = explode(" ", $query);
  // if magic quotes is enabled then stripslashes will be needed
  //echo $query_terms;
  if (get_magic_quotes_gpc() == 1)
  {
    $query = stripslashes($query);
  }

  for($i = 0; $i < sizeof($query_terms); $i++)
  {
  	$check = SpellCorrector::correct($query_terms[$i]);
	if($i == 0) 
	{
		$correct_query = $correct_query . $check;
	}
	else 
	{
		$correct_query = $correct_query . ' ' . $check;
	}
  }

  if(strtolower($query) != strtolower($correct_query))
  {
	$flag = true;
  }

  // in production code you'll always want to use a try /catch for any
  // possible exceptions emitted  by searching (i.e. connection
  // problems or a query parsing error)
  try
  {
    if($_REQUEST['rank'] == 'lucene') {
	$page_rank = "lucene";
	$results = $solr->search($query, 0, $limit);
    }
    else {
	$page_rank = "pagerank";
	$additionalParameters = array(
	    'sort' => 'pageRankFile desc'
	);
	$results = $solr->search($query, 0, $limit, $additionalParameters);
    }
  }
  catch (Exception $e)
  {
    // in production you'd probably log or email this error to an admin
    // and then show a special message to the user but for this example
    // we're going to show the full exception
    die("<html><head><title>SEARCH EXCEPTION</title><body><pre>{$e->__toString()}</pre></body></html>");
  }
}

?>
<html>
  <head>
    <title>PHP Solr Client</title>
    <link href="http://code.jquery.com/ui/1.10.4/themes/ui-lightness/jquery-ui.css" rel="stylesheet"></link>
    <script src="http://code.jquery.com/jquery-1.10.2.js"></script>
    <script src="http://code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
  </head>
  <body>
    <center><b><h2>PHP Solr Client</h2></b></center>
    <form  accept-charset="utf-8" method="get">
      <label for="q">Search:</label>
      <input id="q" name="q" type="text" value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'utf-8'); ?>"/>
      <br>
      <input type="radio" name="rank" value="lucene" default="checked" <?php if($_REQUEST['rank'] == "lucene") echo "checked"; ?> /> Lucene (Default)
      <br>
      <input type="radio" name="rank" value="pagerank" <?php if($_REQUEST['rank'] == "pagerank") echo "checked"; ?> /> PageRank
      <br>
      <br>
      <input type="submit"/>
    </form>
    <script>
	$(function() {
		var URL_PREFIX = "http://localhost:8080/solr/IRHW4_FoxNews/suggest?q=";
		var URL_SUFFIX = "&wt=json&indent=true";
		var count = 0;
		var tags = [];

		$("#q").autocomplete({
			source: function(request, response) {
				var correct = "", before = "";
				var query = $("#q").val().toLowerCase();
				var char_count = query.length - (query.match(/ /g) || []).length;
				var space = query.lastIndexOf(' ');
				var URL = URL_PREFIX + query + URL_SUFFIX;

				$.ajax({
					url: URL,
					dataType: 'json',
					headers: {"cors" : "Access-Control-Allow-Origin"},
					success: function(data) {
						var js = data.suggest.suggest;
					
						for(var i = 0; i < js[query].suggestions.length; i++) {
							tags[i] = js[query].suggestions[i].term;
						}
						
						response(tags);
					}
				});
			}
		});
	});
    </script>
<?php

// display results
if ($results)
{
  
  if($flag == true)
  {
	echo "Showing results for " . $query;
	$link = "http://localhost/QueryBox.php?q=$correct_query&rank=$page_rank";
	echo "<br> Search instead for <a href='$link'>" . $correct_query . "</a><br>";
  }

  $total = (int) $results->response->numFound;
 
  $start = min(1, $total);
  $end = min($limit, $total);
?>
    <div>Results <?php echo $start; ?> - <?php echo $end;?> of <?php echo $total; ?>:</div>
    <ol>
<?php
  // iterate result documents
  $query_terms = explode(" ", $query);
  foreach ($results->response->docs as $doc)
  {
?>
  	<ol>
<?php
	$pageId = "";
	$desc = "N/A";
	$title = "";
	$term = "";
	$found_index = -1;
	$start = 0;
	$end = 0;
        $ellipsis_start = "";
	$ellipsis_end = "";
	$j = 0;
	$query_desc = [];
      
    // iterate document fields / values
    foreach ($doc as $field => $value)
    {
	if($field == "id") {
		$pageId = $value;
	}
	if($field == "og_description") {
		if($value == null || $value == "") {
			$desc = "N/A";
		}
		else {
			$desc = "";
			$words = explode(" ", $value);
			$len = count($words);
			$index = 0;
            
			for($i = 0; $i <= $len - count($query_terms); $i++) {
				$query_found = false;
				for($k = 0; $k < count($query_terms); $k++) {
					if(preg_match("/[.!?:;\",']/", $words[$i+$k]) == 1) {
						$term = strtolower(trim(preg_replace("/[.!?:;\",']/", "", $words[$i+$k])));
					}
					else {
						$term = strtolower($words[$i+$k]);
					}
					if($term == strtolower($query_terms[$k])) {
						if($k == 0) {
							$query_found = true;
						} else {
							$query_found = $query_found && true;
						}
					} else {
						$query_found = false;
					}
				}

				for($j = 0; $j < count($query_terms); $j++) {
					if($query_found == 1) {
						$query_desc[$i+$j] = true;
						if($found_index == -1) {
							$found_index = $i;
						}
					}
				}
			}

			for($i = 0; $i <= $len; $i++) {
				if($query_desc[$i] == 1) {
					$desc = $desc . "<b>" . $words[$i] . "</b> ";
				} else  {
					$desc = $desc . $words[$i] . " ";
				}
			}
		}

		$words = explode(" ", $desc);
		if($found_index !== -1) {
			if($found_index < 7) {
				$start = 0;
				$end = count($words) > 25 ? 25 : count($words);
			}
			else {
				$ellipsis_start = "...";
				$start = $found_index - 7;
				$end = count($words) > 25 ? $start + 25 : $start + count($words);
			}
		}
		else {
			$start = 0;
			$end = count($words) > 25 ? 25 : count($words);
		}
		
		if(count($words) > $end) {
			$ellipsis_end = "...";
		}

		$desc = $ellipsis_start;
		for($i = $start; $i < $end; $i++) {
			$desc = $desc . $words[$i] . " ";
		}
		$desc = $desc . $ellipsis_end;
	   }
        if($field == "title") {
            $title = $value;
        }
	
    }
      
    // the value 42 for substring is calculated based on the length of path of the file stored in Apache Solr
	echo "<b>Title: </b><a href='" . $idToURLMap[substr($pageId, 42)] . "'>" . $title . "</a><br>";
	echo "<b>URL: </b><a href='" . $idToURLMap[substr($pageId, 42)]. "'>" . $idToURLMap[substr($pageId, 42)] . "</a><br>";
	echo "<b>ID: </b>" . $pageId . "<br>";
	echo "<b>Description: </b>" . $desc . "<br>";
	echo "<br>";
?>
      </ol>
<?php
  }
?>
    </ol>
<?php
}
?>
  </body>
</html>
