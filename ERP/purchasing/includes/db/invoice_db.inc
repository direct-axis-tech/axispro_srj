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

use App\Exceptions\BusinessLogicException;
use App\Models\Inventory\StockCategory;
use App\Models\Inventory\StockItem;

include_once($path_to_root . "/purchasing/includes/db/invoice_items_db.inc");
include_once($path_to_root . "/admin/db/attachments_db.inc");

//--------------------------------------------------------------------------------------------------

function read_supplier_details_to_trans(&$supp_trans, $supplier_id)
{
	$sql = "SELECT supp.supp_name, terms.terms, terms.days_before_due, supp.dimension_id, supp.dimension2_id,
    	terms.day_in_following_month, supp.tax_included,
    	supp.tax_group_id, tax_group.name AS tax_group_name,
		supp.credit_limit - Sum((ov_amount + ov_gst + ov_discount)) as cur_credit, curr_code
		FROM ".TB_PREF."suppliers supp LEFT JOIN "
			.TB_PREF."supp_trans trans ON supp.supplier_id = trans.supplier_id, "
			.TB_PREF."payment_terms terms, "
			.TB_PREF."tax_groups tax_group
		WHERE supp.tax_group_id = tax_group.id
		AND supp.payment_terms=terms.terms_indicator
		AND supp.supplier_id = ".db_escape($supplier_id)." GROUP BY supp.supp_name";

$result = db_query($sql, "The supplier record selected: " . $supplier_id . " cannot be retrieved");

$myrow = db_fetch($result);

if ($supp_trans->tran_date == "")
{
	$supp_trans->tran_date = Today();

	if (!is_date_in_fiscalyear($supp_trans->tran_date))
		$supp_trans->tran_date = end_fiscalyear();
	}

	$supp_trans->supplier_id = $supplier_id;
	$supp_trans->tax_included = $myrow['tax_included'];
	$supp_trans->supplier_name = $myrow['supp_name'];
	$supp_trans->terms = array( 
		'description' => $myrow['terms'],
		'days_before_due' => $myrow['days_before_due'], 
		'day_in_following_month' => $myrow['day_in_following_month']
	);

	if ($supp_trans->supplier_id != $supplier_id)
	    get_duedate_from_terms($supp_trans);

    $supp_trans->supplier_id = $supplier_id;
	$supp_trans->credit = $myrow['cur_credit'];

	$supp_trans->tax_description = $myrow['tax_group_name'];
	$supp_trans->tax_group_id = $myrow['tax_group_id'];
	// $supp_trans->dimension = $myrow['dimension_id'];
	$supp_trans->dimension2 = $myrow['dimension2_id'];
	$supp_trans->currency = $myrow['curr_code'];

}

//-------------------------------------------------------------------------------------------------
//	Updates invoiced quantity in PO and GRN line, and act_price in PO
//  Returns:
// if chg_price is set:	 previous act_price, delivery date and unit_price from PO
//
function update_supp_received_items_for_invoice($id, $po_detail_item, $qty_invoiced, $chg_price=null)
{
	if ($chg_price != null)
	{
		$sql = "SELECT act_price, unit_price, govt_fee FROM ".TB_PREF."purch_order_details WHERE
		po_detail_item = ".db_escape($po_detail_item);
		$result = db_query($sql, "The old actual price of the purchase order line could not be retrieved");
		$row = db_fetch($result);
		$ret = $row['act_price'];

		$unit_price = $row['unit_price']; //Added by Rasmus
		$non_taxable_amt = $row['govt_fee'];

		$sql = "SELECT delivery_date 
		FROM ".TB_PREF."grn_batch grn,"
		.TB_PREF."grn_items line
		WHERE
		grn.id = line.grn_batch_id AND line.id=".db_escape($id);
		$result = db_query($sql, "The old delivery date from the received record cout not be retrieved");
		$row = db_fetch($result);
		$date = $row['delivery_date'];
	}
	else
	{
		$ret = 0;
		$date = "";
		$unit_price = 0; // Added by Rasmus
		$non_taxable_amt = 0;
	}
	$sql = "UPDATE ".TB_PREF."purch_order_details
	SET qty_invoiced = qty_invoiced + ".db_escape($qty_invoiced);

	if ($chg_price != null)
		$sql .= " , act_price = ".db_escape($chg_price);

	$sql .= " WHERE po_detail_item = ".db_escape($po_detail_item);
	db_query($sql, "The quantity invoiced of the purchase order line could not be updated");

	$sql = "UPDATE ".TB_PREF."grn_items
	SET quantity_inv = quantity_inv + ".db_escape($qty_invoiced)."
	WHERE id = ".db_escape($id);
	db_query($sql, "The quantity invoiced off the items received record could not be updated");
	return array($ret, $date, $unit_price, $non_taxable_amt);
}

function get_diff_in_home_currency($supplier, $old_date, $date, $amount1, $amount2)
{
	$dec = user_price_dec();
	price_decimal_format($amount2, $dec);
	$currency = get_supplier_currency($supplier);
	$ex_rate = get_exchange_rate_to_home_currency($currency, $old_date);
	$amount1 = $amount1 / $ex_rate;
	$ex_rate = get_exchange_rate_to_home_currency($currency, $date);
	$amount2 = $amount2 / $ex_rate;
	$diff = $amount2 - $amount1;
	//return round2($diff, $dec);
	return $diff;
}
//----------------------------------------------------------------------------------------

