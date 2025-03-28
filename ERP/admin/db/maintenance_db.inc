<?php
/**********************************************************************
    Direct Axis Technology L.L.C.
	Released under the terms of the GNU General Public License,
	GPL, as published by the Free Software Foundation, either version 
	3 of the License, or (at your option) any later version.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
    See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
***********************************************************************/

/**
 * @return Returns the array sorted as required
 * @param $aryData Array containing data to sort
 * @param $strIndex name of column to use as an index
 * @param $strSortBy Column to sort the array by
 * @param $strSortType String containing either asc or desc [default to asc]
 * @desc Naturally sorts an array using by the column $strSortBy
 */
define('EXPORT_MAX_INSERT', 50000);

function array_natsort($aryData, $strIndex, $strSortBy, $strSortType=false)
{
   //    if the parameters are invalid
   if (!is_array($aryData) || !$strSortBy)
       //    return the array
       return $aryData;

   //    create our temporary arrays
   $arySort = $aryResult = array();

   //    loop through the array
   foreach ($aryData as $key => $aryRow)
       //    set up the value in the array
       $arySort[$strIndex ? $aryRow[$strIndex] : $key] = $aryRow[$strSortBy];

   //    apply the natural sort
   natsort($arySort);

   //    if the sort type is descending
   if ($strSortType=="desc")
       //    reverse the array
       arsort($arySort);

   //    loop through the sorted and original data
		foreach ($arySort as $arySortKey => $arySorted)
			if($strIndex) 
			{
				foreach ($aryData as $aryOriginal)
				// if the key matches
					if ($aryOriginal[$strIndex]==$arySortKey)
						// add it to the output array
						array_push($aryResult, $aryOriginal);
			} else
				$aryResult[$arySortKey] = $aryData[$arySortKey];
   //    return the return
   return $aryResult;
}

function update_admin_password($conn, $password)
{
	$sql = "UPDATE ".$conn['tbpref']."users SET password=".db_escape($password) . "
		WHERE user_id='admin'";
	db_query($sql, "could not update user password for 'admin'");
}

function write_config_db($new = false)
{
	global $path_to_root, $def_coy, $db_connections, $tb_pref_counter;

	if ($new)
		$tb_pref_counter++;
	$msg = "<?php\n\n";
	$msg .= "/*Connection Information for the database\n";
	$msg .= "\$def_coy - the default company that is pre-selected on login\n\n";
	$msg .= "'host' - the computer ip address or name where the database is. The default is 'localhost' assuming that the web server is also the sql server.\n\n";
	$msg .= "'port' - the computer port where the database is. The default is '3306'. Set empty for default.\n\n";
	$msg .= "'dbuser' - the user name under which the company database should be accessed.\n";
	$msg .= "  NB it is not secure to use root as the dbuser with no password - a user with appropriate privileges must be set up.\n\n";
	$msg .= "'dbpassword' - the password required for the dbuser to authorise the above database user.\n\n";
	$msg .= "'dbname' - the name of the database as defined in the RDMS being used. Typically RDMS allow many databases to be maintained under the same server.\n";
	$msg .= "'collation' - the character set used for the database.\n";
	$msg .= "'tbpref' - prefix on table names, or '' if not used. Always use non-empty prefixes if multiply company use the same database.\n";
	$msg .= "*/\n\n\n";

	$msg .= "\$def_coy = " . $def_coy . ";\n\n";
	$msg .= "\$tb_pref_counter = " . $tb_pref_counter . ";\n\n";
	$msg .= "\$db_connections = " .var_export($db_connections, true);
	$msg .= ";\n";

	$filename = $path_to_root . "/config_db.php";
	// Check if the file exists and is writable first.
	if ((!file_exists($filename) && is_writable($path_to_root)) || is_writable($filename))
	{
		if (!$zp = fopen($filename, 'w'))
		{
			return -1;
		}
		else
		{
			if (!fwrite($zp, $msg))
			{
				fclose($zp);
				return -2;
			}
			// Close file
			fclose($zp);
			cache_invalidate($filename);
		}
	}
	else
	{
		return -3;
	}
	return 0;
}

