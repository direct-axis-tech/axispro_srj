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
$page_security = 'SA_GLACCOUNT';
$path_to_root = "../..";
include($path_to_root . "/includes/session.inc");

$js = "";
if ($SysPrefs->use_popup_windows && $SysPrefs->use_popup_search)
	$js .= get_js_open_window(900, 500);

page(trans($help_context = "Chart of Accounts"), false, false, "", $js);

include($path_to_root . "/includes/ui.inc");
include($path_to_root . "/gl/includes/gl_db.inc");
include($path_to_root . "/admin/db/tags_db.inc");
include_once($path_to_root . "/includes/data_checks.inc");

check_db_has_gl_account_groups(trans("There are no account groups defined. Please define at least one account group before entering accounts."));

if (isset($_GET["id"]))
	$_POST["id"] = $_GET["id"];	

//-------------------------------------------------------------------------------------

if (isset($_POST['_AccountList_update'])) 
{
	$_POST['selected_account'] = $_POST['AccountList'];
	unset($_POST['account_code']);
}

if (isset($_POST['selected_account']))
{
	$selected_account = $_POST['selected_account'];
} 
elseif (isset($_GET['selected_account']))
{
	$selected_account = $_GET['selected_account'];
}
else
	$selected_account = "";
//-------------------------------------------------------------------------------------

if (isset($_POST['add']) || isset($_POST['update'])) 
{

	$input_error = 0;

	if (strlen(trim($_POST['account_code'])) == 0) 
	{
		$input_error = 1;
		display_error( trans("The account code must be entered."));
		set_focus('account_code');
	} 
	elseif (strlen(trim($_POST['account_name'])) == 0) 
	{
		$input_error = 1;
		display_error( trans("The account name cannot be empty."));
		set_focus('account_name');
	} 
	elseif (!$SysPrefs->accounts_alpha() && !preg_match("/^[0-9.]+$/",$_POST['account_code'])) // we only allow 0-9 and a dot
	{
	    $input_error = 1;
	    display_error( trans("The account code must be numeric."));
		set_focus('account_code');
	}
	if ($input_error != 1)
	{
		if ($SysPrefs->accounts_alpha() == 2)
			$_POST['account_code'] = strtoupper($_POST['account_code']);

		if (!isset($_POST['account_tags']))
			$_POST['account_tags'] = array();

    	if ($selected_account) 
		{
			if (get_post('inactive') == 1 && is_bank_account($_POST['account_code']))
			{
				display_error(trans("The account belongs to a bank account and cannot be inactivated."));
			}
    		elseif (update_gl_account($_POST['account_code'], $_POST['account_name'], 
				$_POST['account_type'], $_POST['account_code2'])) {
				update_record_status($_POST['account_code'], $_POST['inactive'],
					'chart_master', 'account_code');
				update_tag_associations(TAG_ACCOUNT, $_POST['account_code'], 
					$_POST['account_tags']);
				$Ajax->activate('account_code'); // in case of status change
				display_notification(trans("Account data has been updated."));
			}
		}
    	else 
		{
    		if (add_gl_account($_POST['account_code'], $_POST['account_name'], 
				$_POST['account_type'], $_POST['account_code2']))
				{
					add_tag_associations($_POST['account_code'], $_POST['account_tags']);
					display_notification(trans("New account has been added."));
					$selected_account = $_POST['AccountList'] = $_POST['account_code'];

				}
			else
                 display_error(trans("Account not added, possible duplicate Account Code."));
		}



        //OPENING BALANCE GL ENTRY

		begin_transaction();
        $op_bal_amount = input_num('opening_balance');

        if(!empty($op_bal_amount)) {

            $ref = $Refs->get_next(ST_JOURNAL, null, Today());
            $trans_type = 0;
            $total_gl = 0;
            $dr_account = $_POST['account_code'];
            $cr_account = $SysPrefs->prefs['opening_bal_equity_account'];
            $opening_bal_type = get_post('dr_cr');

            if($opening_bal_type == 'cr') {
                $dr_account =  $SysPrefs->prefs['opening_bal_equity_account'];;
                $cr_account = $_POST['account_code'];;
            }

            $memo = "Opening Balance Entry";

            $trans_id = get_next_trans_no(0);

            $total_gl += add_gl_trans($trans_type, $trans_id, Today(), $dr_account, 0, 0,
                $memo, $op_bal_amount, 'AED', null, null, "", 0);

            $total_gl += add_gl_trans($trans_type, $trans_id, Today(), $cr_account, 0, 0,
                $memo, -$op_bal_amount, 'AED', null, null, "", 0);


            add_journal($trans_type, $trans_id, $op_bal_amount, Today(), 'AED', $ref,
                '', 1, Today(), Today());
            $Refs->save($trans_type, $trans_id, $ref);
            add_comments($trans_type, $trans_id, Today(), $memo);
            add_audit_trail($trans_type, $trans_id, Today());
        }
		commit_transaction();


		$Ajax->activate('_page_body');
	}
}

//-------------------------------------------------------------------------------------

