<?php 
	
include("functions.php"); 

if ($_COOKIE[uid] == "")
	{
	header("location:login.php");
	exit();
	};

if ($_POST[search] != "")
	{
	setcookie("search", $_POST[search]);
	
	header("location:results.php");
	exit();
	};

if ($_GET[clear] == 1)
	{
	setcookie("search", "");
	
	header("location:results.php");
	exit();
	};

include("header.php"); 

$clients = array();
$results = sql_query("select * from `database`.`clients` where `uid` = \"$_COOKIE[uid]\" ");
while($row = mysql_fetch_array($results))
	{
	$clients[] = $row[client];
	
	$clients_q .= "`client` = \"$row[client]\" or ";
	};
$clients_q = substr($clients_q, 0, -4);

$providers = array();
$results = sql_query("select * from `database`.`providers` where `uid` = \"$_COOKIE[uid]\" ");
while($row = mysql_fetch_array($results))
	{
	$providers[] = $row[provider];
	
	$providers_q .= "`provider` like \"$row[provider]^%\" or ";
	};
$providers_q = substr($providers_q, 0, -4);

echo "<table width=100%><tr><td><b>CLIENTS:</b> ";

$str = "";
foreach ($clients as $c)
	$str .= $c.", ";

$str = substr($str, 0, -2);

echo $str;

echo "</br>";

echo "<b>PROVIDERS:</b> ";

$str = "";
foreach ($providers as $p)
	$str .= $p.", ";

$str = substr($str, 0, -2);

echo $str;

?>
<!--
</td>
<td align=right>
	
<!--Drop down menu for now --> 
<!--Can change to input field -->
<!--Drop down menu for age of pdfs that are displayed (default is 30 days)-->

<!--
<form action="#" method="post">
	<select name="formAge">
		<option value="30 days">30 Days</option>
		<option value="60 days">60 Days</option>
		<option value="90 days">90 Days</option>
		<option value="180 days">180 Days</option>
		<option value="365 days">365 Days</option>
		<option value="">All</option>
	</select>
-->
	
	<!--<input class="btn btn-sm btn-primary" type="submit" name="submit" value="Select Age of PDFs Displayed" style="background-color: #f44336;"" /> -->
<!--	<input class="btn" type="submit" name="submit" value="Click Me to Change the Age of PDFs Displayed to the Value in the Dropdown Box" style="background-color: #4ee44e;"" />
</form>
-->

<?php

if(isset($_POST['submit'])){
	$selected_val = $_POST['formAge'];			//Stores a variable for the other variable $old's strtotime() function
	//echo "You have selected: " .$selected_val;  // Displaying Selected Value For Debugging (Can be removed (just this row))
	
	sql_query("UPDATE `database`.`users`  SET `pdfage` = \"".$selected_val."\" WHERE `id` = \"$_COOKIE[uid]\";");	
}

//else gets the users stored pdfage value
else {
	$results = sql_query("SELECT pdfage FROM `database`.`users` WHERE `id` = \"$_COOKIE[uid]\";");
	$row = mysql_fetch_array($results);	
	$selected_val = $row[pdfage];
	}

?>
		
<form method=post action=<?php echo "results.php"; ?>><b>Search:</b> (Name, SSN, CaseNum) <input name=search value="<?php echo $_COOKIE[search]; ?>" size=30 > <input class="btn btn-sm btn-primary" type=submit value="Search"> [ <a href=<?php echo "results.php?clear=1"; ?>>Clear</a> ]</form>
</td>
</tr>
</table>

<table class="table table-striped table-hover">
	<thead>
	<tr>
		<th>Result Date/Time</th>
		<th>Patient</th>
		<th>Date of Birth</th>
		<th>Report #</th>
		<th>Client</th>
		<th>Provider</th>
		<th>Account #</th>
		<th>PDF</th>
	</tr>
	<thead>
	<tbody>
		
<?php

if ( (count($clients) > 0) && (count($providers) > 0) )
	$cp = "( ($clients_q) or ($providers_q) ) and ";
else if (count($clients) > 0)
	$cp = "($clients_q) and ";
else if (count($providers) > 0)
	$cp = "($providers_q) and ";
else
	{
	echo "<tr><td colspan=100% align=center>Please contact the Help Desk at (555) 555-5555 to enable a client and/or provider.</td></tr></tbody></table>";
	include("footer.php");
	exit();
	};

if ($_COOKIE[search] != "")
	$sq = "and ( (`patient` like \"%$_COOKIE[search]%\") or (`casenum` like \"%$_COOKIE[search]%\") or (`ssn` like \"%$_COOKIE[search]%\") )";

$old = date("Y-m-d 00:00:00", strtotime("-$selected_val")); //Value from pdfage

