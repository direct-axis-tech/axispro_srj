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

function add_supplier($supp_name, $supp_ref, $address, $supp_address, $gst_no,
	$website, $supp_account_no, $bank_account, $credit_limit, $dimension_id, $dimension2_id, 
	$curr_code, $payment_terms, $payable_account, $purchase_account, $payment_discount_account, 
	$notes, $tax_group_id, $tax_included)
{
	$sql = "INSERT INTO ".TB_PREF."suppliers (supp_name, supp_ref, address, supp_address, gst_no, website,
		supp_account_no, bank_account, credit_limit, dimension_id, dimension2_id, curr_code,
		payment_terms, payable_account, purchase_account, payment_discount_account, notes, 
		tax_group_id, tax_included)
		VALUES (".db_escape($supp_name). ", "
		.db_escape($supp_ref). ", "
		.db_escape($address) . ", "
		.db_escape($supp_address) . ", "
		.db_escape($gst_no). ", "
		.db_escape($website). ", "
		.db_escape($supp_account_no). ", "
		.db_escape($bank_account). ", "
		.$credit_limit. ", "
		.db_escape($dimension_id). ", "
		.db_escape($dimension2_id). ", "
		.db_escape($curr_code). ", "
		.db_escape($payment_terms). ", "
		.db_escape($payable_account). ", "
		.db_escape($purchase_account). ", "
		.db_escape($payment_discount_account). ", "
		.db_escape($notes). ", "
		.db_escape($tax_group_id). ", "
		.db_escape($tax_included). ")";

	db_query($sql,"The supplier could not be added");
}

function update_supplier($supplier_id, $supp_name, $supp_ref, $address, $supp_address, $gst_no, 
	$website, $supp_account_no, $bank_account, $credit_limit, $dimension_id, $dimension2_id, 
	$curr_code, $payment_terms, $payable_account, $purchase_account, $payment_discount_account, 
	$notes, $tax_group_id, $tax_included)
{
	$sql = "UPDATE ".TB_PREF."suppliers SET supp_name=".db_escape($supp_name) . ",
		supp_ref=".db_escape($supp_ref) . ",
		address=".db_escape($address) . ",
		supp_address=".db_escape($supp_address) . ",
		gst_no=".db_escape($gst_no) . ",
		website=".db_escape($website) . ",
		supp_account_no=".db_escape($supp_account_no) . ",
		bank_account=".db_escape($bank_account) . ",
		credit_limit=".$credit_limit . ",
		dimension_id=".db_escape($dimension_id) . ",
		dimension2_id=".db_escape($dimension2_id) . ",
		curr_code=".db_escape($curr_code).",
		payment_terms=".db_escape($payment_terms) . ",
		payable_account=".db_escape($payable_account) . ",
		purchase_account=".db_escape($purchase_account) . ",
		payment_discount_account=".db_escape($payment_discount_account) . ",
		notes=".db_escape($notes) . ",
		tax_group_id=".db_escape($tax_group_id). ",
		tax_included=".db_escape($tax_included)
		." WHERE supplier_id = ".db_escape($supplier_id);

	db_query($sql,"The supplier could not be updated");
}

function delete_supplier($supplier_id)
{
	$sql="DELETE FROM ".TB_PREF."suppliers WHERE supplier_id=".db_escape($supplier_id);
	db_query($sql,"check failed");
}