function can_delete($selected_account)
{
    global $SysPrefs;
	if ($selected_account == "")
		return false;

    if($selected_account == $SysPrefs->prefs['opening_bal_equity_account']) {
        display_error(trans("Cannot delete this system default account."));
        return false;
    }

	if (key_in_foreign_table($selected_account, 'gl_trans', 'account'))
	{
		display_error(trans("Cannot delete this account because transactions have been created using this account."));
		return false;
	}

	if (gl_account_in_company_defaults($selected_account))
	{
		display_error(trans("Cannot delete this account because it is used as one of the company default GL accounts."));
		return false;
	}

	if (key_in_foreign_table($selected_account, 'bank_accounts', 'account_code'))
	{
		display_error(trans("Cannot delete this account because it is used by a bank account."));
		return false;
	}

	if (gl_account_in_stock_category($selected_account))
	{
		display_error(trans("Cannot delete this account because it is used by one or more Item Categories."));
		return false;
	}

	if (gl_account_in_stock_master($selected_account))
	{
		display_error(trans("Cannot delete this account because it is used by one or more Items."));
		return false;
	}

	if (gl_account_in_tax_types($selected_account))
	{
		display_error(trans("Cannot delete this account because it is used by one or more Taxes."));
		return false;
	}

	if (gl_account_in_cust_branch($selected_account))
	{
		display_error(trans("Cannot delete this account because it is used by one or more Customer Branches."));
		return false;
	}
	if (gl_account_in_suppliers($selected_account))
	{
		display_error(trans("Cannot delete this account because it is used by one or more suppliers."));
		return false;
	}

	if (gl_account_in_quick_entry_lines($selected_account))
	{
		display_error(trans("Cannot delete this account because it is used by one or more Quick Entry Lines."));
		return false;
	}

	return true;
}

//--------------------------------------------------------------------------------------

if (isset($_POST['delete'])) 
{

	if (can_delete($selected_account))
	{
		delete_gl_account($selected_account);
		$selected_account = $_POST['AccountList'] = '';
		delete_tag_associations(TAG_ACCOUNT,$selected_account, true);
		$selected_account = $_POST['AccountList'] = '';
		display_notification(trans("Selected account has been deleted"));
		unset($_POST['account_code']);
		$Ajax->activate('_page_body');
	}
} 

//-------------------------------------------------------------------------------------
$filter_id = (isset($_POST["id"]));

start_form();

if (db_has_gl_accounts()) 
{
	start_table(TABLESTYLE_NOBORDER);
	start_row();
	if ($filter_id)
		gl_all_accounts_list_cells(null, 'AccountList', null, false, false, trans('New account'), true, check_value('show_inactive'), $_POST['id']);
	else
		gl_all_accounts_list_cells(null, 'AccountList', null, false, false, trans('New account'), true, check_value('show_inactive'));
	check_cells(trans("Show inactive:"), 'show_inactive', null, true);
	end_row();
	end_table();
	if (get_post('_show_inactive_update')) {
		$Ajax->activate('AccountList');
		set_focus('AccountList');
	}
}
	
br(1);
start_table(TABLESTYLE2);

if ($selected_account != "") 
{
	//editing an existing account
	$myrow = get_gl_account($selected_account);

	$_POST['account_code'] = $myrow["account_code"];
	$_POST['account_code2'] = $myrow["account_code2"];
	$_POST['account_name']	= $myrow["account_name"];
	$_POST['account_type'] = $myrow["account_type"];
 	$_POST['inactive'] = $myrow["inactive"];
 	
 	$tags_result = get_tags_associated_with_record(TAG_ACCOUNT, $selected_account);
 	$tagids = array();
 	while ($tag = db_fetch($tags_result)) 
 	 	$tagids[] = $tag['id'];
 	$_POST['account_tags'] = $tagids;

	hidden('account_code', $_POST['account_code']);
	hidden('selected_account', $selected_account);
		
	label_row(trans("Account Code:"), $_POST['account_code']);
} 
else
{
	if (!isset($_POST['account_code'])) {
		$_POST['account_tags'] = array();
		$_POST['account_code'] = $_POST['account_code2'] = '';
		$_POST['account_name']	= $_POST['account_type'] = '';
 		$_POST['inactive'] = 0;
		if ($filter_id) $_POST['account_type'] = $_POST['id'];
	}
	text_row_ex(trans("Account Code:"), 'account_code', 15);
}

text_row_ex(trans("Account Code 2:"), 'account_code2', 15);

text_row_ex(trans("Account Name:"), 'account_name', 60);

gl_account_types_list_row(trans("Account Group:"), 'account_type', null);

tag_list_row(trans("Account Tags:"), 'account_tags', 5, TAG_ACCOUNT, true);

record_status_list_row(trans("Account status:"), 'inactive');


start_row();

//FOR OPENING BALANCE
//if ($selected_account == "") {
    label_cell("Opening Balance ");
    echo "<td>" . array_selector("dr_cr", null, ["dr" => "Debit", "cr" => "Credit"]) .
        "<input type='text' name='opening_balance'></td>";
//}
//END -- FOR OPENING BALANCE

end_row();

end_table(1);

if ($selected_account == "") 
{
	submit_center('add', trans("Add Account"), true, '', 'default');
} 
else 
{
    submit_center_first('update', trans("Update Account"), '', 'default');
    submit_center_last('delete', trans("Delete account"), '',true);
}
end_form();

end_page();

