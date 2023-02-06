<!Doctype html>
<html moznomarginboxes mozdisallowselectionprint>
 <head>
 <meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Unicenta PHP Reports</title>
	<!-- Latest compiled and minified CSS -->
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

	<!-- Optional theme -->
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">
	<link rel="icon" href="unicentaopos-100x100.png" sizes="32x32" />
	<link rel="icon" href="unicentaopos-300x300.png" sizes="192x192" />
	<link rel="apple-touch-icon" href="unicentaopos-300x300.png" />
	<meta name="msapplication-TileImage" content="unicentaopos-300x300.png" />
  
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
	  background:hsl(0,0%,100%);
		line-height: 26px;
		font-weight: 300;	  
	}
	.bg-grey {background: grey;}
    .header {font-size: 14pt; color: #FFF; background: #2a5d84; padding: 15px;}
	.header a {color: #FFF;}	
	.header a.active {color: yellow;}
	p.alert { padding: 5px; }
	.search {padding:10px; background: wheat;}
	#term {padding: 5px;width: 300px;}	
	.bold {font-weight: bold;}
	.underline {text-decoration: underline;}	
	.align-right {text-align: right; }
	.discount-highlight {color: green;}
	table.sales thead tr th:nth-child(6){ text-align:right;}
	table.sales tbody tr td:nth-child(6){ text-align:right;}	
	table.sales thead tr th:nth-child(7){ text-align:right;}
	table.sales tbody tr td:nth-child(7){ text-align:right;}
	table.details thead tr th:nth-child(2){ text-align:right;}
	table.details tbody tr td:nth-child(2){ text-align:right;}
	table.details thead tr th:nth-child(3){ text-align:center;}
	table.details tbody tr td:nth-child(3){ text-align:center;}	
	table.details thead tr th:nth-child(4){ text-align:right;}
	table.details tbody tr td:nth-child(4){ text-align:right;}	
	table.details {font-size: small;}
	
	.outline {
		margin: 20px;
	  padding: 10px;
	}
	.center {
	  text-align: center;	  
   }
	div.center p {
	  padding: 0px;
	  margin: 0px;
	  font-size: small;
	}  	
	 
	.float-right {
		float: right;
	}
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
     * How discounts are stored
     *
     * When transaction discount
     * attributes column in ticketlines table contains product name + Item Discount @ ?%
     *
     * When line discount:
	 * attributes column in ticketlines table contains BLOB XML:
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
	// how we show transactions with discounts applied
	// SELECT distinct(ticket) FROM ticketlines WHERE attributes LIKE '%Discount%';
	
  	// database settings
    $user = 'root';
	$pwd  = 'Admin.2015!';
	$db   = 'unicentaopos';
	$host = 'localhost';
  	        				
	// connect to MySQL server before doing anything more
	$conn = @ new mysqli($host, $user,$pwd, $db ) or die($mysqli->error);

	$currency = 'N$';
	$max = 20; // records to return for default
	$showsql = false;
	$hidedetails = true; // by default don't show the products in a transaction   	         
	// we list the table columns dynamically, we can filter out the ones we don't need to show
	$ignored = ['id', 'code', 'codetype'];
	
    // menu (title->view)
  	$urls = ['Sales'=>'sales', 						// show all sales (minus details)
			 'Sales Extended'=>'sales-extended', 	// show sales with details
  	         'Discounts'=>'discounts', 				// show all discounts + details when dates set
  	         'Category Detail'=>'category-detail',	// not implemented yet
  	         'Stock Update'=>'stock-update', 
  	         'Stock in Minus'=>'stock-minus'
  	        ];

	// PDF receipt
	 $store_line0 = 'Oscar and Olive Clothing Boutique';
     $store_line1 = "<p>$store_line0</p>";
     $store_line2 = '<p>Hendrik Witbooi Street</p>';
     $store_line3 = '<p>Brauhaus Arcade</p>';
     $store_line4 = '<p>Swakopmund</p>';
     $store_line5 = '<p>VAT No.: 4181777015<p>'; 
     $store_line6 = '<p>Terms: Returns Valid for 7 days only.<p>'; 
     $store_line7 = '<p>Please Call Again.<p>'; 
     	
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

  	echo "<div class='header noprint'>$tmp</div>";
  	
	echo "<div class='search noprint'>	
	        <!-- form to filter report by ticket/receipt id -->
			<form>
			 <input type='hidden' name='view' value=\"$view\">
			 <b>Ticketid</b> <input type='number' name='receiptid' required value=\"$receiptid\">   	    	 
	   	     <input type='submit' value='Run Report' class='btn btn-sm btn-primary'>
			</form>
						
	        <!-- form to filter report by date -->						
			<form>
			 <input type='hidden' name='view' value=\"$view\">
			 <b>Start Date</b> <input type='date' name='date_start' required value=\"$date_start\">
			 <input type='time' name='time_start' required value=\"$time_start\"> &middot;
			 <b>End Date</b> <input type='date' name='date_end' required value=\"$date_end\">
   	    	 <input type='time' name='time_end' required value=\"$time_end\">
	   	     <input type='submit' value='Run Report' class='btn btn-sm btn-primary'>
	   	     <a href='#' class='btn btn-sm btn-success' onclick='window.print(); return false;'>Print</a>
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

	// default sql
	$sql = "SELECT 
					DISTINCT t.ticketid,
					datenew AS `date`, 
					pp.id as personid, 
					pp.name AS person,
					payment AS type, 
					total, 
					receipt,
					tendered
				FROM
					receipts r
						INNER JOIN payments p ON r.id = p.receipt
					INNER JOIN tickets t ON t.id = r.id
					INNER JOIN ticketlines tl ON tl.ticket = t.id
					INNER JOIN people pp ON pp.id = t.person
				
				ORDER BY 
					r.datenew DESC LIMIT $max;";
	 
  	switch ($view){
  	    case 'sales':
  	    case 'sales-extended':
  	    case 'discounts':
	  	    // add to list ofignored columns
			$ign = ['units', 'money', 'receipt', 'tip', 'transid', 'isprocessed',
			       'returnmsg', 'personid',	'notes','tenderedx',	'cardname',	
			       'voucher','ticketidx', 'tickettype', 'customer', 'status'];			       
			foreach($ign as $col) {
				array_push($ignored, $col);
			}  	    
								
			if ($personid){
				$extras = ($view == 'discounts') ? "AND tl.attributes LIKE '%Discount%'" : '';
				$sql = "SELECT 
								DISTINCT t.ticketid,
								datenew AS `date`, 
								pp.id as personid, 
								pp.name AS person, 
								payment AS type, 
								total, 
								receipt,
								tendered
							FROM
								receipts r
									INNER JOIN payments p ON r.id = p.receipt
								INNER JOIN tickets t ON t.id = r.id
								INNER JOIN ticketlines tl ON tl.ticket = t.id
								INNER JOIN people pp ON pp.id = t.person
							WHERE
								pp.id = '$personid' $extras				
							ORDER BY 
								r.datenew DESC;";	
			} elseif ($receiptid){
                $hidedetails = false;
				$sql = "SELECT 
								DISTINCT t.ticketid,
								datenew AS `date`, 
								pp.id as personid, 
								pp.name AS person,
								payment AS type, 
								total,  
								receipt,
								tendered
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
					$hidedetails = $view == 'sales' ? true : false;
					$extras = ($view == 'discounts') ? "AND tl.attributes LIKE '%Discount%'" : '';
					$sql = "SELECT 
									DISTINCT ticketid,
									datenew AS `date`, 
									pp.id as personid, 
									pp.name AS person,
									payment AS type, 
									total,   
									receipt,
									tendered
								FROM
									receipts r
										INNER JOIN payments p ON r.id = p.receipt
									INNER JOIN tickets t ON t.id = r.id
									INNER JOIN ticketlines tl ON tl.ticket = t.id
									INNER JOIN people pp ON pp.id = t.person
								WHERE
									(r.datenew BETWEEN '$date_start $time_start' AND '$date_end $time_end') 
									$extras
								ORDER BY 
									r.datenew ASC;";	
																					
			} else {
				$hidedetails = ($view == 'sales') ? true : false;
				$extras = ($view == 'discounts') ? "WHERE	tl.attributes LIKE '%Discount%'" : '';
				$sql = "SELECT 
								DISTINCT t.ticketid,
								datenew AS `date`, 
								pp.id as personid, 
								pp.name AS person,
								payment AS type, 
								total, 
								tendered,
								receipt
							FROM
								receipts r
									INNER JOIN payments p ON r.id = p.receipt
								INNER JOIN tickets t ON t.id = r.id
								INNER JOIN ticketlines tl ON tl.ticket = t.id
								INNER JOIN people pp ON pp.id = t.person
							$extras
							ORDER BY 
								r.datenew DESC LIMIT $max;";
			}
  	    	break;
		
		case 'category-detail':
			die(alert('This feature is not yet implemented.','danger'));
			break;

  		case 'stock-minus':
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
			 break;  	    				 			                            	
  	}
	 
	 // did we get a submit for stock update?
	 $extra = (int) @ $_POST['extra'];	
	 if ($extra){
		 $id      = $_POST['id'];
		 $product = $_POST['product'];
		 $units   = (int) $_POST['units'];		 
		 $sql0 = "UPDATE `stockcurrent` SET units=$units WHERE product='$id';";
		 $conn->query($sql0) or die(alert($conn->error, 'red'));
		 echo alert("<b>$product</b> has been updated to $units units. It may no longer show in this view.", 'success');
	 }

	 //echo alert($sql, 'success');
	 if ($personid) echo alert("Showing $view transactions only for selected employee &middot; <a href='?view=$view'>Show for All</a>", 'info');
	 elseif ($receiptid) {}//echo alert("Showing only receipt <b>$receiptid</b> &middot; <a href='?view=$view'>Show All</a>", 'info');
	 elseif ($date_start && $date_end) echo alert("Showing $view between <b>$date_start $time_start</b> and <b>$date_end $time_end</b> &middot; <a href='?view=$view'>Show All</a>", 'info');
	 else if (!in_array($view, ['stock-update', 'stock-minus']))	echo alert("Showing last $max <b>$view</b> transactions.", 'info');

     if ($showsql){
     	echo "<p class='alert alert-info noprint'>$sql</p>";
     }
	     
	 $ret = $conn->query($sql) or die( alert('fatal error: '.$mysqli->error, 'danger'));
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

		// account for cols: #, ticketid
	    $max_cols = sizeof($cols) ? sizeof($cols)-2 : 0;
	    	     
		$cols = (sizeof($cols)) ? implode('', $cols) : '';

		$grandtotal = 0;
		
		// receipt PDF
		$r_receipt = $receiptid;
		$r_date = '';
		$r_terminal = 'DESKTOP-ACI7TGA';
		$r_staff = '';
		
		while ( $row = $ret->fetch_array() ){
			$data .= "<tr>";
			$data .= "<td>$idx.</td>";
			$details ='';
			
			if (!$hidedetails){
				$receipt = @$row['receipt'];

				$sql0 = "SELECT line, 
								(CASE WHEN product IS NULL THEN '_line_discount_' 
								ELSE (SELECT p.name FROM products p WHERE p.id=tl.product) END) AS product, 	
								(CASE WHEN product IS NULL THEN '' 
								ELSE (SELECT c.name FROM products p INNER JOIN categories c WHERE p.id=tl.product AND p.category=c.id) END) AS category,												
								price,
								tl.units, 
								tl.attributes
						FROM 
							ticketlines tl 
						WHERE tl.ticket='$receipt';";
						
				$ret0 = $conn->query($sql0) or die($mysqli->error);
				if (!$ret0 || !$ret0->num_rows){
				} else {
					while ($row0 = $ret0->fetch_array()){
							$item=$row0['product'];
							$attrs=$row0['attributes'];
							$category = $row0['category'];
							$categoryf=$category ? "<span class='badge'>$category</span>" : '';
							$units = 1;

							// if 'Line Discount ?%' or 'Item Discount @ ?%' in attributes
							if ($item == '_line_discount_' || strpos($attrs,'%') !== false){
								//if ($showsql) echo "<p class='alert alert-warning'>$sql0</p>";
								/*
								<?xml version="1.0" encoding="UTF-8"?>
								<!DOCTYPE properties SYSTEM "http://java.sun.com/dtd/properties.dtd">
								<properties>
									<comment>uniCenta oPOS</comment>
									<entry key="product.taxcategoryid">001</entry>      -->[0]
									<entry key="product.printer">1</entry>              -->[1] 
									<entry key="product.name">Line Discount 20%</entry> -->[2]
								</properties>
								*/
								$attributes = $row0['attributes'];
								$xml = simplexml_load_string($attributes);
								if ($xml === false){
									$product = 'error';
								} else {

									$item  = $xml->entry[2];
									$units = (int) $xml->entry[1];
									// Find the percentage from Line Discount 5% 
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
							$categoryf = ($receiptid) ? '' : $categoryf;
							$details.= "<tr>
										<td>$item $categoryf</td>
										<td>$currency $pricef</td>
										<td>x$units</td>
										<td>$currency $value</td>
										</tr>";
					}
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
					case 'date':
						$val = date_create($val);
						$val = date_format($val,"d-M-Y H:i:s");
						$r_date = $val;
						$data .= "<td>$val</td>";	
						break;
						
					case 'payment':
					case 'type':
						if ($val == 'ccard') $val = 'card';
						$val = ucwords($val);
						$data .= "<td>$val</td>";	
						break;
												
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
						// when in this view, send click on ticketid to sales
						$view = in_array($view,['transactions-extended']) ? 'sales':$view;
												
						$val = "<a href='?view=$view&receiptid=$val'>$val</td>";
						$data .= "<td>$val</td>";
						break;
								
					case 'person':
						$pid = $row['personid'];
						$r_staff = $val;
						$val = "<a href='?view=$view&person=$pid'>$val</td>";
						$data .= "<td>$val</td>";						
						break;
								
					case 'tendered':
						$val = number_format($val, 2);
						$data .= "<td class='bold'>$currency $val</td>";					
						break;	
														
					case 'value':										
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
	     	echo "<h2 class='bold align-right underline'>Total: $currency $grandtotalf</h2>";
	     }
	     
	     if ($receiptid){
	     	require_once('logo.php');
	     	$grandtotalf = number_format($grandtotal, 2);
	     	echo "<script>document.title = '$store_line0 Receipt #$receiptid';</script>";
	     	echo "<div class='outline'>
	     	        <div class='center'>
	     	         <img src='$logo'>
	     	         <p>&nbsp;</p>
	     	         $store_line1
	     	         $store_line2
	     	         $store_line3
	     	         $store_line4	  	         
	     	         </div>
	     	         <p>&nbsp;</p>
	     	         <table>
						<tbody>				
						 <tr><td>Receipt:</td><td>$receiptid</td></tr>
						 <tr><td>Date:</td><td>$r_date</td></tr>
						 <tr><td>Terminal:</td><td>$r_terminal</td></tr>
						 <tr><td>Served by:</td><td>&nbsp;$r_staff</td></tr>
						</tbody>
	     	         </table>
	     	         <p>&nbsp;</p>
	     	         $table
	     	         <h3><strong>Balance Due <span class='float-right'>$currency $grandtotalf</span></strong></h3>
	     	         <HR>
	     	         <div class='center'>
	     	         	$store_line5
	     	         	$store_line6
	     	         	$store_line7
	     	         </div>
	     		  </div>";
	     	
	     } else {
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
	 }
	 
	 function alert($msg, $color='info'){
 		 $date = new DateTime('now');
		 $date = $date->format('d-M-Y H:i:s');	 
		 return "<p class='alert alert-$color noprintx'><b>$date</b> &middot; $msg</p>";
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
