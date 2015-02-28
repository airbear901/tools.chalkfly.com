<?php
error_reporting(E_ALL);
$am = $_GET["am_id"];
$name = $_GET["name"];
$page_title = $name . '&#39;s Tracker';
$page_style_href = '../../css/style.css';

include '../../includes/header.php';
include '../../includes/queries.php';
include '../../includes/functions.php';
include '../../includes/variables.php';

//current month - 6 months
$lessSixMonthsM = (date("m") - 6);

//if its negative, subtract it from 12. if its 0, make it january.
if ($lessSixMonthsM < 0) {
	$sixMonthsBackM = (12 + $lessSixMonthsM);
	$sixMonthsBackY = date("Y") - 1;
	if ($sixMonthsBackM < 10) {
	$sixMonthsBackM = "0" . $sixMonthsBackM;
	}

	$sixMonthsBack = $sixMonthsBackY . "-" . $sixMonthsBackM . "-01 00:00:00";	

} elseif ($lessSixMonthsM == 0) {
	$sixMonthsBackM = 1;
	if ($sixMonthsBackM < 10) {
	$sixMonthsBackM = "0" . $sixMonthsBackM;
	}
	$sixMonthsBack = date ("Y") . "-" . $sixMonthsBackM . "-01 00:00:00";

} else {
	$sixMonthsBack = date ("Y") . "-" . $sixMonthsBackM . "-01 00:00:00";
}
?>

<div class="container">
	<div class="row">
		<h1><?php echo $name . "'s " ?>Retention List</h1>
	</div>
	<div class="row">
		<p style="text-align: center;">The following customers have placed an order in the last 6 months, but not this month.</p>
	</div>


<?php


//by account manager
$retention = retentionCustomersAM ($sixMonthsBack,$firstDayMonth,$_GET["am_id"]);

Print "<table cellpadding=1 class='table tablesorter' id='thetable'>"; 
	Print "<thead>";
	Print " <th>Customer</th> <th>Email</th> <th>Group</th><th>Date of Last Purchase</th>";
	Print "</thead>";
	
	 while($info = mysqli_fetch_array( $retention )) 
	 { 
	Print "<tr>"; 
	Print "<td>".$info['Customer'] . "</td> "; 
	Print "<td>".$info['Email'] . "</td> ";
	Print "<td>".$info['customer_group_code'] . "</td> ";
	Print " <td>".$info['maximum'] . "</td></tr>"; 
	} 
	Print "</table>"; 

?>

</div>
<script type="text/javascript">
$(document).ready(function() 
    { 
        $("#thetable").tablesorter(); 
    } 
); 
</script>



</body>
</html>