function write_extensions($extensions=null, $company = -1)
{
	global $path_to_root, $installed_extensions, $next_extension_id;

	if (!isset($extensions)) {
		$extensions = $installed_extensions;
	}
	if (!isset($next_extension_id)) {
		$next_extension_id = 1;
	}

	$msg = "<?php\n\n";
	if ($company == -1)
		$msg .=
"/* List of installed additional extensions. If extensions are added to the list manually
	make sure they have unique and so far never used extension_ids as a keys,
	and \$next_extension_id is also updated. More about format of this file yo will find in 
	FA extension system documentation.
*/
\n\$next_extension_id = $next_extension_id; // unique id for next installed extension\n\n";
	else 
		$msg .=
"/*
	Do not edit this file manually. This copy of global file is overwritten
	by extensions editor.
*/\n\n";

	$msg .= "\$installed_extensions = ". var_export($extensions, true);
	$msg .= ";\n";
	$filename = $path_to_root . ($company==-1 ? '' : '/company/'.$company)
		.'/installed_extensions.php';

	// Check if the file is writable first.
	if (!$zp = @fopen($filename, 'w'))
	{
		display_error(sprintf(trans("Cannot open the extension setup file '%s' for writing."),
			 $filename));
		return false;
	}
	else
	{
		if (!fwrite($zp, $msg))
		{
			display_error(sprintf(trans("Cannot write to the extensions setup file '%s'."),
				$filename));
			fclose($zp);
			return false;
		}
		// Close file
		fclose($zp);
		cache_invalidate($filename);
	}
	return true;
}
//---------------------------------------------------------------------------------------------
//
// Update per-company list of installed extensions
//
function update_extensions($extensions) {
	global $db_connections;
	
	if (!write_extensions($extensions)) {
		display_notification(trans("Cannot update system extensions list."));
		return false;
	}

	// update per company files
 	$cnt = max(1, count_array($db_connections));
	for($i = 0; $i < $cnt; $i++) 
	{
		$newexts = $extensions;
		// update 'active' status 
		$exts = get_company_extensions($i);
		foreach ($exts as $key => $ext) 
		{
			if (isset($newexts[$key]))
				$newexts[$key]['active'] = $exts[$key]['active'];
		}
		if(!write_extensions($newexts, $i)) 
		{
			display_notification(sprintf(trans("Cannot update extensions list for company '%s'."),
				$db_connections[$i]['name']));
		 return false;
		}
	}
	return true;
}


