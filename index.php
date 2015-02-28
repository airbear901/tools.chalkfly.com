<?php

$page_style_href = '../css/style.css';
include 'includes/functions.php';
include 'includes/variables.php';
include 'includes/queries.php';

$dayStart = date("Y-m-d") . " 00:00:00";
$dayEnd = date("Y-m-d") . " 23:59:59";

$link = mysqli_connect("mce130-db1-int", "chalkfly_magento", "PixieSquawsCajoleBlink68") or die(mysqli_error()); 
	mysqli_select_db($link, "chalkfly_magento") or die(mysqli_error());
$sql = "SELECT SUM(subtotal) + SUM(discount_amount) AS Sales FROM sales_flat_order
WHERE (created_at BETWEEN '$dayStart' AND '$dayEnd')
AND status IN ('complete' , 'preprocessing','sent_to_warehouse','processing');";
$result = mysqli_query($link, $sql);
$todaysSales = mysqli_fetch_array( $result );

$percentToGoal = number_format(monthSales ((date("Y-m") . "-01 00:00:00"),(date("Y-m") . "-31 23:59:59"))/$revGoal * 100,0);

$page_title = "($" . number_format( $todaysSales['Sales'] ) . ') Chalkfly Tools';
include 'includes/header.php';

?>
<div class="container">
	<div class="row">
		<h1>Welcome to the Chalkfly Toolbox!</h1>
	</div>
	<div class="row">
		<div class="col-sm-12">
			<h2><?php echo $monthText; ?> Sales</h2>
			<table border="1px">
				<th><?php echo $monthText; ?> Goal</th><th>MTD</th><th>Left To Go</th>
				<tr>
					<td><?php echo "$" . number_format($revGoal,0); ?></td>
					<td><?php echo "$" . number_format(monthSales ((date("Y-m") . "-01 00:00:00"),(date("Y-m") . "-31 23:59:59")),0); ?></td>
					<td><?php echo "$" . number_format($revGoal - monthSales ((date("Y-m") . "-01 00:00:00"),(date("Y-m") . "-31 23:59:59")) ,0); ?></td>
				</tr>
			</table>
		</div>
		<div class="col-sm-12">
			  <?php if ( $percentToGoal > $percentThroughMonth ) : ?>
			<h2 class="blue"><?php echo "Crushin' It!"  ?></h2>
			<?php else : ?>
			<h2 class="orange"><?php echo "Behind Schedule"  ?></h2>
			<?php endif ?>

			<table border="1px">
				<th>% through Month</th><th>% to Goal</th>
				<tr>
					<td><?php echo $percentThroughMonth . "%"; ?></td>
					<td><?php echo $percentToGoal . "%"; ?></td>
					
				</tr>
			</table>
		</div>
	</div> <!-- row -->
</div> <!-- COntainer -->

</body>
</html>