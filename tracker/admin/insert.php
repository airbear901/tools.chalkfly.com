<?php
error_reporting(E_ALL);
$am = $_GET["am_id"];
$name = $_GET["name"];
$page_title = $name . '&#39;s Admin Panel';
$page_style_href = '../../css/style.css';


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

$goals = mysqli_connect('mce130-db1-int', 'chalkfly_magento', 'PixieSquawsCajoleBlink68');
	mysqli_select_db($goals, 'chalkfly_fartfactory');

// Check connection
if (mysqli_connect_errno()) {
  echo "Failed to connect to MySQL: " . mysqli_connect_error();
}

// escape variables for security

foreach ($months as $value) {
		  	
	if (!empty($_POST[$value . "_goal"])) {

    	$goal = mysqli_real_escape_string($goals, $_POST[$value . '_goal']);
    	$sql="UPDATE goals
		SET $value='$goal'
		WHERE id='$am';";

	}
}


if (!mysqli_query($goals,$sql)) {
  die('Error: ' . mysqli_error($goals));
}
echo "Records added";

mysqli_close($goals);

?>