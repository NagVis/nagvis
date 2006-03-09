<?
##########################################################################
##     	                           NagVis                              ##
##         *** Klasse zum einlesen verschiedenener Dateien ***          ##
##                               Lizenz GPL                             ##
##########################################################################
/* DEPRECATED
class readFile_wui 
{	
	// <map>.cfg einlesen (neues Format).
	function readNagVisCfg($file) {
		$NagVisCfg = file('../etc/maps/'.$file.".cfg");
		
		$l="0";
		$x="0";
		$type = array("global","host","service","hostgroup","servicegroup","map","textbox");
		
		while (isset($NagVisCfg[$l]) && $NagVisCfg[$l] != "") {
			if(!ereg("^#",$NagVisCfg[$l]) && !ereg("^;",$NagVisCfg[$l])) {
				$defineCln = explode("{", $NagVisCfg[$l]);
				$define = explode(" ",$defineCln[0]);
				if (isset($define[1]) && in_array(trim($define[1]),$type)) {
					$x++;
					$l++;
					$nagvis[$x]['type'] = $define[1];
					while (trim($NagVisCfg[$l]) != "}") {
						$entry = explode("=",$NagVisCfg[$l], 2);
						if(isset($entry[1])) {
							//if(ereg("name", $entry[0])) {
							//	$entry[0] = "name";
							//}
							$nagvis[$x][trim($entry[0])] = trim($entry[1]);
						}
						$l++;	
					}
				}
			}
			$l++;
		}
		return($nagvis);
	}
}*/


class langFile 
{
	
	var $filepath;
	var $indexes=array();
	var $values=array();
	var $nb=0;

	function langFile($fullpath)
	{
		$filepath=$fullpath;
		
		# we check that the language file specified exists and is readable
		if(!is_readable($filepath))
		{
			print "<script>alert('Impossible to read from the language file ".$filepath."');</script>";
			return;
		}
		
		# we fill the indexes and values arrays, by reading all the file lines
		array_push($this->indexes,"");
		array_push($this->values,"");
		
		$fic = fopen($fullpath,"r");
		while (!feof($fic)) 
		{
		   $myline=fgets($fic, 4096);
		   $myline=substr($myline,0,strlen($myline)-1);
		   if(strlen($myline)>0)
		   {
			
			$temp=explode("~",$myline);
			array_push($this->indexes,$temp[0]);
			array_push($this->values,$temp[1]);
			$this->nb=$this->nb+1;	
		   }
		}
		fclose ($fic);
	}
	
	
	function get_text($myid)
	{
		if (count($this->$indexes)==1)
		{
			print "The language coudln't be loaded";
			return "";
		}
		else
		{
			$key = array_search($myid,$this->indexes); 
			if ($key!==null&&$key!==false) 
			{
				return $this->values[$key];
			}
			else
			{
				print "<script>alert('Impossible to find the index ".$myid." in the language file".$this->$filepath."');</script>";
				return "";
			}
		}
	}
	
	
	function get_text_replace($myid,$myvalues)
	{
		if (count($this->$indexes)==1)
		{
			print "The language coudln't be loaded";
			return "";
		}
		else
		{
			$key = array_search($myid,$this->indexes); 
			if ($key!==null&&$key!==false) 
			{
				$message=$this->values[$key];
				$vars=explode(',', $myvalues);
				for($i=0;$i<count($vars);$i++) 
				{
					$var = explode('=', $vars[$i]);
					$message = str_replace("[".$var[0]."]", $var[1], $message);
				}
				return $message;
			}
			else
			{
				print "<script>alert('Impossible to find the index ".$myid." in the language file".$this->$filepath."');</script>";
				return "";
			}
		}
	}
	
	function get_text_silent($myid)
	{
		if(count($this->indexes) == 1)
		{
			return "";
		}
		else
		{
			// Cause of the new config-format this has to be case insensitive
			$key = $this->array_lsearch($myid,$this->indexes);
			if($key !== null && $key !== false && !is_array($key)) 
			{
				return str_replace("'","\'",$this->values[$key]);
			}
			else
			{
				return "";
			}
		}
	}
	
	function array_lsearch($str,$array) {
		$found = Array();
		
		foreach($array as $k => $v) {
			if(@strtolower($v) == @strtolower($str)) {
				$found[] = $k;
			}
		}
		
		$f = @count($found);
		
		if($f == 0)
			return FALSE;
		elseif($f == 1)
			return $found[0];
		else
			return $found;
	}

}