/**
 * Write supplier invoice from the cart objec
 *
 * @param supp_trans $supp_trans
 * @return int
 */
function add_supp_invoice(&$supp_trans)
{
	global $Refs;

	//$company_currency = get_company_currency();
	$trans_no = $supp_trans->trans_no;
	$trans_type = $supp_trans->trans_type;
	$supplier = get_supplier($supp_trans->supplier_id);
    $dim = !empty($supp_trans->dimension) ? $supp_trans->dimension :
        ($supplier['dimension_id'] ? $supplier['dimension_id'] : 0);
    $dim2 = !empty($supp_trans->dimension2) ? $supp_trans->dimension2 :
        ($supplier['dimension2_id'] ? $supplier['dimension2_id'] : 0);

	begin_transaction();
	hook_db_prewrite($supp_trans, $trans_type);
	$tax_total = 0;
	$taxes = $supp_trans->get_taxes($supp_trans->tax_group_id);
	if ($trans_no) {
		$allocs = get_payments_for($trans_no, $trans_type, $supp_trans->supplier_id); // save allocations
		void_transaction($trans_type, $trans_no, $supp_trans->tran_date, trans("Document reentered."));
		$Refs->restore_last($trans_type, $trans_no);
	} else
	$allocs = get_po_prepayments($supp_trans);

	add_new_exchange_rate($supp_trans->currency, $supp_trans->tran_date, $supp_trans->ex_rate);

	foreach ($taxes as $n => $taxitem)
	{
		$taxes[$n]['Value'] =  round2($taxitem['Value'], user_price_dec());
		$tax_total += $taxes[$n]['Value'];
	}

	$invoice_items_total = $supp_trans->get_items_total();

	$item_added_tax = 0;
	if (!$supp_trans->tax_included)
	{
		$taxes = $supp_trans->get_taxes($supp_trans->tax_group_id);
		foreach ($taxes as $n => $taxitem)
			$item_added_tax += isset($taxitem['Override']) ? $taxitem['Override'] : round2($taxitem['Value'], user_price_dec());
	}

	if ($trans_type == ST_SUPPCREDIT)
	{
		// let's negate everything because it's a credit note
		$invoice_items_total = -$invoice_items_total;
		$tax_total = -$tax_total;
		$supp_trans->ov_discount = -$supp_trans->ov_discount; // this isn't used at all...
		$item_added_tax = -$item_added_tax;
	}

	$date_ = $supp_trans->tran_date;
	$ex_rate = get_exchange_rate_from_home_currency($supp_trans->currency, $date_);

	/*First insert the invoice into the supp_trans table*/
	$invoice_id = write_supp_trans($trans_type, 0, $supp_trans->supplier_id, $date_, $supp_trans->due_date,
		$supp_trans->reference, $supp_trans->supp_reference,
		$invoice_items_total, $item_added_tax, $supp_trans->ov_discount, "", 0,
		$supp_trans->tax_included, $supp_trans->dimension);

	if ($trans_no)
		move_trans_attachments($trans_type, $trans_no, $invoice_id);

	$supp_trans->trans_no = $invoice_id;

	$total = 0;

	/* Now the TAX account */
	$taxes = $supp_trans->get_taxes($supp_trans->tax_group_id, 0, false);
	$net_diff = 0;

	foreach ($taxes as $taxitem)
	{
		if ($taxitem['Net'] != 0)
		{
			if (isset($taxitem['Override'])) {
				if ($supp_trans->tax_included) { // if tax included, fix net amount to preserve overall line price
					$net_diff += $taxitem['Override'] - $taxitem['Value'];
					$taxitem['Net'] += $taxitem['Override'] - $taxitem['Value'];
				}
				$taxitem['Value'] = $taxitem['Override'];
			}
			add_trans_tax_details($trans_type, $invoice_id, 
				$taxitem['tax_type_id'], $taxitem['rate'], $supp_trans->tax_included, $taxitem['Value'],
				$taxitem['Net'], $ex_rate, $date_, $supp_trans->supp_reference, TR_INPUT);

			if (isset($taxitem['purchasing_gl_code']))
			{
				if ($trans_type == ST_SUPPCREDIT)
					$taxitem['Value'] = -$taxitem['Value'];

				$total += add_gl_trans_supplier($trans_type, $invoice_id, $date_,
					$taxitem['purchasing_gl_code'], $dim, $dim2, $taxitem['Value'],
					$supp_trans->supplier_id,
					"A general ledger transaction for the tax amount could not be added");
			}
		}
	}
	if ($trans_type == ST_SUPPCREDIT)
		$net_diff = -$net_diff;

	/* Now the AP account */
    $total += add_gl_trans_supplier($trans_type, $invoice_id, $date_, $supplier["payable_account"], $dim, $dim2,
		-($invoice_items_total +  $item_added_tax + $supp_trans->ov_discount),
		$supp_trans->supplier_id,
		"The general ledger transaction for the control total could not be added");

	$to_allocate = ($invoice_items_total + $item_added_tax + $supp_trans->ov_discount);

	foreach ($supp_trans->gl_codes as $entered_gl_code)
	{
	    /*GL Items are straight forward - just do the debit postings to the GL accounts specified -
	    the credit is to creditors control act  done later for the total invoice value + tax*/

	    if ($trans_type == ST_SUPPCREDIT)
	    	$entered_gl_code->amount = -$entered_gl_code->amount;

	    $memo_ = $entered_gl_code->memo_;
	    $total += add_gl_trans_supplier($trans_type, $invoice_id, $date_, $entered_gl_code->gl_code,
	    	$entered_gl_code->gl_dim, $entered_gl_code->gl_dim2, $entered_gl_code->amount, $supp_trans->supplier_id, "", 0, $memo_);
			
		$taxfree_line = get_tax_free_price_for_item(
			null,
			$entered_gl_code->amount,
			$supp_trans->tax_group_id,
			$supp_trans->tax_included,
			null,
			$entered_gl_code->tax_type
		) - $net_diff;
		$net_diff = 0;

		$line_tax = get_full_price_for_item(
			null,
			$entered_gl_code->amount,
			0,
			$supp_trans->tax_included,
			null,
			$entered_gl_code->tax_type
		) - $taxfree_line;
			
	    add_supp_invoice_gl_item($trans_type, $invoice_id, $entered_gl_code->gl_code, $entered_gl_code->amount, $memo_,
	    	$entered_gl_code->gl_dim, $entered_gl_code->gl_dim2,$entered_gl_code->tax_type, $line_tax);
	}

	$commission = 0;
	$commissionIncomeAccount = pref('axispro.supp_comm_income_acc');
	$commissionReceivableAccount = pref('axispro.supp_comm_receivable_acc');
	$shouldPostCommission = !empty($commissionIncomeAccount) && !empty($commissionReceivableAccount);
	$clearing_act = get_company_pref('grn_clearing_act');
	foreach ($supp_trans->grn_items as $line_no => $entered_grn)
	{

		if ($trans_type == ST_SUPPCREDIT)
		{
			$entered_grn->this_quantity_inv = -$entered_grn->this_quantity_inv;
			set_grn_item_credited($entered_grn, $supp_trans->supplier_id, $invoice_id, $date_, $supp_trans);
		}

		// For tax included pricelist the net price is calculated down from tax_included price.
		// To avoid rounding errors we have to operate on line value instead of price
		// Additionally we have to take into account differences in net value
		// due to variations in tax calculations on supplier side. More over there is no direct relation between
		// taxes and sales accounts, so we add net_diff just to first posted net value. This is _ugly_hack_
		// which save us from rewriting whole routine, and works right only for small tax differences.

		$taxfree_line = (
			get_tax_free_price_for_item(
				$entered_grn->item_code,
				$entered_grn->this_quantity_inv * $entered_grn->taxable_amount(), 
				$supp_trans->tax_group_id,
				$supp_trans->tax_included
			)
			+ ($entered_grn->this_quantity_inv * $entered_grn->non_taxable_amount())
		) - $net_diff;

		$net_diff = 0;

		$line_tax = (
			get_full_price_for_item(
				$entered_grn->item_code,
				$entered_grn->this_quantity_inv * $entered_grn->taxable_amount(),
				0,
				$supp_trans->tax_included
			)
			+ ($entered_grn->this_quantity_inv * $entered_grn->non_taxable_amount())
		) - $taxfree_line;

		$stock_gl_code = get_stock_gl_code($entered_grn->item_code);

		$dim = !empty($supp_trans->dimension) ? $supp_trans->dimension :
		($supplier['dimension_id'] ? $supplier['dimension_id'] : $stock_gl_code['dimension_id']);
		$dim2 = !empty($supp_trans->dimension2) ? $supp_trans->dimension2 :
		($supplier['dimension2_id'] ? $supplier['dimension2_id'] : $stock_gl_code['dimension2_id']);
		
		if ($entered_grn->so_line_reference) {
			$ordered_item = get_so_detail_from_line_reference($entered_grn->so_line_reference);
			$deferred_cogs = StockCategory::query()
				->whereCategoryId($ordered_item['category_id'])
				->value('dflt_pending_cogs_act');
	
			if ($ordered_item['costing_method'] == COSTING_METHOD_EXPENSE) {
				if (!$deferred_cogs) {
					$_line_no = $line_no + 1;
					throw new BusinessLogicException(
						"Error: Could not find the deferred COGS account. At Line no {$_line_no},"
						. " The costing method configured for the stock"
						. " {$ordered_item['stock_id']} ordered in the transaction"
						. " {$entered_grn->so_line_reference} is {$GLOBALS['costing_methods'][COSTING_METHOD_EXPENSE]}."
						. " Deferred COGS account is required for this item to be expensed"
					);
				}
	
				$iv_act = $deferred_cogs;
			}
		}

		$_maid_id = null;
		if (!is_inventory_item($entered_grn->item_code)) {
			$_maid_id = $entered_grn->maid_id;
		}

		if ($trans_type == ST_SUPPCREDIT)
		{
			if (!isset($iv_act)) {
				$iv_act = is_inventory_item($entered_grn->item_code)
					? $stock_gl_code["inventory_account"]
					: ($supplier["purchase_account"] ?: $stock_gl_code["cogs_account"]);
			}

			$total += add_gl_trans_supplier(
				$trans_type,
				$invoice_id,
				$date_,
				$iv_act,
				$dim,
				$dim2,
				$taxfree_line,
				$supp_trans->supplier_id,
				"",
				0,
				"",
				$_maid_id
			);
		}
		else
		{
    		// -------------- if price changed since po received.
			if (!isset($iv_act)) {
				$iv_act = is_inventory_item($entered_grn->item_code)
					? ($clearing_act ?: $stock_gl_code["inventory_account"])
					: ($supplier["purchase_account"] ?: $stock_gl_code["cogs_account"]);
			}
			
			$old = update_supp_received_items_for_invoice(
				$entered_grn->id,
				$entered_grn->po_detail_item,
				$entered_grn->this_quantity_inv,
				$entered_grn->chg_price
			);

			// Since the standard cost is always calculated on basis of the po unit_price,
			// this is also the price that should be the base of calculating the price diff.
			// In cases where there is two different po invoices on the same delivery with different unit prices this will not work either

			$old_price = $old[2];
			$old_non_taxable_price = $old[3];

			$old_date = sql2date($old[1]);
			if (!is_inventory_item($entered_grn->item_code))
				$total += add_gl_trans_supplier($trans_type, $invoice_id, $date_, $iv_act,
					$dim, $dim2, $taxfree_line, $supp_trans->supplier_id, "", 0, "", $_maid_id);
			else
			{
				$ex_rate = get_exchange_rate_from_home_currency($supp_trans->currency, $old_date);
				$old_value = (
					($entered_grn->this_quantity_inv * $old_non_taxable_price)
					+ get_tax_free_price_for_item(
						$entered_grn->item_code,
						$entered_grn->this_quantity_inv * ($old_price - $old_non_taxable_price), 
						$supp_trans->tax_group_id,
						$supp_trans->tax_included
					)
				);

				$currency = get_supplier_currency($supp_trans->supplier_id);

				$total += add_gl_trans_supplier(
					$trans_type,
					$invoice_id,
					$date_,
					$iv_act,
					$dim,
					$dim2,
					$old_value,
					$supp_trans->supplier_id,
					"",
					$ex_rate,
					"",
					$_maid_id
				);

				$diff = get_diff_in_home_currency(
					$supp_trans->supplier_id,
					$old_date,
					$date_,
					$old_value, 
					$taxfree_line
				);

				$mat_cost = update_average_material_cost(null, $entered_grn->item_code,
					$diff/$entered_grn->this_quantity_inv, $entered_grn->this_quantity_inv, null, true, $entered_grn->maid_id);

				//Add GL transaction for GRN Provision in case of difference
				if ($diff != 0)
				{
					$total += add_gl_trans($trans_type, $invoice_id, $date_, $stock_gl_code["inventory_account"],
						$dim, $dim2, 'GRN Provision', $diff, null, null, null,
						"The general ledger transaction could not be added for the GRN of the inventory item");

					//If QOH is 0 or negative then update_average_material_cost will be skipped
					//Thus difference in PO and Supp Invoice should be handled separately
					$qoh = get_qoh_on_date($entered_grn->item_code);
					if ($qoh <= 0)
					{
						global $Refs;

						$id = get_next_trans_no(ST_JOURNAL);
						$ref = $Refs->get_next(ST_JOURNAL, null, $date_);
						add_journal(ST_JOURNAL, $id, $diff, $date_, get_company_currency(), $ref);
						$stock_id = $entered_grn->item_code;
						$stock_gl_code = get_stock_gl_code($stock_id);
						$memo = trans("Supplier invoice adjustment for zero inventory of ").$stock_id." ".trans("Invoice")." ".$supp_trans->reference;
						//Reverse the inventory effect if $qoh <=0
						add_gl_trans_std_cost(ST_JOURNAL, $id, $date_, 
							$stock_gl_code["inventory_account"],
							$dim, $dim2, $memo, -$diff);
						//GL Posting to inventory adjustment account
						add_gl_trans_std_cost(
							ST_JOURNAL,
							$id,
							$date_, 
							$stock_gl_code["adjustment_account"],
							$dim,
							$dim2,
							$memo,
							$diff,
							null,
							null,
							"",
							0,
							'N/A',
							null,
							null,
							null,
							$entered_grn->maid_id
						);

						add_audit_trail(ST_JOURNAL, $id, $date_);
						add_comments(ST_JOURNAL, $id, $date_, $memo);
						$Refs->save(ST_JOURNAL, $id, $ref);
					}
				}
			}
			add_or_update_purchase_data($supp_trans->supplier_id, $entered_grn->item_code, $entered_grn->chg_price, "", "", $entered_grn->govt_fee); 
		}

		if ($shouldPostCommission && $entered_grn->supp_commission != 0) {
			$commission += add_gl_trans_supplier(
				$trans_type,
				$invoice_id,
				$date_,
				$commissionIncomeAccount,
				$dim,
				$dim2,
				-($entered_grn->this_quantity_inv * $entered_grn->supp_commission),
				$supp_trans->supplier_id,
				"",
				0,
				"Commission Earned",
				$_maid_id,
				$entered_grn->so_line_reference
			);
		}

		update_purchase_value($entered_grn->item_code, $entered_grn->chg_price * $ex_rate, $date_);
		add_supp_invoice_item(
			$trans_type,
			$invoice_id,
			$entered_grn->item_code,
			$entered_grn->item_description,
			0,
			$entered_grn->chg_price,
			$line_tax/$entered_grn->this_quantity_inv,
			$entered_grn->this_quantity_inv,
			$entered_grn->id,
			$entered_grn->po_detail_item,
			"",
			0,
			0,
			null,
			$entered_grn->maid_id,
			$entered_grn->unit_price,
			$entered_grn->govt_fee,
			$entered_grn->so_line_reference,
			$entered_grn->supp_commission
		);
	} /* end of GRN postings */

	if ($shouldPostCommission && $commission != 0) {
		$commission += add_gl_trans_supplier(
			$trans_type,
			$invoice_id,
			$date_,
			$commissionReceivableAccount,
			$dim,
			$dim2,
			-($commission),
			$supp_trans->supplier_id,
			"",
			0,
			"Commission Receivable",
			$_maid_id,
			$entered_grn->so_line_reference
		);
	}

	/*Post a balance post if $total != 0 */
	add_gl_balance($trans_type, $invoice_id, $date_, -$total, PT_SUPPLIER, $supp_trans->supplier_id); // ??

	add_comments($trans_type, $invoice_id, $date_, $supp_trans->Comments);

	$Refs->save($trans_type, $invoice_id, $supp_trans->reference);

	if (count($supp_trans->src_docs) == 1)
	{
		$invoice_no = key($supp_trans->src_docs);
		$invoice_alloc_balance = get_supp_trans_allocation_balance(ST_SUPPINVOICE, $supp_trans->src_docs);
		if ($invoice_alloc_balance > 0) 
		{ 	//the invoice is not already fully allocated 

			$trans = get_supp_trans($invoice_id, ST_SUPPCREDIT);
			$total = -$trans['Total'];

			$allocate_amount = ($invoice_alloc_balance > $total) ? $total : $invoice_alloc_balance;

			/*Now insert the allocation record if > 0 */
			if ($allocate_amount != 0) 
			{
				$invoice = get_supp_trans($invoice_no, ST_SUPPINVOICE);
				add_supp_allocation($allocate_amount, ST_SUPPCREDIT, $invoice_id, ST_SUPPINVOICE, $invoice_no,
					$supp_trans->supplier_id, $date_, sql2date($invoice['tran_date']));
				update_supp_trans_allocation(ST_SUPPINVOICE, $invoice_no, $supp_trans->supplier_id);
				update_supp_trans_allocation(ST_SUPPCREDIT, $invoice_id, $supp_trans->supplier_id);

				exchange_variation(ST_SUPPCREDIT, $invoice_id, ST_SUPPINVOICE, $supp_trans->src_docs, $date_,
					$allocate_amount, PT_SUPPLIER);
			}
		}
	}

	reallocate_payments($invoice_id, ST_SUPPINVOICE, $date_, $to_allocate, $allocs, $supp_trans->supplier_id);
	$supp_trans->trans_no = $invoice_id;
	hook_db_postwrite($supp_trans, $supp_trans->trans_type);
	commit_transaction();

	return $invoice_id;
}

