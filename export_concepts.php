<?php


date_default_timezone_set('Europe/Paris');
$date = date("Y-m-d_H-i",time());


session_start(); 

if ($_SESSION['server']) $nmf = $_SESSION['server'];
else $nmf = $_GET['nmf'];
//if (!preg_match("/fic$/",$nmf)) $nmf .= ".fic";
//TODO add extension according to concept type

header("Content-Type: application/force-download"); 
header("Content-type: text/plain; charset=ISO-8859-1");
header("Content-Type: application/download"); 
header("Content-Disposition: attachment; filename=\"$date-".$nmf."\""); 

if ($_SESSION['Lficcatcol'] == "Fictions") $content =  "fic0001\r\n";
elseif ($_SESSION['Lficcatcol'] == "Collections") $content =  "col0001\r\n";
elseif ($_SESSION['Lficcatcol'] == "Cat&eacute;gories") $content =  "cat0001\r\n";


foreach ($_SESSION['EFS'] as $k1 => $v1) 
{	
    if ($_SESSION['Lficcatcol'] == "Cat&eacute;gories") 
    {
	foreach($v1 as  $k2 => $v2)
	{
                $content .= "$k1\r\n$k2\r\n";
			foreach($v2 as $v3) $content .=  "$v3\r\n";
		$content .= "END\r\nENDCAT\r\n";
	}

    }else
    {
        $content .= "FICTION\r\n$k1\r\n";

	foreach($v1 as  $k2 => $v2)
	{
		$content .= "$k2\r\n";
			foreach($v2 as $v3) $content .=  "$v3\r\n";
		$content .= "END\r\n";
	}

	$content .= "ENDFICTION\r\n";
    }
}
$content .=  "ENDFILE";
print $content;


?>