function write_lang()
{
	global $path_to_root, $installed_languages, $dflt_lang;

	$installed_languages = array_natsort($installed_languages, 'code', 'code');
	$msg = "<?php\n\n";

	$msg .= "/* How to make new entries here for non-packaged languages:\n\n";
	$msg .= "-- 'code' should match the name of the directory for the language under \\lang\n.";
	$msg .= "-- 'name' is the name that will be displayed in the language selection list (in Users and Display Setup)\n";
	$msg .= "-- 'rtl' only needs to be set for right-to-left languages like Arabic and Hebrew\n";
	$msg .= "-- 'encoding' used in translation file\n";
	$msg .= "-- 'version' always set to '' for manually installed languages.\n";
	$msg .= "-- 'path' installation path related to FA root (e.g. 'lang/en_US').\n";
	$msg .= "*/\n\n\n";

	$msg .= "\$installed_languages = " . var_export($installed_languages, true);
	$msg .= ";\n";
	$msg .= "\n\$dflt_lang = '$dflt_lang';\n";

	$path = $path_to_root . "/lang";
	$filename = $path.'/installed_languages.inc';
	// Check if directory exists and is writable first.
	if (file_exists($path) && is_writable($path))
	{
		if (!$zp = fopen($filename, 'w'))
		{
			display_error(trans("Cannot open the languages file - ") . $filename);
			return false;
		}
		else
		{
			if (!fwrite($zp, $msg))
			{
				display_error(trans("Cannot write to the language file - ") . $filename);
				fclose($zp);
				return false;
			}
			// Close file
			fclose($zp);
			cache_invalidate($filename);
		}
	}
	else
	{
		display_error(trans("The language files folder ") . $path . trans(" is not writable. Change its permissions so it is, then re-run the operation."));
		return false;
	}
	return true;
}
/*
	Database import:
		$filename - sql file name
		$connection - database connection
		$force - ignore duplicate errors
		$init - presume $filename is initialization file with '0_' prefix
		$protect - protect users/roles 
		$return_errors - return errors instead of display them
*/
function db_import($filename, $connection, $force=true, $init=true, $protect=false, $return_errors=false)
{
	global $db, $SysPrefs;

	$trail = $SysPrefs->sql_trail;
	$SysPrefs->sql_trail = false;

	$allowed_commands = array(
		"create"  => 'table_queries', 
		"delimiter" => 'table_queries',
		"alter table" => 'table_queries', 
		"insert" => 'data_queries', 
		"update" => 'data_queries', 
		"set names" => 'set_names',
		"drop table if exists" => 'drop_queries',
		"drop function if exists" => 'drop_queries',
		"drop trigger if exists" => 'drop_queries',
		"select" => 'data_queries', 
		"delete" => 'data_queries',
		"drop view if exists" => 'drop_queries',
		"create view as" => 'data_queries'		//we should be able to create views after all tables have been created 
		);

	$protected = array(
		'security_roles',
		'users'
	);

	$ignored_mysql_errors = array( //errors ignored in normal (non forced) mode
		'1022',	// duplicate key
		'1050', // Table %s already exists
		'1060', // duplicate column name
		'1061', // duplicate key name
		'1062', // duplicate key entry
		'1091'  // can't drop key/column check if exists
	);

	$set_names = array();
	$data_queries = array();
	$drop_queries = array();
	$table_queries = array();
	$sql_errors = array();

	$old_encoding = db_get_charset($db);

	ini_set("max_execution_time", max("180", ini_get("max_execution_time")));
	db_query("SET foreign_key_checks=0");
	db_query("SET sql_mode=''");

	if (isset($connection['collation']))
		db_set_collation($db, $connection['collation']);

	$check_line_len = false;

	// uncompress gziped backup files
	if (strpos($filename, ".gz") || strpos($filename, ".GZ"))
	{	$lines = db_ungzip("lines", $filename);
		$check_line_len = true;
	} elseif (strpos($filename, ".zip") || strpos($filename, ".ZIP"))
		$lines = db_unzip("lines", $filename);
	else
		$lines = file("". $filename);

	// parse input file
	$query_table = '';
	$delimiter = ';';

	foreach($lines as $line_no => $line)
	{
		$gzfile_bug = $check_line_len && (strlen($line) == 8190); // there is a bug in php (at least 4.1.1-5.5.9) gzfile which limits line length to 8190 bytes!

		$line = trim($line);
		if ($init)
			$line = str_replace("0_", $connection["tbpref"], $line);

		if ($query_table == '') 
		{	// check if line begins with one of allowed queries
		 	foreach($allowed_commands as $cmd => $table) 
			{
				if (strtolower(substr($line, 0, strlen($cmd))) == $cmd) 
				{
					if ($cmd == 'delimiter') {
						$delimiter = trim(substr($line, 10));
						continue 2;
					}
					$query_table = $table;
					$skip = false;
					if ($protect)
					{
						foreach($protected as $protbl)
							if (strpos($line, $connection["tbpref"].$protbl) !== false)
							{
								$skip = true; break;
							}
					}
					if (!$skip)
						${$query_table}[] = array('', $line_no+1);
					break;
				}
		 	}
		 }
		 if($query_table != '')  // inside allowed query
		 {
		 	$table = $query_table;
			if (!$gzfile_bug && substr($line, -strlen($delimiter)) == $delimiter) // end of query found 
			{
				$line = substr($line, 0, strlen($line) - strlen($delimiter)); // strip delimiter
				$query_table = '';
			}
			if (!$skip)
				${$table}[count(${$table}) - 1][0] .= $line . "\n";
		}

	}

	//
	// 'set names' or equivalents should be used only on post 2.3 FA versions
	// otherwise text encoding can be broken during import
	//
	$encoding = null; // UI encoding for default site language is the default
	$new_db = $init || db_fixed();
	$new_file = count($set_names);
	if ($new_db)
	{
		if ($new_file)
		{
			if (count($set_names)) // standard db restore
			{
				if (preg_match('/set\s*names\s*[\']?(\w*)[\']?/i', $set_names[0][0], $match))
					$encoding = $match[1];
			}
			// otherwise use default site ui encoding
		}
	}
	else
	{
		if ($new_file) // import on old db is forbidden: this would destroy db content unless latin1 was used before in UI
		{
			$msg = trans("This is new format backup file which cannot be restored on database not migrated to utf8.");
			if ($return_errors)
				return $msg;
			else
				display_error($msg);
			return false;
		}
		 else	// backup restore during upgrade failure
			$encoding = 'latin1'; // standard encoding on mysql client
	}

	db_set_charset($db, $encoding);

/*/	{ 	// for debugging purposes
	global $path_to_root;
	$f = fopen($path_to_root.'/tmp/dbimport.txt', 'w+');
	fwrite($f, print_r($set_names,true) ."\n");
	fwrite($f, print_r($drop_queries,true) ."\n");
	fwrite($f, print_r($table_queries,true) ."\n");
	fwrite($f, print_r($data_queries,true));
	fclose($f);
	}
/*/
	if ($return_errors)
	{	// prevent errors display
		$save_debug = $SysPrefs->go_debug;
		$SysPrefs->go_debug = 0;
	}
	// execute drop tables if exists queries
	if (is_array($drop_queries))
	{
		foreach($drop_queries as $drop_query)
		{
			if (!db_query($drop_query[0]))
			{
				if (!in_array(db_error_no(), $ignored_mysql_errors) || !$force)
					$sql_errors[] = array(db_error_msg($db), $drop_query[1]);
			}
		}
	}

	// execute create tables queries
	if (is_array($table_queries))
	{
		foreach($table_queries as $table_query)
		{
			if (!db_query($table_query[0]))
			{	
				if (!in_array(db_error_no(), $ignored_mysql_errors) || !$force) {
					$sql_errors[] = array(db_error_msg($db), $table_query[1]);
				}
			}
		}
	}

	// execute insert data queries
	if (is_array($data_queries))
	{
		foreach($data_queries as $data_query)
		{
			if (!db_query($data_query[0]))
			{
				if (!in_array(db_error_no(),$ignored_mysql_errors) || !$force)
					$sql_errors[] = array(db_error_msg($db), $data_query[1]);
			}
		}
	}

	if ($return_errors)
		$SysPrefs->go_debug = $save_debug;

	$SysPrefs->sql_trail = $trail;

	db_query("SET foreign_key_checks=1");
	if ($delimiter != ';') db_query("delimiter ;"); // just for any case

	db_set_charset($db, $old_encoding); // restore connection encoding

	if (count($sql_errors)) {
		if ($return_errors)
			return $sql_errors;

		// display first failure message; the rest are probably derivative 
		$err = $sql_errors[0];
		display_error(sprintf(trans("SQL script execution failed in line %d: %s"),
			$err[1], $err[0]));
		return false;
	} else
		return true;
}

