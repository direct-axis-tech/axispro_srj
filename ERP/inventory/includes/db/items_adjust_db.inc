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

function add_stock_adjustment($items, $location, $date_, $reference, $memo_)
{
	global $SysPrefs, $path_to_root, $Refs;

	begin_transaction();
	$args = func_get_args();
	$args = (object)array_combine(array('items', 'location', 'date_', 'reference', 'memo_'), $args);
	$args->trans_no = 0;
	hook_db_prewrite($args, ST_INVADJUST);

	$adj_id = get_next_trans_no(ST_INVADJUST);

	if ($SysPrefs->loc_notification() == 1)
	{
		include_once($path_to_root . "/inventory/includes/inventory_db.inc");
		$st_ids = array();
		$st_names = array();
		$st_num = array();
		$st_reorder = array();
	}
	foreach ($items as $line_item)
	{

		if ($SysPrefs->loc_notification() == 1 && $line_item->quantity < 0)
		{
			$tmp = $line_item->quantity;
			$line_item->quantity = -$line_item->quantity;	// calculate_reorder_level expect positive qty
			$loc = calculate_reorder_level($location, $line_item, $st_ids, $st_names, $st_num, $st_reorder);
			$line_item->quantity = $tmp;
		}

		add_stock_adjustment_item($adj_id, $line_item->stock_id, $location, $date_, $reference,
			$line_item->quantity, $line_item->standard_cost, $memo_, $line_item->dimension_id, $line_item->maid_id);
	}

	add_comments(ST_INVADJUST, $adj_id, $date_, $memo_);

	$Refs->save(ST_INVADJUST, $adj_id, $reference);
	add_audit_trail(ST_INVADJUST, $adj_id, $date_);

	$args->trans_no = $adj_id;
	hook_db_postwrite($args, ST_INVADJUST);
	commit_transaction();
	if ($SysPrefs->loc_notification() == 1 && count($st_ids) > 0)
		send_reorder_email($loc, $st_ids, $st_names, $st_num, $st_reorder);

	return $adj_id;
}

//-------------------------------------------------------------------------------------------------------------

function void_stock_adjustment($type_no, $type=null)
{
	$type = $type ?? ST_INVADJUST;
	hook_db_prevoid($type, $type_no);
	void_gl_trans($type, $type_no);
	void_stock_move($type, $type_no);
}

//-------------------------------------------------------------------------------------------------------------

function get_stock_adjustment_items($trans_no, $type=null)
{
	$result = get_stock_moves($type ?? ST_INVADJUST, $trans_no);

	if (db_num_rows($result) == 0)
	{
		return null;
	}

	return $result;
}

//--------------------------------------------------------------------------------------------------

function add_stock_adjustment_item($adj_id, $stock_id, $location, $date_, $reference,
	$quantity, $standard_cost, $memo_, $dim_id=null, $maid_id=null)
{
	$mb_flag = get_mb_flag($stock_id);

    if (is_service($mb_flag))
    {
    	display_db_error("Cannot do inventory adjustment for Service item : $stock_id", "");
    }

	update_average_material_cost(null, $stock_id, $standard_cost, $quantity, $date_, false, $maid_id);

	if (is_fixed_asset($mb_flag)) {
		$sql = "UPDATE ".TB_PREF."stock_master SET inactive=1
			WHERE stock_id=".db_escape($stock_id);
		db_query($sql,"The inactive flag for the fixed asset could not be updated");
	}

	add_stock_move(ST_INVADJUST, $stock_id, $adj_id, $location,
        $date_, $reference, $quantity, $standard_cost, 0, $maid_id);

	$inv_value = $standard_cost * $quantity;
	$adj_value = $standard_cost * -($quantity);

	if (is_fixed_asset($mb_flag)) {
		// get the initial value of the fixed assset.
		$row = get_fixed_asset_move($stock_id, ST_SUPPRECEIVE);
		$inv_value = $row['price'] * $quantity;
		$adj_value = (-($row['price']) + $standard_cost) * $quantity;
	}

	if ($standard_cost > 0 || is_fixed_asset($mb_flag))
	{


		$stock_gl_codes = get_stock_gl_code($stock_id);

		//dd($stock_id);

//        	    dd($dim_id);

        if(!empty($dim_id))
            $stock_gl_codes['dimension_id'] = $dim_id;

		add_gl_trans_std_cost(
			ST_INVADJUST,
			$adj_id,
			$date_,
			$stock_gl_codes['adjustment_account'],
			$stock_gl_codes['dimension_id'],
			$stock_gl_codes['dimension2_id'],
			$memo_,
			$adj_value,
			null,
			null,
			"",
			0,
			'N/A',
			null,
			null,
			null,
			$maid_id
		);

		add_gl_trans_std_cost(ST_INVADJUST, $adj_id, $date_, $stock_gl_codes['inventory_account'], 0, 0, $memo_, $inv_value);
	}

	if (is_fixed_asset($mb_flag)) {
		// Additional gl entry for fixed asset.
		$grn_act = get_company_pref('default_loss_on_asset_disposal_act');
		add_gl_trans_std_cost(ST_INVADJUST, $adj_id, $date_, $grn_act, 0, 0, $memo_, ($standard_cost * -($quantity)));
	}
}