//----------------------------------------------------------------------------------------

// get all the invoices/credits for a given PO - quite long route to get there !

function get_po_invoices_credits($po_number)
{
	$sql = "SELECT DISTINCT trans.trans_no, trans.type,	ov_amount+ov_discount+ov_gst AS Total,
	trans.tran_date
	FROM ".TB_PREF."supp_trans trans,"
	.TB_PREF."supp_invoice_items line,"
	.TB_PREF."purch_order_details poline,"
	.TB_PREF."purch_orders po
	WHERE line.supp_trans_no = trans.trans_no
	AND line.supp_trans_type = trans.type
	AND line.po_detail_item_id = poline.po_detail_item
	AND po.supplier_id = trans.supplier_id
	AND po.order_no = poline.order_no
	AND poline.order_no = ".db_escape($po_number);

	return db_query($sql, "The invoices/credits for the po $po_number could not be retreived");
}

//----------------------------------------------------------------------------------------
//
// Retrieve tax ovverides from tax register.
//
function get_tax_overrides($trans_type, $trans_no)
{
	$tax_overrides = array();
	$sql = "SELECT amount, tax_type_id as id, rate
	FROM ".TB_PREF."trans_tax_details details
	WHERE trans_type=".db_escape($trans_type)." AND trans_no=".db_escape($trans_no)
		." AND amount>0"; // skip reposting of nondeductible VAT

		$result = db_query($sql, 'Cannot retrieve tax overrides');
		while($row = db_fetch($result))
		{
			$tax_overrides[$row['id']] = $row['amount'];
		}

		return $tax_overrides;
	}