// returns the content of the gziped $path backup file. use of $mode see below
function db_ungzip($mode, $path)
{
    $file_data = gzfile($path);
    // returns one string or an array of lines
    if ($mode != "lines")
    	return implode("",$file_data);
    else
    	return $file_data;
}

// returns the content of the ziped $path backup file. use of $mode see below
function db_unzip($mode, $path)
{
    $all = false;
    $all = implode("", file($path));

    // convert path to name of ziped file
    $filename = preg_replace("/.*\//", "", $path);
    $filename = substr($filename, 0, strlen($filename) - 4);

    // compare filname in zip and filename from $_GET
    if (substr($all, 30, strlen($filename)-4) . substr($all, 30+strlen($filename)+9, 4)
	  != $filename) {
		return '';     // exit if names differ
    }
    else
    {
    	// get the suffix of the filename in hex
		$crc_bugfix = substr($all, 30, strlen($filename)+13);
        $crc_bugfix = substr(substr($crc_bugfix, 0, strlen($crc_bugfix) - 4), 
				strlen($crc_bugfix) - 12 - 4);
        $suffix = false;
        // convert hex to ascii
        for ($i=0; $i < 12; )
        	$suffix .= chr($crc_bugfix[$i++] . $crc_bugfix[$i++] . $crc_bugfix[$i++]);

        // remove central directory information (we have always just one ziped file)
        $comp = substr($all, -(strlen($all) - 30 - strlen($filename)-13));
        $comp = substr($comp, 0, (strlen($comp) - 80 - strlen($filename)-13));

        // fix the crc bugfix (see function save_to_file)
        $comp = "x�" . $comp . $suffix;
        $file_data = gzuncompress($comp);
    }

    // returns one string or an array of lines
    if ($mode != "lines")
    	return $file_data;
    else
    	return explode("\n", $file_data);
}

