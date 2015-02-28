<?php 
error_reporting(E_ALL);

$am = $_GET["am_id"];
$name = $_GET["name"];
$page_style_href = '../css/style.css';
include '../includes/queries.php';
include '../includes/functions.php';
include '../includes/variables.php';
$dayStart = date("Y-m-d") . " 00:00:00";
$dayEnd = date("Y-m-d") . " 23:59:59";
$page_title = '($' . number_format(todaySales ( $dayStart, $dayEnd, $am),0) . ') ' . $name . '&#39;s Tracker' ; 
include '../includes/header.php';

echo todaySales ($am, $dayStart, $dayEnd);
echo $todaysSales ['Sales'];

$days = array();
for($i=1; $i<=12; $i++) {
	$days[] = array(
	date('Y') . '-' . str_pad($i, 2, '0', STR_PAD_LEFT) . '-01 00:00:00',
	date('Y') . '-' . str_pad($i, 2, '0', STR_PAD_LEFT) . '-' . cal_days_in_month(CAL_GREGORIAN, $i, date('Y')) . ' 23:59:59');
}

$amGoals = accountManagerGoals ($_GET["am_id"]);
$percentToGoal = number_format((accountManagerSubtotal ($_GET["am_id"],$firstDayMonth,$currentMonthDate) / $amGoals[date("F")] * 100),0);

if(isset($_POST['submit'])) 
{ 
   
    $customerGroup = $_POST['customerGroupSelect'];
}

