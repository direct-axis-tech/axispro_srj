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
	$page_security = 'SA_SETUPDISPLAY'; // A very low access level. The real access level is inside the routines.
	$path_to_root = "..";

	include_once($path_to_root . "/includes/session.inc");
	include_once($path_to_root . "/includes/ui.inc");
	include_once($path_to_root . "/reporting/includes/class.graphic.inc");
	include_once($path_to_root . "/includes/dashboard.inc"); // here are all the dashboard routines.
	
	$app = isset($_GET['sel_app']) ? $_GET['sel_app'] : (isset($_POST['sel_app']) ? $_POST['sel_app'] : "orders");
	$js = "";
	if ($SysPrefs->use_popup_windows)
		$js .= get_js_open_window(800, 500);

	page(trans($help_context = "Dashboard"), false, false, "", $js);
	dashboard($app);
	end_page();
	exit;