function get_supplier_details($supplier_id, $to=null, $all=true)
{

	if ($to == null)
		$todate = date("Y-m-d");
	else
		$todate = date2sql($to);
	$past1 = get_company_pref('past_due_days');
	$past2 = 2 * $past1;
	$past3 = 3 * $past1;
	// removed - supp_trans.alloc from all summations

	if ($all)
    	$value = "(trans.ov_amount + trans.ov_gst + trans.ov_discount)";
    else	
    	$value = "IF (trans.type=".ST_SUPPINVOICE." OR trans.type=".ST_BANKDEPOSIT."
  	OR (trans.type=".ST_JOURNAL." AND (trans.ov_amount + trans.ov_gst + trans.ov_discount)>0), 1, -1)*
    		ABS(trans.ov_amount + trans.ov_gst + trans.ov_discount) - trans.alloc";

	$due = "IF (trans.type=".ST_SUPPINVOICE." OR trans.type=".ST_SUPPCREDIT.",trans.due_date,trans.tran_date)";
    $sql = "SELECT supp.supp_name, supp.curr_code, ".TB_PREF."payment_terms.terms,

		Sum(IFNULL($value,0)) AS Balance,

		Sum(IF ((TO_DAYS('$todate') - TO_DAYS($due)) > 0,$value,0)) AS Due,
		Sum(IF ((TO_DAYS('$todate') - TO_DAYS($due)) >$past1,$value,0)) AS Overdue1,
		Sum(IF ((TO_DAYS('$todate') - TO_DAYS($due))  >$past2,$value,0)) AS Overdue2,
		Sum(IF ((TO_DAYS('$todate') - TO_DAYS($due))  >$past3,$value,0)) AS Overdue3,
		supp.credit_limit - Sum(IFNULL(IF(trans.type=".ST_SUPPCREDIT.", -1, 1) 
			* (ov_amount + ov_gst + ov_discount),0)) as cur_credit,
		supp.tax_group_id

		FROM ".TB_PREF."suppliers supp
			 LEFT JOIN ".TB_PREF."supp_trans trans ON supp.supplier_id = trans.supplier_id AND trans.tran_date <= '$todate',
			 ".TB_PREF."payment_terms

		WHERE
			 supp.payment_terms = ".TB_PREF."payment_terms.terms_indicator
			 AND supp.supplier_id = $supplier_id ";
	if (!$all)
		$sql .= "AND ABS(trans.ov_amount + trans.ov_gst + trans.ov_discount) - trans.alloc > ".FLOAT_COMP_DELTA." ";  
	$sql .= "GROUP BY
			  supp.supp_name,
			  ".TB_PREF."payment_terms.terms,
			  ".TB_PREF."payment_terms.days_before_due,
			  ".TB_PREF."payment_terms.day_in_following_month";


//    echo $sql;
//    die;

    $result = db_query($sql,"The customer details could not be retrieved");
    $supp = db_fetch($result);

    return $supp;
}

function get_supplier($supplier_id)
{
	$sql = "SELECT * FROM ".TB_PREF."suppliers WHERE supplier_id=".db_escape($supplier_id);

	$result = db_query($sql, "could not get supplier");

	return db_fetch($result);
}

function get_supplier_name($supplier_id)
{
	$sql = "SELECT supp_name AS name FROM ".TB_PREF."suppliers WHERE supplier_id=".db_escape($supplier_id);

	$result = db_query($sql, "could not get supplier");

	$row = db_fetch_row($result);

	return is_array($row) ? $row[0] : false;
}

function get_supplier_accounts($supplier_id)
{
	$sql = "SELECT payable_account,purchase_account,payment_discount_account, dimension_id, dimension2_id FROM ".TB_PREF."suppliers WHERE supplier_id=".db_escape($supplier_id);

	$result = db_query($sql, "could not get supplier");

	return db_fetch($result);
}

function get_supplier_contacts($supplier_id, $action=null)
{
	$results = array();
	$res = get_crm_persons('supplier', $action, $supplier_id);
	while($contact = db_fetch($res))
		$results[] = $contact;

	return $results;
}

function get_current_supp_credit($supplier_id)
{
	$suppdet = get_supplier_details($supplier_id);
	return $suppdet['cur_credit'];

}

function is_new_supplier($id)
{
	$tables = array('supp_trans', 'grn_batch', 'purch_orders', 'purch_data');

	return !key_in_foreign_table($id, $tables, 'supplier_id');
}

function get_supplier_currency($supplier_id)
{
    $sql = "SELECT curr_code FROM ".TB_PREF."suppliers WHERE supplier_id = ".db_escape($supplier_id);

	$result = db_query($sql, "Retreive currency of supplier $supplier_id");

	$myrow=db_fetch_row($result);
	return $myrow ? $myrow[0] : get_company_currency();
}

function get_suppliers_search($supplier)
{
	global $SysPrefs;

	if (isset($SysPrefs->max_rows_in_search))
		$limit = $SysPrefs->max_rows_in_search;
	else
		$limit = 10;

	$sql = "SELECT supplier_id, supp_name, supp_ref, address, gst_no
		FROM ".TB_PREF."suppliers
		WHERE (supp_name LIKE " . db_escape("%" . $supplier. "%") . " OR 
			supp_ref LIKE " . db_escape("%" . $supplier. "%") . " OR 
			address LIKE " . db_escape("%" . $supplier. "%") . " OR 
			gst_no LIKE " . db_escape("%" . $supplier. "%") . ")
		ORDER BY supp_name LIMIT 0,".(int)($limit);

	return db_query($sql, "Failed in retreiving supplier list.");
}

function get_supplier_by_refno($suppRefNo)
{
    $sql="SELECT * FROM ".TB_PREF."suppliers s WHERE s.supp_ref = ".db_escape($suppRefNo);

    $result = db_query($sql,"supplier could not be retrieved");

    return db_fetch($result);
}