$results = archive_query("select * from `msgs`.`index` where 
								`server` = \"SERVER\" and
								`channel` = \"P_MIRTH_CONNECT_CHANNEL\" and
								`mrn` not like \"X%\" and 
								$cp
								`msgtype` = \"ORU^R01\" 
								$sq
								and `msgdatetime` >= \"$old\"	
								order by `msgdatetime` desc ");
								
//Defines how many results per page
$results_per_page = 100;

//Total number of PDFs
$total = mysql_num_rows($results);

//echo "$total";

//Determine number of total pages avaliable
$number_of_pages = ceil($total/$results_per_page);

//Determine which page number visitor is currently on
if (!isset($_GET['page'])) {
	$page = 1;
} else {
	$page = $_GET['page'];
}

//Determine the SQL LIMIT starting number for the results on the dispalying page
$this_page_first_result = ($page-1)*$results_per_page;

$results = archive_query("select * from `msgs`.`index` where 
								`server` = \"SERVER\" and
								`channel` = \"P_MIRTH_CONNECT_CHANNEL\" and
								`mrn` not like \"X%\" and 
								$cp
								`msgtype` = \"ORU^R01\" 
								$sq
								and `msgdatetime` >= \"$old\"	
								order by `msgdatetime` desc LIMIT " . $this_page_first_result . ',' . $results_per_page);
								
//Display the links to the pages
//for ($page=1; $page<=$number_of_pages; $page++) {
//	echo "<a href=\"results20180529.php?page=" . $page . "\">" . $page . "</a> ";
	
//	}

echo "Page number: $page ";


//Makes the nevagation arrows (previous and next) dynamic
//Also routes to the same page if the end is reached.
if ($page != 1) {
	$prev = $page-1;
} else {
	$prev = $page;
};

if ($page !=  $number_of_pages) {
	$next = $page+1;
} else {
	$next = $page;
}


//Determines if there will be an ... on the right and/or left side of apge links.
$low = 0;

$mid = 0;

$high = 0;

//The if statment will render 10 pages at time if there is less than 10 it will render all the pages.
if ($number_of_pages > 10) {
	
	if ($page > $number_of_pages-10) {
	
		$start = $number_of_pages-10;	
		
		$end = $number_of_pages;
			
		$high = 1;
		
	}
	
	if ($page <= $number_of_pages-10 &&
		$page > 10) {
			
		$start = $page-4;	
		
		$end = $page+5;
			
		$mid = 1;
	}
	
	if ($page <= 10) {
		
		$start = 1;
		
		$end = 10;
		
		$low = 1;
		
	}	
}

else {
	
	$start = 1;
	
	$end = $number_of_pages;
	
}

echo <<<HTML
	<nav aria-label="Page navigation example">
		<ul class="pagination justify-content-center">
HTML;

if ($page != 1) {
	
	echo <<<HTML
		<li class="page-item">
			 <a class="page-link" href="results20180529.php?page=$prev" tabindex="-1">Previous</a>
		</li>
HTML;
}


// ... on the left side of links
if ($mid == 1 || $high == 1) {
	echo "<li class=\"disabled\"><span>...</span></li>";
}

//Renders the links according to their position and higlights current link
for ($link = $start; $link <= $end; $link++) {
	
	if ($link == $page) {
		echo "<li class=\"active\"><a class=\"page-link\" href=\"results20180529.php?page=$link\">$link</a></li>";
	}
	else {
		echo "<li class=\"page-item\"><a class=\"page-link\" href=\"results20180529.php?page=$link\">$link</a></li>";
	}
}

//... on the right side of links
if ($mid == 1 || $low == 1) {
	echo "<li class=\"disabled\"><span>...</span></li>";
}


if ($page == $number_of_pages) {
	echo <<<HTML
	    <li class="page-item">
	      <a class="btn disabled" href="results20180529.php?page=$next">Next</a>
	    </li>
	  </ul>
	</nav>	
HTML;
} else {
	echo <<<HTML
	    <li class="page-item">
	      <a class="page-link" href="results20180529.php?page=$next">Next</a>
	    </li>
	  </ul>
	</nav>	
HTML;
}

					
$nums = array();

while($row = mysql_fetch_array($results))
	{
	$results2 = sql_query("select * from `database`.`views` where `uid` = \"$_COOKIE[uid]\" and `report` = \"$row[casenum]\" limit 1");
	$count = mysql_num_rows($results2);
	
	$provider = strtoupper(trim(provName($row[provider])));

	if ($count == 0)
		$boldtr = "style=\"font-weight:bold;color:blue\"";
	else
		$boldtr = "";
	

	if(!in_array( $row[casenum], $nums))
		{
		
	echo <<<HTML
	
		<tr $boldtr>
			<td>$row[msgdatetime]</td>
			<td>$row[patient]</td>
			<td>$row[dob]</td>
			<td>$row[casenum]</td>
			<td>$row[client]</td>
			<td>$provider</td>
			<td>$row[account]</td>
			<td align=right><img onclick="window.open('download.php?id=$row[id]', '_new');location.reload()" src="pdf.jpg" width=25></td>
		</tr>
HTML;

	$nums[] = $row[casenum];
};

};
	
?>
		
	</tbody>
</table>

<?php include("footer.php"); ?>