function db_backup($conn, $ext='no', $comm='', $path='')
{
	if ($conn['tbpref'] != "")
		$filename = $conn['dbname'] . "_" . $conn['tbpref'] . date("Ymd_Hi") . ".sql";
	else
		$filename = $conn['dbname'] . "_" . date("Ymd_Hi") . ".sql";

	return db_export($conn, $path . clean_file_name($filename), $ext, $comm);
}
// Generates a dump of $db database
//
function db_export($conn, $filename, $zip='no', $comment='')
{

	global $SysPrefs, $version;

    $error = false;
    // set max string size before writing to file
    $max_size = 1048576 * 2; // 2 MB
    // changes max size if value can be retrieved
    if (ini_get("memory_limit"))
    	$max_size = 1048576 * (int)ini_get("memory_limit");
    // set backupfile name
    if ($zip == "gzip")
    	$backupfile = $filename . ".gz";
    elseif ($zip == "zip")
    	$backupfile = $filename . ".zip";
    else
    	$backupfile = $filename;
    $company = $conn['name']; // get_company_pref('coy_name');

	if (file_exists($backupfile))	// prevent appends
		unlink($backupfile);

    //create comment
    $out="# MySQL dump of database '".$conn["dbname"]."' on host '".$conn["host"]."'\n";
    $out.="# Backup Date and Time: ".date("Y-m-d H:i")."\n";
    $out.="# Built by " . $SysPrefs->app_title . " " . $version ."\n";
    $out.="# ".$SysPrefs->power_url."\n";
    $out.="# Company: ". @html_entity_decode($company, ENT_QUOTES, $_SESSION['language']->encoding)."\n";
    $out.="# User: ".$_SESSION["wa_current_user"]->name."\n\n";
    $out.="# Compatibility: ".get_company_pref('version_id')."\n\n";

	// write users comment
	if ($comment)
	{
		$out .= "# Comment:\n";
		$comment=preg_replace("'\n'","\n# ","# ".$comment);
		//$comment=str_replace("\n", "\n# ", $comment);
		foreach(explode("\n",$comment) as $line)
			$out .= $line."\n";
		$out.="\n";
	}

	if (db_fixed())
	{
		db_set_encoding();
		if ($mysql_enc = get_mysql_encoding_name($_SESSION['language']->encoding))
			$out .= "\nSET NAMES $mysql_enc;\n";
	}
    // get auto_increment values and names of all tables
    $res = db_query("show table status");
    $all_tables = array();
    while($row = db_fetch($res))
    {
//		if (($conn["tbpref"] == "" && !preg_match('/[0-9]+_/', $row['Name'])) ||
//			($conn["tbpref"] != "" && strpos($row['Name'], $conn["tbpref"]) === 0))
    		$all_tables[] = $row;
    }

//    display_error(print_r($all_tables,true)); die;

        // get table structures
    $view_sql = [];
	foreach ($all_tables as $table)
	{
		$res1 = db_query("SHOW CREATE TABLE `" . $table['Name'] . "`");
		$tmp = db_fetch($res1);

		if($table['Comment'] == 'VIEW') {
            $view_sql[$table['Name']] = $tmp["Create View"];
        }
        else {
            $table_sql[$table['Name']] = $tmp["Create Table"];
        }
	}



	// find foreign keys
	$fks = array();
	if (isset($table_sql))
	{
        array_merge($table_sql,$view_sql);
		foreach($table_sql as $tablenme=>$table)
		{
			$tmp_table=$table;
			// save all tables, needed for creating this table in $fks
			while (($ref_pos = strpos($tmp_table, " REFERENCES ")) > 0)
			{
				$tmp_table = substr($tmp_table, $ref_pos + 12);
				$ref_pos = strpos($tmp_table, "(");
				$fks[$tablenme][] = substr($tmp_table, 0, $ref_pos);
			}
		}
	}
	// order $all_tables
	$all_tables = order_sql_tables($all_tables, $fks);

	// as long as no error occurred
	if (!$error)
	{


	    $tables_array = [];
	    $views_array = [];



        foreach ($all_tables as $table)
        {

            if($table['Comment'] == 'VIEW') {
                $views_array[] = $table;
            }
            else {
                $tables_array[] = $table;
            }
        }


//        array_merge($tables_array,$views_array);

//        display_error(count($views_array)); die;

//        display_error("<pre>".print_r($tables_array+$views_array,true)."</pre>"); die;

        $all_tables = $tables_array;

		foreach ($all_tables as $row)
		{
			if (!empty($row['Engine'])) {	//don't export views like tables
				$tablename = $row['Name'];
				$auto_incr[$tablename] = $row['Auto_increment'];
	
				$out.="\n\n";
				// export tables
				$out.="### Structure of table `".$tablename."` ###\n\n";
	
				$out.="DROP TABLE IF EXISTS `".$tablename."`;\n\n";
				$out.=$table_sql[$tablename];
	
				$out.=" ;";
				$out.="\n\n";
	
				// export data
				if (!$error)
				{
					$out.="### Data of table `".$tablename."` ###\n";
	
					// check if field types are NULL or NOT NULL
					$res3 = db_query("SHOW COLUMNS FROM `" . $tablename . "`");
	
					$field_null = array();
					for ($j = 0; $j < db_num_rows($res3); $j++)
					{
						$row3 = db_fetch($res3);
						$field_null[] = $row3[2]=='YES' && $row3[4]===null;
					}
	
					$res2 = db_query("SELECT * FROM `" . $tablename . "`");
					$maxinsert = 0;
					$insert = '';
					for ($j = 0; $j < db_num_rows($res2); $j++)
					{
						$row2 = db_fetch_row($res2);
						$values = '(';
						for ($k = 0; $k < $nf = db_num_fields($res2); $k++)
						{
							$values .= db_escape($row2[$k], $field_null[$k]);
							if ($k < ($nf - 1))
								$values .= ', ';
						}
						$values .= ')';
						$len = strlen($values);
						if ($maxinsert < $len+1)
						{
							$maxinsert = EXPORT_MAX_INSERT;
							if ($insert)
							{
								$out .= $insert .';'; // flush insert query
								$insert = '';
							}
						}
	
						if ($insert == '')
						{
							$insert = "\nINSERT INTO `" . $tablename . "` VALUES\n";
							$maxinsert -= strlen($insert);
						} else {
							$insert .= ",\n";
						}
	
						$maxinsert -= $len;
						$insert .= $values;
	
						// if saving is successful, then empty $out, else set error flag
						if (strlen($out) > $max_size && $zip != "zip")
						{
							if (save_to_file($backupfile, $zip, $out))
								$out = "";
							else
								$error = true;
						}
					}
					if ($insert)
						$out .= $insert. ';';
				// an error occurred! Try to delete file and return error status
				}
				elseif ($error)
				{
					@unlink($backupfile);
					return false;
				}
	
				// if saving is successful, then empty $out, else set error flag
				if (strlen($out) > $max_size && $zip != "zip")
				{
					if (save_to_file($backupfile, $zip, $out))
						$out= "";
					else
						$error = true;
				}
			}	//end if Engine set (not a view)
		}
		//process views last so all tables have been created before views are created
		foreach ($all_tables as $row)
		{
			if (empty($row['Engine'])) {
				$viewname = $row['Name'];
				$out .= "\n\nDROP VIEW IF EXISTS `".$viewname."`;\n";
				//db_get_view_schema() is in /includes/db/connect_db_mysqli.inc because it is mysql specific
				if (NULL !== ($schema = db_get_view_schema($viewname)))
				{
					$out .= "CREATE VIEW `".$viewname."` AS ".$schema.";\n\n";
					if (!save_to_file($backupfile, $zip, $out)) $error = true;
					else $out = "";
				}
				else $error = true;
				
				if ($error) {
					@unlink($backupfile);
					return false;
				}
			}
		}


        $all_tables = $views_array;

        foreach ($all_tables as $row)
        {
            $tablename = $row['Name'];
            $auto_incr[$tablename] = $row['Auto_increment'];

            $out.="\n\n";
            // export tables
            $out.="### Structure of table `".$tablename."` ###\n\n";


            if($row['Comment'] != 'VIEW') {
                $out.="DROP TABLE IF EXISTS `".$tablename."`;\n\n";
                $out.=$table_sql[$tablename];
            }
            else {
                $out.="DROP VIEW IF EXISTS `".$tablename."`;\n\n";
                $out.=$view_sql[$tablename];
            }


            $out.=" ;";
            $out.="\n\n";

            // export data
            if (!$error && ($row['Comment'] != 'VIEW'))
            {
                $out.="### Data of table `".$tablename."` ###\n";

                // check if field types are NULL or NOT NULL
                $res3 = db_query("SHOW COLUMNS FROM `" . $tablename . "`");

                $field_null = array();
                for ($j = 0; $j < db_num_rows($res3); $j++)
                {
                    $row3 = db_fetch($res3);
                    $field_null[] = $row3[2]=='YES' && $row3[4]===null;
                }

                $res2 = db_query("SELECT * FROM `" . $tablename . "`");
                $maxinsert = 0;
                $insert = '';
                for ($j = 0; $j < db_num_rows($res2); $j++)
                {
                    $row2 = db_fetch_row($res2);
                    $values = '(';
                    for ($k = 0; $k < $nf = db_num_fields($res2); $k++)
                    {
                        $values .= db_escape($row2[$k], $field_null[$k]);
                        if ($k < ($nf - 1))
                            $values .= ', ';
                    }
                    $values .= ')';
                    $len = strlen($values);
                    if ($maxinsert < $len+1)
                    {
                        $maxinsert = EXPORT_MAX_INSERT;
                        if ($insert)
                        {
                            $out .= $insert .';'; // flush insert query
                            $insert = '';
                        }
                    }

                    if ($insert == '')
                    {
                        $insert = "\nINSERT INTO `" . $tablename . "` VALUES\n";
                        $maxinsert -= strlen($insert);
                    } else {
                        $insert .= ",\n";
                    }

                    $maxinsert -= $len;
                    $insert .= $values;

                    // if saving is successful, then empty $out, else set error flag
                    if (strlen($out) > $max_size && $zip != "zip")
                    {
                        if (save_to_file($backupfile, $zip, $out))
                            $out = "";
                        else
                            $error = true;
                    }
                }
                if ($insert)
                    $out .= $insert. ';';
                // an error occurred! Try to delete file and return error status
            }
            elseif ($error)
            {
                @unlink($backupfile);
                return false;
            }

            // if saving is successful, then empty $out, else set error flag
            if (strlen($out) > $max_size && $zip != "zip")
            {
                if (save_to_file($backupfile, $zip, $out))
                    $out= "";
                else
                    $error = true;
            }
        }

	// an error occurred! Try to delete file and return error status
	}
	else
	{
		@unlink($backupfile);
		return false;
	}

	if (save_to_file($backupfile, $zip, $out))
	{
		$out = "";
	}
	else
	{
		@unlink($backupfile);
		return false;
	}
    return $backupfile;
}

