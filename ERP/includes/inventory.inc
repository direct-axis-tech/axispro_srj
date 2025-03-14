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
include_once($path_to_root . "/includes/db/inventory_db.inc");


function is_manufactured($mb_flag)
{
	return ($mb_flag == STOCK_TYPE_MANUFACTURED);
}

function is_purchased($mb_flag)
{
	return ($mb_flag == STOCK_TYPE_PURCHASED);
}

function is_service($mb_flag)
{
	return ($mb_flag == STOCK_TYPE_SERVICE);
}

function is_fixed_asset($mb_flag)
{
	return ($mb_flag == STOCK_TYPE_FIXED_ASSET);
}

function has_stock_holding($mb_flag)
{
	return is_purchased($mb_flag) || is_manufactured($mb_flag);
}
