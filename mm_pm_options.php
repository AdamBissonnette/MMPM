<div class="mmpm_wrapper container">
	<div class="row">
		<div class="span12">
			<?php screen_icon(); ?>
			<h2>MM Class Manager</h2>
		</div>
	</div>
	<div class="row" style="margin-top: 30px;">
		<div class="span10 tabbable">
			<ul class="nav nav-tabs">
				<li class="active"><a href="#overview" data-toggle="tab"><i class="icon-home"></i> Overview</a></li>
				<li><a href="#purchases" data-toggle="tab"><i class="icon-barcode"></i> Purchase List</a></li>
				<li><a id="plisttab" href="#list" data-toggle="tab"><i class="icon-list"></i> Class List</a></li>
				<li><a id="addtab" href="#add" data-toggle="tab"><i class="icon-plus"></i> Add Class</a></li>
				<li><a id="optionstab" href="#options" data-toggle="tab"><i class="icon-cog"></i> Options</a></li>
			</ul>
			
			
			<div class="tab-content">
				<div id="overview" class="tab-pane active">
					<legend>Overview</legend>
					<p>Looks like you're new here!  Follow these steps and you'll be on your way to fortunes untold.</p>
					
					<ol>
						<li>Setup your paypal account and other setting in the <a id="btnOverviewOptions" href="#">Options</a> tab</li>
						<li>Create a Product</li>
						<li>Add that product to a page or post using this: [MMProductGroup description="YourDescriptionHere" /]</li>
						<li>Profit!</li>
					</ol>
				</div>
				
				<div id="purchases" class="tab-pane">
					<legend>Purchases</legend>
					<p><strong>Note on States</strong>: 1 is validated purchase (money in the bank), 2 represents debugging / test data, 3 is an incomplete payment (not paid yet but potentially abandoned), 4 is a Refund</p>
					
<?php
echo genPurchaseReport();
?>
				</div>
			
				<div id="options" class="tab-pane">
				    <form id="mm_pm_settings_form" name="mm_pm_settings_form" class="form-horizontal" method="post">
				    <fieldset>
				    	<legend>Options</legend>
				        <div class="control-group">
				        	<label class="control-label" for="mm_pm_paypalaccount">Paypal Account</label>
				        	<div class="controls">
					        	<input id="mm_pm_paypalaccount" type="text" class="input-large req" name="mm_pm_paypalaccount" value="<?php echo($this->_settings['mm_pm_paypalaccount']); ?>" />
				        	</div>
				        </div>
				        
				        <div class="control-group">
				        	<label class="control-label" for="mm_pm_notifyemail">Notify Email</label>
				        	<div class="controls">
					        	<input id="mm_pm_notifyemail" class="req" type="text" name="mm_pm_notifyemail" value="<?php echo($this->_settings['mm_pm_notifyemail']); ?>" />
					        	<p class="help-block">note: The email we will send notifications to</p>
				        	</div>
				        </div>
				        
				        <div class="control-group">
				        	<label class="control-label" for="mm_pm_notifyquantity">Notify Quantity</label>
				        	<div class="controls">
					        	<input id="mm_pm_notifyquantity" class="req" type="text" name="mm_pm_notifyquantity" value="<?php echo($this->_settings['mm_pm_notifyquantity']); ?>" />
					        	<p class="help-block">note: for unlimited set the quantity to -1</p>
				        	</div>
				        </div>
				        
				        <div class="control-group">
				        	<label class="control-label" for="mm_pm_notifyquantity">Tax Amount</label>
				        	<div class="controls">
					        	<input id="mm_pm_tax" class="req" type="text" name="mm_pm_tax" value="<?php echo($this->_settings['mm_pm_tax']); ?>" />
					        	<p class="help-block">note: a percentage amount to charge in tax (e.g. for 10% enter 10)</p>
				        	</div>
				        </div>
				        
				        <div class="control-group">
				        	<label class="control-label" for="mm_pm_invoice">Invoice Text</label>
			    		    <div class="controls">
					        	<input id="mm_pm_invoice" class="req" type="text" name="mm_pm_invoice" value="<?php echo($this->_settings['mm_pm_invoice']); ?>" /> 
					        	<p class="help-block">e.g. YourInvoiceTextHere-1XXXX, this appears in your paypal account</p>
					        </div>
				        </div>
				        
				        <div class="control-group">
				        	<label class="control-label" for="mm_pm_currency">Currency Code</label>
			    		    <div class="controls">
					        	<input id="mm_pm_currency" class="req" type="text" name="mm_pm_currency" value="<?php echo($this->_settings['mm_pm_currency']); ?>" /> 
					        	<p class="help-block">e.g. CAD, USD, GBP??</p>
					        </div>
				        </div>
				        
				        
				        <div class="form-actions clearfix">
				            <a href="#" id="btnOptionsSave" name="mm_pm_settings_saved" class="btn btn-primary">Save</a>
				            <input type="reset" class="btn" />
				        </div>
				        </fieldset>
				    </form>
			    </div>
			    
			    <div id="list" class="tab-pane">
			        <form name="mm_pm_product_list_form" method="post">
			        	<legend>Class List</legend>
			        	
			        	<div class="form-actions">
			                <a id="btnListAdd" href="#add" class="btn-primary btn">
				                <i class="icon-plus-sign icon-white"></i> 
				                Add a Class
			                </a>
			            </div>
	
