<?php
	function GetQuantitySold($ProductID)
	{
		global $wpdb;
		
		$prefix = $wpdb->prefix;
		$lineitem = $prefix . "mmpm_lineitem";
		$product = $prefix . "mmpm_product";
		$purchase = $prefix . "mmpm_purchase";
		
		$query = sprintf("SELECT coalesce(sum(li.intQuantity), 0) as 'sold' FROM %s li
		JOIN %s p1 ON li.intProductID = p1.intID
		JOIN %s p2 ON li.intPurchaseID = p2.intID
		WHERE intProductID = %d AND p2.intValid < 2",
		$lineitem, $product, $purchase, $ProductID);
		
		$result = $wpdb->get_row($query);
		
		//echo $query . "<br /><br />";
		
		return $result->sold;
	}
	
	function CanSellProduct($product, $Quantity)
	{
		$CanSell = true;
		$sold = GetQuantitySold($product->intID) + $Quantity;
		
		//echo $sold . "aaa";
		
		$CanSell = IsActive($product);
		
		if (($product->intQuantity - $sold) < 0) // removed <= because we want to be able to go to 0 but not less than 0
		{
			$CanSell = false;
		}
		
		//echo $CanSell . "aaa";
		
		return $CanSell;
	}
	
	function CanSell($ProductID, $Quantity)
	{
		$product = GetProductById($ProductID);
		
		return CanSellProduct($product, $Quantity);
	}
	
	function GetProductById($pid)
	{
		global $wpdb;
		$sql = sprintf("SELECT * FROM %s WHERE tinDeleted = 0 AND intID = %s",
				$wpdb->prefix . "mmpm_product", $pid);
				
		return $wpdb->get_row($sql);
	}
	
	function GetProductByName($pname)
	{
		global $wpdb;
		$sql = sprintf("SELECT * FROM %s WHERE vcrName = '%s' AND tinDeleted = 0",
				$wpdb->prefix . "mmpm_product", $pname);
		
		return $wpdb->get_row($sql);
	}
	
	function GetProductsByDescription($pdesc)
	{
		global $wpdb;
		$sql = sprintf("SELECT * FROM %s WHERE vcrDescription = '%s' AND tinDeleted = 0",
				$wpdb->prefix . "mmpm_product", $pdesc);

		return $wpdb->get_results($sql);
	}
	
	function GetProducts()
	{
		global $wpdb;
		$sql = sprintf("SELECT * FROM %s WHERE tinDeleted = 0 ORDER BY dtmEndDate ASC",
				$wpdb->prefix . "mmpm_product");
		
		return $wpdb->get_results($sql);
	}
	
	function GetActiveProducts()
	{
		$curdate = date('Y-m-d H:i');
		
		global $wpdb;
		$sql = sprintf("SELECT * FROM %s WHERE tinDeleted = 0 AND dtmEndDate > '%s' ORDER BY dtmEndDate ASC LIMIT 100",
				$wpdb->prefix . "mmpm_product", $curdate);
		
		//echo $sql;
		
		return $wpdb->get_results($sql);
	}
	
	function IsActive($Product)
	{
		$active = true;
		
		$curdate = date('Y-m-d H:i');
		//echo $Product->dtmStartDate; 
		if ($Product->dtmStartDate > $curdate)
		{
			$active = false;
			//echo "product not started";
		}
		elseif ($Product->dtmEndDate < $curdate)
		{
			$active = false;
			//echo "product ended" . $Product->dtmEndDate . " a " . $curdate;
		}
		
		return $active;
	}
	
	function OutputProductJSON($pid)
	{
		$Product = GetProductById($pid);
		
		$active = "false";
		
		if (IsActive($Product))
		{
			$active = "true";
		}
	
		$json = sprintf('{"pid" : %s, "pname" : "%s", "pdesc" : "%s", "pquant" : %s, "psales" : %s,	"pend" : "%s", "pstart" : "%s", "deleted": %s, "pnquant" : %s, "pcost" : %s, "purl" : "%s", "active" : %s}',
				 $Product->intID,
	 			 $Product->vcrName,
				 $Product->vcrDescription,
				 $Product->intQuantity,
				 GetQuantitySold($Product->intID),
				 $Product->dtmEndDate,
				 $Product->dtmStartDate,
				 $Product->tinDeleted,
				 $Product->intNotifyQuantity,
				 $Product->decPrice,
				 $Product->vcrUrl,
				 $active);
		
		echo $json;
	}
	
	function OutputProductList()
	{
		$Products = GetActiveProducts();
	
		if ($Products)
		{
		
	?>
		
		<table id="mm_pm_productlist" class="table table-bordered table-striped">
		<thead>
			<tr>
				<th></th>
				<th>Name</th>
				<th>Description</th>
				<th>Quantity</th>
				<th>Sold</th>
				<th>End Date</th>
				<th>Controls</th>
			</tr>
		</thead>
		<tbody>
								
	<?php
			foreach ($Products as $Product)
			{
				$output = "";
				
				if (IsActive($Product))
				{
					$output .= sprintf("<tr id=\"row-%s\" class=\"active\">
						<td>
							<a href=\"#\" title=\"Active\"><i class=\"icon-ok\"></i></a>
						</td>", $Product->intID);
				}
				else
				{
					$output .= sprintf("<tr id=\"row-%s\" class=\"inactive\" style=\"display: none;\">
						<td>
							<a href=\"#\" title=\"Inactive\"><i class=\"icon-remove\"></i></a>
						</td>", $Product->intID);
				}
				$output .= sprintf("<td>%s</td>
					<td>%s</td>
					<td>%s</td>
					<td>%s</td>
					<td>%s</td>
					<td>
						<a href=\"#\" class=\"btnProductEdit\" id=\"product-edit-1\" onclick=\"javascript: EditProduct(%s);\" title=\"Edit\"><i class=\"icon-edit\"></i></a>
						<a href=\"#\" class=\"btnProductCopy\" id=\"product-copy-1\" onclick=\"javascript: CopyProduct(%s);\" title=\"Copy\"><i class=\"icon-file\"></i></a>
						<a href=\"#\" class=\"btnProductDelete\" id=\"product-delete-1\" onclick=\"javascript: DeleteProduct(%s);\" title=\"Delete\"><i class=\"icon-trash\"></i></a>
						<a href=\"#\" class=\"btnFillClass btn btn-warning\" onclick=\"javascript: FillClass(%s);\" title=\"Fill Class\">Fill Class</a>
					</td>
				</tr>",
				$Product->vcrName,
				$Product->vcrDescription,
				$Product->intQuantity,
				GetQuantitySold($Product->intID),
				$Product->dtmEndDate,
				$Product->intID,
				$Product->intID,
				$Product->intID,
				$Product->intID);
				
				echo $output;
			}
			
			echo "</tbody></table>";
		}
		else
		{
			echo "Looks like you haven't added any Products.  Do that <a href=\"#\" onclick=\"javascript: ShowAddProduct()\">here</a>.";
		}
	}
	
	function InsertProduct($Name, $Desc, $Price, $Max, $Notify, $Start, $End, $Url)
	{
		global $wpdb;
		$defaultDeleted = 0;
		
		$CalDate = date("Y-m-d", strtotime($End));
		
		$input = array('user_id' 		=> 1,
				  'title'	 		=> $Desc,
				  'start'			=> $CalDate,
				  'end'				=> $CalDate,
				  'category_id'		=> 1,
				  'description'		=> $Desc,
				  'link'			=> $Url
				);

		$eid = Ajax_Calendar_Insert(array_to_object($input));
		
		$array = array(
					'vcrName' => $Name,
					'vcrDescription' => $Desc,
					'decPrice' => $Price,
					'intQuantity' => $Max,
					'intNotifyQuantity' => $Notify,
					'dtmStartDate' => $Start,
					'dtmEndDate' => $End,
					'vcrUrl' => $Url,
					'intExternalID' => $eid//,
					//'bitDeleted' => $defaultDeleted
				);
		$format = array(
					'%s',
					'%s',
					'%d',
					'%d',
					'%d',
					'%s',
					'%s',
					'%s'//,
					//'%d'
				);

		return InsertStatement($wpdb->prefix . "mmpm_product", $array, $format);
	}
	
	function UpdateProduct($id, $Name, $Desc, $Price, $Max, $Notify, $Start, $End, $Url)
	{
		global $wpdb;
		$sql = sprintf("UPDATE %s SET vcrName = '%s',
								vcrDescription = '%s',
								decPrice = %d,
								intQuantity = %d,
								intNotifyQuantity = %d,
								dtmStartDate = '%s',
								dtmEndDate = '%s',
								vcrUrl = '%s'
								WHERE intID = %d",
								$wpdb->prefix . "mmpm_product",
								$Name, $Desc, $Price, $Max, $Notify, $Start, $End, $Url, $id);
		
		$CalDate = date("Y-m-d", strtotime($End));
		
		$input = array('id' 		=> $id,
				  'user_id' 		=> 1,
				  'title'	 		=> $Desc,
				  'start'			=> $CalDate,
				  'end'				=> $CalDate,
				  'category_id'		=> 1,
				  'description'		=> $Desc,
				  'link'			=> $Url
				);

		Ajax_Calendar_Update(array_to_object($input));
								
		ExecuteStatement($sql);
	}
	
	function DeleteProduct($id)
	{
		$product = GetProductById($id);
		
		Ajax_Calendar_Delete($product->intExternalID);
		
		global $wpdb;
		$sql = sprintf("UPDATE %s SET tinDeleted = 1
								WHERE intID = %s",
								$wpdb->prefix . "mmpm_product",
								$id);
		ExecuteStatement($sql);
	}
	
	function FinishProduct($id)
	{
		$sold = GetQuantitySold($id);
	
		global $wpdb;
		$sql = sprintf("UPDATE %s SET intQuantity = %s
								WHERE intID = %s",
								$wpdb->prefix . "mmpm_product",
								$sold,
								$id);
		ExecuteStatement($sql);
	}
	
	function HandleIPN($invoiceid, $value, $json, $pemail)
	{
		$purchase = SelectPurchaseByInvoiceID($invoiceid); 
	
		if ($purchase->intID > 0)
		{
			UpdatePurchase($purchase->intID, $value);
		}
		
		$purchaserid = $purchase->intPurchaserID;

		switch($value){
			case 1:
				UpdatePurchaser($purchaserid, $json);
			break;
			default:
				//Derp
			break;
		}
		
		$_settings = get_option('mm_pm_settings') ? get_option('mm_pm_settings') : array();
		$message = "Thank you for your purchase.<br /><br />
					I will send you a reminder and further information nearer to the class if anything comes up.<br /><br />
					Check out more information on our cancellation policy and what to bring to your class <a href=\"http://www.simonsfinefoods.com/cooking-classes/\">here</a>.<br /><br />
					If you have a question regarding the class please e-mail me at: simon@simonsfinefoods.com<br /><br />
					- Simon";
		
		SendEmail($pemail,
					"Registration Confirmation",
					$_settings['mm_pm_notifyemail'],
					$message);
	}
	
	function SelectPurchaseByInvoiceID($invoiceid)
	{
		global $wpdb;
		$sql = sprintf("SELECT * FROM %s WHERE vcrInvoiceNumber = '%s'",
				$wpdb->prefix . "mmpm_purchase", $invoiceid);
		
		return $wpdb->get_row($sql);
	}
	
	function UpdatePurchase($intID, $value)
	{
		global $wpdb;
		$statement = sprintf("UPDATE %s SET intValid = %d WHERE intID = %d",
						$wpdb->prefix . "mmpm_purchase", $value, $intID);
	
		//echo $statement;
	
		ExecuteStatement($statement);
	}
	
	function UpdatePurchaser($intID, $json)
	{
		global $wpdb;
		$statement = sprintf("UPDATE %s SET vcrJSON = '%s' WHERE intID = %d",
								$wpdb->prefix . "mmpm_purchaser", $json, $intID);
	
		ExecuteStatement($statement);
	}
	
	function Buy($ProductName, $Quantity)
	{
		$Product = GetProductByName($ProductName);
		$sold = GetQuantitySold($Product->intID) + $Quantity;
		$remaining = $Product->intQuantity - $sold;
		//Check if the Quantity selected can be bought
		//echo $remaining;
		
		if ($remaining >= 0 && IsActive($Product))
		{
			$_settings = get_option('mm_pm_settings') ? get_option('mm_pm_settings') : array();
		
			$Account = $_settings['mm_pm_paypalaccount'];
			$Currency = $_settings['mm_pm_currency'];
			$InvoicePrefix = $_settings['mm_pm_invoice'] . "-";
			$TaxPercent = $_settings['mm_pm_tax'] / 100;
		
			if ($remaining <= $Product->intNotifyQuantity)
			{
				if ($remaining == 0)
				{
					$message = sprintf("Hey there!<br /><br /> Product: '%s' is sold out.  Here is the list of sales:<br /><br />
					%s<br /><br />
					Sincerely,<br />The Media Manifesto Team",
					$Product->vcrDescription,
					genPurchaseReport($Product->intID));
				}
				else
				{				
					//Send Notification Email
					$message = sprintf("Hey there!<br /><br/> This is just a friendly reminder that this product: '%s' is selling out.
					There are only %d left of %d which means you've sold %d.  Here is the list of sales:<br />%s<br /><br />
					Have a Great Day !!<br /><br /> Sincerely,<br />The Media Manifesto Team",
							$Product->vcrName,
							$remaining,
							$Product->intQuantity,
							$sold,
							genPurchaseReport($Product->intID));
				}
				
				SendEmail($_settings['mm_pm_notifyemail'] . ', adam@mediamanifesto.com',
					"Product Notification",
					"info@mediamanifesto.com",
					$message);
			}
			
			$InvoiceNumber = InsertPurchase($Product->intID, $Quantity, $InvoicePrefix);
			$Total = $Product->decPrice * $Quantity;
			$Tax = round($Total * $TaxPercent, 2);
			
			echo OutputPurchaseJSON($Total, $Tax, $InvoiceID, $ProductName, $Account, $Currency, $InvoiceNumber, $Quantity);
		}
		else
		{
			OutputFailureJSON();
		}
	}
	
	function SendEmail($to, $subject, $from, $message)
	{
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
		$headers .= sprintf('From: %s' . "\r\n", $from) .
			'X-Mailer: PHP/' . phpversion();
	
		mail($to, $subject, $message, $headers);
	}
	
	function OutputFailureJSON()
	{
		$g = "false";
		$gm = "The selected quantity is unavailable.";
		
		$output = sprintf('{"g" : %s, "gm" : "%s"}',
		$g, $gm);
		
		echo $output;
	}
	
	function OutputPurchaseJSON($Total, $Tax, $Invoice, $Name, $Account, $Currency, $Invoice, $Quantity)
	{
		$IPNUrl =  get_bloginfo( 'url' ) . '/mm_ipn/paypal';
		
		$html = sprintf("<input type='hidden' name='amount_1' value='%s' />", $Total);
		$html .= sprintf("<input type='hidden' name='cmd' value='_cart' />");		
		$html .= sprintf("<input type='hidden' name='upload' value='1' />");
		$html .= sprintf("<input type='hidden' name='business' value='%s' />", $Account);
		$html .= sprintf("<input type='hidden' name='item_name_1' value='%s x %s' />", $Quantity, $Name);
		//$html .= sprintf("<input type='hidden' name='amount_2' value='%s' />", $Tax);
		$html .= sprintf("<input type='hidden' name='tax_1' value='%s' />", $Tax);  //override account tax settings to 0
		//$html .= sprintf("<input type='hidden' name='item_name_2' value='Tax' />");
		$html .= sprintf("<input type='hidden' name='invoice' value='%s' />", $Invoice);
		$html .= sprintf("<input type='hidden' name='notify_url' value='%s' />", $IPNUrl);
		$html .= sprintf("<input type='hidden' name='currency_code' value='%s' />", $Currency);
		$html .= sprintf("<input id='mmsubmit-%s' type='submit' style='display: none;' />", $Name);
		
		$action = "https://www.paypal.com/cgi-bin/webscr";
		$form = "#mmform-" . $Name;
		$attr = "#mmattr-" . $Name;
		$submit = "#mmsubmit-" . $Name;
		$g = "true";
		
		$output = sprintf('{"html" : "%s", "form" : "%s", "action" : "%s", "attr" : "%s", "submit" : "%s", "g" : %s}',
		$html, $form, $action, $attr, $submit, $g);
		
		return $output;
	}
	
	function InsertPurchase($ProductID, $Quantity, $InvoicePrefix)
	{
		global $wpdb;
		
		$PurchaserID = InsertPurchaser();
		
		$InvoiceID = $InvoicePrefix . (10000 + $PurchaserID);
		
		//echo $InvoiceID . " " . $PurchaserID;
		
		$curdate = date('Y-m-d H:i');
		
		$array = array(
					'intPurchaserID' => $PurchaserID,
					'vcrInvoiceNumber' => $InvoiceID,
					'dtmDate' => $curdate
				);
		
		$format = array(
					'%d',
					'%s',
					'%s'
				);
		
		$PurchaseID = InsertStatement($wpdb->prefix . "mmpm_purchase", $array, $format);
		
		$array = array(
					'intPurchaseID' => $PurchaseID,
					'intProductID' => $ProductID,
					'intQuantity' => $Quantity
				);
		
		$format = array(
					'%d',
					'%d',
					'%d'
				);
		
		InsertStatement($wpdb->prefix . "mmpm_lineitem", $array, $format);
		
		return $InvoiceID;
	}
	
	function InsertPurchaser()
	{
		global $wpdb;
		
		$array = array(
					'vcrIP' => $_SERVER['REMOTE_ADDR'],
					'vcrAgent' => $_SERVER['HTTP_USER_AGENT']
				);
		
		$format = array(
					'%s',
					'%s'
				);
		
		return InsertStatement($wpdb->prefix . "mmpm_purchaser", $array, $format);
	}
	
	function genPurchaseReport($pid = 0)
	{
		global $wpdb;

		$sql = "SELECT pu.vcrInvoiceNumber, po.vcrName, li.intQuantity,  pur.vcrJSON, pu.intValid, pu.dtmDate, pur.vcrIP
				FROM wp_mmpm_lineitem li
				JOIN wp_mmpm_purchase pu ON li.intPurchaseID = pu.intID
				JOIN wp_mmpm_purchaser pur ON pu.intPurchaserID = pur.intID
				JOIN wp_mmpm_product po ON po.intID = li.intProductID
				WHERE po.tinDeleted = 0
				ORDER BY pu.dtmDate DESC LIMIT 100";
		
		if ($pid != 0)
		{
			$sql = "SELECT pu.vcrInvoiceNumber, po.vcrName, li.intQuantity,  pur.vcrJSON, pu.intValid, pu.dtmDate, pur.vcrIP
				FROM wp_mmpm_lineitem li
				JOIN wp_mmpm_purchase pu ON li.intPurchaseID = pu.intID
				JOIN wp_mmpm_purchaser pur ON pu.intPurchaserID = pur.intID
				JOIN wp_mmpm_product po ON po.intID = li.intProductID
				WHERE po.tinDeleted = 0 AND li.intProductID = " . $pid .
				" ORDER BY pu.dtmDate DESC LIMIT 20";
		}

		$result = $wpdb->get_results($sql);
	
		$message = "";
	
		if (!$result) {
		//die("Query to show fields from table failed " . $pid);
		//Not sure why we kill this here...
		}
		else
		{
			//echo "Query Run <br />";
		}
		$fields_num = $wpdb->num_rows;
		
		$count = 0;
		
		if ($fields_num > 0)
		{	
			$message .= "<table class='table table-bordered table-striped' style=\"max-width: none !important;\"><tr style=\"font-weight: bold;\">";
			$message .= '<tr><th>Invoice</th><th>State</th><th>Name</th><th>Quant</th><th>Value</th><th>Email</th><th>Date</th><th>IP</th></tr>';
			
			setlocale(LC_MONETARY, 'en_CA');
			
			// printing table rows
			foreach ($result as $row)
			{	
				$count++;
			
				$invoice = $row->vcrInvoiceNumber;
				$name = $row->vcrName;
				$quant = $row->intQuantity;
				$state = $row->intValid;
				$data = json_decode ($row->vcrJSON, true);
				$gross = $data['mc_gross'];
				$email = $data['payer_email'];
				$date = $row->dtmDate;
				$ipaddress = $row->vcrIP;
				
				if ($email == "")
				{
					$email = "Data Missing";
				}
				else
				{
					$email = sprintf('<a href="mailto:%s">%s</a>', $email, $email);
				}
				
				$message .= sprintf('<tr><td>%s</td><td>%s</td></td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td>',
								$invoice,
								$state,
								$name,
								$quant,
								money_format('%i',$gross),
								$email,
								$date,
								$ipaddress);	
				$message .= "</tr>\n";
			}
			
			$message .= "</table>";
			
			if ($count == 0)
			{
				$message = "There are no results to display.";
			}
		}
		
		return $message;
	}
	
	function InsertStatement($table, $array, $format)
	{
		global $wpdb;
		$wpdb->insert($table, $array, $format);
		
		return $wpdb->insert_id;
	}
	
	function ExecuteStatement($statement)
	{
		global $wpdb;
		$wpdb->query($statement);
	}
	
	function ExecuteQuery($query)
	{
		global $wpdb;
		$result = $wpdb->get_results($query);
		
		return $result;
	}
	
	class Absolute_to_Relative_URLs
	{
		protected $site_domain;
		protected $site_url;
		
		
		public function __construct()
		{
			$this->getSiteURL();
		}
		
		
		protected function getDomainPath($url)
		{
			$prefixes = array('http://www.', 'https://www.', 'http://', 'https://', 'www.');
			
			foreach ($prefixes as $value)
			{
				if (strpos($url, $value) === 0)
				{
					$url = substr($url, strlen($value));
					break;
				}
			}
			
			$separators = array('/', '?', '#');
			$separatorIndex = strlen($url);
			
			foreach ($separators as $value)
			{
				$pos = strpos($url, $value);
				
				if ($pos !== false)
				{
					if ($pos < $separatorIndex)
					{
						$separatorIndex = $pos;
					}
				}
			}
			
			$domain = substr($url, 0, $separatorIndex);
			$path   = substr($url, $separatorIndex);
			
			return array($domain, $path);
		}
		
		
		protected function getSiteURL()
		{
			$this->site_url = (!isset($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'];
			
			$path = $this->getDomainPath($this->site_url);
			
			$this->site_domain = $path[0];
		}
		
		
		public function relateURL($url)
		{
			// Avoid unmatched protocols and already-relative URLs
			if (!isset($_SERVER['HTTPS']))
			{
				if (strpos($url, 'http://') !== 0) return $url;
			}
			else
			{
				if (strpos($url, 'https://') !== 0) return $url;
			}
			
			$url_split = $this->getDomainPath($url);
			
			if ($url_split[0] == $this->site_domain)
			{
				if ($url_split[1] != '')
				{
					return $url_split[1];
				}
				else
				{
					return '/';
				}
			}
			else
			{
				// Different domain, /, or unknown format
				return $url;
			}
		}
	}
	
	function absolute_to_relative_url($url)
	{
		global $absolute_to_relative_url_instance;
		
		if (is_null($absolute_to_relative_url_instance))
		{
			$absolute_to_relative_url_instance = new Absolute_to_Relative_URLs();
		}
		
		return $absolute_to_relative_url_instance->relateURL($url);
	}
	
	
	$absolute_to_relative_url_instance = null;
	
	/** Ajax Event Calendar Hooks **/
	function Ajax_Calendar_Insert($input) {
		global $wpdb;
		
		/*
		array('user_id' 		=> 1,
			  'title'	 		=> $Name,
			  'start'			=> $Start,
			  'end'				=> $End,
			  'category_id'		=> 1,
			  'description'		=> $Desc,
			  'link' => $Url,
			);
		*/
		
		$result = $wpdb->insert($wpdb->prefix . 'aec_event',
								array('user_id' 		=> $input->user_id,
									  'title'	 		=> $input->title,
									  'start'			=> $input->start,
									  'end'				=> $input->end,
									  'allDay'			=> 1,
									  'repeat_freq'		=> $input->repeat_freq,
									  'repeat_int'		=> $input->repeat_int,
									  'repeat_end'		=> $input->repeat_end,
									  'category_id'		=> $input->category_id,
									  'description'		=> $input->description,
									  'link'			=> $input->link,
									  'venue'			=> $input->venue,
									  'address'			=> $input->address,
									  'city'			=> $input->city,
									  'state'			=> $input->state,
									  'zip'				=> $input->zip,
									  'country'			=> $input->country,
									  'contact'			=> $input->contact,
									  'contact_info'	=> $input->contact_info,
									  'access'			=> $input->access,
									  'rsvp'			=> $input->rsvp
									),
								array('%d',				// user_id
									  '%s',				// title
									  '%s',				// start
									  '%s',				// end
									  '%d',				// allDay
									  '%d',				// repeat_freq
									  '%d',				// repeat_int
									  '%s',				// repeat_end
									  '%d',				// category_id
									  '%s',				// description
									  '%s',				// link
									  '%s',				// venue
									  '%s',				// address
									  '%s',				// city
									  '%s',				// state
									  '%s',				// zip
									  '%s',				// country
									  '%s',				// contact
									  '%s',				// contact_info
									  '%d',				// access
									  '%d' 				// rsvp
									)
							);
							
		return $wpdb->insert_id;
	}

	function Ajax_Calendar_Update($input) {
		global $wpdb;
		
		$product = GetProductById($input->id);
		
		$result = $wpdb->update($wpdb->prefix . 'aec_event' ,
								array('user_id' 		=> $input->user_id,
									  'title'	 		=> $input->title,
									  'start'			=> $input->start,
									  'end'				=> $input->end,
									  'allDay'			=> 1	,
									  'repeat_freq'		=> $input->repeat_freq,
									  'repeat_int'		=> $input->repeat_int,
									  'repeat_end'		=> $input->repeat_end,
									  'category_id'		=> $input->category_id,
									  'description'		=> $input->description,
									  'link'			=> $input->link,
									  'venue'			=> $input->venue,
									  'address'			=> $input->address,
									  'city'			=> $input->city,
									  'state'			=> $input->state,
									  'zip'				=> $input->zip,
									  'country'			=> $input->country,
									  'contact'			=> $input->contact,
									  'contact_info'	=> $input->contact_info,
									  'access'			=> $input->access,
									  'rsvp'			=> $input->rsvp
									),
								array('id' 				=> $product->intExternalID),
								array('%d',				// user_id
									  '%s',				// title
									  '%s',				// start
									  '%s',				// end
									  '%d',				// allDay
									  '%d',				// repeat_freq
									  '%d',				// repeat_int
									  '%s',				// repeat_end
									  '%d',				// category_id
									  '%s',				// description
									  '%s',				// link
									  '%s',				// venue
									  '%s',				// address
									  '%s',				// city
									  '%s',				// state
									  '%s',				// zip
									  '%s',				// country
									  '%s',				// contact
									  '%s',				// contact_info
									  '%d',				// access
									  '%d' 				// rsvp
									),
								array ('%d') 			// id
							);
	}

	function Ajax_Calendar_Delete($eid) {
		$sql = sprintf('DELETE FROM wp_aec_event WHERE id = %s', $eid);
		
		ExecuteStatement($sql);
	}
	
	function GetCalendarUrl($EventID)
	{
		$query = sprintf("SELECT * FROM wp_aec_event WHERE id = %s", $EventID);
		$CalEvent = ExecuteQuery($query);
		
		//print_r($CalEvent);
		
		return $CalEvent[0]->link;
	}
	
	function array_to_object($array = array()) {
		$return = new stdClass();
		foreach ($array as $key => $val) {
			if (is_array($val)) {
				$return->$key = $this->convert_array_to_object($val);
			} else {
				$return->{$key} = $val;
			}
		}
		return $return;
	}
?>