// orders the tables in $tables according to the constraints in $fks
// $fks musst be filled like this: $fks[tablename][0]=needed_table1; $fks[tablename][1]=needed_table2; ...
function order_sql_tables($tables, $fks)
{
	// do not order if no contraints exist
	if (!count($fks))
		return $tables;

	// order
	$new_tables = array();
	$existing = array();
	$modified = true;
	while (count($tables) && $modified == true)
	{
	 	$modified = false;
	    foreach ($tables as $key=>$row)
	    {
	        // delete from $tables and add to $new_tables
	        if (isset($fks[$row['Name']]))
	        {
	        	foreach($fks[$row['Name']] as $needed)
	        	{
	            	// go to next table if not all needed tables exist in $existing
	                if (!in_array($needed,$existing))
	                	continue 2;
	            }
	        }
            // delete from $tables and add to $new_tables
	        $existing[] = $row['Name'];
			$new_tables[] = $row;
            prev($tables);
            unset($tables[$key]);
            $modified = true;

	    }
	}

	if (count($tables))
	{
	    // probably there are 'circles' in the constraints, bacause of that no proper backups can be created yet
	    // TODO: this will be fixed sometime later through using 'alter table' commands to add the constraints after generating the tables
	    // until now, just add the lasting tables to $new_tables, return them and print a warning
	    foreach($tables as $row)
	    	$new_tables[] = $row;
	    echo "<div class=\"red_left\">THIS DATABASE SEEMS TO CONTAIN 'RING CONSTRAINTS'. WA DOES NOT SUPPORT THEM. PROBABLY THE FOLOWING BACKUP IS DEFECT!</div>";
	}
	return $new_tables;
}

