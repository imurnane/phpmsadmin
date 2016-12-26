<?PHP

/*
Simple phpMySQL clone for Microsoft SQL Server
Created by: Ian Murnane
Last updated: 26/12/2016

To install
 - (optional) add a password
 - add your sql server connection details
*/



error_reporting(0);
ini_set('display_errors', 0);




/* it is recommended to enter a simple password here
 * when loading, use phpmsadmin.php?pass=pass
 */
//session_start();
//if (!$_GET['pass'] AND !$_SESSION['pass']) die;
//$_SESSION['pass'] = true;



$_table = preg_replace("/[^a-zA-Z]+/", "", $_GET['table']);



/* add your sql server connection details */
$mssqlserver = '123.123.123.123';
$mssqlusername = 'user_name';
$mssqlpassword = 'password';
$mssqldb = 'database_name';

$con = mssql_connect($mssqlserver, $mssqlusername ,$mssqlpassword) OR die('Could not connect to the server!');
mssql_select_db($mssqldb) OR die("<p>Could not select a database.</p>");



// collate all the table and column names
$result = mssql_query("SELECT * FROM INFORMATION_SCHEMA.COLUMNS ORDER BY TABLE_NAME, ORDINAL_POSITION") OR die(mssql_get_last_message());

$tables_array = array();
$datatype_array = array();
$datalength_array = array();
while ($row = mssql_fetch_assoc($result)) {
	if (!is_array($tables_array[$row['TABLE_NAME']])) $tables_array[$row['TABLE_NAME']] = array();
	// Users => 1, UserKey
	array_push($tables_array[$row['TABLE_NAME']], $row['COLUMN_NAME']);
	if ($_table == $row['TABLE_NAME']) {
		$datatype_array[$row['COLUMN_NAME']] = $row['DATA_TYPE'];
		$datalength_array[$row['COLUMN_NAME']] = ($row['CHARACTER_MAXIMUM_LENGTH'] == -1 ? "MAX" : $row['CHARACTER_MAXIMUM_LENGTH']);
	}
}


// create the tables list
$show_tables = "<div class='tables'>\n";
foreach ($tables_array AS $key => $value) {
	$show_tables .= "\t<p><a href='phpmsadmin.php?table={$key}'" . ($_table == $key ? " style='color: black'" : "") . ">{$key}</a></p>\n";
}
$show_tables .= "</div>\n";


// add the column headers
$show_rows = "<div class='rows'>\n\t<table>\n";
if ($_table) $show_rows .= "\t\t<tr>\n";
foreach ($tables_array AS $key => $value) {
	if ($key == $_table) {
		foreach ($value AS $key2 => $value2) {
			$show_rows .= "\t\t\t<th>{$value2} <small>{$datatype_array[$value2]}" . ($datalength_array[$value2] ? "({$datalength_array[$value2]}) " : "") . "</small></th>\n";
		}
		$show_rows .= "\t\t</tr>\n";
		break;	// only need the headers, eg first iteration
	}
}


// add the rows
if ($_table) {
	$result = mssql_query("SELECT TOP 2000 * FROM " . $_table) OR die(mssql_get_last_message());
	while ($row = mssql_fetch_assoc($result)) {
		$show_rows .= "\t\t<tr>\n";
		foreach ($row AS $key => $value) {
			$show_rows .= "\t\t\t<td>$value</td>\n";
		}
		$show_rows .= "\t\t</tr>\n";
	}
}
$show_rows .= "\t</table>\n</div>\n";




// allow the user to enter a manual query
if ($_POST['query']) {
	$result = mssql_query($_POST['query']);
	
	$query_rows = "";
	while ($row = mssql_fetch_assoc($result)) {
		$query_rows .= "<p>" . print_r($row, true) . "</p>";
	}
	
	$mysql_result = "<p>Result: (" . ($result ? "SUCCESS" : "FAIL") . ")</p> " . mssql_get_last_message() . "<br><br>{$query_rows}";
}




// MAIN HTML
echo "
<!DOCTYPE html>
<html>
<head></head>
<body>

<style>
	body { font-family: helvetica; font-size: 13px; }
	.tables { border-right: 2px solid; float: left; margin-right: 40px; padding-right: 40px; }
	.tables p { margin: 8px 0 0 20px; }
	.tables p a { text-decoration: none; color: blue; }
	.tables p a:hover { cursor: pointer; }
	.rows { width: 86%; float: left; }
	.rows th { padding-right: 20px; }
	.rows tr:nth-child(even) { background-color: #FFEBCD; }
	.rows td { padding-right: 20px; }
	.rows::after { clear: both; }
	small { display: block; }
</style>

$show_tables
$show_rows

<div style='clear: both'></div>

<hr>

<form method='POST'>
<p>Enter a query</p>
<input name='query' value=\"" . $_POST['query'] . "\" style='min-width: 40%'>
<input type='submit'>
</form>
{$mysql_result}

</body>
</html>
";

?>