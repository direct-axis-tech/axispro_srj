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
$page_security = 'SA_GLTRANSVIEW';
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");

include($path_to_root . "/includes/db_pager.inc");

include_once($path_to_root . "/admin/db/fiscalyears_db.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/data_checks.inc");

include_once($path_to_root . "/gl/includes/gl_db.inc");

$js = '';
set_focus('account');
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(800, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();

page(trans($help_context = "General Ledger Inquiry"), false, false, '', $js);

//----------------------------------------------------------------------------------------------------
// Ajax updates
//
if (get_post('Show')) 
{
	$Ajax->activate('trans_tbl');
}

if (isset($_GET["account"]))
	$_POST["account"] = $_GET["account"];
if (isset($_GET["TransFromDate"]))
	$_POST["TransFromDate"] = $_GET["TransFromDate"];
if (isset($_GET["TransToDate"]))
	$_POST["TransToDate"] = $_GET["TransToDate"];
if (isset($_GET["Dimension"]))
	$_POST["Dimension"] = $_GET["Dimension"];
if (isset($_GET["Dimension2"]))
	$_POST["Dimension2"] = $_GET["Dimension2"];
if (isset($_GET["amount_min"]))
	$_POST["amount_min"] = $_GET["amount_min"];
if (isset($_GET["amount_max"]))
	$_POST["amount_max"] = $_GET["amount_max"];
if (isset($_GET["person_id"]))
    $_POST['person_id'] = $_GET['person_id'];

if (!isset($_POST["amount_min"]))
	$_POST["amount_min"] = price_format(0);
if (!isset($_POST["amount_max"]))
	$_POST["amount_max"] = price_format(0);

//----------------------------------------------------------------------------------------------------

function gl_inquiry_controls()
{
	$dim = get_company_pref('use_dimension');
    start_form();

    div_start('filter-table'); {
        start_table(TABLESTYLE_NOBORDER); {
            start_row(); {
                gl_all_accounts_list_cells(trans("Account:"), 'account', null, false, false, trans("All Accounts"), true);

                if (is_subledger_account(get_post('account'))) {   
                    subledger_list_cells('Person', 'person_id', get_post('account'), null, '-- all --');
                }

                if (list_updated('account')) {
                    $GLOBALS['Ajax']->activate('filter-table');
                    $GLOBALS['Ajax']->addScript(true, '$(`select[name="account"]`).select2()');
                }
                
                systypes_list_cells(trans("Type"), "filterType", null, false, [], trans("No Type Filter"), ['spec_id' => '']);
                
                date_cells(trans("from:"), 'TransFromDate', '', null);
                date_cells(trans("to:"), 'TransToDate');
            } end_row();
            start_row(); {
                if ($dim >= 1)
                    dimensions_list_cells(trans("Dimension")." 1:", 'Dimension', null, true, " ", false, 1);
                if ($dim > 1)
                    dimensions_list_cells(trans("Dimension")." 2:", 'Dimension2', null, true, " ", false, 2);

                ref_cells(trans("Memo:"), 'Memo', '',null, trans('Enter memo fragment or leave empty'));
                small_amount_cells(trans("Amount min:"), 'amount_min', null, " ");
                small_amount_cells(trans("Amount max:"), 'amount_max', null, " ");
                submit_cells('Show',trans("Show"),'','', 'default');
            } end_row();
        } end_table();

    } div_end();

	
    end_form();
}

//----------------------------------------------------------------------------------------------------

function show_results()
{
	global $path_to_root, $systypes_array;

	if (!isset($_POST["account"]))
		$_POST["account"] = null;

    if (!isset($_POST['person_id'])) {
        $_POST['person_id'] = null;
    }
    if (!isset($_POST['filterType']) || $_POST['filterType'] == -1) {
        $_POST['filterType'] = null;
    }

    // Guess the person type
    $_POST['person_type'] = (!isset($_POST['account']) || !($type = is_subledger_account($_POST["account"])))
        ? null
        : get_subledger_person_type($type);

	$act_name = $_POST["account"] ? get_gl_account_name($_POST["account"]) : "";
	$dim = get_company_pref('use_dimension');

    /*Now get the transactions  */
    if (!isset($_POST['Dimension']))
    	$_POST['Dimension'] = 0;
    if (!isset($_POST['Dimension2']))
    	$_POST['Dimension2'] = 0;
	$result = get_gl_transactions(
        $_POST['TransFromDate'],
        $_POST['TransToDate'], -1,
    	$_POST["account"],  
        $_POST['Dimension'],
        $_POST['Dimension2'],
        $_POST["filterType"],
    	input_num('amount_min'),
        input_num('amount_max'),
        $_POST['person_type'],
        $_POST['person_id'],
        $_POST['Memo'],
        null
    );

	$colspan = ($dim == 2 ? "7" : ($dim == 1 ? "6" : "5"));

	if ($_POST["account"] != null)
		display_heading($_POST["account"]. "&nbsp;&nbsp;&nbsp;".$act_name);

	// Only show balances if an account is specified AND we're not filtering by amounts
	$show_balances = $_POST["account"] != null && 
                     input_num("amount_min") == 0 && 
                     input_num("amount_max") == 0;
		
	start_table(TABLESTYLE);
	
	$first_cols = array(trans("Type"), trans("#"), trans("Reference"), trans("Date"));
	
	if ($_POST["account"] == null)
	    $account_col = array(trans("Account"));
	else
	    $account_col = array();
	
	if ($dim == 2)
		$dim_cols = array(trans("Dimension")." 1", trans("Dimension")." 2");
	elseif ($dim == 1)
		$dim_cols = array(trans("Dimension"));
	else
		$dim_cols = array();
	
	if ($show_balances)
	    $remaining_cols = array(trans("Person/Item"), trans("Debit"), trans("Credit"), trans("Balance"), trans("Memo"), trans("Transaction ID"), "", "");
	else
	    $remaining_cols = array(trans("Person/Item"), trans("Debit"), trans("Credit"), trans("Memo"), trans("Transaction ID"), "", "");
	    
	$th = array_merge($first_cols, $account_col, $dim_cols, $remaining_cols);
			
	table_header($th);
	if ($_POST["account"] != null && is_account_balancesheet($_POST["account"]))
		$begin = "";
	else
	{
		$begin = get_fiscalyear_begin_for_date($_POST['TransFromDate']);
		if (date1_greater_date2($begin, $_POST['TransFromDate']))
			$begin = $_POST['TransFromDate'];
		$begin = add_days($begin, -1);
	}

	$bfw = 0;
	if ($show_balances) {
	    $bfw = get_gl_balance_from_to(
            $begin,
            $_POST['TransFromDate'],
            $_POST["account"],
            $_POST['Dimension'],
            $_POST['Dimension2'],
            $_POST['person_id']
        );
    	start_row("class='inquirybg'");
    	label_cell("<b>".trans("Opening Balance")." - ".$_POST['TransFromDate']."</b>", "colspan=$colspan");
    	display_debit_or_credit_cells($bfw, true);
    	label_cell("");
    	label_cell("");
    	label_cell("");
    	end_row();
	}
	
	$running_total = $bfw;
	$k = 0; //row colour counter

	while ($myrow = db_fetch($result))
	{

    	alt_table_row_color($k);

    	$running_total += $myrow["amount"];

    	$trandate = sql2date($myrow["tran_date"]);

    	label_cell($systypes_array[$myrow["type"]]);
		label_cell(get_gl_view_str($myrow["type"], $myrow["type_no"], $myrow["type_no"], true));
		label_cell(get_trans_view_str($myrow["type"],$myrow["type_no"],$myrow['reference']));
    	label_cell($trandate);
    	
    	if ($_POST["account"] == null)
    	    label_cell($myrow["account"] . ' ' . get_gl_account_name($myrow["account"]));
    	
		if ($dim >= 1)
			label_cell(get_dimension_string($myrow['dimension_id'], true));
		if ($dim > 1)
			label_cell(get_dimension_string($myrow['dimension2_id'], true));
		label_cell($myrow['person_name'] ?: payment_person_name($myrow["person_type_id"],$myrow["person_id"]));
		display_debit_or_credit_cells($myrow["amount"]);
		if ($show_balances)
		    amount_cell($running_total);
		if ($myrow['memo_'] == "")
			$myrow['memo_'] = get_comments_string($myrow['type'], $myrow['type_no']);
    	label_cell($myrow['memo_']);
    	label_cell($myrow['transaction_id']);
        if ($myrow["type"] == ST_JOURNAL)
            echo "<td>" . trans_editor_link( $myrow["type"], $myrow["type_no"]) . "</td>";
        else
            label_cell("");
        
        // We are using white as a substitue for null
        if (empty($myrow['color_code'])) {
            $myrow['color_code'] = '#FFFFFF';
        }
        text_cells(
            "",
            "color_code[{$myrow['counter']}]",
            $myrow['color_code'],
            7,
            7,
            false,
            "",
            "",
            "data-jscolor=\"{value: '{$myrow['color_code']}'}\" data-previous=\"{$myrow['color_code']}\""
        );
    	end_row();
	}
	//end of while loop

	if ($show_balances) {
    	start_row("class='inquirybg'");
    	label_cell("<b>" . trans("Ending Balance") ." - ".$_POST['TransToDate']. "</b>", "colspan=$colspan");
    	display_debit_or_credit_cells($running_total, true);
    	label_cell("");
    	label_cell("");
    	label_cell("");
    	end_row();
	}

	end_table(2);
    $GLOBALS['Ajax']->addScript(true, 'initialiseJSColor();');
	if (db_num_rows($result) == 0)
		display_note(trans("No general ledger transactions have been created for the specified criteria."), 0, 1);

}

//----------------------------------------------------------------------------------------------------

gl_inquiry_controls();

  
/** EXPORT */
$Ajax->activate("PARAM_0");
$Ajax->activate("PARAM_1");
$Ajax->activate("PARAM_2");
$Ajax->activate("PARAM_3");
$Ajax->activate("PARAM_6");
$Ajax->activate("PARAM_7");
$Ajax->activate("PARAM_8");
$Ajax->activate("Memo");
$Ajax->activate("PARAM_10");
$Ajax->activate("PARAM_11");
$Ajax->activate("PARAM_12");
$Ajax->activate("PARAM_13");
$Ajax->activate("SUBLEDGER_CODE");

start_form(false, false, $path_to_root . "/reporting/prn_redirect.php", "export_from");


hidden("REP_ID", "704");
hidden("PARAM_0", $_POST['TransFromDate']);
hidden("PARAM_1", $_POST['TransToDate']);
hidden("PARAM_2", $_POST["account"]);
hidden("PARAM_3", "");
hidden("PARAM_4", $_POST['Dimension']);
hidden("PARAM_6", 0);
hidden("PARAM_7", "");
// hidden("PARAM_8", $_POST["user_id"]);
hidden("Memo", $_POST["Memo"]);
hidden("PARAM_10", input_num('amount_min'));
hidden("PARAM_11", input_num('amount_max'));
hidden("PARAM_12", $_POST['filterType']);
hidden("SUBLEDGER_CODE", $_POST['person_id']);


array_selector_cells('Export to', 'PARAM_13', null, ['PDF', 'Excel']);
submit_cells('EXPORT', trans('Export'), '', 'Export', true);

echo '<hr>';
end_form();



div_start('trans_tbl');

if (get_post('Show') || get_post('account'))
    show_results();

div_end();

//----------------------------------------------------------------------------------------------------

ob_start(); ?>
<script src="<?= $path_to_root ?>/../assets/plugins/general/jscolor/jscolor.js" type="text/javascript"></script>
<script>
    function hexToRgb(hex) {
        var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
        return result ? {
            r: parseInt(result[1], 16),
            g: parseInt(result[2], 16),
            b: parseInt(result[3], 16)
        } : null;
    }

    $(function () {
        jscolor.presets.default = {
            format:'hex',
            position:'right',
            smartPosition: true,
            hideOnPaletteClick: true,
            palette: [
                '#ffffff',
                '#ffed96',
                '#ffcfe9',
                '#c2ffe0',
                '#fcd7b1',
                '#b4ddfa',
                '#8cff32',
                '#fdff32',
                '#ff99a0',
                '#ff99dd',
                '#cf99ff'
            ]
        };

        $(document).on('change', '[data-jscolor]', function() {
            if (this.value == '#FFFFFF') {
                $(this).closest('tr').find('td').each(function(i, el) {
                    el.style.removeProperty('background-color');
                })
            } else {
                var rgb = hexToRgb(this.value);
                var rgba = 'rgba(' + rgb.r + ',' + rgb.g + ',' + rgb.b + ',' + 0.4 + ')';
                $(this).closest('tr').find('td').each(function(i, el) {
                    el.style.setProperty('background-color', rgba, 'important');
                })
            }

            if (this.dataset.previous != this.value) {
                var _this = this;
                var data = {};
                data[this.name] = this.value;

                var error = function() {
                    // something went wrong
                }

                $.ajax({
                    url: route('API_Call', {method: 'updateGLColor'}),
                    method: 'post',
                    data: data,
                    dataType: 'json'
                }).done(function(res) {
                    if (res.status && res.status == 200) {
                        //success
                        _this.dataset.previous = _this.value;
                    } else {
                        error();
                    }
                }).fail(error);
            }
        })
    })

    function initialiseJSColor() {
        setTimeout(function() {
            jscolor.install();
            jscolor.trigger('change');
        }, 1);
    }
</script>
<?php $GLOBALS['__FOOT__'][] = ob_get_clean(); end_page();
