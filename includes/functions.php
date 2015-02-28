<?php
//Teacher Queries


function teacherCount($startDate,$endDate) {

	$link = mysqli_connect("mce130-db1-int", "chalkfly_magento", "PixieSquawsCajoleBlink68") or die(mysqli_error()); 
		mysqli_select_db($link, "chalkfly_magento") or die(mysqli_error());

	$result = mysqli_query($link, "SELECT COUNT(customer_id) AS TeacherCount FROM enterprise_customersegment_customer WHERE segment_id=1;");
	$teacherCount = mysqli_fetch_array( $result );

	echo $teacherCount['TeacherCount'];
}



function givebackSentCount ($startDate,$endDate) {

	$link = mysqli_connect("mce130-db1-int", "chalkfly_magento", "PixieSquawsCajoleBlink68") or die(mysqli_error()); 
	mysqli_select_db($link, "chalkfly_magento") or die(mysqli_error());

	$result = mysqli_query($link,"SELECT COUNT(balance_id) AS TeacherGivenOutCount FROM enterprise_customerbalance_history WHERE action IN(1,2)
AND (updated_at BETWEEN '{$startDate}' AND '{$endDate}');");
	$givebackSentCount = mysqli_fetch_array( $result );

	echo $givebackSentCount['TeacherGivenOutCount'];
}

function teacherRedemptionCount ($startDate,$endDate) {

	$link = mysqli_connect("mce130-db1-int", "chalkfly_magento", "PixieSquawsCajoleBlink68") or die(mysqli_error()); 
	mysqli_select_db($link, "chalkfly_magento") or die(mysqli_error());

	$result = mysqli_query($link,"SELECT COUNT(balance_id) AS TeacherRedeemCount FROM enterprise_customerbalance_history 
WHERE action=3 AND updated_at BETWEEN '{$startDate}' AND '{$endDate}';");
	$teacherRedemptionCount = mysqli_fetch_array( $result );

	echo $teacherRedemptionCount['TeacherRedeemCount'];

}

function teacherAmountGivenOut ($startDate,$endDate) {

	$link = mysqli_connect("mce130-db1-int", "chalkfly_magento", "PixieSquawsCajoleBlink68") or die(mysqli_error()); 
	mysqli_select_db($link, "chalkfly_magento") or die(mysqli_error());

	$result = mysqli_query($link,"SELECT SUM(balance_delta) AS giveBackGivenOut FROM enterprise_customerbalance_history
WHERE (updated_at BETWEEN '$startDate' AND '$endDate')
AND (action IN(1,2));");
	$teacherAmountGivenOut = mysqli_fetch_array( $result );

	echo "$" . number_format($teacherAmountGivenOut['giveBackGivenOut'],0);

}

function teacherAmountRedeemed ($startDate,$endDate) {

	$link = mysqli_connect("mce130-db1-int", "chalkfly_magento", "PixieSquawsCajoleBlink68") or die(mysqli_error()); 
	mysqli_select_db($link, "chalkfly_magento") or die(mysqli_error());

	$result = mysqli_query($link,"SELECT SUM(balance_delta) AS giveBackRedeemed FROM enterprise_customerbalance_history WHERE (updated_at BETWEEN '$startDate' AND '$endDate') AND (action = 3);");
	$teacherAmountRedeemed = mysqli_fetch_array( $result );

	echo "$" . number_format($teacherAmountRedeemed['giveBackRedeemed'],0);

}

function marginAnalysis ($startDate,$endDate) {

	$qbo = mysqli_connect('mce130-db1-int', 'chalkfly_magento', 'PixieSquawsCajoleBlink68');
	mysqli_select_db($qbo, 'chalkfly_fartfactory');

	$sql = "SELECT created_at,increment_id AS OrderID, CONCAT (`customer_firstname`,' ',`customer_lastname`) AS Customer, customer_group_id AS CustomerGroup, ROUND (subtotal,2) AS Sale, SUM(amount) AS Cost, ROUND (subtotal - SUM(amount),2) AS Profit, ROUND (((subtotal - (SUM(amount)))/subtotal)*100,0) AS Margin
	FROM `chalkfly_magento`.`sales_flat_order`
	LEFT JOIN vendor_transaction_detail ON `chalkfly_magento`.`sales_flat_order`.`increment_id` = vendor_transaction_detail.order_id
	WHERE DATE BETWEEN '$startDate' AND '$endDate'
	GROUP BY increment_id;";


	$marginTable = mysqli_query($qbo, $sql);
	return $marginTable;

}

function marginAnalysisCustomerGroup ($startDate,$endDate,$groupId) {

	$qbo = mysqli_connect('mce130-db1-int', 'chalkfly_magento', 'PixieSquawsCajoleBlink68');
	mysqli_select_db($qbo, 'chalkfly_fartfactory');

	$sql = "SELECT created_at,increment_id AS OrderID, CONCAT (`customer_firstname`,' ',`customer_lastname`) AS Customer, customer_group_id AS CustomerGroup, ROUND (subtotal,2) AS Sale, SUM(amount) AS Cost, ROUND (subtotal - SUM(amount),2) AS Profit, ROUND (((subtotal - (SUM(amount)))/subtotal)*100,0) AS Margin
	FROM `chalkfly_magento`.`sales_flat_order`
	LEFT JOIN vendor_transaction_detail ON `chalkfly_magento`.`sales_flat_order`.`increment_id` = vendor_transaction_detail.order_id
	WHERE date BETWEEN '$startDate' AND '$endDate'
	AND customer_group_id='$groupId'
	GROUP BY increment_id;";



	$marginTableCustomerGroup = mysqli_query($qbo, $sql);
	return $marginTableCustomerGroup;

}

function searchResultsTable ($customerFirstName,$customerLastName,$andOr) {

	$qbo = mysqli_connect('mce130-db1-int', 'chalkfly_magento', 'PixieSquawsCajoleBlink68');
	mysqli_select_db($qbo, 'chalkfly_fartfactory');

	$sql = "SELECT created_at,increment_id AS OrderID, CONCAT (`customer_firstname`,' ',`customer_lastname`) AS Customer, customer_group_id AS CustomerGroup, ROUND (subtotal,2) AS Sale, SUM(amount) AS Cost, ROUND (subtotal - SUM(amount),2) AS Profit, ROUND (((subtotal - (SUM(amount)))/subtotal)*100,0) AS Margin
	FROM `chalkfly_magento`.`sales_flat_order`
	LEFT JOIN vendor_transaction_detail ON `chalkfly_magento`.`sales_flat_order`.`increment_id` = vendor_transaction_detail.order_id WHERE (customer_firstname LIKE '%$customerFirstName%' $andOr customer_lastname LIKE '%$customerLastName%') GROUP BY increment_id;";

	$searchResults = mysqli_query($qbo, $sql);
	return $searchResults;

}


function searchResultsTableOrderId ($orderNumber) {

	$qbo = mysqli_connect('mce130-db1-int', 'chalkfly_magento', 'PixieSquawsCajoleBlink68');
	mysqli_select_db($qbo, 'chalkfly_fartfactory');

	$sql = "SELECT created_at,increment_id AS OrderID, CONCAT (`customer_firstname`,' ',`customer_lastname`) AS Customer, customer_group_id AS CustomerGroup, ROUND (subtotal,2) AS Sale, SUM(amount) AS Cost, ROUND (subtotal - SUM(amount),2) AS Profit, ROUND (((subtotal - (SUM(amount)))/subtotal)*100,0) AS Margin
	FROM `chalkfly_magento`.`sales_flat_order`
	LEFT JOIN vendor_transaction_detail ON `chalkfly_magento`.`sales_flat_order`.`increment_id` = vendor_transaction_detail.order_id WHERE `chalkfly_magento`.`sales_flat_order`.`increment_id`= $orderNumber;";
	
	$searchResults = mysqli_query($qbo, $sql);

	return $searchResults;
}


function accountManagerSubtotal ($amCode,$startDate,$endDate,$groupID) {

	$link = mysqli_connect("mce130-db1-int", "chalkfly_magento", "PixieSquawsCajoleBlink68") or die(mysqli_error()); 
	mysqli_select_db($link, "chalkfly_magento") or die(mysqli_error());

	$sql = "SELECT SUM(subtotal) + SUM(discount_amount) AS Sales
		FROM sales_flat_order
		WHERE (customer_email IN (

			SELECT email FROM customer_entity
			JOIN customer_entity_int
			ON `customer_entity`.entity_id=`customer_entity_int`.entity_id
			WHERE `customer_entity_int`.attribute_id = (SELECT attribute_id FROM eav_attribute WHERE attribute_code='account_manager' LIMIT 1) AND `customer_entity_int`.value = $amCode)";


	
		if ($groupID) {
		$sql .= " AND customer_group_id = $groupID";
		}
				
		$sql .= " AND created_at BETWEEN '$startDate' AND '$endDate'
		AND status='complete');";
	
	$result = mysqli_query($link,$sql);
	$accountManagerSales = mysqli_fetch_array( $result );
	return $accountManagerSales['Sales'];

}


function retentionCustomers ($sixMonthsAgo,$firstOfCurrentMonth,$customerGroups) {

	$link = mysqli_connect("mce130-db1-int", "chalkfly_magento", "PixieSquawsCajoleBlink68") or die(mysqli_error()); 
	mysqli_select_db($link, "chalkfly_magento") or die(mysqli_error());

	$result = mysqli_query($link,"SELECT DISTINCT (CONCAT (customer_firstname,' ',customer_lastname)) AS Customer,customer_email AS Email,customer_group_code AS CustomerGroup
		FROM sales_flat_order
		JOIN customer_group ON sales_flat_order.customer_group_id=customer_group.customer_group_id
		WHERE (created_at BETWEEN '$sixMonthsAgo' AND '$firstOfCurrentMonth')
		AND (customer_email NOT IN (
		     SELECT DISTINCT(customer_email)
		     FROM sales_flat_order
		     WHERE (customer_group_id IN ($customerGroups))
		     AND (created_at BETWEEN '$firstOfCurrentMonth' AND NOW())
		))
		AND sales_flat_order.customer_group_id IN ($customerGroups)
		AND sales_flat_order.status='complete'
		GROUP BY sales_flat_order.customer_email;");

	return $result;

}


function retentionCustomersAM ($sixMonthsAgoAM,$firstOfCurrentMonthAM,$amID) {

	$link = mysqli_connect("mce130-db1-int", "chalkfly_magento", "PixieSquawsCajoleBlink68") or die(mysqli_error()); 
	mysqli_select_db($link, "chalkfly_magento") or die(mysqli_error());

	$sql = "SELECT 
			    (CONCAT (customer_firstname,' ',customer_lastname)) AS Customer,
			    s_order.customer_email AS Email,  
			    customer_group_code, 
			    MAX(created_at) AS maximum
			FROM
			    sales_flat_order s_order
			        INNER JOIN
			    customer_entity_int c_int ON c_int.entity_id = s_order.customer_id
			    	JOIN 
			    customer_group ON s_order.customer_group_id=customer_group.customer_group_id
			WHERE
			    c_int.value = $amID
			    	AND c_int.attribute_id = 9869
			        AND s_order.status = 'complete'
			        AND s_order.created_at >'$sixMonthsAgoAM'
					AND s_order.customer_email NOT IN (SELECT customer_email FROM sales_flat_order WHERE created_at > CURRENT_DATE - INTERVAL '30' DAY AND status='complete')
			GROUP BY customer_email;";


$result = mysqli_query($link, $sql);
return $result;
}

function accountManagerGoals ($amID) {

	$link = mysqli_connect('mce130-db1-int', 'chalkfly_magento', 'PixieSquawsCajoleBlink68');
	mysqli_select_db($link, 'chalkfly_fartfactory');

	$sql = "SELECT January,February,March,April,May,June,July,August,September,October,November,December 
			FROM goals WHERE id=$amID;";

	
	$result = mysqli_query($link, $sql);
	
	$accountmanagergoal = mysqli_fetch_array( $result );

	return $accountmanagergoal;
}

function accountManagerOrderCount ($amID,$startDate,$endDate,$groupID) {

$link = mysqli_connect("mce130-db1-int", "chalkfly_magento", "PixieSquawsCajoleBlink68") or die(mysqli_error()); 
	mysqli_select_db($link, "chalkfly_magento") or die(mysqli_error());

	$sql = "SELECT COUNT(DISTINCT increment_id) AS Count
			   	    
			FROM
			    sales_flat_order s_order
			        INNER JOIN
			    customer_entity_int c_int ON c_int.entity_id = s_order.customer_id
			    	JOIN 
			    customer_group ON s_order.customer_group_id=customer_group.customer_group_id
			WHERE
			    c_int.value = $amID
			    
			    	AND c_int.attribute_id = 9869
			        AND s_order.status = 'complete'
			        AND s_order.created_at >'$startDate'
			        AND s_order.created_at <'$endDate'";

	if ($groupID) {
		$sql .= " AND customer_group.customer_group_id = $groupID;";
		}

	$result = mysqli_query($link, $sql);
	$accountManagerCount = mysqli_fetch_array( $result );
	return $accountManagerCount ['Count'];
}

function accountManagerGroups ($amID) {

$link = mysqli_connect("mce130-db1-int", "chalkfly_magento", "PixieSquawsCajoleBlink68") or die(mysqli_error()); 
	mysqli_select_db($link, "chalkfly_magento") or die(mysqli_error());

	$sql = "SELECT DISTINCT (s_order.customer_group_id)
FROM sales_flat_order s_order
INNER JOIN customer_entity_int c_int ON c_int.entity_id = s_order.customer_id
JOIN customer_group ON s_order.customer_group_id=customer_group.customer_group_id
WHERE c_int.value = $amID AND c_int.attribute_id = 9869;";

	$result = mysqli_query($link, $sql);
	// $accountManagerGroups = mysqli_fetch_array( $result );
	return $result;

}

function todaySales ( $beginDay, $endDay, $am) {

	$link = mysqli_connect("mce130-db1-int", "chalkfly_magento", "PixieSquawsCajoleBlink68") or die(mysqli_error()); 
	mysqli_select_db($link, "chalkfly_magento") or die(mysqli_error());

	$sql = "SELECT SUM(subtotal) + SUM(discount_amount) AS Sales
			FROM sales_flat_order
			WHERE (customer_email IN (

			SELECT email FROM customer_entity
			JOIN customer_entity_int
			ON `customer_entity`.entity_id=`customer_entity_int`.entity_id
			WHERE `customer_entity_int`.attribute_id = (
				
				SELECT attribute_id FROM eav_attribute 
				WHERE attribute_code='account_manager' LIMIT 1
				) ";

			if ($am) {
		
			$sql .= " AND `customer_entity_int`.value = $am)";

			} else {
				$sql .= " )";
			}
			
			$sql .= " AND (created_at BETWEEN '$beginDay' AND '$endDay') 
			AND status IN ('complete','preprocessing','sent_to_warehouse'));";

	$result = mysqli_query($link, $sql);
	$todaysSales = mysqli_fetch_array( $result );
	return $todaysSales ['Sales'];

}

function monthSales ( $startDate,$endDate,$groupID ) {
	

	$link = mysqli_connect("mce130-db1-int", "chalkfly_magento", "PixieSquawsCajoleBlink68") or die(mysqli_error()); 
	mysqli_select_db($link, "chalkfly_magento") or die(mysqli_error());

	$snippet = '';

	if ($groupID) {
		$snippet .= " AND s_order.customer_group_id IN ($groupID) ";
		$snippet2 .= " AND ce.group_id IN ($groupID)";
	}

	$sql="SELECT COALESCE(SUM(subtotal),0) + COALESCE(SUM(discount_amount),0)
	 + (SELECT COALESCE(SUM(ecbh.balance_delta),0) FROM enterprise_customerbalance_history ecbh JOIN customer_entity ce ON ecbh.balance_id=ce.entity_id WHERE (ecbh.updated_at BETWEEN '{$startDate}' AND '{$endDate}')  AND ecbh.action=3 $snippet2) 
	 - (SELECT COALESCE(SUM(sfc.subtotal),0) AS creditDiscounts FROM sales_flat_creditmemo sfc JOIN sales_flat_order s_order ON s_order.increment_id=sfc.increment_id WHERE sfc.created_at BETWEEN '{$startDate}' AND '{$endDate}' $snippet)	
	 + (SELECT COALESCE(SUM(sfc.discount_amount),0) AS creditDiscounts FROM sales_flat_creditmemo sfc JOIN sales_flat_order s_order ON s_order.increment_id=sfc.increment_id WHERE sfc.created_at BETWEEN '{$startDate}' AND '{$endDate}' $snippet)
	 - (SELECT COALESCE(SUM(sfc.adjustment),0) AS creditAdjustments FROM sales_flat_creditmemo sfc JOIN sales_flat_order s_order ON s_order.increment_id=sfc.increment_id WHERE sfc.created_at BETWEEN '{$startDate}' AND '{$endDate}' $snippet)		
	 AS Sales	 		
FROM sales_flat_order s_order
JOIN customer_group ON s_order.customer_group_id=customer_group.customer_group_id
WHERE created_at BETWEEN '{$startDate}' AND '{$endDate}'
AND STATUS IN ('complete','preprocessing','processing','sent_to_warehouse','closed')
$snippet;";
	
	
	$result = mysqli_query($link, $sql);
	$monthSalesTotal = mysqli_fetch_array( $result );
	return $monthSalesTotal ['Sales'];

}

function totalSalesGoals () {

	$link = mysqli_connect('mce130-db1-int', 'chalkfly_magento', 'PixieSquawsCajoleBlink68');
	mysqli_select_db($link, 'chalkfly_fartfactory');

	$sql = "SELECT January,February,March,April,May,June,July,August,September,October,November,December 
			FROM company_goals ;";

	
	$result = mysqli_query($link, $sql);
	
	$companyGoal = mysqli_fetch_array( $result );

	return $companyGoal;
}

function totalSalesCount ($startDate,$endDate,$groupID) {

$link = mysqli_connect("mce130-db1-int", "chalkfly_magento", "PixieSquawsCajoleBlink68") or die(mysqli_error()); 
	mysqli_select_db($link, "chalkfly_magento") or die(mysqli_error());

	$sql = "SELECT COUNT(DISTINCT increment_id) AS Count
			   	    
			FROM sales_flat_order s_order        
			WHERE s_order.status = 'complete'
	        AND s_order.created_at >'$startDate'
	        AND s_order.created_at <'$endDate'";

	if ($groupID) {
		$sql .= " AND customer_group_id = $groupID;";
		}

	$result = mysqli_query($link, $sql);
	$totalOrderCount = mysqli_fetch_array( $result );
	return $totalOrderCount ['Count'];

}

function customerGifts () {

	$link = mysqli_connect('mce130-db1-int', 'chalkfly_magento', 'PixieSquawsCajoleBlink68');
	mysqli_select_db($link, 'chalkfly_fartfactory');

	$sql = "SELECT 
	sfo.customer_email, 
	CONCAT(sfo.customer_firstname,' ',sfo.customer_lastname) AS account_name, 
	cg.customer_group_code,
	CONCAT(sfoa.firstname,' ',sfoa.lastname,',',sfoa.street,',',sfoa.city,',',sfoa.region,',',sfoa.postcode) AS shipping_address,
	CONCAT(sfoa2.firstname,' ',sfoa2.lastname,',',sfoa2.street,',',sfoa2.city,',',sfoa2.region,',',sfoa2.postcode) AS billing_address,
	
	cg1.date AS Postcard,
	
	cg2.date	AS MoneyTree,
	cg3.date	AS Granola
FROM `chalkfly_magento`.`sales_flat_order` sfo
	JOIN `chalkfly_magento`.`customer_group` cg ON sfo.customer_group_id=cg.customer_group_id
	JOIN `chalkfly_magento`.sales_flat_order_address  sfoa ON sfo.entity_id=sfoa.parent_id AND sfoa.address_type = 'Shipping'
	JOIN `chalkfly_magento`.sales_flat_order_address  sfoa2 ON sfo.entity_id=sfoa2.parent_id AND sfoa2.address_type = 'Billing'
	LEFT JOIN customer_gifts cg1 ON sfo.customer_email=cg1.customer_email AND cg1.gift_id=1
	LEFT JOIN customer_gifts cg2 ON sfo.customer_email=cg2.customer_email AND cg2.gift_id=2
	LEFT JOIN customer_gifts cg3 ON sfo.customer_email=cg3.customer_email AND cg3.gift_id=3
WHERE created_at BETWEEN DATE_ADD(CURDATE(), INTERVAL 4 HOUR) AND DATE_ADD(NOW(), INTERVAL 4 HOUR)
GROUP BY customer_email;";

	
	$customerGift = mysqli_query($link, $sql);
	return $customerGift;
	

	
}

function optimizeSKU ($groupID) {

	$link = mysqli_connect('mce130-db1-int', 'chalkfly_magento', 'PixieSquawsCajoleBlink68');
	mysqli_select_db($link, 'chalkfly_magento');

	$sql = "SELECT cpe.sku,SUM(sfoi.qty_ordered) AS qty,sfoi.price, (SUM(sfoi.qty_ordered))*sfoi.price AS totalSpend, cpev.value,cpeu.value AS url,cpeu2.value AS url2   FROM catalog_product_entity cpe
JOIN catalog_product_index_price cpip ON cpip.entity_id=cpe.entity_id
JOIN catalog_product_entity_varchar cpev ON cpev.entity_id=cpe.entity_id 
JOIN sales_flat_order_item sfoi ON cpe.sku=sfoi.sku 
JOIN sales_flat_order sfo ON sfoi.order_id=sfo.entity_id 
LEFT JOIN catalog_product_entity_url_key cpeu ON cpeu.entity_id=sfoi.product_id
LEFT JOIN catalog_product_entity cpe2 ON cpe2.sku=cpev.value
left JOIN catalog_product_entity_varchar cpev2 ON cpev2.entity_id=cpe2.entity_id 
LEFT JOIN catalog_product_entity_url_key cpeu2 ON cpeu2.entity_id=cpe2.entity_id
WHERE cpev.attribute_id = 9838 
AND sfo.customer_group_id=$groupID
AND cpip.customer_group_id=$groupID
AND cpev.value IS NOT NULL 
GROUP BY cpe.sku 
ORDER BY SUM(sfoi.qty_ordered) DESC LIMIT 25;";

	
	$skusOptimize = mysqli_query($link, $sql);
	return $skusOptimize;


}

function alternateSkuPrice ($sku) {

	$link = mysqli_connect('mce130-db1-int', 'chalkfly_magento', 'PixieSquawsCajoleBlink68');
	mysqli_select_db($link, 'chalkfly_magento');

	$sql = "SELECT cpip.price FROM catalog_product_index_price cpip
			LEFT JOIN catalog_product_entity cpe ON cpip.entity_id=cpe.entity_id
			WHERE cpe.sku IN ('$sku')
			GROUP BY cpe.sku;";

	
	$result = mysqli_query($link, $sql);
	$altSkuPrice = mysqli_fetch_array( $result );
	return $altSkuPrice['price'];

}


function SkuCost ($sku) {

	$link = mysqli_connect('mce130-db1-int', 'chalkfly_magento', 'PixieSquawsCajoleBlink68');
	mysqli_select_db($link, 'chalkfly_magento');

	$sql = "SELECT cpev.value FROM catalog_product_index_price cpip 
			LEFT JOIN catalog_product_entity cpe ON cpip.entity_id=cpe.entity_id
			LEFT JOIN catalog_product_entity_varchar cpev ON  cpev.entity_id=cpe.entity_id
			WHERE cpe.sku IN ('$sku')
			AND cpev.attribute_id=203
			GROUP BY cpe.sku;";

	
	$result = mysqli_query($link, $sql);
	$SkuCost = mysqli_fetch_array( $result );
	return $SkuCost['value'];

}

function topProduct ($groupID) {

	$link = mysqli_connect('mce130-db1-int', 'chalkfly_magento', 'PixieSquawsCajoleBlink68');
	mysqli_select_db($link, 'chalkfly_magento');

	$sql = "SELECT product_id,sku,SUM(qty_ordered) AS qty,price,VALUE AS url FROM sales_flat_order_item sfoi
			LEFT JOIN sales_flat_order ON order_id=entity_id
			LEFT JOIN catalog_product_entity_url_key cpeu ON cpeu.entity_id=sfoi.product_id
			WHERE customer_group_id IN ($groupID)
			AND sfoi.created_at > DATE_SUB(NOW(), INTERVAL 3 MONTH) 
			GROUP BY sku
			ORDER BY SUM(qty_ordered) DESC
			LIMIT 50;";

	
	$topProducts = mysqli_query($link, $sql);
	return $topProducts;


}

function crossSell ($sku) {

	$link = mysqli_connect('mce130-db1-int', 'chalkfly_magento', 'PixieSquawsCajoleBlink68');
	mysqli_select_db($link, 'chalkfly_magento');

	$sql = "SELECT sku FROM sales_flat_order_item
			WHERE order_id IN (
			    SELECT DISTINCT(order_id) FROM sales_flat_order_item sfoi
			    WHERE sku = '{$sku}'
			    )
			AND sku != '{$sku}'
			GROUP BY sku
			ORDER BY sum(qty_ordered) DESC
			LIMIT 3;";

	
	$crossSellResult = mysqli_query($link, $sql);
	return $crossSellResult;


}
?>



