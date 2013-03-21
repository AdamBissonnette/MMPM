function Start()
{
	BindActions();
	jQuery('#mm_pm_pstart').datepicker({
	dateFormat: 'yy-mm-dd',
	separator: ' ',
	});
	jQuery('#mm_pm_pend').datepicker({
	dateFormat: 'yy-mm-dd',
	separator: ' '
	});
}

function BindActions()
{
	jQuery('#btnListAdd').click(function() {ResetAdd(); ShowAddProduct()});
	jQuery('#btnOverviewOptions').click(function() {ShowOptions()});
	jQuery('#btnProductSave').click(function() {if (ValidateForm(jQuery("#mm_pm_product_add_form"))){SaveProduct()}});
	jQuery('#btnOptionsSave').click(function() {if (ValidateForm(jQuery("#mm_pm_settings_form"))){SaveOptions()}});
}

function ShowAddProduct()
{
	jQuery('#addtab').tab('show');
}

function ShowOptions()
{
	jQuery('#optionstab').tab('show');
}

function ShowProductList()
{
	jQuery('#plisttab').tab('show');
}

function SaveOptions()
{
	var paypal = jQuery('#mm_pm_paypalaccount').val();
	var nemail = jQuery('#mm_pm_notifyemail').val();
	var nquant = jQuery('#mm_pm_notifyquantity').val();
	var tax = jQuery('#mm_pm_tax').val();
	var invoice = jQuery('#mm_pm_invoice').val();
	var currency = jQuery('#mm_pm_currency').val();
	
	var info = '{"info":[{' + bJSONS("paypal", paypal) + ', ' + bJSONS("nemail", nemail) + ', ' + bJSONS("nquant", nquant) + ', ' +
	bJSONS("tax", tax) + ', ' + bJSONS("invoice", invoice) + ', ' + bJSONS("currency", currency) + '}]}';
	
	jQuery.post ('admin-ajax.php', { 'action':'do_ajax', 'fn':'settings', 'count':10, settings:info }, function(data){FinalizeOptions(data)}, "json");
}

function SaveProduct()
{
	var pid = jQuery('#mm_pm_pid').val();
	var name = jQuery('#mm_pm_pname').val();
	var desc = jQuery('#mm_pm_pdesc').val();
	var price = jQuery('#mm_pm_pcost').val();
	var max = jQuery('#mm_pm_pquant').val();
	var notify = jQuery('#mm_pm_pnquant').val();
	var start = jQuery('#mm_pm_pstart').val();
	var end = jQuery('#mm_pm_pend').val();
	var url = jQuery('#mm_pm_purl').val();
	//mm_pm_purl
	
	var info = '{"info":[{' + bJSONS("pid", pid) + ', ' + bJSONS("name", name) + ', ' + bJSONS("desc", desc) + ', ' +
	bJSONS("price", price) + ', ' + bJSONS("max", max) + ', ' + 	bJSONS("notify", notify) +
	', ' + bJSONS("start", start) + ', ' + bJSONS("end", end) + ', ' + bJSONS("url", url) + '}]}';
	
	jQuery.post ('admin-ajax.php', { 'action':'do_ajax', 'fn':'product', 'count':10, product:info }, function(data){FinalizeAdd(data)}, "json");
}

function EditProduct(id)
{
	var info = '{"info":[{' + bJSONS("Pid", id) + '}]}';
	jQuery.post ('admin-ajax.php', { 'action':'do_ajax', 'fn':'get', 'count':10, get:info }, function(data){FinalizeEdit(data)}, "json");
}

function CopyProduct(id)
{
	var info = '{"info":[{' + bJSONS("Pid", id) + '}]}';
	jQuery.post ('admin-ajax.php', { 'action':'do_ajax', 'fn':'get', 'count':10, get:info }, function(data){FinalizeCopy(data)}, "json");
}

function DeleteProduct(id)
{
	if (confirm('Are you sure you want to delete this product?'))
	{
		var info = '{"info":[{' + bJSONS("Pid", id) + '}]}';
		
		jQuery.post ('admin-ajax.php', { 'action':'do_ajax', 'fn':'delete', 'count':10, delete:info }, function(data){FinalizeDelete(data, id)});
	}
}

function FillClass(id)
{
	if (confirm('Are you sure you want to fill this class?'))
	{
		var info = '{"info":[{' + bJSONS("Pid", id) + '}]}';
		
		jQuery.post ('admin-ajax.php', { 'action':'do_ajax', 'fn':'fill', 'count':10, fill:info }, function(data){FinalizeFill(data, id)});
	}
}

function FinalizeAdd(data)
{
	var pid = data.pid;
	var row = jQuery('#row-' + pid);
	
	if (row.length == 0)
	{
		jQuery('#mm_pm_productlist tbody tr:first').before('<tr id="row-' + pid + '" class="success"></tr>');
		row = jQuery('#row-' + pid);
	}
	
	var icon = "icon-off";
	
	if (data.active)
	{
		icon = "icon-ok";
	}
	
	var productBody = '<td><a href="#" title="Active"><i class="' + icon + '"></i></a></td><td>' +
	data.pname + '</td><td>' + data.pdesc + '</td><td>' + data.pquant +
	'</td><td>' + data.psales + '</td><td>' + data.pend + '</td><td>' +
	'<a href=\"#\" class=\"btnProductEdit\" onclick=\"javascript: EditProduct(' + pid +
	');\" title=\"Edit\"><i class=\"icon-edit\"></i>Edit</a> ' +
	'<a href=\"#\" class=\"btnProductCopy\" onclick=\"javascript: CopyProduct(' + pid +
	');\" title=\"Copy\"><i class=\"icon-file\"></i>Copy</a> ' +
	'<a href=\"#\" class=\"btnProductDelete\" onclick=\"javascript: DeleteProduct(' + pid +
	');\" title=\"Delete\"><i class=\"icon-trash\"></i>Delete</a> ' +
	'<a href=\"#\" class=\"btnFillClass\" onclick=\"javascript: FillClass(' + pid +
	');\" title=\"Fill Class\"><i class=\"icon-tint\"></i>Fill</a></td>';
	
	row.html(productBody);

   	ShowProductList();
   	ResetAdd();
}

function ResetAdd()
{
	jQuery('#mm_pm_product_add_form')[0].reset();
	jQuery('#mm_pm_pid').val(-1);
}

function FinalizeFill(data, id)
{	
	jQuery('#row-' + id).hide();
}

function FinalizeDelete(data, id)
{	
	jQuery('#row-' + id).hide();
}

function FinalizeEdit(data)
{
	PopulateProduct(data);
	jQuery('#mm_pm_pid').val(data.pid);
	ShowAddProduct();
}

function FinalizeCopy(data)
{
	PopulateProduct(data);
	jQuery('#mm_pm_pid').val(-1);
	ShowAddProduct();
}

function FinalizeOptions(data)
{
	alert("Settings have been saved!");
}

function PopulateProduct(data)
{
	jQuery('#mm_pm_pname').val(data.pname);
	jQuery('#mm_pm_pdesc').val(data.pdesc);
	jQuery('#mm_pm_pcost').val(data.pcost);
	jQuery('#mm_pm_pquant').val(data.pquant);
	jQuery('#mm_pm_pnquant').val(data.pnquant);
	jQuery('#mm_pm_pstart').val(data.pstart);
	jQuery('#mm_pm_pend').val(data.pend);
	jQuery('#mm_pm_purl').val(data.purl);
}

function bJSONS(key, value)
{
	return "\"" + key + "\": \"" + value + "\"";
}
jQuery(document).ready(function($) {
	Start();
});