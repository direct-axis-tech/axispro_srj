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

use App\Contracts\Cart;

$messages = array(); // container for system messages
$before_box = ''; // temporary container for output html data before error box

function get_backtrace($html = false, $skip=0)
{
	$str = '';
	if ($html) $str .= '<table border=0>';
	$trace = debug_backtrace();

	foreach($trace as $trn => $tr) {
		if ($trn <= $skip) continue;
		if ($html) $str .= '<tr><td>';
		if (isset($tr['file']) && isset($tr['line']))
			$str .= $tr['file'].':'.$tr['line'].': ';
		if ($html) $str .= '</td><td>';
		if (isset($tr['type'])) {
	 		if($tr['type'] == '::') {
				$str .= $tr['class'].'::';
			} else if($tr['type'] == '->') {
				$str .= '('.$tr['class'].' Object)'.'->';
			}
		}
		$str .= $tr['function'].'(';
		
		if(isset($tr['args']) && is_array($tr['args'])) {
			$args = array();
			foreach($tr['args'] as $n=>$a) {
				if (is_object($tr['args'][$n]))
					$args[$n] = "(".get_class($tr['args'][$n])." Object)";
				elseif (is_array($tr['args'][$n]))
					$args[$n] = "(Array[".count($tr['args'][$n])."])";
				else
					$args[$n] = "'".$tr['args'][$n]."'";
			}
			$str .= implode(',',$args);
		}
		$str .= ')</td>';
	}

	if ($html) $str .= '</tr></table>';
	return $str;
}

//-----------------------------------------------------------------------------
//    Error handler - collects all php/user messages for
//    display in message box.

function error_handler($errno, $errstr, $file, $line) {
    global $messages, $SysPrefs, $cur_error_level;

	// skip well known warnings we don't care about.
	// Please use restrainedly to not risk loss of important messages
	$excluded_warnings = array(
		'html_entity_decode', 				// nevermind encodings, special chars are processed anyway
		'should be compatible with that',	// ignore cpdf/frontreport wrapper warnings
		'mysql extension is deprecated'		// ignore strict warning in 5.4
	);
	foreach($excluded_warnings as $ref) {
		if (strpos($errstr, $ref) !== false) {
			return true;
		}
	}

	$bt = isset($SysPrefs) && $SysPrefs->go_debug>1 ? get_backtrace(true, 1) : array();

	// error_reporting!=cur_error_level when messages are set off with @ 
	if ($cur_error_level == error_reporting()) {
		if ($errno & $cur_error_level) {
			// suppress duplicated errors
			if (!in_array(array($errno, $errstr, $file, $line, @$bt), $messages))
				$messages[] = array($errno, $errstr, $file, $line, @$bt);
		}
		else if ($errno&~E_NOTICE && $errstr != '') { // log all not displayed messages 
			$user = @$_SESSION["wa_current_user"]->loginname;
			$context = isset($SysPrefs) && !$SysPrefs->db_ok ? '[before upgrade]' : '';
			error_log(user_company() . ":$user:". basename($file) .":$line:$context $errstr");
		}
	}
	
    return true;
}

