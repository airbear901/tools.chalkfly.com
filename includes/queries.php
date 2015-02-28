<?php

include '/variables.php';

	//SQL Connect PRODUCTION
	$link = mysqli_connect("mce130-db1-int", "chalkfly_magento", "PixieSquawsCajoleBlink68") or die(mysqli_error()); 
	mysqli_select_db($link, "chalkfly_magento") or die(mysqli_error());

	$qbo = mysqli_connect('mce130-db1-int', 'chalkfly_magento', 'PixieSquawsCajoleBlink68');
mysqli_select_db($qbo, 'chalkfly_fartfactory');


	// Revenue Goals by Month
	if (date("m") == 04) {
		$revGoal = 426202;
	} elseif (date("m") == 05) {
		$revGoal = 443081;
	} elseif (date("m") == 06) {
		$revGoal = 480000;
	} elseif (date("m") == 07) {
		$revGoal = 480000;
	} elseif (date("m") == '08') {
		$revGoal = 500000;
	} elseif (date("m") == 09) {
		$revGoal = 1214546;
	} elseif (date("m") == 10) {
		$revGoal = 1496800;
	} elseif (date("m") == 11) {
		$revGoal = 1816043;
	} elseif (date("m") == 12) {
		$revGoal = 2113512;
	} else {
		echo "error with Monthly Goal!";
	}

	 if(isset($_POST['orderId']))
	{
	    $orderId = $_POST['name'];
	}

	if(isset($_POST['timeFrame'])) 
	{ 
	    $timeFrame = $_POST['dateRange'];
	}

//YEAR TO DATE REVENUE

	// YTD Revenue
	$result = mysqli_query($link, "SELECT SUM(subtotal) AS YTDTotalRevenue FROM sales_flat_order WHERE (created_at BETWEEN '{$firstDayYear}' AND '{$currentMonthDate}') AND status IN ('preprocessing','processing','sent_to_warehouse','complete','closed');") or die(mysqli_error()); 
	$YTDrevenue = mysqli_fetch_array( $result );
	

	//year credit memos subtotal
	$result = mysqli_query($link, "SELECT SUM(subtotal) AS YTDcredits FROM sales_flat_creditmemo WHERE created_at BETWEEN '{$firstDayYear}' AND '{$currentMonthDate}';") or die(mysqli_error());
	$YTDcredit = mysqli_fetch_array( $result );
	

	//monthly credit memo discounts
	$result = mysqli_query($link, "SELECT SUM(discount_amount) AS YTDcreditDiscounts FROM sales_flat_creditmemo WHERE created_at BETWEEN '{$firstDayMonth}' AND '{$currentMonthDate}';") or die(mysqli_error());
	$YTDcreditDiscount = mysqli_fetch_array( $result );

	//monthly credit memo adjustments
	$result = mysqli_query($link, "SELECT SUM(adjustment) AS YTDcreditAdjustments FROM sales_flat_creditmemo WHERE created_at BETWEEN '{$firstDayMonth}' AND '{$currentMonthDate}';") or die(mysqli_error());
	$YTDcreditAdjustment = mysqli_fetch_array( $result );

	//YTD Giveback
	$result = mysqli_query($link, "SELECT SUM(balance_delta) AS YTDgiveBack FROM enterprise_customerbalance_history WHERE (updated_at BETWEEN '{$firstDayYear}' AND '{$currentMonthDate}') AND (action = 3);") or die(mysqli_error());
	$YTDgiveback = mysqli_fetch_array( $result );
	


//MONTHLY REVENUE

	// Monthly Revenue
	$result = mysqli_query($link, "SELECT SUM(subtotal) AS MonthRevenue FROM sales_flat_order WHERE (created_at BETWEEN '{$firstDayMonth}' AND '{$currentMonthDate}') AND status IN('preprocessing','processing','sent_to_warehouse','complete','closed');") or die(mysqli_error());
	$revenue = mysqli_fetch_array( $result );

	//monthly credit memos subtotal
	$result = mysqli_query($link, "SELECT SUM(subtotal) AS credits FROM sales_flat_creditmemo WHERE created_at BETWEEN '{$firstDayMonth}' AND '{$currentMonthDate}';") or die(mysqli_error());
	$credit = mysqli_fetch_array( $result );

	//monthly credit memo discounts
	$result = mysqli_query($link, "SELECT SUM(discount_amount) AS creditDiscounts FROM sales_flat_creditmemo WHERE created_at BETWEEN '{$firstDayMonth}' AND '{$currentMonthDate}';") or die(mysqli_error());
	$creditDiscount = mysqli_fetch_array( $result );

	//monthly credit memo adjustments
	$result = mysqli_query($link, "SELECT SUM(adjustment) AS creditAdjustments FROM sales_flat_creditmemo WHERE created_at BETWEEN '{$firstDayMonth}' AND '{$currentMonthDate}';") or die(mysqli_error());
	$creditAdjustment = mysqli_fetch_array( $result );

	//Monthly Giveback
	$result = mysqli_query($link, "SELECT SUM(balance_delta) AS giveBack FROM enterprise_customerbalance_history WHERE (updated_at BETWEEN '{$firstDayMonth}' AND '{$currentMonthDate}') AND (action = 3);") or die(mysqli_error());
	$giveback = mysqli_fetch_array( $result );


