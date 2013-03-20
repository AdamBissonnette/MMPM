function FormToolsSetup()
{	
	CheckScripts();
}

function CheckScripts()
{
	if(typeof(jQuery)=='undefined'){
		var loadjQuery = document.createElement("script");
		loadjQuery.setAttribute("type","text/javascript");
		loadjQuery.setAttribute("src","jquery-1.7.1.min.js");
		document.getElementsByTagName("head")[0].appendChild(loadjQuery);
	}
}

function updateSubtotal(code)
{
	var price = jQuery('#mmprice-' + code).val();
	var quant = jQuery('#mmquant-' + code).val();
	
	if (!jQuery.isNumeric(price))
	{
		price = 0;
	}
	
	if (!jQuery.isNumeric(quant))
	{
		quant = 0;
	}
	
	var subtotal = (price * quant);
	var formatted = '$' + (subtotal).formatMoney(2, '.', ',')
	jQuery('#mmsubtotal-' + code).val(formatted);
}

function doBuy(code, url)
{
	if (ValidateForm(jQuery("#mmform-" + code)))
	{
		StoreJSON(code, url);
	}
}

function ValidateForm(Form)
{
	var FormID = jQuery(Form).attr('id');
	jQuery('#' + FormID + ' .error').each(function() {jQuery(this).removeClass('error');})
	
	var ErrorFields = new Array();
	var IsValid = true;
	
	ErrorFields = CheckRequiredFields(FormID, ErrorFields);
	
	if (ErrorFields.length == 0)
	{
		ErrorFields = CheckMatchingFields(FormID, ErrorFields);
		
		if (ErrorFields.length == 0)
		{
			ErrorFields = CheckNonZeroFields(FormID, ErrorFields);
			
			if (ErrorFields.length != 0)
			{
				IsValid = false;
			}
		}
		else
		{
			IsValid = false;
		}
	}
	else {		
		IsValid = false;
	}
	
	if (IsValid == false)
	{
		Handle(Form, ErrorFields);
		alert('There is an error in the form.  Please correct any fields highlight in red.');
	}
	
	return IsValid;
}

function CheckRequiredFields(FormID, ErrorFields)
{	
	var fields = jQuery('#' + FormID + ' .req');
	var ErrorCount = ErrorFields.length;
	
	for (var i = 0; i < fields.length; i++)
	{
		var x = jQuery(fields[i]);
		
		if (isEmpty(x.val()))
		{
			ErrorFields[ErrorCount++] = x;
		}
	}
	
	return ErrorFields;
}

function CheckNonZeroFields(FormID, ErrorFields)
{
	var fields = jQuery('#' + FormID + ' .nonzero');
	var ErrorCount = ErrorFields.length;
	
	var ZeroCount = 0;
	
	for (var i = 0; i < fields.length; i++)
	{
		var x = jQuery(fields[i]);
		
		if (x.val() == '0')
		{
			ZeroCount++;
		}
	}
	
	if (ZeroCount == fields.length)
	{
		jQuery('#' + FormID + ' .nonzero').each(function() {ErrorFields[ErrorCount++] = jQuery(this);});
	}
	
	return ErrorFields;
}

function CheckMatchingFields(FormID, ErrorFields)
{	
	var fields = jQuery('#' + FormID + ' .match');
	var ErrorCount = ErrorFields.length;
	
	for (var i = 0; i < fields.length; i++)
	{
		var x = jQuery(fields[i]);
		
		for (var j = 0; j < fields.length; j++)
		{
			var y = jQuery(fields[j]);
			
			if (!FieldsMatch(x, y))
			{
				ErrorFields[ErrorCount++] = x;
				j = fields.length;
			}
		}
	}
	
	return ErrorFields;
}

function FieldsMatch(field1, field2)
{
	var Match = field1.val() == field2.val();
	
	return Match;
}

/* Error Handling */
function AddErrorClass(Field)
{
	if (!Field.hasClass('error'))
	{
		Field.addClass('error');
	}
}

function bJSONS(key, value)
{
	return "\"" + key + "\": \"" + value + "\"";
}

function isEmpty(value)
{
	return value == '';
}

function Handle(Form, ErrorFields)
{
	var Fields = '';
	for (var i = 0; i < ErrorFields.length; i++)
	{
		var x =	jQuery(ErrorFields[i]).parent().parent();
		
		AddErrorClass(x);
	}
}

/* Form Submit */
function StoreJSON(code, url)
{
	var quant = jQuery("#mmquant-" + code).val();
	
	var info = '{"info":[{' + bJSONS("quant", quant) + ', ' + bJSONS("code", code) + '}]}';
	
	jQuery.post (url, { 'action':'do_ajax', 'fn':'buy', 'count':10, buy:info }, function(data){FinalizeForm(data)}, "json");
}

function FinalizeForm(data)
{
	if (data.g)
	{
		jQuery(data.form).attr('action', data.action);
		jQuery(data.attr).html(data.html);
		jQuery(data.submit).click();
	}
	else {
		alert(data.gm);
	}
}

function GetCalUrl(calid)
{
	var info = {id : calid};

	jQuery.post ('../../wp-admin/admin-ajax.php', { 'action':'do_ajax', 'fn':'calid', 'count':10, calid : info }, function(data){GotCalUrl(data)});
}

function GotCalUrl(data)
{
	if (data)
	{
		window.location.href = data;
	}
}
Number.prototype.formatMoney = function(c, d, t){
var n = this, c = isNaN(c = Math.abs(c)) ? 2 : c, d = d == undefined ? "," : d, t = t == undefined ? "." : t, s = n < 0 ? "-" : "", i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + "", j = (j = i.length) > 3 ? j % 3 : 0;
   return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "jQuery1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
 };

/* Form Tools Setup */
jQuery(document).ready(function() {
	FormToolsSetup()
});