//----------------------------------------------------------------------------------------

/**
 * Populate the supplier transaction to the cart
 *
 * @param int $trans_no
 * @param int $trans_type
 * @param supp_trans $supp_trans
 * @return void
 */
function read_supp_invoice($trans_no, $trans_type, &$supp_trans)
{
	$sql = "SELECT trans.*, supp_name, sup.dimension2_id, curr_code
		FROM ".TB_PREF."supp_trans trans,"
		.TB_PREF."suppliers sup
		WHERE trans_no = ".db_escape($trans_no)." AND type = ".db_escape($trans_type)."
		AND sup.supplier_id=trans.supplier_id";

		$result = db_query($sql, "Cannot retreive a supplier transaction");

		if (db_num_rows($result) == 1)
		{
			$trans_row = db_fetch($result);

			$supp_trans->supplier_id = $trans_row["supplier_id"];
			$supp_trans->supplier_name = $trans_row["supp_name"];
			$supp_trans->tran_date = sql2date($trans_row["tran_date"]);
			$supp_trans->due_date = sql2date($trans_row["due_date"]);
			$supp_trans->Comments = get_comments_string($trans_type, $trans_no);
			$supp_trans->reference = $trans_row["reference"];
			$supp_trans->supp_reference = $trans_row["supp_reference"];
			$supp_trans->ov_amount = $trans_row["ov_amount"];
			$supp_trans->ov_discount = $trans_row["ov_discount"];
			$supp_trans->ov_gst = $trans_row["ov_gst"];
			$supp_trans->tax_included = $trans_row["tax_included"];
		$supp_trans->dimension = $trans_row["dimension_id"];
		$supp_trans->dimension2 = $trans_row["dimension2_id"];
		$supp_trans->currency = $trans_row["curr_code"];

			$id = $trans_row["trans_no"];

			$result = get_supp_invoice_items($trans_type, $id);

			if (db_num_rows($result) > 0)
			{

				while ($details_row = db_fetch($result))
				{

					if ($details_row["gl_code"] == 0)
					{
						$supp_trans->add_grn_to_trans(
							$details_row["grn_item_id"],
							$details_row["po_detail_item_id"],
							$details_row["stock_id"],
							$details_row["description"], 
							$details_row['qty_recd'],
							$details_row['quantity_inv'] - $details_row["quantity"],
							$details_row["quantity"],
							0,
							$details_row["FullUnitPrice"],
							0,
							0,
							$details_row['maid_id'],
							$details_row['_unit_price'],
							$details_row['govt_fee'],
							$details_row['so_line_reference'],
							$details_row['supp_commission']
						);
					}
					else
					{
						$supp_trans->add_gl_codes_to_trans($details_row["gl_code"], get_gl_account_name($details_row["gl_code"]), 
							$details_row["dimension_id"], $details_row["dimension2_id"],
							$details_row["FullUnitPrice"], $details_row["memo_"],$details_row['tax_type']);
					}
				}
				$supp_trans->tax_overrides = get_tax_overrides($trans_type, $trans_no);
			}
			else
			{
				return display_db_error("Invalid supptrans details for supptrans number : $trans_no and type : $trans_type", $sql, true);
			}

		}
		else
		{
			return display_db_error("Invalid supptrans number : $trans_no and type : $trans_type", $sql, true);
		}
	}

