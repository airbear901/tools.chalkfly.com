<?php 

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

//query
$sql="INSERT INTO customer_gifts (customer_email, gift_id, date)
VALUES ('{$email}','{$gift_id}','{$date}');";

//errors
if (!mysqli_query($customerService,$sql)) {
  die('Error: ' . mysqli_error($customerService));
}
echo "Records added";

mysqli_close($customerService);
