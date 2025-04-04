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

use App\Models\Labour\Contract;
use App\Models\Labour\Labour;
use App\Models\Inventory\StockItem;

$page_security = 'SA_SALESTRANSVIEW';
$path_to_root = "../..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");

include_once($path_to_root . "/sales/includes/sales_ui.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 600);

if ($_GET['trans_type'] == ST_SALESQUOTE)
{
	page(trans($help_context = "View Sales Quotation"), true, false, "", $js);
	display_heading(sprintf(trans("Sales Quotation #%d"),$_GET['trans_no']));
}
else if ($_GET['trans_type'] == Contract::CONTRACT)
{
	page(trans($help_context = "View Tadbeer Contract"), true, false, "", $js);
	display_heading(sprintf(trans("Tadbeer Contract #%d"),$_GET['trans_no']));

	$contract  = Contract::whereType($_GET['trans_type'])->whereContractNo($_GET['trans_no'])->first();
	$_GET['trans_type'] = $contract->order->trans_type;
	$_GET['trans_no'] = $contract->order->order_no;
}
else
{
	page(trans($help_context = "View Sales Order"), true, false, "", $js);
	display_heading(sprintf(trans("Sales Order #%d"),$_GET['trans_no']));
}

if (isset($_SESSION['View']))
{
	unset ($_SESSION['View']);
}

$_SESSION['View'] = new Cart($_GET['trans_type'], $_GET['trans_no']);

start_table(TABLESTYLE2, "width='95%'", 5);

if ($_GET['trans_type'] != ST_SALESQUOTE)
{
	echo "<tr valign=top><td>";
	display_heading2(trans("Order Information"));
	echo "</td><td>";
	display_heading2(trans("Deliveries"));
	echo "</td><td>";
	display_heading2(trans("Invoices/Credits"));
	echo "</td></tr>";
}	

echo "<tr valign=top><td>";

start_table(TABLESTYLE, "width='95%'");
label_row(trans("Customer Name"), $_SESSION['View']->customer_name, "class='tableheader2'",
	"colspan=3");
start_row();
label_cells(trans("Customer Order Ref."), $_SESSION['View']->cust_ref, "class='tableheader2'");
label_cells(trans("Deliver To Branch"), $_SESSION['View']->deliver_to, "class='tableheader2'");
end_row();
start_row();
label_cells(trans("Ordered On"), $_SESSION['View']->document_date, "class='tableheader2'");
if ($_GET['trans_type'] == ST_SALESQUOTE)
	label_cells(trans("Valid until"), $_SESSION['View']->due_date, "class='tableheader2'");
elseif ($_SESSION['View']->reference == "auto")
	label_cells(trans("Due Date"), $_SESSION['View']->due_date, "class='tableheader2'");
else
	label_cells(trans("Requested Delivery"), $_SESSION['View']->due_date, "class='tableheader2'");
end_row();
start_row();
label_cells(trans("Order Currency"), $_SESSION['View']->customer_currency, "class='tableheader2'");
label_cells(trans("Deliver From Location"), $_SESSION['View']->location_name, "class='tableheader2'");
end_row();


if ($_SESSION['View']->payment_terms['days_before_due']<0)
{
start_row();
label_cells(trans("Payment Terms"), $_SESSION['View']->payment_terms['terms'], "class='tableheader2'");
label_cells(trans("Required Pre-Payment"), price_format($_SESSION['View']->prep_amount), "class='tableheader2'");
end_row();
start_row();
label_cells(trans("Non-Invoiced Prepayments"), price_format($_SESSION['View']->alloc), "class='tableheader2'");
label_cells(trans("All Payments Allocated"), price_format($_SESSION['View']->sum_paid), "class='tableheader2'");
end_row();
} else
	label_row(trans("Payment Terms"), $_SESSION['View']->payment_terms['terms'], "class='tableheader2'", "colspan=3");

label_row(trans("Delivery Address"), nl2br($_SESSION['View']->delivery_address),
	"class='tableheader2'", "colspan=3");
label_row(trans("Reference"), $_SESSION['View']->reference, "class='tableheader2'", "colspan=3");
label_row(trans("Telephone"), $_SESSION['View']->phone, "class='tableheader2'", "colspan=3");
label_row(trans("E-mail"), "<a href='mailto:" . $_SESSION['View']->email . "'>" . $_SESSION['View']->email . "</a>",
	"class='tableheader2'", "colspan=3");
label_row(trans("Comments"), nl2br($_SESSION['View']->Comments), "class='tableheader2'", "colspan=3");

if ($_SESSION['View']->isFromLabourContract()) {
	$contract = $_SESSION['View']->contract;

	label_row(trans("Contract Ref"), $contract->reference, "class='tableheader2'", "colspan=3");
	label_row(trans("Package"), $contract->category->description, "class='tableheader2'", "colspan=3");
	label_row(trans("Maid"), $contract->maid->name, "class='tableheader2'", "colspan=3");
	label_row(
		trans("Contract Period"),
		$contract->contract_from->format(dateformat()) . ' to ' . $contract->contract_till->format(dateformat()),
		"class='tableheader2'",
		"colspan=3"
	);
}
end_table();

if ($_GET['trans_type'] != ST_SALESQUOTE)
{
	echo "</td><td valign='top'>";

	start_table(TABLESTYLE);
	display_heading2(trans("Delivery Notes"));


	$th = array(trans("#"), trans("Ref"), trans("Date"), trans("Total"));
	table_header($th);

	$dn_numbers = array();
	$delivery_total = 0;

	if ($result = get_sales_child_documents(ST_SALESORDER, $_GET['trans_no'])) {

		$k = 0;
		while ($del_row = db_fetch($result))
		{

			alt_table_row_color($k);
			$dn_numbers[] = $del_row["trans_no"];
			$this_total = $del_row["ov_freight"]+ $del_row["ov_amount"] + $del_row["ov_freight_tax"]  + $del_row["ov_gst"] ;
			$delivery_total += $this_total;

			label_cell(get_customer_trans_view_str($del_row["type"], $del_row["trans_no"]));
			label_cell($del_row["reference"]);
			label_cell(sql2date($del_row["tran_date"]));
			amount_cell($this_total);
			end_row();
		}
	}

	label_row(null, price_format($delivery_total), " ", "colspan=4 align=right");

	end_table();
	echo "</td><td valign='top'>";

	start_table(TABLESTYLE);
	display_heading2(trans("Sales Invoices"));

	$th = array(trans("#"), trans("Ref"), trans("Date"), trans("Total"));
	table_header($th);
	
	$inv_numbers = array();
	$invoices_total = 0;

	if ($_SESSION['View']->prepaid)
		$result = get_sales_order_invoices($_GET['trans_no']);
	else
		$result = get_sales_child_documents(ST_CUSTDELIVERY, $dn_numbers);

	if ($result) {
		$k = 0;

		while ($inv_row = db_fetch($result))
		{
			alt_table_row_color($k);

			$this_total = $_SESSION['View']->prepaid ? $inv_row["prep_amount"] : 
				$inv_row["ov_freight"] + $inv_row["ov_freight_tax"]  + $inv_row["ov_gst"] + $inv_row["ov_amount"];
			$invoices_total += $this_total;

			$inv_numbers[] = $inv_row["trans_no"];
			label_cell(get_customer_trans_view_str($inv_row["type"], $inv_row["trans_no"]));
			label_cell($inv_row["reference"]);
			label_cell(sql2date($inv_row["tran_date"]));
			amount_cell($this_total);
			end_row();
		}
	}
	label_row(null, price_format($invoices_total), " ", "colspan=4 align=right");

	end_table();

	display_heading2(trans("Credit Notes"));

	start_table(TABLESTYLE);
	$th = array(trans("#"), trans("Ref"), trans("Date"), trans("Total"));
	table_header($th);

	$credits_total = 0;

	if ($result = get_sales_child_documents(ST_SALESINVOICE, $inv_numbers)) {
		$k = 0;

		while ($credits_row = db_fetch($result))
		{

			alt_table_row_color($k);

			$this_total = $credits_row["ov_freight"] + $credits_row["ov_freight_tax"]  + $credits_row["ov_gst"] + $credits_row["ov_amount"];
			$credits_total += $this_total;

			label_cell(get_customer_trans_view_str($credits_row["type"], $credits_row["trans_no"]));
			label_cell($credits_row["reference"]);
			label_cell(sql2date($credits_row["tran_date"]));
			amount_cell(-$this_total);
			end_row();

		}

	}
	label_row(null, "<font color=red>" . price_format(-$credits_total) . "</font>",
		" ", "colspan=4 align=right");

	end_table();

	echo "</td></tr>";

	end_table();
}
echo "<center>";
if ($_SESSION['View']->so_type == 1)
	display_note(trans("This Sales Order is used as a Template."), 0, 0, "class='currentfg'");
display_heading2(trans("Line Details"));

start_table(TABLESTYLE, "width='95%'");
$th = array(trans("Item Code"), trans("Item Description"), trans("Quantity"), trans("Unit"),
	trans("Price"), trans("Discount"), trans("Total"), trans("Quantity Delivered"));
table_header($th);

$k = 0;  //row colour counter

foreach ($_SESSION['View']->line_items as $stock_item) {

	$line_total = round2($stock_item->quantity * $stock_item->price * (1 - $stock_item->discount_percent),
	   user_price_dec());

	alt_table_row_color($k);

	label_cell($stock_item->stock_id);
	label_cell($stock_item->item_description);
	$dec = get_qty_dec($stock_item->stock_id);
	qty_cell($stock_item->quantity, false, $dec);
	label_cell($stock_item->units);
	amount_cell($stock_item->price);
	amount_cell($stock_item->discount_percent * 100);
	amount_cell($line_total);

	qty_cell($stock_item->qty_done, false, $dec);
	end_row();
}

if ($_SESSION['View']->freight_cost != 0.0)
	label_row(trans("Shipping"), price_format($_SESSION['View']->freight_cost),
		"align=right colspan=6", "nowrap align=right", 1);

$sub_tot = $_SESSION['View']->get_items_total() + $_SESSION['View']->freight_cost;

$display_sub_tot = price_format($sub_tot);

label_row(trans("Sub Total"), $display_sub_tot, "align=right colspan=6",
	"nowrap align=right", 1);

$taxes = $_SESSION['View']->get_taxes();

$tax_total = display_edit_tax_items($taxes, 6, $_SESSION['View']->tax_included,2);

$display_total = price_format($sub_tot + $tax_total);

start_row();
label_cells(trans("Amount Total"), $display_total, "colspan=6 align='right'","align='right'");
label_cell('', "colspan=2");
end_row();
end_table();

display_allocations_to(PT_CUSTOMER, $_SESSION['View']->customer_id, $_GET['trans_type'], $_GET['trans_no'], $sub_tot + $tax_total);

if ($_SESSION['View']->isFromLabourContract()) {
	$contract = $_SESSION['View']->contract;

	if(!empty($contract->maidReturn)){
		$maidReturn = $contract->maidReturn;
		display_heading2(trans("Maid Return Transactions"));
		start_table(TABLESTYLE, "width='95%'");
		$th = array(trans("Date"), trans("Item Code"), trans("Item Description"), trans("Maid"), trans("Quantity"));
		table_header($th);

		$item   = StockItem::find($maidReturn->stock_id);

		label_cell(sql2date($maidReturn->tran_date));
		label_cell($maidReturn->stock_id);
		label_cell($item->description);
		label_cell($maidReturn->maid->name);
		label_cell($maidReturn->qty);
		end_row();	

		end_table();
	}

	if(count($contract->maidReplacements) != 0){
		$maidReplacements = $contract->maidReplacements;	
		display_heading2(trans("Maid Replacement Transactions"));
		start_table(TABLESTYLE, "width='95%'");
		$th = array(trans("Date"), trans("Reference"), trans("Old Maid"), trans("New Maid"));
		table_header($th);
		foreach ($maidReplacements as $replace)
		{
			label_cell(sql2date($replace['tran_date']));
			label_cell($replace['reference']);
			label_cell($replace->incomingStockMove->maid->name);
			label_cell($replace->outgoingStockMove->maid->name);
			end_row();		
		}
		end_table();
	}

	if(count($contract->maidMovements) != 0){
		$maidMovements = $contract->maidMovements;	
		display_heading2(trans("Maid Movements"));
		start_table(TABLESTYLE, "width='95%'");
		$th = array(trans("Transaction Type"), trans("Reference"),  trans("Transaction Date"), trans("Maid"), trans("Status"));
		table_header($th);
		foreach ($maidMovements as $move)
		{
			$labour = Labour::find($move['maid_id']);
			label_cell($systypes_array[$move['type']]);
			label_cell($move['reference']);
			label_cell(sql2date($move['tran_date']));
			label_cell($labour->name);
			label_cell($move['qty'] > '0' ? 'Returned' : 'Delivered');
			end_row();	
		}	
		end_table();
	}

	if ($contract->installment()->exists()) {
		$contract->installment->installmentDetails->loadMissing('bank');
		display_heading2(trans("Installment Details"));
		start_table(TABLESTYLE, "width='95%'");
		
		$th = array(
			trans("Cheque Date"),
			trans("Payee Name"),
			trans("Bank"),
			trans("Cheque No"),
			trans("Amount"),
			trans("Invoice Ref")
		);

		table_header($th);
		foreach ($contract->installment->installmentDetails as $ins)
		{
			label_cell(sql2date($ins->due_date));
			label_cell($ins->payee_name);
			label_cell($ins->bank->name);
			label_cell($ins->cheque_no);
			label_cell($ins->amount);
			label_cell($ins->invoice_ref);
			end_row();	
		}
		end_table();
	}

}


end_page(true, false, false, $_GET['trans_type'], $_GET['trans_no']);