// saves the string in $fileData to the file $backupfile as gz file or not ($zip)
// returns backup file name if name has changed (zip), else TRUE. If saving failed, return value is FALSE
function save_to_file($path, $zip, $fileData)
{

	$backupfile = basename($path);

    if ($zip == "gzip")
    {
        if ($zp = @gzopen($path, "a9"))
        {
			@gzwrite($zp, $fileData);
			@gzclose($zp);
			return true;
        }
        else
        {
        	return false;
        }

    // $zip contains the timestamp
    }
    elseif ($zip == "zip")
    {
        // based on zip.lib.php 2.2 from phpMyBackupAdmin
        // offical zip format: http://www.pkware.com/appnote.txt

        // End of central directory record
        $eof_ctrl_dir = "\x50\x4b\x05\x06\x00\x00\x00\x00";

        // "local file header" segment
        $unc_len = strlen($fileData);
        $crc = crc32($fileData);
        $zdata = gzcompress($fileData);

		// extend stored file name with suffix
        // needed for decoding (because of crc bug)
        $name_suffix = substr($zdata, -4, 4);
        $name_suffix2 = "_";
        for ($i = 0; $i < 4; $i++)
        	$name_suffix2 .= sprintf("%03d", ord($name_suffix[$i]));

      	$name = substr($backupfile, 0, strlen($backupfile) - 8) . $name_suffix2 . ".sql";

        // fix crc bug
        $zdata = substr(substr($zdata, 0, strlen($zdata) - 4), 2);
        $c_len = strlen($zdata);

        // dos time
        $timearray = getdate();
        $dostime = (($timearray['year'] - 1980) << 25) | ($timearray['mon'] << 21) | ($timearray['mday'] << 16) |
            ($timearray['hours'] << 11) | ($timearray['minutes'] << 5) | ($timearray['seconds'] >> 1);
        $dtime = dechex($dostime);
        $hexdtime = "\x" . $dtime[6] . $dtime[7] . "\x" . $dtime[4].$dtime[5] . "\x" . $dtime[2] . $dtime[3] . "\x" . $dtime[0] . $dtime[1];
        eval('$hexdtime="' . $hexdtime . '";');

        // ver needed to extract, gen purpose bit flag, compression method, last mod time and date
        $sub1 = "\x14\x00" . "\x00\x00" . "\x08\x00" . $hexdtime;

        // crc32, compressed filesize, uncompressed filesize
        $sub2 = pack('V', $crc) . pack('V', $c_len) . pack('V', $unc_len);

        $fr = "\x50\x4b\x03\x04" . $sub1. $sub2;

        // length of filename, extra field length
        $fr .= pack('v', strlen($name)) . pack('v', 0);
        $fr .= $name;

        // "file data" segment and "data descriptor" segment (optional but necessary if archive is not served as file)
        $fr .= $zdata . $sub2;

        // now add to central directory record
        $cdrec = "\x50\x4b\x01\x02";
        $cdrec .= "\x00\x00";                // version made by
        $cdrec .= $sub1 . $sub2;

         // length of filename, extra field length, file comment length, disk number start, internal file attributes, external file attributes - 'archive' bit set, offset
        $cdrec .= pack('v', strlen($name)) . pack('v', 0) . pack('v', 0) . pack('v', 0) . pack('v', 0) . pack('V', 32) . pack('V',0);
        $cdrec .= $name;

        // combine data
        $fileData = $fr . $cdrec . $eof_ctrl_dir;

        // total # of entries "on this disk", total # of entries overall, size of central dir, offset to start of central dir, .zip file comment length
        $fileData .= pack('v', 1) . pack('v', 1) . pack('V', strlen($cdrec)) . pack('V', strlen($fr)) . "\x00\x00";

        if ($zp = @fopen($path, "w"))
        {
			@fwrite($zp, $fileData);
			@fclose($zp);
			return true;
        }
        else
        {
        	return false;
        }

	// uncompressed
    }
    else
    {
        if ($zp = @fopen($path, "a"))
        {
			@fwrite($zp, $fileData);
			@fclose($zp);
			return true;
        }
        else
        {
        	return false;
        }
    }
}

