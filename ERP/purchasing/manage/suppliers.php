<?php
/**********************************************************************
    Direct Axis Technology L.L.C.
	Released under the terms of the GNU General Public License, GPL, 
	as published by the Free Software Foundation, either version 3 
	of the License, or (at your option) any later version.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
    See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
***********************************************************************/
$page_security = 'SA_SUPPLIER';
$path_to_root = "../..";
include($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/includes/session.inc");

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();

page(trans($help_context = "Suppliers"), @$_REQUEST['popup'], false, "", $js);

include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/ui/contacts_view.inc");
include_once($path_to_root . "/includes/ui/attachment.inc");
include_once($path_to_root . "/purchasing/includes/ui/supp_item_discounts_view.inc");

check_db_has_tax_groups(trans("There are no tax groups defined in the system. At least one tax group is required before proceeding."));

if (isset($_GET['supplier_id'])) 
{
	$_POST['supplier_id'] = $_GET['supplier_id'];
}

$supplier_id = get_post('supplier_id', ''); 

function can_process()
{
	/* actions to take once the user has clicked the submit button
	ie the page has called itself with some user input */

	//first off validate inputs sensible

	if (strlen($_POST['supp_name']) == 0 || $_POST['supp_name'] == "") 
	{
		display_error(trans("The supplier name must be entered."));
		set_focus('supp_name');
		return false;
	}

	if (strlen($_POST['supp_ref']) == 0 || $_POST['supp_ref'] == "") 
	{
		display_error(trans("The supplier short name must be entered."));
		set_focus('supp_ref');
		return false;
	}

	return true;
}

function handle_submit(&$supplier_id)
{
	global $Ajax;
	if (!can_process()) {
		return;
	}

	begin_transaction();
	if ($supplier_id) 
	{
		update_supplier($_POST['supplier_id'], $_POST['supp_name'], $_POST['supp_ref'], $_POST['address'],
			$_POST['supp_address'], $_POST['gst_no'],
			$_POST['website'], $_POST['supp_account_no'], $_POST['bank_account'], 
			input_num('credit_limit', 0), $_POST['dimension_id'], $_POST['dimension2_id'], $_POST['curr_code'],
			$_POST['payment_terms'], $_POST['payable_account'], $_POST['purchase_account'], $_POST['payment_discount_account'],
			$_POST['notes'], $_POST['tax_group_id'], check_value('tax_included'));
		update_record_status($_POST['supplier_id'], $_POST['inactive'],
			'suppliers', 'supplier_id');

		$Ajax->activate('supplier_id'); // in case of status change
		display_notification(trans("Supplier has been updated."));
	} 
	else 
	{
		add_supplier($_POST['supp_name'], $_POST['supp_ref'], $_POST['address'], $_POST['supp_address'],
			$_POST['gst_no'], $_POST['website'], $_POST['supp_account_no'], $_POST['bank_account'], 
			input_num('credit_limit',0), $_POST['dimension_id'], $_POST['dimension2_id'],
			$_POST['curr_code'], $_POST['payment_terms'], $_POST['payable_account'], $_POST['purchase_account'],
			$_POST['payment_discount_account'], $_POST['notes'], $_POST['tax_group_id'], check_value('tax_included'));

		$supplier_id = $_POST['supplier_id'] = db_insert_id();

		add_crm_person($_POST['supp_ref'], $_POST['contact'], '', $_POST['address'], 
			$_POST['phone'], $_POST['phone2'], $_POST['fax'], $_POST['email'], 
			$_POST['rep_lang'], '');

		add_crm_contact('supplier', 'general', $supplier_id, db_insert_id());

		display_notification(trans("A new supplier has been added."));
		$Ajax->activate('_page_body');
	}
	commit_transaction();
}

if (isset($_POST['submit'])) 
{
	handle_submit($supplier_id);
} 
elseif (isset($_POST['delete']) && $_POST['delete'] != "") 
{
	//the link to delete a selected record was clicked instead of the submit button

	$cancel_delete = 0;

	// PREVENT DELETES IF DEPENDENT RECORDS IN 'supp_trans' , purch_orders

	if (key_in_foreign_table($_POST['supplier_id'], 'supp_trans', 'supplier_id'))
	{
		$cancel_delete = 1;
		display_error(trans("Cannot delete this supplier because there are transactions that refer to this supplier."));

	} 
	else 
	{
		if (key_in_foreign_table($_POST['supplier_id'], 'purch_orders', 'supplier_id'))
		{
			$cancel_delete = 1;
			display_error(trans("Cannot delete the supplier record because purchase orders have been created against this supplier."));
		}

	}
	if ($cancel_delete == 0) 
	{
		delete_supplier($_POST['supplier_id']);

		unset($_SESSION['supplier_id']);
		$supplier_id = '';
		$Ajax->activate('_page_body');
		display_notification("#" . $_POST['supplier_id'] . " " . trans("Supplier has been deleted."));
	} //end if Delete supplier
}
//--------------------------------------------------------------------------------------------
function supplier_settings(&$supplier_id)
{
	global $page_nested;
	
	start_outer_table(TABLESTYLE2);

	table_section(1);

	if ($supplier_id) 
	{
		//SupplierID exists - either passed when calling the form or from the form itself
		$myrow = get_supplier($_POST['supplier_id']);

		$_POST['supp_name'] = $myrow["supp_name"];
		$_POST['supp_ref'] = $myrow["supp_ref"];
		$_POST['address']  = $myrow["address"];
		$_POST['supp_address']  = $myrow["supp_address"];

		$_POST['gst_no']  = $myrow["gst_no"];
		$_POST['website']  = $myrow["website"];
		$_POST['supp_account_no']  = $myrow["supp_account_no"];
		$_POST['bank_account']  = $myrow["bank_account"];
		$_POST['dimension_id']  = $myrow["dimension_id"];
		$_POST['dimension2_id']  = $myrow["dimension2_id"];
		$_POST['curr_code']  = $myrow["curr_code"];
		$_POST['payment_terms']  = $myrow["payment_terms"];
		$_POST['credit_limit']  = price_format($myrow["credit_limit"]);
		$_POST['tax_group_id'] = $myrow["tax_group_id"];
		$_POST['tax_included'] = $myrow["tax_included"];
		$_POST['payable_account']  = $myrow["payable_account"];
		$_POST['purchase_account']  = $myrow["purchase_account"];
		$_POST['payment_discount_account'] = $myrow["payment_discount_account"];
		$_POST['notes']  = $myrow["notes"];
	 	$_POST['inactive'] = $myrow["inactive"];
	} 
	else 
	{
		if (list_updated('supplier_id') || !isset($_POST['supp_name'])) {
			$_POST['supp_name'] = $_POST['supp_ref'] = $_POST['address'] = $_POST['supp_address'] = 
				$_POST['tax_group_id'] = $_POST['website'] = $_POST['supp_account_no'] = $_POST['notes'] = '';
			$_POST['dimension_id'] = 0;
			$_POST['dimension2_id'] = 0;
			$_POST['tax_included'] = 0;
			$_POST['sales_type'] = -1;
			$_POST['gst_no'] = $_POST['bank_account'] = '';
			$_POST['payment_terms']  = '';
			$_POST['credit_limit'] = price_format(0);

			$company_record = get_company_prefs();
			$_POST['curr_code']  = $company_record["curr_default"];
			$_POST['payable_account'] = $company_record["creditors_act"];
			$_POST['purchase_account'] = ''; // default/item's cogs account
			$_POST['payment_discount_account'] = $company_record['pyt_discount_act'];
		}
	}

	table_section_title(trans("Basic Data"));

	text_row(trans("Supplier Reference No.:"), 'supp_ref', null, 15, 30);
	text_row(trans("Supplier Name:"), 'supp_name', null, 42, 60);

	text_row(trans("GSTNo:"), 'gst_no', null, 42, 40);
	link_row(trans("Website:"), 'website', null, 42, 55);
	if ($supplier_id && !is_new_supplier($supplier_id) && (key_in_foreign_table($_POST['supplier_id'], 'supp_trans', 'supplier_id') ||
		key_in_foreign_table($_POST['supplier_id'], 'purch_orders', 'supplier_id'))) 
	{
		label_row(trans("Supplier's Currency:"), $_POST['curr_code']);
		hidden('curr_code', $_POST['curr_code']);
	} 
	else 
	{
		currencies_list_row(trans("Supplier's Currency:"), 'curr_code', null);
	}
	tax_groups_list_row(trans("Tax Group:"), 'tax_group_id', null);
	text_row(trans("Our Customer No:"), 'supp_account_no', null, 42, 40);

	table_section_title(trans("Purchasing"));
	text_row(trans("Bank Name/Account:"), 'bank_account', null, 42, 40);
	amount_row(trans("Credit Limit:"), 'credit_limit', null);
	payment_terms_list_row(trans("Payment Terms:"), 'payment_terms', null);
	//
	// tax_included option from supplier record is used directly in update_average_cost() function,
	// therefore we can't edit the option after any transaction was done for the supplier.
	//
	if (is_new_supplier($supplier_id))
		check_row(trans("Prices contain tax included:"), 'tax_included');
	else {
		hidden('tax_included');
		label_row(trans("Prices contain tax included:"), $_POST['tax_included'] ? trans('Yes') : trans('No'));
	}

	if (!$supplier_id) table_section(2);

	table_section_title(trans("Accounts"));
	gl_all_accounts_list_row(trans("Accounts Payable Account:"), 'payable_account', $_POST['payable_account']);
	gl_all_accounts_list_row(trans("Purchase Account:"), 'purchase_account', $_POST['purchase_account'],
		false, false, trans("Use Item Inventory/COGS Account"));
	gl_all_accounts_list_row(trans("Purchase Discount Account:"), 'payment_discount_account', $_POST['payment_discount_account']);
	if (!$supplier_id) {
		table_section_title(trans("Contact Data"));
		text_row(trans("Contact Person:"), 'contact', null, 42, 40);
		text_row(trans("Phone Number:"), 'phone', null, 32, 30);
		text_row(trans("Secondary Phone Number:"), 'phone2', null, 32, 30);
		table_section_title(trans("Contact Data"));
		text_row(trans("Fax Number:"), 'fax', null, 32, 30);
		email_row(trans("E-mail:"), 'email', null, 35, 55);
		languages_list_row(trans("Document Language:"), 'rep_lang', null, trans('System default'));
	}
	else
		table_section(2);
	$dim = get_company_pref('use_dimension');
	if ($dim >= 1)
	{
		table_section_title(trans("Dimension"));
		dimensions_list_row(trans("Dimension")." 1:", 'dimension_id', null, true, " ", false, 1);
		if ($dim > 1)
			dimensions_list_row(trans("Dimension")." 2:", 'dimension2_id', null, true, " ", false, 2);
	}
	if ($dim < 1)
		hidden('dimension_id', 0);
	if ($dim < 2)
		hidden('dimension2_id', 0);
	if (!$supplier_id)	
		table_section(2);

	table_section_title(trans("Addresses"));
	textarea_row(trans("Mailing Address:"), 'address', null, 35, 5);
	textarea_row(trans("Physical Address:"), 'supp_address', null, 35, 5);

	table_section_title(trans("General"));
	textarea_row(trans("General Notes:"), 'notes', null, 35, 5);
	if ($supplier_id)
		record_status_list_row(trans("Supplier status:"), 'inactive');
	end_outer_table(1);

	div_start('controls');
	if (@$_REQUEST['popup']) hidden('popup', 1);
	if ($supplier_id) 
	{
		submit_center_first('submit', trans("Update Supplier"),
		  trans('Update supplier data'), $page_nested ? true : false);
		submit_return('select', get_post('supplier_id'), trans("Select this supplier and return to document entry."));
		submit_center_last('delete', trans("Delete Supplier"),
		  trans('Delete supplier data if have been never used'), true);
	}
	else 
	{
		submit_center('submit', trans("Add New Supplier Details"), true, '', false);
	}
	div_end();
}

start_form(true);

if (db_has_suppliers()) 
{
	start_table(false, "", 3);
	start_row();
	supplier_list_cells(trans("Select a supplier: "), 'supplier_id', null,
		  trans('New supplier'), true, check_value('show_inactive'), false, !$_SESSION['wa_current_user']->is_developer_session);
	check_cells(trans("Show inactive:"), 'show_inactive', null, true);
	end_row();
	end_table();
	if (get_post('_show_inactive_update')) {
		$Ajax->activate('supplier_id');
		set_focus('supplier_id');
	}
} 
else 
{
	hidden('supplier_id', get_post('supplier_id'));
}

if (!$supplier_id)
	unset($_POST['_tabs_sel']); // force settings tab for new customer

	$tabs = array(
		'settings' => array(trans('&General settings'), $supplier_id),
		'contacts' => array(trans('&Contacts'), $supplier_id),
		'transactions' => array(trans('&Transactions'), (user_check_access('SA_SUPPTRANSVIEW') ? $supplier_id : null)),
		'orders' => array(trans('Purchase &Orders'), (user_check_access('SA_SUPPTRANSVIEW') ? $supplier_id : null)),
		'attachments' => array(trans('Attachments'), (user_check_access('SA_ATTACHDOCUMENT') ? $supplier_id : null)),
	);

	if (user_check_access('SA_SUPPDISC')) {
		$tabs['items_and_discounts'] = array(trans('Items and discounts'), $supplier_id);
	}

	tabbed_content_start('tabs', $tabs);
	
	switch (get_post('_tabs_sel')) {
		default:
		case 'settings':
			supplier_settings($supplier_id); 
			break;
		case 'contacts':
			$contacts = new contacts('contacts', $supplier_id, 'supplier');
			$contacts->show();
			break;
		case 'transactions':
			$_GET['supplier_id'] = $supplier_id;
			include_once($path_to_root."/purchasing/inquiry/supplier_inquiry.php");
			break;
		case 'orders':
			$_GET['supplier_id'] = $supplier_id;
			include_once($path_to_root."/purchasing/inquiry/po_search_completed.php");
			break;
		case 'attachments':
			$_GET['trans_no'] = $supplier_id;
			$_GET['type_no']= ST_SUPPLIER;
			$attachments = new attachments('attachment', $supplier_id, 'suppliers');
			$attachments->show();
		case 'items_and_discounts':
			$contacts = new supplier_discount_items('items_and_discounts', $supplier_id, 'supplier');
			$contacts->show();
			break;
	};
br();
tabbed_content_end();
end_form();
end_page(@$_REQUEST['popup']);

