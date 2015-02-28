<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo $page_title; ?></title>
	<!-- <link rel="stylesheet" type="text/css" href="style.css"> -->
	<!-- bootstrap -->
	<link rel="stylesheet" href="/css/bootstrap.min.css">	
	<link rel="stylesheet" type="text/css" href="<?php echo $page_style_href ?>">
	<script src="http://code.jquery.com/jquery.js"></script>
	<script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
	<link rel="apple-touch-icon-precomposed" href="/logo_iphone.png"/>
  <script type="text/javascript" src="/jquery-latest.js"></script> 
  <script type="text/javascript" src="jquery.tablesorter.js"></script>
</head>
<body>
<nav class="navbar navbar-default navbar-fixed-top" role="navigation">
  <div class="container-fluid">
    <!-- Brand and toggle get grouped for better mobile display -->
    <div class="navbar-header">
      <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#collapse">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a class="navbar-brand" href="/">Chalkfly Tools</a>
    </div>

    <!-- Collect the nav links, forms, and other content for toggling -->
    <div class="collapse navbar-collapse" id="collapse">
      <ul class="nav navbar-nav">
        <li class="dropdown">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown">Sales &amp; AM <b class="caret"></b></a>
          <ul class="dropdown-menu">
            <li><a href="/sales">Flash Report</a></li>
            <li><a href="/sales/detail">Annual Sales Report</a></li>
            <li class="divider"></li>
            <li><a href="http://chalkfly.com/accountmanager/index.html" target="_blank">The AMT</a></li>
            <li><a href="/sales/products">Top Products</a></li>
            <li><a href="/tracker/customer_service">Snalz Wonderland</a></li>
            <li class="divider"></li>
            <li><a href="/tracker/?am_id=938&name=Katie">Katie Turner</a></li>
            <li><a href="/tracker/?am_id=936&name=Sonali">Sonali Devarajan</a></li>
            <li><a href="/tracker/?am_id=937&name=Tre">Tre Mascola</a></li>
            <li><a href="/tracker/?am_id=935&name=Kyle">Kyle Steiner</a></li>    
            <li><a href="/tracker/?am_id=959&name=Alyse">Alyse Wesorick</a></li>
          </ul>
        </li>
        <li class="dropdown">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown">Marketing<b class="caret"></b></a>
          <ul class="dropdown-menu">
            <li><a href="/marketing/flashreport">Flash Report</a></li>
            <li class="divider"></li>
            <li><a href="http://creative.test.chalkfly.com/logo-standards.pdf" target="_blank">Logo Standards</a></li>
            <li><a href="https://docs.google.com/a/chalkfly.com/document/d/1f8bT2cpJP1V0RtJpYZQTc4ae-Jp6uFF_ycPimyVV8xY/edit" target="_blank">Brand Standards</a></li>
            
          
          </ul>
        </li>
        <li class="dropdown">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown">Operations<b class="caret"></b></a>
          <ul class="dropdown-menu">
            <!-- <li><a href="#">Flash Report</a></li> -->
            <li><a href="/operations/toolbox">Tool Box</a></li>
            <li><a href="/operations/optimize">Optimizer</a></li>
            <li class="divider"></li>
            <li><a href="https://qbo.intuit.com/" target="_blank">Quickbooks</a></li>
            <li><a href="https://ecc.webgility.com/Login/Index" target="_blank">ECC Webgility</a></li>
           	
          </ul>
        </li>
        <li class="dropdown">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown">Giveback<b class="caret"></b></a>
          <ul class="dropdown-menu">
            <li><a href="/giveback2/flashreport">Flash Report</a></li>
            <li class="divider"></li>
            <li><a href="http://tools.chalkfly.com/giveback/teachers">Transaction Analytics</a></li>
            <li><a href="http://blog.chalkfly.com/category/follow-the-giveback/" target="_blank">Follow the Giveback</a></li>
          </ul>
        </li>
        <li class="dropdown">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown">DevTeamSix<b class="caret"></b></a>
          <ul class="dropdown-menu">
            <li><a href="https://github.com/Chalkfly/issues/issues?state=open" target="_blank">GitHub</a></li>
            <li><a href="https://portal.nexcess.net/" target="_blank">Nexcess</a></li>
          </ul>
        </li>
        <li class="dropdown">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown">Culture<b class="caret"></b></a>
          <ul class="dropdown-menu">
            <li><a href="http://blog.chalkfly.com/" target="_blank">Blog</a></li>
            <li><a href="https://www.flickr.com/photos/119389602@N02/" target="_blank">Flickr</a></li>
            <li class="divider"></li>
            <li><a href="http://twitter.com/chalkfly" target="_blank">Twitter</a></li>
            <li><a href="http://facebook.com/chalkfly" target="_blank">Facebook</a></li>
            <li class="divider"></li>
            <li><a href="https://docs.google.com/a/chalkfly.com/spreadsheet/ccc?key=0AkEKh5dM6ubadEdEdWRWZ3ZfdXRFWFZ1cVMybzZqbkE&usp=drive_web#gid=2" target="_blank">Goal Tracker</a></li>
          </ul>
        </li>
        <li class="dropdown">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown">HR<b class="caret"></b></a>
          <ul class="dropdown-menu">
            <li><a href="http://myaphr.com/" target="_blank">Accesspoint</a></li>
            <li><a href="https://www.myaphr.com/hris/admin/upload/CHALKFLYHandbook_71520146145.pdf" target="_blank">Handbook</a></li>
            <li><a href="https://www.myaphr.com/hris/admin/upload/Chalkflyexpense_7142014115333.pdf" target="_blank">Expense Report</a></li>

            
          </ul>
        </li>
      </ul>
    </div><!-- /.navbar-collapse -->
  </div><!-- /.container-fluid -->
</nav>