//----------------------------------------------------------------------------------------

	function get_matching_invoice_item($stock_id, $po_item_id)
	{
		$sql = "SELECT *, tran_date
		FROM ".TB_PREF."supp_invoice_items,"
		.TB_PREF."supp_trans
		WHERE supp_trans_type = ".ST_SUPPINVOICE." AND stock_id = "
		.db_escape($stock_id)." AND po_detail_item_id = ".db_escape($po_item_id)."
		AND supp_trans_no = trans_no";
		$result = db_query($sql, "Cannot retreive supplier transaction detail records");
		return db_fetch($result);  
	}

function void_supp_invoice($type, $type_no)
{
	begin_transaction();

	hook_db_prevoid($type, $type_no);
	$trans = get_supp_trans($type_no, $type);

	void_bank_trans($type, $type_no, true);

	void_gl_trans($type, $type_no, true);

	void_supp_allocations($type, $type_no);

	void_supp_trans($type, $type_no);

	$result = get_supp_invoice_items($type, $type_no);
	$grn_batches = [];

	// now remove this invoice/credit from any GRNs/POs that it's related to
	if (db_num_rows($result) > 0)
	{
		$date_ = Today();
		while ($details_row = db_fetch($result))
		{
			if ((int)$details_row["grn_item_id"] > 0) // it can be empty for GL items
			{
				// Changed 2008-10-17 by Joe Hunt to get the avg. material cost updated
				$old = update_supp_received_items_for_invoice($details_row["grn_item_id"],
					$details_row["po_detail_item_id"], -$details_row["quantity"], $details_row["FullUnitPrice"]); 
				
				//$diff = $details_row["FullUnitPrice"] - $old[2];
				$old_date = sql2date($old[1]);
				
				$batch = get_grn_batch_from_item($details_row["grn_item_id"]);	
				$grn = get_grn_batch($batch);
				$grn_batches[$grn['id']] = $grn['reference'];
				if ($type == ST_SUPPCREDIT) // credit note 2009-06-14 Joe Hunt Must restore the po and grn
				{	// We must get the corresponding invoice item to check for price chg.
					$match = get_matching_invoice_item($details_row["stock_id"], $details_row["po_detail_item_id"]);
					//Chaitanya : Skipped costing block & handle in void_stock_move
					// We must get the corresponding invoice item to check for price chg.
					/*if ($match !== false)
						$mat_cost = update_average_material_cost($grn["supplier_id"], $details_row["stock_id"],  
							$match["unit_price"], -$details_row["quantity"], sql2date($match['tran_date']), $match['tran_date'] !== $trans['tran_date']);
					else		
						$mat_cost = update_average_material_cost($grn["supplier_id"], $details_row["stock_id"],  
							$details_row["FullUnitPrice"], -$details_row["quantity"], $old_date, $old[1] !== $trans['tran_date']);*/

					before_delete_purch_order_details($details_row["po_detail_item_id"]);
					$sql = "UPDATE ".TB_PREF."purch_order_details
						SET quantity_ordered = quantity_ordered + ".-$details_row["quantity"].", ";
					
					if ($match !== false) {
						$sql .= "act_price=".$match['unit_price'].", ";
					}

					$sql .= "quantity_received = quantity_received + ".-$details_row["quantity"]."
						WHERE po_detail_item = ".$details_row["po_detail_item_id"];
					db_query($sql, "a purchase order details record could not be updated. This receipt of goods has not been processed ");
					
					update_order_line_qty_expensed($details_row['so_line_reference'], -$details_row["quantity"]);

					before_delete_grn_items($details_row["grn_item_id"]);
					
					$sql = "UPDATE ".TB_PREF."grn_items SET qty_recd=qty_recd+".-$details_row["quantity"]."
						WHERE id=".$details_row["grn_item_id"];
					db_query($sql);
				}
				else
				{
					$diff = get_diff_in_home_currency($grn["supplier_id"], $old_date, sql2date($trans['tran_date']), $old[2], 
						$details_row["FullUnitPrice"]);
					// Only adjust the avg for the diff
					$mat_cost = update_average_material_cost(null, $details_row["stock_id"],
						$diff, -$details_row["quantity"], $old_date, true, $details_row['maid_id'] ?? null);
					
					//Chaitanya : Reverse effect
					//If QOH is 0 or negative then update_average_material_cost will be skipped
					//Thus difference in PO and Supp Invoice should be handled separately
					$qoh = get_qoh_on_date($details_row["stock_id"]);
					if ($diff*$details_row["quantity"] !=0 && $qoh <= 0) {
						global $Refs;

						//Chaitanya : Post a journal entry
						$id = get_next_trans_no(ST_JOURNAL);
						$ref = $Refs->get_next(ST_JOURNAL, null, $date_);
						add_journal(ST_JOURNAL, $id, $details_row["quantity"] * $diff, $old_date, get_company_currency(), $ref);
						$stock_id = $details_row["stock_id"];
						$stock_gl_code = get_stock_gl_code($stock_id);
						$memo = "Reversing Supplier invoice adjustment for zero inventory of ".$stock_id." Invoice: ".$trans['reference'];
						//Reverse the inventory effect if $qoh <=0
						add_gl_trans_std_cost(ST_JOURNAL, $id, $old_date, $stock_gl_code["inventory_account"],
							$details_row['dimension_id'], $details_row['dimension2_id'], $memo,
							$details_row["quantity"] * $diff);
							
						//GL Posting to inventory adjustment account
						add_gl_trans_std_cost(
							ST_JOURNAL,
							$id,
							$old_date, 
							$stock_gl_code["adjustment_account"],
							$details_row['dimension_id'],
							$details_row['dimension2_id'],
							$memo,
							-$details_row["quantity"] * $diff,
							null,
							null,
							'',
							0,
							'N/A',
							null,
							null,
							null,
							$details_row['maid_id'] ?? null
						);
						
						add_audit_trail(ST_JOURNAL, $id, $old_date);
						add_comments(ST_JOURNAL, $id, $old_date, $memo);
						$Refs->save(ST_JOURNAL, $id, $ref);
					}
				}
			}
		}
	}

	if ($type == ST_SUPPCREDIT) // void the credits in stock moves
		void_stock_move($type, $type_no); 
	void_supp_invoice_items($type, $type_no);

	if (
		$type == ST_SUPPINVOICE
		&& count($grn_batches) == 1
		&& reset($grn_batches) == 'auto'
	) {
		post_void_supp_trans(ST_SUPPRECEIVE, array_key_first($grn_batches));
	}
		
	void_trans_tax_details($type, $type_no);

	commit_transaction();
}