//Get the group Name if someone selects a group
$groupID = $customerGroup;
$result = mysqli_query($link, "SELECT customer_group_code
FROM customer_group
WHERE customer_group_id = $groupID;");
$groupName = mysqli_fetch_array( $result );

$months = array('January',
				'February',
				'March',
				'April',
				'May',
				'June',
				'July',
				'August',
				'September',
				'October',
				'November',
				'December'
				);

	$goalsChart = array();
	foreach($days as $a) {
		array_push($goalsChart, $amGoals[date("F",strtotime($a[0])) ]);
	}

	$actualsChart = array();
	foreach($days as $a) {

	array_push($actualsChart,accountManagerSubtotal ($_GET["am_id"],$a[0],$a[1]));

	}

	$actualsGroupChart = array();
	foreach($days as $a) {

	array_push($actualsGroupChart,accountManagerSubtotal ($_GET["am_id"],$a[0],$a[1],$customerGroup));

	} 

?>

<div class="container">
	<div class="row">
		<div class="col-xs-12">
			<h1><?php echo $name . "'s Tracker" ?></h1>
		</div>
	</div>
	<div class="row">
		<div class="col-sm-6">
			<h2><?php echo $monthText; ?> Report</h2>
				<table border="1px">
					<th><?php echo $monthText; ?> Goal</th><th>MTD</th><th>Left To Go</th>
					<tr>
						<td><?php echo "$" . number_format($amGoals[date("F")],0); ?></td>
						<td><?php echo "$" . number_format( accountManagerSubtotal ($_GET["am_id"],$firstDayMonth,$currentMonthDate),0) ?></td>
						<td><?php echo "$" . number_format($amGoals[date("F")] - accountManagerSubtotal ($_GET["am_id"],$firstDayMonth,$currentMonthDate)); ?></td>
					</tr>
				</table>
		</div>
		<div class="col-sm-6">
			<?php if ( $percentToGoal > $percentThroughMonth ) : ?>
			<h2 class="blue"><?php echo "Crushin' It!"  ?></h2>
			<?php else : ?>
			<h2 class="orange"><?php echo "Behind Schedule"  ?></h2>
			<?php endif ?>
			<table border="1px">
				<th>% through <?php echo $monthText; ?></th><th>% to Goal</th>
				<tr>
					<td><?php echo $percentThroughMonth . "%"; ?></td>
					<td><?php echo $percentToGoal . "%"; ?></td>
				</tr>
			</table>
		</div>
	</div>

	<br />
	<br />

	<div class="row">
		<?php if ($customerGroup == 'all' || !(isset($customerGroup))) { 
			echo "<h2>Total Sales</h2>";
			} elseif ($customerGroup) {
			echo "<h2>" . $groupName['customer_group_code'] . " Sales</h2>";
				}
				?>	
	</div>
	<div class="row">
		<p style="text-align: center;">
			<a href="/tracker/admin?am_id=<?php echo $am ?>&amp;name=<?php echo $name ?>">Update Goals</a>
		</p>
	</div>
	<br />
	<div class="row">
		<form method="post" action="/tracker/index.php?am_id=<?php echo $am ?>&amp;name=<?php echo $name ?>">
			<div class="col-xs-12 col-sm-3 col-sm-offset-4">
				<?php
					$result = mysqli_query($link, "SELECT DISTINCT (customer_group_code ), customer_group.customer_group_id
							FROM sales_flat_order s_order
							INNER JOIN customer_entity_int c_int ON c_int.entity_id = s_order.customer_id
							JOIN customer_group ON s_order.customer_group_id=customer_group.customer_group_id
							WHERE c_int.value = $am AND c_int.attribute_id = 9869;");
						Print '<select class="form-control" name="customerGroupSelect">'; 
						Print '<option selected value="x">Choose a Customer Group</option>';
						Print '<option value="all">All</option>';
						while($info = mysqli_fetch_array( $result )) {
							Print 
							'<option value="'. 
							htmlspecialchars($info['customer_group_id']) . '">' . 
							htmlspecialchars($info['customer_group_code']) . '</option>';
							}
						Print '</select>';
				?>
			</div>
			<div class="col-xs-12 col-sm-2">
				<!-- <input type="text" name="name"><br> -->
		   		<input type="submit" name="submit" value="Submit"><br>
		   	</div>
		</form> 
	</div>
	<p style="text-align:center;">Sales vs. Goals</p>
	<div class="row">
		<div class="hidden-xs col-sm-10 col-sm-offset-2">
			<?php if ($customerGroup == 'all' || !(isset($customerGroup))) { ?>
				<canvas id="myChart" width="590" height="200"></canvas>
				<script src="chart.js"></script>
				<script type="text/javascript">
				//Get the context of the canvas element we want to select
				var ctx = document.getElementById("myChart").getContext("2d");
				var data = {
					labels : ["January","February","March","April","May","June","July"],
					datasets : [
						{
							fillColor : "rgba(220,220,220,0.5)",
							strokeColor : "rgba(220,220,220,1)",
							pointColor : "rgba(220,220,220,1)",
							pointStrokeColor : "#fff",
							data : <?php echo json_encode($goalsChart); ?>

						},
						{
							fillColor : "rgba(151,187,205,0.5)",
							strokeColor : "rgba(151,187,205,1)",
							pointColor : "rgba(151,187,205,1)",
							pointStrokeColor : "#fff",
							data : <?php echo json_encode($actualsChart); ?>,
							
						}
					]
				}
				new Chart(ctx).Line(data); 
				</script>

			<?php } elseif ($customerGroup) { ?>
				<canvas id="myChart" width="590" height="200"></canvas>
				<script src="chart.js"></script>
				<script type="text/javascript">
				//Get the context of the canvas element we want to select
				var ctx = document.getElementById("myChart").getContext("2d");
				var data = {
					labels : ["January","February","March","April","May"],
					datasets : [
					
						{
							fillColor : "rgba(151,187,205,0.5)",
							strokeColor : "rgba(151,187,205,1)",
							pointColor : "rgba(151,187,205,1)",
							pointStrokeColor : "#fff",
							data : <?php echo json_encode($actualsGroupChart); ?>
						}
					]
				}
				new Chart(ctx).Line(data); </script>
			<?php } ?>
		</div>
	</div>
	<br />
	<div class="row">
		<div class="table-responsive">
		<table class="table">
			<tr>
				<th></th>
				<th>January</th>
				<th>February</th>
				<th>March</th>
				<th>April</th>
				<th>May</th>
				<th>June</th>
				<th>July</th>
				<th>August</th>
				<th>September</th>
				<th>October</th>
				<th>November</th>
				<th>December</th>
			</tr>
			<tr>
				<td>Goal</td>
				<?php			
				Print "<td>$" . number_format($amGoals['January'],0) . "</td> "; 
				Print "<td>$" . number_format($amGoals['February'],0) . "</td> ";
				Print "<td>$" . number_format($amGoals['March'],0) . "</td>"; 
				Print "<td>$" . number_format($amGoals['April'],0) . "</td>"; 
				Print "<td>$" . number_format($amGoals['May'],0) . "</td>"; 
				Print "<td>$" . number_format($amGoals['June'],0) . "</td> "; 
				Print "<td>$" . number_format($amGoals['July'],0). "</td> ";
				Print "<td>$" . number_format($amGoals['August'],0) . "</td>"; 
				Print "<td>$" . number_format($amGoals['September'],0) . "</td>"; 
				Print "<td>$" . number_format($amGoals['October'],0) . "</td>";
				Print "<td>$" . number_format($amGoals['November'],0) . "</td>"; 
				Print "<td>$" . number_format($amGoals['December'],0) . "</td>";
				?>		
			</tr>
			<tr>
				<td>Total Sales</td>
				<?php if ($customerGroup == 'all' || !(isset($customerGroup))) {
					foreach($days as $a) {
						echo "<td>$" . number_format( accountManagerSubtotal ($_GET["am_id"],$a[0],$a[1]),0);	
					} 

					echo "</td><tr><td>% of Goal</td>";

					foreach($days as $a) {
					echo "<td>" . number_format(accountManagerSubtotal ($_GET["am_id"],$a[0],$a[1]) / $amGoals[date("F",strtotime($a[0]))] * 100,0) . "%"; 
								}
				
					echo "</td></tr><tr><td>Number of Orders</td>";

					foreach($days as $a) {
					echo "<td>" . accountManagerOrderCount ($_GET["am_id"],$a[0],$a[1]);
					} 
					
					echo "</td></tr><tr><td>AOV</td>";
				
					foreach($days as $a) {
					echo "<td>$" . number_format( accountManagerSubtotal ($_GET["am_id"],$a[0],$a[1]) /accountManagerOrderCount ($_GET["am_id"],$a[0],$a[1]),0);
					} 
					
					echo "</td></tr>";

				} elseif ($customerGroup) {
					foreach($days as $a) {
					
					echo "<td>$" . number_format( 
						accountManagerSubtotal ($_GET["am_id"],$a[0],$a[1]),0);
					} 

					echo "</td><tr><td>" . $groupName['customer_group_code'] . " Sales</td>";
					
					foreach($days as $a) {

					echo "<td>$" . number_format( 
						accountManagerSubtotal ($_GET["am_id"],$a[0],$a[1],$customerGroup),0);
					} 

					echo "</td><tr><td>% of Total Sales</td>";

					foreach($days as $a) {
					echo "<td>" . number_format((accountManagerSubtotal ($_GET["am_id"],$a[0],$a[1],$customerGroup)) / accountManagerSubtotal ($_GET["am_id"],$a[0],$a[1]) * 100,1) . "%";
					} 

					
					echo "</td><tr><td>Number of Orders</td>";

					foreach($days as $a) {
					echo "<td>" . accountManagerOrderCount ($_GET["am_id"],$a[0],$a[1],$customerGroup);
					} 
					
					echo "</td></tr><tr><td>AOV</td>";
				
					foreach($days as $a) {
					echo "<td>$" . number_format( accountManagerSubtotal ($_GET["am_id"],$a[0],$a[1],$customerGroup) /accountManagerOrderCount ($_GET["am_id"],$a[0],$a[1],$customerGroup),0);
					} 
					
					echo "</td></tr>"; 
				} 
				?>
			</tr>
		</table>
	</div>
	</div>
	<div class="row">
		<h2>Retention List</h2>
	</div>
	<div class="row">
		<div class="col-xs-4 col-xs-offset-4">
			<form action="/tracker/retention?am_id=<?php echo $am ?>&amp;name=<?php echo $name ?>" method="post">
			<input type="submit" value="Run">
			</form>
		</div>
	</div>
	<div class="row">
		<p style="text-align:center;">This may take up to 30 seconds to run.</p>
	</div>
</div> <!-- container -->


<script type="text/javascript">

$(document).ready(function() 
    { 
        $("#thetable").tablesorter(); 
    } 
); 
</script>
</body>
</html>