function create_comp_dirs($comp_path, $comp_subdirs)
{
		$index = "<?php\nheader(\"Location: ../index.php\");\n";
	    $cdir = $comp_path;
	    @mkdir($cdir);
		$f = @fopen("$cdir/index.php", "wb");
		@fwrite($f, $index);
		@fclose($f);

	    foreach($comp_subdirs as $dir)
	    {
			@mkdir($cdir.'/'.$dir);
			$f = @fopen("$cdir/$dir/index.php", "wb");
			@fwrite($f, $index);
			@fclose($f);
	    }
}

//
//	Checks $field existence in $table with given field $properties
//	$table - table name without prefix
//  $field -  optional field name
//  $properties - optional properties of field defined by MySQL:
//		'Type', 'Null', 'Key', 'Default', 'Extra'
//
function check_table($pref, $table, $field=null, $properties=null)
{
	$tables = @db_query("SHOW TABLES LIKE '".$pref.$table."'");
	if (!db_num_rows($tables))
		return 1;		// no such table or error

	$fields = @db_query("SHOW COLUMNS FROM ".$pref.$table);
	if (!isset($field)) 
		return 0;		// table exists

	while( $row = db_fetch_assoc($fields)) 
	{
		if ($row['Field'] == $field) 
		{
			if (!isset($properties)) 
				return 0;
			foreach($properties as $property => $value) 
			{
				if ($row[$property] != $value) 
					return 3;	// failed type/length check
			}
			return 0; // property check ok.
		}
	}
	return 2; // field not found
}

/*
	Update or create setting in simple php config file.
*/
function update_config_var($file, $variable, $value, $comment='')
{
	if (!is_file($file) || !is_writeable($file))
		return false;
	$content = file_get_contents($file);
	$strvalue = '$'."$variable = ".var_export($value, true).';';
	$pattern = '/'.preg_quote('$'.$variable).'\s*=\s*[^;]*;/m';
	$content = preg_replace($pattern, $strvalue, $content, -1, $result);
	if (!$result)
	{
		$strvalue = ($comment ? "// $comment" : '') ."\n$strvalue\n";
		$content = preg_replace('/\?>\s*/m', $strvalue, $content, -1, $result);
		if (!$result)
			$content .= $strvalue;
	}

	return file_put_contents($file, $content)!=false;
}