/**
 * Returns the supplier commission configuration for this item
 *
 * @param string $supplier_id
 * @param string $stock_id
 * @return array
 */
function get_supp_commission_config($supplier_id, $stock_id)
{
	$result = [
		"supplier_id" => $supplier_id,
		"category_id" => data_get(StockItem::find($stock_id), 'category_id'),
		"commission" => 0,
		"comm_calc_method" => CCM_AMOUNT
	];

	$config = db_query(
		"SELECT
			di.supplier_id,
			di.category_id,
			di.commission,
			di.comm_calc_method
		FROM 0_stock_master as sm
		JOIN 0_supplier_discount_items as di ON
			di.category_id = sm.category_id
		WHERE
			sm.stock_id = ".db_escape($stock_id)."
			AND di.supplier_id = ".db_escape($supplier_id),
		"Could not query for supplier commission configuration"
	)->fetch_assoc();

	if ($config) {
		$result = $config;
	}

	return $result;
}

/**
 * Returns the supplier commission for this item
 *
 * @param string $supplier_id
 * @param string $stock_id
 * @param double $price
 * @return array
 */
function get_supp_commission($supplier_id, $stock_id, $price, $config = null)
{
	$result = [
		'supp_commission' => 0
	];

	if (is_null($config)) {
		$config = get_supp_commission_config($supplier_id, $stock_id);
	}

	if (!$config['commission']) {
		return $result;
	}

	$supp_commission = $config['commission'];
	$comm_calc_method = $config['comm_calc_method'] ?: CCM_AMOUNT;
	if ($comm_calc_method == CCM_PERCENTAGE) {
		$supp_commission = round2($supp_commission / 100 * $price, user_price_dec());
	}

	$result['supp_commission'] = $supp_commission;

	return $result;
}