//TOTALS

	//YTD
		//TOTAL YTD credit memos
		$YTDfinalCreditMemos = $YTDcredit['YTDcredits'] + $YTDcreditDiscount['YTDcreditDiscounts'] - $YTDcreditAdjustment['YTDcreditAdjustments'];
		
		//Sales - credits - Giveback
		$YTDfinalRevenue = $YTDrevenue['YTDTotalRevenue'] - $YTDfinalCreditMemos + $YTDgiveback['YTDgiveBack'];


	//MONTHlY

		//TOTAL monthly credit memos
		$finalCreditMemos = $credit['credits'] + $creditDiscount['creditDiscounts'] - $creditAdjustment['creditAdjustments'];
		
		//Sales - credits - Giveback
		$finalRevenue = $revenue['MonthRevenue'] - $finalCreditMemos + $giveback['giveBack'];

		//Revenue needed to reach goal
		$revtogo = $revGoal - $finalRevenue;


		//FOC revenue - NOTE: FOC customers groups are hardcoded in
		$result = mysqli_query($link, "SELECT SUM(subtotal) AS FOCrevenue FROM sales_flat_order WHERE (created_at BETWEEN '{$firstDayMonth}' AND '{$currentMonthDate}') AND (customer_group_id IN({$focGroups})) AND status IN('preprocessing','processing','sent_to_warehouse','complete','closed');") or die(mysqli_error());
		$FOCrevenue = mysqli_fetch_array( $result );

		//Non-FOC Revenue
		$nonFOC = $finalRevenue - $FOCrevenue['FOCrevenue'];

		//B2C Revenue
		$result = mysqli_query($link, "SELECT SUM(subtotal) AS revenue FROM sales_flat_order WHERE (created_at BETWEEN '{$firstDayMonth}' AND '{$currentMonthDate}') AND (customer_group_id IN({$b2cGroups})) AND status IN('preprocessing','processing','sent_to_warehouse','complete','closed');") or die(mysqli_error());
		$B2Crevenue = mysqli_fetch_array( $result );

		//B2C orders for the month
		$result = mysqli_query($link, "SELECT COUNT(customer_group_id) AS B2CcustomerCount FROM sales_flat_order WHERE (created_at BETWEEN '{$firstDayMonth}' AND '{$currentMonthDate}') AND (customer_group_id IN ({$b2cGroups})) AND status IN('preprocessing','processing','sent_to_warehouse','complete','closed');") or die(mysqli_error());
		$B2Corders = mysqli_fetch_array( $result );
		
		
		//B2C customers for the month
		$result = mysqli_query($link, "SELECT COUNT(DISTINCT subtotal) AS NumberOfCustomers FROM sales_flat_order WHERE (created_at BETWEEN '{$firstDayMonth}' AND '{$currentMonthDate}') AND (customer_group_id IN ({$b2cGroups})) AND (status IN('preprocessing','processing','sent_to_warehouse','complete','closed'));") or die(mysqli_error());
		$B2Ccustomers = mysqli_fetch_array( $result );
		

		//B2B revenue
		$B2Brevenue = $finalRevenue - $B2Crevenue['revenue'];

	// % through the month, % to goal
		$percentThroughMonth = number_format((date("j") / date("t") * 100),0);
		$percentToGoal = number_format(($finalRevenue / $revGoal * 100),0);


		// Find the total cost by order ID
$result = mysqli_query($qbo, "SELECT order_id, SUM(amount) AS totalCost FROM vendor_transaction_detail WHERE order_id='$orderId';");
$vendorCost = mysqli_fetch_array($result);

//find the total amount paid
$result = mysqli_query($link, "SELECT total_paid FROM sales_flat_order WHERE increment_id='$orderId';");
$saleSubtotal = mysqli_fetch_array($result);

//total amount of tax collected
$result = mysqli_query($link, "SELECT tax_amount FROM sales_flat_order WHERE increment_id='$orderId';");
$saleTaxAmount = mysqli_fetch_array($result);

// $marginTable = mysqli_query($qbo, "SELECT date,increment_id AS OrderID,
// CONCAT (`customer_firstname`,' ',`customer_lastname`) AS Customer,
// customer_group_id AS CustomerGroup,
// ROUND (subtotal,2) AS Sale,
// amount AS Cost,
// ROUND (subtotal - amount,2) AS Profit,
// ROUND (((subtotal - amount)/subtotal) * 100,0) AS Margin
// FROM `chalkfly_magento`.`sales_flat_order`,vendor_transaction_detail
// WHERE  `chalkfly_magento`.`sales_flat_order`.`increment_id` = vendor_transaction_detail.order_id
// AND date BETWEEN '{$start}' AND '{$currentMonthDate}';");
// $marginTableQuery = mysqli_fetch_array($marginTable);

$marginTable = mysqli_query($qbo, "SELECT date,increment_id AS OrderID, CONCAT (`customer_firstname`,' ',`customer_lastname`) AS Customer, customer_group_id AS CustomerGroup, ROUND (subtotal,2) AS Sale, SUM(amount) AS Cost, ROUND (subtotal - SUM(amount),2) AS Profit, ROUND (((subtotal - (SUM(amount)))/subtotal)*100,0) AS Margin
FROM `chalkfly_magento`.`sales_flat_order`
LEFT JOIN vendor_transaction_detail ON `chalkfly_magento`.`sales_flat_order`.`increment_id` = vendor_transaction_detail.order_id
WHERE DATE BETWEEN '{$start}' AND '{$currentMonthDate}'
GROUP BY increment_id;");
$marginTableQuery = mysqli_fetch_array($marginTable);


?>