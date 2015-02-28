<?php
error_reporting(E_ALL);
$am = $_GET["am_id"];
$name = $_GET["name"];
$page_title = $name . '&#39;s Admin Panel';
$page_style_href = '../../css/style.css';

include '../../includes/header.php';
include '../../includes/queries.php';
include '../../includes/functions.php';
include '../../includes/variables.php';

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


?>

<div class="container">
	<div class="row">
		<div class="col-xs-12">
			<h1><?php echo $name . '&#39;s Admin Panel' ?></h1>
		</div>
	</div>
	<div class="row">
		<div class="col-xs-12">
			<p style="text-align:center;">Use only numbers. Example: If your goal is $45,000, enter it below as 45000.</p>
		</div>
	</div>
	<div class="col-xs-12 col-sm-4 col-sm-offset-4">
		<form action="insert.php?am_id=<?php echo $am ?>" method="post">
			<?php foreach ($months as $value) {
		  	echo $value . ' Goal: <input type="text" name="' . $value . '_goal"><input type="submit">';
			} ?>
		
		</form> 
	</div>
</div>


</body>
</html>