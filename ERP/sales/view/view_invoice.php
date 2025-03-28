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
$page_security = 'SA_SALESTRANSVIEW';
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/sales/includes/sales_ui.inc");

include_once($path_to_root . "/sales/includes/sales_db.inc");

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 600);
page(trans($help_context = "View Sales Invoice"), true, false, "", $js);


if (isset($_GET["trans_no"]))
{
	$trans_id = $_GET["trans_no"];
}
elseif (isset($_POST["trans_no"]))
{
	$trans_id = $_POST["trans_no"];
}

// 3 different queries to get the information - what a JOKE !!!!

$myrow = get_customer_trans($trans_id, ST_SALESINVOICE);
$paym = get_payment_terms($myrow['payment_terms']);

$branch = get_branch($myrow["branch_code"]);

$sales_order = get_sales_order_header($myrow["order_"], ST_SALESORDER);

display_heading(sprintf($myrow['prep_amount'] > 0 ? (
	$paym['days_before_due']>=0 ? trans("FINAL INVOICE #%d") : trans("PREPAYMENT INVOICE #%d")) : trans("SALES INVOICE #%s"),$myrow['reference']));

echo "<br>";
start_table(TABLESTYLE2, "width='95%'");
echo "<tr valign=top><td>"; // outer table

/*Now the customer charged to details in a sub table*/
start_table(TABLESTYLE, "width='100%'");
$th = array(trans("Charge To"));
table_header($th);

label_row(null, $myrow["DebtorName"] . "<br>" . nl2br($myrow["address"]), "nowrap");

end_table();

/*end of the small table showing charge to account details */

echo "</td><td>"; // outer table

/*end of the main table showing the company name and charge to details */

start_table(TABLESTYLE, "width='100%'");
$th = array(trans("Charge Branch"));
//table_header($th);

//label_row(null, $branch["br_name"] . "<br>" . nl2br($branch["br_address"]), "nowrap");
end_table();

echo "</td><td>"; // outer table

start_table(TABLESTYLE, "width='100%'");
$th = array(trans("Payment Terms"));
//table_header($th);
//label_row(null, $paym["terms"], "nowrap");
end_table();

echo "</td><td>"; // outer table

start_table(TABLESTYLE, "width='100%'");
start_row();
label_cells(trans("Reference"), $myrow["reference"], "class='tableheader2'");
label_cells(trans("Invoice Date"), sql2date($myrow["tran_date"]), "class='tableheader2'", "nowrap");
//label_cells(trans("Currency"), $sales_order["curr_code"], "class='tableheader2'");
//label_cells(trans("Our Order No"),
//	get_customer_trans_view_str(ST_SALESORDER,$sales_order["order_no"]), "class='tableheader2'");
end_row();
//start_row();
//label_cells(trans("Customer Order Ref."), $sales_order["customer_ref"], "class='tableheader2'");
//label_cells(trans("Shipping Company"), $myrow["shipper_name"], "class='tableheader2'");
//label_cells(trans("Sales Type"), $myrow["sales_type"], "class='tableheader2'");
//end_row();
start_row();
label_cells(trans("Due Date"), sql2date($myrow["due_date"]), "class='tableheader2'", "nowrap");
if ($myrow['prep_amount']==0)
//	label_cells(trans("Deliveries"), get_customer_trans_view_str(ST_CUSTDELIVERY,
//		get_sales_parent_numbers(ST_SALESINVOICE, $trans_id)), "class='tableheader2'");

end_row();
comments_display_row(ST_SALESINVOICE, $trans_id);
end_table();

echo "</td></tr>";
end_table(1); // outer table


$result = get_customer_trans_details(ST_SALESINVOICE, $trans_id);

start_table(TABLESTYLE, "width='95%'");

if (db_num_rows($result) > 0)
{
	$th = array(trans("Item Code"), trans("Item Description"), trans("Quantity"),
		trans("Unit"),trans("Govt. Fee"), trans("Price"), trans("Discount"), trans("Total"));
	table_header($th);

	$k = 0;	//row colour counter
	$sub_total = 0;
	while ($myrow2 = db_fetch($result))
	{
	    if($myrow2["quantity"]==0) continue;
		alt_table_row_color($k);

        $govt_charges = ($myrow2['govt_fee']+$myrow2['bank_service_charge']+$myrow2['bank_service_charge_vat'])*$myrow2["quantity"];

		$value = round2((($myrow2["unit_price"]-$myrow2["discount_amount"]) * $myrow2["quantity"])+$govt_charges,
		   user_price_dec());
		$sub_total += $value;

	    if ($myrow2["discount_amount"] == 0)
	    {
		  	$display_discount = "";
	    }
	    else
	    {
		  	$display_discount = price_format($myrow2["discount_amount"]);
	    }

	    label_cell($myrow2["stock_id"]);
		label_cell($myrow2["StockDescription"]);
        qty_cell($myrow2["quantity"], false, get_qty_dec($myrow2["stock_id"]));
        label_cell($myrow2["units"], "align=right");
        label_cell($govt_charges, "align=right");
        amount_cell($myrow2["unit_price"]*$myrow2["quantity"]);
        label_cell($display_discount, "nowrap align=right");
        amount_cell($value);
		end_row();
	} //end while there are line items to print out

	$display_sub_tot = price_format($sub_total);
	label_row(trans("Sub-total"), $display_sub_tot, "colspan=7 align=right",
		"nowrap align=right width='15%'");
}
else
	display_note(trans("There are no line items on this invoice."), 1, 2);

/*Print out the invoice text entered */
if ($myrow['ov_freight'] != 0.0)
{
	$display_freight = price_format($myrow["ov_freight"]);
//	label_row(trans("Shipping"), $display_freight, "colspan=7 align=right", "nowrap align=right");
}

$tax_items = get_trans_tax_details(ST_SALESINVOICE, $trans_id);

//    display_error(print_r($tax_items ,true));

display_customer_trans_tax_details($tax_items, 7);

$display_total = price_format($myrow["Total"]);

if ($myrow['round_of_amount'] != 0) {
    label_row(trans("Round Off"), price_format($myrow['round_of_amount']), "colspan=7 align=right", "nowrap align=right");
}

label_row(trans("TOTAL INVOICE"), $display_total, "colspan=7 align=right",
	"nowrap align=right");
if ($myrow['prep_amount'])
	label_row(trans("PREPAYMENT AMOUNT INVOICED"), '<b>'.price_format($myrow['prep_amount']).'</b>', "colspan=7 align=right",
		"nowrap align=right");
end_table(1);

$voided = is_voided_display(ST_SALESINVOICE, $trans_id, trans("This invoice has been voided."));

if (!$voided)
{
	display_allocations_to(PT_CUSTOMER, $myrow['debtor_no'], ST_SALESINVOICE, $trans_id, $myrow['Total']);
}

end_page(true, false, false, ST_SALESINVOICE, $trans_id);

