<?php
header('Content-disposition: attachment; filename=product_id_export_sli.csv');
header('Content-type: text/plain');

		$con = mysql_connect("mce130-db1-int","chalkfly_mkting","Earthwise123")
			or die("Error: Connection");
		$db = mysql_select_db("chalkfly_magento", $con)
			or die ("Error: Database");

		echo "PRODUCT ID, SKU\r\n";

		$getIds = "SELECT entity_id,sku FROM catalog_product_entity";
			$findIds = mysql_query($getIds);
			while ($row = mysql_fetch_array($findIds)){

					$itemId = $row["entity_id"];
					$sku = $row["sku"];
					echo $itemId.",".$sku."\r\n";	
			}		

?>