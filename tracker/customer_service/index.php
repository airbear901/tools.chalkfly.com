<?php
error_reporting(E_ALL);
$page_title = 'Snalz Wonderland';
$page_style_href = '../../css/style.css';
include '../../includes/header.php';
include '../../includes/queries.php';
include '../../includes/functions.php';
include '../../includes/variables.php';

$Date = date("Y-m-d", strtotime('-4 hours'));

if(isset($_POST['submit'])) { 

 	$customerService = mysqli_connect('mce130-db1-int', 'chalkfly_magento', 'PixieSquawsCajoleBlink68');
	mysqli_select_db($customerService, 'chalkfly_fartfactory');

	// Check connection
	if (mysqli_connect_errno()) {
	  echo "Failed to connect to MySQL: " . mysqli_connect_error();
	}

	//set variables
	$date = $_POST['dateInput'];
	$email = $_POST['emailInput'];
	$gift_id = $_POST['giftDropdown'];
	$giftName = 'Gift';

	if ($gift_id == 1) {$giftName = 'Postcard';}
	if ($gift_id == 2) {$giftName = 'Money Tree';}
	if ($gift_id == 3) {$giftName = 'Granola';}

	//query
	$sql="INSERT INTO customer_gifts (customer_email, gift_id, date)
	VALUES ('{$email}','{$gift_id}','{$date}');";

	//errors
	if (!mysqli_query($customerService,$sql)) {
	  die('Error: ' . mysqli_error($customerService));
	}
	echo "<h2>" . $giftName. " added for " . $email . " :]</h2>";

	mysqli_close($customerService);
} 

$customerGift = customerGifts();

?>
<div class="container">
	<div class="row">
		<div class="col-xs-12">
			<h1><?php echo 'Customer Service Panel' ?></h1>
		</div>
	</div>
	<div class="col-xs-12 col-sm-6 col-sm-offset-3">
		<form action="/tracker/customer_service/index.php" method="post" class="form-inline" role="form">
			<div class="form-group">	
		  		<input type="text" name="emailInput" placeholder="Email">
		  	</div>
		  	<div class="form-group">	
		  		<input type="text" name="dateInput" value="<?php echo $Date; ?>">
		  	</div>
		  	<div class="form-group">
			    <select name="giftDropdown">
					<option value="1" name="postcard">Postcard</option>
					<option value="2" name="money_tree">Money Tree</option>
					<option value="3" name="granola">Granola</option>
				</select>
		  	</div>
		  	<button type="submit" class="btn" name="submit" >Snalz</button>
		  	<!-- <input type="submit" name="submit"> -->
		</form>	
	</div>			
	<?php 
		echo "<table cellpadding=1 class='table tablesorter' id='thetable'>"; 
		echo "<thead>";
		echo "<th>Email</th><th>Name</th><th>Group</th><th>Billing Address</th><th>Shipping Address</th><th>Postcard</th><th>Money Tree</th><th>Granola</th>";
		echo "</thead>";
		 while($info = mysqli_fetch_array( $customerGift )) 
		 { 
		echo "<tr>"; 
		echo "<td>".$info['customer_email'] . "</td> "; 
		echo "<td>".$info['account_name'] . "</td> ";
		echo "<td>".$info['customer_group_code'] . "</td> ";
		echo "<td>".$info['billing_address'] . "</td> ";
		echo "<td>".$info['shipping_address'] . "</td> ";
		echo "<td>".$info['Postcard'] . "</td> ";
		echo "<td>".$info['MoneyTree'] . "</td>";
		echo "<td>".$info['Granola'] . "</td></tr> ";
		} 
		echo "</table>"; 
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