<?php
	OutputProductList();
?>       		
			            </table>
			            
			            
			        </form>
			    </div>
			    
			    <div id="add" class="tab-pane">
				    <form id="mm_pm_product_add_form" name="mm_pm_product_add_form" method="post" class="form-horizontal">
				    	<legend>Add a Product</legend>
				    	<fieldset>
				    		<input id="mm_pm_pid" type="hidden" value="-1" />
					        <div for="mm_pm_pname" class="control-group">
					        	<label class="control-label">Item Code</label>
					        	<div class="controls">
						        	<input id="mm_pm_pname" class="medium req" type="text" name="mm_pm_pname" />
					        	</div>
					        </div>
					        
					        <div class="control-group">
					        	<label class="control-label" for="mm_pm_pdesc">Description</label>
					        	<div class="controls">
						        	<input id="mm_pm_pdesc" class="large req" type="text" name="mm_pm_pdesc" />
						        	<p class="help-block">note: usage is [MMProductGroup description="YourCodeHere" /]</p>
					        	</div>
					        </div>
					        
					        <div class="control-group">
					        	<label class="control-label" for="mm_pm_pcost">Price</label>
					        	<div class="controls">
					            	<input id="mm_pm_pcost" class="large req" type="text" name="mm_pm_pcost" />
					        	</div>
					        </div>
					        
					        <div class="control-group">
					        	<label class="control-label" for="mm_pm_pquant">Max Quantity</label>
					        	<div class="controls">
						        	<input id="mm_pm_pquant" class="small req" type="text" name="mm_pm_pquant" />
						        </div>
					        </div>
					        
					        <div class="control-group">
					        	<label class="control-label" for="mm_pm_pnquant">Notify Quantity</label>
					        	<div class="controls">
						        	<input id="mm_pm_pnquant" class="small" type="text" name="mm_pm_pnquant" value="<?php echo($this->_settings['mm_pm_notifyquantity']); ?>" />
						        </div>
					        </div>
					        
					        <div class="control-group">
					        	<label class="control-label" for="mm_pm_pstart">Start Date</label>
					        	<div class="controls">
						        	<input id="mm_pm_pstart" class="medium req" type="text" name="mm_pm_pstart" />
						        </div>
					        </div>
					        
					        <div class="control-group">
					        	<label class="control-label" for="mm_pm_pend">End Date</label>
					        	<div class="controls">
						        	<input id="mm_pm_pend" class="medium req" type="text" name="mm_pm_pend" />
					        	</div>
					        </div>
					        
					        <div class="control-group">
					        	<label class="control-label" for="mm_pm_purl">Url</label>
					        	<div class="controls">
						        	<input id="mm_pm_purl" class="medium req" type="text" name="mm_pm_purl" />
					        	</div>
					        </div>
					        
					        <div class="form-actions clearfix">
					            <a href="javascript: void(0);" id="btnProductSave" name="mm_pm_product_saved" class="btn btn-primary">Save</a>
					            <input type="reset" class="btn" />
					        </div>
				        </fieldset>
				    </form>
			    </div>
		    </div>
	    </div>
	</div>
</div>