<!Doctype html>
<html moznomarginboxes mozdisallowselectionprint>
 <head>
  <title>Unicenta POS Reports</title>
	<!-- Latest compiled and minified CSS -->
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

	<!-- Optional theme -->
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">
  
  <style>
	@media print {
		a[href]:after {
		    content: none !important;
		}
	}
	  @media print {
	.noprint {
		display:none;
	  }
	}
	  
	body {
	  margin:0;
	  padding: 0;
	  width: 100%;
	  height:100%; 
	}
	.container {
		background-color: #fff;
	}
	.bg-grey {background: grey;}
    .header {font-size: 14pt; color: #FFF; background: #2a5d84; padding: 15px;}
	.header a {color: #FFF;}	
	.header a.active {color: yellow; font-weight: bold;}
	p.alert { padding: 5px; }
	.search {padding:10px; background: wheat;}
	#term {padding: 5px;width: 300px;}	
	.bold {font-weight: bold;}
	.underline {text-decoration: underline;}	
	.align-right {text-align: right; }
	.discount-highlight {color: green;}
	table.sales thead tr th:nth-child(6){ text-align:right;}
	table.sales tbody tr td:nth-child(6){ text-align:right; text-decoration: underline;}
	table.details thead tr th:nth-child(2){ text-align:right;}
	table.details tbody tr td:nth-child(2){ text-align:right;}
	table.details thead tr th:nth-child(3){ text-align:center;}
	table.details tbody tr td:nth-child(3){ text-align:center;}	
	table.details thead tr th:nth-child(4){ text-align:right;}
	table.details tbody tr td:nth-child(4){ text-align:right;}	
	table.details {font-size: small;}
  </style>
 </head>
 <body>
  <div class='container'>
   <div class='row'>
    <div class='col-md-12'>
  <?php
    /*
     * Unicenta oPOS Reports using PHP
     * William Sengdara Jaunary 27 2023
     *
     * I created this because a client could not find line discounts on any of the reports
     * You can use this to create your own flexible custom reports
     *
     * How discounts are stored in the database in a BLOB
     
	 *
		<?xml version="1.0" encoding="UTF-8"?>
		<!DOCTYPE properties SYSTEM "http://java.sun.com/dtd/properties.dtd">
		<properties>
			<comment>uniCenta oPOS</comment>
			<entry key="product.taxcategoryid">001</entry>
			<entry key="product.printer">1</entry>
			<entry key="product.name">Line Discount 20%</entry>
		</properties>
	*   
    */
  
    // menu (title->view)
  	$urls = ['All Sales'=>'sales', 
  	         'Discounts'=>'discounts', 
  	         'Stock Update'=>'stock-update', 
  	         'Stock in Minus'=>'stock-minus'];

  	// database settings
    $user = 'user';
	$pwd  = 'password';
	$db   = 'unicentaopos';
	$host = 'localhost';
	$currency = 'USD';
	$max = 20; // records to return for default
	
	// connect to MySQL server before doing anything more
	$conn = new mysqli($host, $user,$pwd, $db ) or die($mysqli->error);
	 
	// how we show transactions with discounts applied
	// SELECT distinct(ticket) FROM ticketlines WHERE attributes LIKE '%Discount%';
		 
	// we list the table columns dynamically, we can filter out the ones we don't need to show
	$ignored = ['id', 'code', 'codetype'];
	
	// by default don't show the products in a transaction   	         
	$hidedetails = false; 
	
	$view = @ $_GET['view'];
	
	$date_start = @ $_GET['date_start'];
	$date_end   = @ $_GET['date_end'];
	$time_start = urldecode(@ $_GET['time_start']);
    $time_end   = urldecode(@ $_GET['time_end']);
	$time_start = strlen($time_start) ? $time_start : '00:00:00';
	$time_end   = strlen($time_end) ? $time_end : '23:50:00';
	$personid   = @ $_GET['person'];
	$receiptid  = (int) @ $_GET['receiptid'];

	$tmp  = [];
  	foreach($urls as $title=>$link){
  		$view = !strlen($view) ? $link : $view; // default view is first item if not specified
  		$class = ($view == $link) ? "active" : '';
  		array_push($tmp, "<a href='?view=$link' class='$class'>$title</a>");
  	}
  	$tmp = implode(' &middot; ', $tmp);
 
  	echo "<div class='header'>$tmp</div>";
	echo "<div class='search noprint'>
	
	        <!-- form to filter report by ticket/receipt id -->
			<form class='noprint'>
			 <input type='hidden' name='view' value=\"$view\">
			 <b>Ticketid</b> <input type='number' name='receiptid' required value=\"$receiptid\">   	    	 
	   	     <input type='submit' value='Report for Ticketid' class='btn btn-sm btn-primary'>
			</form>
						
	        <!-- form to filter report by date -->						
			<form>
			 <input type='hidden' name='view' value=\"$view\">
			 <b>Start Date</b> <input type='date' name='date_start' required value=\"$date_start\">
			 <input type='time' name='time_start' required value=\"$time_start\"> &middot;
			 <b>End Date</b> <input type='date' name='date_end' required value=\"$date_end\">
   	    	 <input type='time' name='time_end' required value=\"$time_end\">
	   	     <input type='submit' value='Report for Dates' class='btn btn-sm btn-primary'>
			</form>
		 </div>
		 <p>&nbsp;</p>";
		 
		 // we only include filter for these views
		 if (in_array($view, ['stock-minus', 'stock-update'])){
			echo "<div class='search'>
				   <input type='text' class='form-control filter' placeholder='Search for something below' name='term' id='term'>
				  </div>
				  <script>
						window.addEventListener('load', ()=>{
							// Get the table element
							var table = document.querySelector('table');
							
							// Get the search input element
							var input = document.getElementById('term');
							
							// Add an event listener to the input element that will fire a function when the input value changes
							input.addEventListener('keyup', filterTable);
							
							function filterTable() {
							  // Get the search input value
							  var filterValue = input.value.toUpperCase();
							
							  // Get all of the rows in the table using querySelectorAll
							  var rows = table.querySelectorAll('tr');
							
							  // Loop through each row
							  for (var i = 0; i < rows.length; i++) {
								  var text = rows[i].textContent || rows[i].innerText;
								// If a match was found in any cell of the row, show the row
								rows[i].style.display = (text.toUpperCase().indexOf(filterValue) > -1)  ? '' : 'none';
							  }
							}								
					},false);
				  </script>";
			}

  	 $sql = '';
	 
  	switch ($view){
  	    case 'sales':
	  	    // add to list ofignored columns
			$ig = ['units', 'money', 'receipt', 'tip', 'transid', 'isprocessed',
			       'returnmsg', 'personid',	'notes','tendered',	'cardname',	
			       'voucher','ticketidx', 'tickettype', 'customer', 'status'];			       
			foreach($ig as $col) {
				array_push($ignored, $col);
			}  	    
								
			if ($personid){
				$sql = "SELECT 
								DISTINCT t.ticketid,
								datenew AS `Date`, pp.id as personid, pp.name AS person, payment, total, receipt
							FROM
								receipts r
									INNER JOIN payments p ON r.id = p.receipt
								INNER JOIN tickets t ON t.id = r.id
								INNER JOIN ticketlines tl ON tl.ticket = t.id
								INNER JOIN people pp ON pp.id = t.person
							WHERE
								pp.id = '$personid'				
							ORDER BY 
								r.datenew DESC;";	
			} elseif ($receiptid){
				$sql = "SELECT 
								DISTINCT t.ticketid,
								datenew AS `Date`, pp.id as personid, pp.name AS person,payment, total,  receipt
							FROM
								receipts r
									INNER JOIN payments p ON r.id = p.receipt
								INNER JOIN tickets t ON t.id = r.id
								INNER JOIN ticketlines tl ON tl.ticket = t.id
								INNER JOIN people pp ON pp.id = t.person
							WHERE
								t.ticketid = '$receiptid'
							ORDER BY 
								r.datenew DESC;";	
												
			} elseif ($date_start && $date_end){
					$hidedetails = false;
					$sql = "SELECT 
									DISTINCT ticketid,datenew AS `Date`, pp.id as personid, pp.name AS person,payment, total,   receipt
								FROM
									receipts r
										INNER JOIN payments p ON r.id = p.receipt
									INNER JOIN tickets t ON t.id = r.id
									INNER JOIN ticketlines tl ON tl.ticket = t.id
									INNER JOIN people pp ON pp.id = t.person
								WHERE
									(r.datenew BETWEEN '$date_start $time_start' AND '$date_end $time_end') 
								ORDER BY 
									r.datenew ASC;";	
																					
			} else {
				$hidedetails = true;
				$sql = "SELECT 
								DISTINCT t.ticketid,
								datenew AS `Date`, pp.id as personid, pp.name AS person,payment,  total, receipt
							FROM
								receipts r
									INNER JOIN payments p ON r.id = p.receipt
								INNER JOIN tickets t ON t.id = r.id
								INNER JOIN ticketlines tl ON tl.ticket = t.id
								INNER JOIN people pp ON pp.id = t.person
							
							ORDER BY 
								r.datenew DESC LIMIT $max;";
			}
  	    	break;
  	    	
		case 'discounts':  		

			$ig = ['units', 'money', 'receipt', 'tip', 'transid', 'isprocessed',	'returnmsg', 'personid',	'notes',	'tendered',	'cardname',	'voucher','ticketidx', 'tickettype', 'customer', 'status'];
			foreach($ig as $col) {
				array_push($ignored, $col);
			}

			if ($personid){
				$sql = "SELECT 
								DISTINCT t.ticketid,
								datenew AS `Date`, pp.id as personid, pp.name AS person,payment, total,  receipt
							FROM
								receipts r
									INNER JOIN payments p ON r.id = p.receipt
								INNER JOIN tickets t ON t.id = r.id
								INNER JOIN ticketlines tl ON tl.ticket = t.id
								INNER JOIN people pp ON pp.id = t.person
							WHERE
								pp.id = '$personid' AND
									tl.attributes LIKE '%Discount%'								
							ORDER BY 
								r.datenew DESC;";		
										
			} elseif ($receiptid){				
				$sql = "SELECT 
								DISTINCT t.ticketid,
								datenew AS `Date`, pp.id as personid, pp.name AS person,payment,  total, receipt
							FROM
								receipts r
									INNER JOIN payments p ON r.id = p.receipt
								INNER JOIN tickets t ON t.id = r.id
								INNER JOIN ticketlines tl ON tl.ticket = t.id
								INNER JOIN people pp ON pp.id = t.person
							WHERE
								t.ticketid = $receiptid
							ORDER BY 
								r.datenew DESC;";				
								
			} else if ($date_start && $date_end){
					$sql = "SELECT 
									DISTINCT ticketid, datenew AS `Date`, pp.id as personid, pp.name AS person,payment,  total,  receipt
								FROM
									receipts r
										INNER JOIN payments p ON r.id = p.receipt
									INNER JOIN tickets t ON t.id = r.id
									INNER JOIN ticketlines tl ON tl.ticket = t.id
									INNER JOIN people pp ON pp.id = t.person
								WHERE
									(r.datenew BETWEEN '$date_start $time_start' AND '$date_end $time_end') AND
									tl.attributes LIKE '%Discount%'								
								ORDER BY 
									r.datenew ASC;";	
																	
			} else {
					    $hidedetails = true;	
				$sql = "SELECT 
								ticketid,datenew AS `Date`, pp.id as personid, pp.name AS person,payment, total,   receipt
							FROM
								receipts r
									INNER JOIN payments p ON r.id = p.receipt
								INNER JOIN tickets t ON t.id = r.id
								INNER JOIN ticketlines tl ON tl.ticket = t.id
								INNER JOIN people pp ON pp.id = t.person
							WHERE
								tl.attributes LIKE '%Discount%'
							ORDER BY 
								r.datenew DESC LIMIT $max;";		
			}
						
			break;
			
  		case 'stock-minus':
  			 $hidedetails = true;
  			 //$ignored = [];
			 $sql = "SELECT 
			 				p.id, 
			 				c.name AS category, 
			 				p.name, 
			 				p.code, 
			 				p.codetype, 
			 				sc.units 
			 		 FROM products p
			 		 	INNER JOIN categories c ON c.id = p.category
			 		 	INNER JOIN stockcurrent sc ON sc.product = p.id 
			 		 WHERE
			 		 	sc.units < 0
			 		 ORDER BY c.name ASC;";					 		 			 		 		
  			break;

  		case 'stock-update':
	   		 $hidedetails = true;  		
  			 
			 $sql = "SELECT 
			 				p.id, 
			 				c.name AS category, 
			 				p.name, 
			 				p.code, 
			 				p.codetype, 
			 				sc.units 
			 		 FROM products p
			 		 	INNER JOIN categories c ON c.id = p.category
			 		 	INNER JOIN stockcurrent sc ON sc.product = p.id 
			 		 ORDER BY c.name ASC;";				                            	
  	}
	 
	 // did we get a submit?
	 $extra = (int) @ $_POST['extra'];
	
	 if ($extra){
		 $id = $_POST['id'];
		 $product = $_POST['product'];
		 $units = (int) $_POST['units'];
		 echo alert("<b>$product</b> has been updated to $units units. It may no longer show in this view.", 'success');
		 $sql0 = "UPDATE `stockcurrent` SET units=$units WHERE product='$id';";
		 $conn->query($sql0) or die(alert($conn->error, 'red'));
	 }

	 //echo alert($sql, 'success');
	 if ($personid) echo alert("Showing $view transactions only for selected employee &middot; <a href='?view=$view'>Show for All</a>", 'info');
	 elseif ($receiptid) echo alert("Showing only receipt <b>$receiptid</b> &middot; <a href='?view=$view'>Show All</a>", 'info');
	 elseif ($date_start && $date_end) echo alert("Showing $view between <b>$date_start $time_start</b> and <b>$date_end $time_end</b> &middot; <a href='?view=$view'>Show All</a>", 'info');
	 else if (!in_array($view, ['stock-update', 'stock-minus']))	echo alert("Showing last $max <b>$view</b> transactions.", 'info');

	 $ret = $conn->query($sql) or die('xx'.$mysqli->error);
	 if (!$ret || !$ret->num_rows){
		 echo alert("Your parameters did not return any data. Double-check start and end date/time.", 'warning');
	 } else {
	     
		$idx = 1;
		$cols_ = [];
		$cols[] = '<th>#</th>'; // counter
		$cols_[] = '#';
	    $data = '';
	    
		while ($columns = $ret->fetch_field()){
			$col = $columns->name;

			if (!in_array($col, $ignored)){
				$cols_[] = $col;
				$col = ucwords($col);
				$cols[] = "<th>$col</th>";
			}
		}

	    $max_cols = sizeof($cols) ? sizeof($cols)-2 : 0;
	    	     
		if (sizeof($cols)){
			$cols = implode("", $cols);
		} else {
			$cols = "";
		}     

		$grandtotal = 0;
		
		while ( $row = $ret->fetch_array() ){
			$data .= "<tr>";
			$data .= "<td>$idx.</td>";
			$details ='';
			
			$receipt = @$row['receipt'];
			$sql0 = "SELECT line, 
							(CASE WHEN product IS NULL THEN '_line_discount_' ELSE (SELECT p.name FROM products p WHERE p.id=tl.product) END) AS product, 
							price,tl.units, tl.attributes
					 FROM 
					 	ticketlines tl 
					 WHERE tl.ticket='$receipt';";
			$ret0 = $conn->query($sql0) or die($mysqli->error);
			if (!$ret0 || !$ret0->num_rows){
			} else {
				while ($row0 = $ret0->fetch_array()){
						$item=$row0['product'];
						$units = 1;
						
						if ($item == '_line_discount_'){
							/*
							<?xml version="1.0" encoding="UTF-8"?>
							<!DOCTYPE properties SYSTEM "http://java.sun.com/dtd/properties.dtd">
							<properties>
								<comment>uniCenta oPOS</comment>
								<entry key="product.taxcategoryid">001</entry>
								<entry key="product.printer">1</entry>
								<entry key="product.name">Line Discount 20%</entry>
							</properties>
							*/
							$attributes = $row0['attributes'];
							$xml = simplexml_load_string($attributes);
							if ($xml === false){
								$product = 'error';
							} else {

								$item  = $xml->entry[2];
								$units = (int) $xml->entry[1];
								// Filter the Numbers from String
								$perc = (int)filter_var($item, FILTER_SANITIZE_NUMBER_INT);
								$item = "<span class='discount-highlight bold'>$item</span>";
								$price = $perc/100;
								$value = 0;
							}							
						}
						
						//$units=(int)$row0['units'];
						$rate=0.15;//(float)$row0['rate'];
						$price =$row0['price'];
						$pricetax= $price * $rate;
						$priceandtax = ($price+$pricetax);
						$pricef= number_format($priceandtax,2);
						$value = $priceandtax * $units;
						$value = number_format($value,2);
						$details.= "<tr>
							          <td>$item</td>
							          <td>$currency $pricef</td>
							          <td>x$units</td>
							          <td>$currency $value</td>
							        </tr>";
				}
			}

			
			$table = "<table class='table table-compressed table-hover table-bordered table-striped details'>
						<thead>
						  <tr>
						   <th>Item</th><th>Price</th><th>Qty</th><th>Value</th>
						  </tr>
						</thead>
						<tbody>
						  $details
						</tbody>
					  </table>";
					  
			$details = "<tr><td></td><td></td><td colspan='$max_cols'>$table</td></tr>";

			if ($hidedetails) $details = ''; //noneed to show details
			
			foreach($cols_ as $col){
				if ($col == '#') continue;

				$val = $row[$col];

				switch ($col){

					case 'units':

						$id = $row['id'];
						$name = $row['name'];
						$units = $row['units'];
						
						$input = "<form method='POST'>
								   <input type='hidden' name='extra' value='1'>
								   <input type='hidden' name='id' value='$id'>
								   <input type='hidden' name='product' value='$name'>
								   <input name='units' required value=\"$units\" />
								   <input type='submit' class='btn btn-sm btn-default' value='Update'/>
								  </form>";			
						$data .= "<td>$input</td>";						  
						break;

					case 'attributes':
						// attributes are saved as an XML BLOK
						$xml = simplexml_load_string($val);
						if ($xml === false){
							$val = 'error';
						} else {
							$val = $xml->entry[2];
						}	
						
						$data .= "<td>$val</td>";					
						break;
						
					case 'ticketid':
						$val = "<a href='?view=$view&receiptid=$val'>$val</td>";
						$data .= "<td>$val</td>";
						break;
								
					case 'person':
						$pid = $row['personid'];
						$val = "<a href='?view=$view&person=$pid'>$val</td>";
						$data .= "<td>$val</td>";
						break;
																		
					case 'total':
						$grandtotal += $val;
						$val = number_format($val, 2);
						$data .= "<td class='bold'>$currency $val</td>";					
						break;					
						
					default:
						$data .= "<td>$val</td>";
				}

			}
			$data .= "</tr>
					  $details";
			$idx++;
		}
	     
	     if ($date_start && $date_end){
	     	$grandtotalf = number_format($grandtotal, 2);
	     	echo "<h2 class='bold align-right underline'>Grand Total: $currency $grandtotalf</h2>";
	     }
	     
		 echo "<div class='table-responsive'>
		 		<table class='table table-striped table-hover sales'>
				   <thead>
					<tr>
					 $cols
					</tr>
				   </thead>
				   <tbody>
					 $data
			 	   </tbody>
				</table>
		       </div>";
	 }
	 
	 function alert($msg, $color='info'){
		 return "<p class='alert alert-$color noprint'>$msg</p>";
	 }
   ?>
    </div> <!-- col-md-12 -->
   </div> <!-- row -->
  </div> <!-- container -->
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
	<!-- Latest compiled and minified JavaScript -->
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>  
 </body>
</html>