//----------------------------------------------------------------------------------------
function get_gl_account_info($acc)
{
	$sql = "SELECT account_code, account_name FROM ".TB_PREF."chart_master WHERE account_code=".db_escape($acc);
	return db_query($sql,"get account information");
}

function is_reference_already_there($supplier_id, $supp_reference, $trans_no=0)
{
	$sql = "SELECT COUNT(*) FROM ".TB_PREF."supp_trans WHERE supplier_id="
	.db_escape($supplier_id) . " AND supp_reference=" 
	.db_escape($supp_reference) 
		. " AND ov_amount!=0"; // ignore voided invoice references
		if ($trans_no)
			$sql .= " AND trans_no!=$trans_no";
		$result=db_query($sql,"The sql to check for the previous entry of the same invoice failed");

		$myrow = db_fetch_row($result);
		return $myrow[0] > 0;
	}

function remove_not_invoice_item($id)
{
	begin_transaction();

	$myrow = get_grn_item_detail($id);

	$grn = get_grn_batch($myrow['grn_batch_id']);

	$supp = get_supplier($grn["supplier_id"]);

	$sql = "UPDATE ".TB_PREF."purch_order_details
	SET quantity_received = qty_invoiced, quantity_ordered = qty_invoiced WHERE po_detail_item = ".$myrow["po_detail_item"];
	db_query($sql, "The quantity invoiced of the purchase order line could not be updated");

	$sql = "UPDATE ".TB_PREF."grn_items
	SET qty_recd = quantity_inv WHERE id = ".$myrow["id"];
	db_query($sql, "The quantity invoiced off the items received record could not be updated");

	$price = $myrow['unit_price'];
	$non_taxable_amt = $myrow['govt_fee'];
	if ($supp['tax_included']) {
		$price = (
			$non_taxable_amt
			+ get_tax_free_price_for_item(
				$myrow['item_code'],
				$myrow['unit_price'] - $non_taxable_amt,
				$supp['tax_group_id'],
				$supp['tax_included']
			)
		);
	}

	update_average_material_cost($grn["supplier_id"], $myrow["item_code"], $price, -$myrow["QtyOstdg"], Today());       

	update_order_line_qty_expensed($myrow['so_line_reference'], -$myrow["QtyOstdg"]);

	add_stock_move(
		ST_SUPPRECEIVE,
		$myrow["item_code"],
		$myrow['grn_batch_id'],
		$grn['loc_code'],
		sql2date($grn["delivery_date"]),
		"",
		-$myrow["QtyOstdg"],
		$myrow['std_cost_unit'],
		$price,
		null,
		null,
		$myrow['so_line_reference']
	);

	$clearing_act = get_company_pref('grn_clearing_act');
    if ($clearing_act) {    // otherwise GRN clearing account is not used
    	if (is_inventory_item($myrow['item_code']))
    	{
    		$total = 0;
    		$stock_gl_code = get_stock_gl_code($myrow['item_code']);
    		$date = sql2date($grn["delivery_date"]);
    		$total += add_gl_trans_supplier(ST_SUPPRECEIVE, $myrow['grn_batch_id'], $date, $stock_gl_code["inventory_account"],
    			$stock_gl_code['dimension_id'], $stock_gl_code['dimension2_id'],
    			-$myrow['QtyOstdg'] * $price, $grn["supplier_id"], "", 0, trans("GRN Removal"));
    		$total += add_gl_trans_supplier(ST_SUPPRECEIVE, $myrow['grn_batch_id'], $date, $clearing_act,
    			0, 0, -$total, null, "", 0, trans("GRN Removal"));
    	}
    }

    commit_transaction();
}

function find_src_invoices($cart)
{
	$invoices = $po_ids = array();
	foreach($cart->grn_items as $item)
		$po_ids[] = "'$item->po_detail_item'";	// credit item lines

	if (count($po_ids)) {
		$sql = "SELECT DISTINCT trans.trans_no, trans.reference, trans.supp_reference
		FROM ".TB_PREF."supp_invoice_items items
		LEFT JOIN ".TB_PREF."supp_trans trans ON trans.trans_no=items.supp_trans_no AND trans.`type`=items.supp_trans_type
		WHERE items.po_detail_item_id IN (" . implode(',', $po_ids). ")"." AND items.supp_trans_type=20";
		$result = db_query($sql, "cannot find source invoice details");
		while ($rec = db_fetch($result))
		{
			$invoices[$rec['trans_no']] = $rec['supp_reference']; // array($rec['reference'], $rec['supp_reference']);
		}
	}
	return $invoices;
}


function get_tax_type_name($id){

	$sql = "SELECT name FROM ".TB_PREF."item_tax_types where id=".$id;
	$res = db_query($sql);
	$data = db_fetch($res);
	return $data['name'];
}