function exception_handler($exception)
{
    if ($exception instanceof \App\Exceptions\AmcExpiredException) {
        echo view('errors.amc-expired')->render();
        exit();
    }

	error_handler(
		E_ERROR,
		sprintf(
			trans("Unhandled exception [%s]: %s."),
			$exception->getCode(),
			$exception->getMessage()
		),
		$exception->getFile(),
		$exception->getLine()
	);

	rollback_if_in_transaction();

	exit();
}
//------------------------------------------------------------------------------
//	Formats system messages before insert them into message <div>
// FIX center is unused now
function fmt_errors($center=false) {
    global $messages, $path_to_root, $SysPrefs;

  	$msg_class = array(
  		E_USER_ERROR => 'err_msg',
  		E_USER_WARNING =>'warn_msg', 
		E_USER_NOTICE => 'note_msg');

  	$type = E_USER_NOTICE;
  	$content = '';

  	if (count($messages)) {
		foreach($messages as $cnt=>$msg) {
			if ($SysPrefs->go_debug && $msg[0]>E_USER_NOTICE)
		 		$msg[0] = E_ERROR;

			if ($msg[0]>$type) continue;

			if ($msg[0]<$type) { 
				if ($msg[0] == E_USER_WARNING) {
					$type = E_USER_WARNING; // user warnings 
					$content = ''; 			// clean notices when we have errors
				} else  {
					$type = E_USER_ERROR; 	// php or user errors 
					if($type == E_USER_WARNING)
						$content = ''; 			// clean other messages
				}
			}

	    	$str = $msg[1];
			if (!in_array($msg[0], array(E_USER_NOTICE, E_USER_ERROR, E_USER_WARNING)) && $msg[2] != null)
		  		$str .= ' '.trans('in file').': '.$msg[2].' '.trans('at line ').$msg[3];

			if ($SysPrefs->go_debug>1 && $type!=E_USER_NOTICE && $type!=E_USER_WARNING)
		  		$str .= '<br>'.$msg[4];
			$content .= ($cnt ? '<hr>' : '').$str;
		}
		$class = $msg_class[$type];
    	$content = "<div class='$class'>$content</div>";
  	} elseif ($path_to_root=='.')
		return '';
  	return $content;
}
//-----------------------------------------------------------------------------
// Error box <div> element.
//
function error_box() {
    global $before_box;
    
    echo "<div id='msgbox'>";

	// Necessary restart instead of get_contents/clean calls due to a bug in php 4.3.2
	$before_box = ob_get_clean(); // save html content before error box 
    ob_start('output_html');
    echo "</div>";
}
/*
	Helper to avoid sparse log notices.
*/
function end_flush() {
	global $Ajax;

    app(AfterMiddleware::class)->run();

	if (isset($Ajax))
		$Ajax->run();

 	 // on some (but not all) php versions zlib extension adds 1 additional level of buffering, 
 	 // so flush the last buffer outside the loop to be on safe side 
 	while(ob_get_level() > 1)
 		ob_end_flush();
	@ob_end_flush();

	// if any transaction was aborted unexpectedly rollback changes
	cancel_transaction();
}

function display_db_error($msg, $sql_statement=null, $exit=true)
{
	global $db, $SysPrefs, $db_connections;

	$warning = $msg==null;
	$db_error = db_error_no();
	
	if($warning)
		$str = "<b>" . trans("Debug mode database warning:") . "</b><br>";
	else
		$str = "<b>" . trans("DATABASE ERROR :") . "</b> $msg<br>";
	
	if ($db_error != 0) 
	{
		$str .= "error code : " . $db_error . "<br>";
		$str .= "error message : " . db_error_msg($db) . "<br>";
	}
	
	if ($SysPrefs->debug == 1) 
	{
		$cur_prefix = $db_connections[$_SESSION["wa_current_user"]->cur_con]['tbpref'];

		$str .= "sql that failed was : ".str_replace(TB_PREF, $cur_prefix, $sql_statement)."<br>";
	}
	
	$str .= "<br><br>";
	if (!$SysPrefs->go_debug)
		error_log($str);
	else {
		if($msg)
			trigger_error($str, E_USER_ERROR);
		else	// $msg can be null here only in debug mode, otherwise the error is ignored
			trigger_error($str, E_USER_WARNING);
	}
	if ($exit)
		exit;
}

function frindly_db_error($db_error)
{
	if ($db_error == DB_DUPLICATE_ERROR) 
	{
		display_notification(trans("The entered information is conflicting. Please process again"));
		return true;
	}
	
	return false;
}

function check_db_error($msg, $sql_statement, $exit_if_error=true, $mysqli_result = null)
{
	global $db, $SysPrefs, $Ajax;
	$db_error = db_error_no();
	
	if ($db_error != 0 || $mysqli_result === false) 
	{
		if ($SysPrefs->go_debug || !frindly_db_error($db_error)) {
			display_db_error($msg, $sql_statement, false);
		}

		rollback_if_in_transaction();
		
		if ($exit_if_error) 
		{
			end_page(); exit;
		}
	}
	return $db_error;
}

function rollback_if_in_transaction()
{
	global $Ajax;

	if (
		isset($GLOBALS['PROCESSING_ITEMS'])
		&& $GLOBALS['PROCESSING_ITEMS'] instanceof Cart
		&& $GLOBALS['PROCESSING_ITEMS']->getBackup()
	) {
		$GLOBALS['PROCESSING_ITEMS']->rollback(null, false);

		$js = (
			"\nsetTimeout(() => {\n"
			. "    jQuery('#_page_body').addClass('inactive-control');\n"
			. "    toastr.error(\n"
			. "        'The system has encountered an unexpected event during its execution,"
					. " Please refresh the page and try again. If it is not working,"
					. " try contacting the administrator',\n"
			. "        'Something went wrong!',\n"
			. "        {timeOut: 0, extendedTimeOut: 0, closeButton: true}\n"
			. "    );\n"
			."});"
		);

		$Ajax->addScript(true, $js);
		unset($GLOBALS['PROCESSING_ITEMS']);
		exit();
	}
	
	if (is_in_transaction()) {
		cancel_transaction();
		exit();
	}
}
