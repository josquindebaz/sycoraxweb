<?php
// Josquin Debaz 14 octobre 2013 / 8 fev 2018 / 26 avril 2018//31 octobre 2018
// 27 nov 2019
//TODO rendre compatible catégories P2
//TODO dico lexicofonctionnels

session_start(); 

//forcer l'iso
mb_internal_encoding("iso-8859-1");
mb_http_output( "iso-8859-1" );
ob_start("mb_output_handler");

include "obj_concepts.php";

$_POST = array_map('stripslashes',$_POST);
$_GET = array_map('stripslashes',$_GET);

if (isset($_GET['save']) AND ($_GET['nmf'])) {
	if (!preg_match("/fic$/",$_GET['nmf'])) $_GET['nmf'] .= ".fic"; 
	$content =  "fic0001\n";
	foreach ($_SESSION['EFS'] as $k1 => $v1) 
	{	
		$content .= "FICTION\n$k1\n";

		foreach($v1 as  $k2 => $v2)
		{
			$content .= "$k2\n";
				foreach($v2 as $v3) $content .=  "$v3\n";
			$content .= "END\n";
		}

		$content .= "ENDFICTION\n";
	}
	$content .=  "ENDFILE";
	$fp = fopen("concepts/".$_GET['nmf'], 'w');
	fwrite($fp,$content);
	fclose($fp); 
}

if ( isset($_GET['import']) AND ($_FILES['localfile']['tmp_name']) ) {
	$local = new parse_concept($_FILES['localfile']['tmp_name']);
	$_SESSION['local'] = serialize($local);
	$_SESSION['localfilename'] = $_FILES['localfile']['name'];
	unset($_SESSION['sel_R_EF'],$_SESSION['sel_R_type'],$_SESSION['sel_R_expr']);
}

if (isset($_SESSION['local'])) $local = unserialize($_SESSION['local']);

if (isset($_GET['server'])) {
	if (in_array($_GET['server'], array('new.fic', 'new.col', 'new.cat')))  {
            unset($_SESSION['EFS'],$_SESSION['server']);
            if ($_GET['server'] == 'new.fic') {
                $_SESSION['Lficcatcol'] = "Fictions";
                $_SESSION['EFS']['MY-FICTION@']["..."] = array("expression");
            } elseif ($_GET['server'] == 'new.col') {
                $_SESSION['Lficcatcol'] = "Collections";
                $_SESSION['EFS']['MY-COLLECTION*']["..."] = array("expression");
            } elseif ($_GET['server'] == 'new.cat') {
                $_SESSION['Lficcatcol'] = "Cat&eacute;gories";
                $_SESSION['EFS']["*ENTITE*"] = array();
                $_SESSION['EFS']["*QUALITE*"] = array();
                $_SESSION['EFS']["*MARQUEUR*"] = array();
                $_SESSION['EFS']["*EPREUVE*"] = array();
            }
	 } else {
		$_SESSION['server'] =  $_GET['server'];
		$server = new parse_concept("concepts/".$_GET['server']);
		$_SESSION['EFS'] = $server->EFS;
                $_SESSION['Lficcatcol'] = $server->ficcatcol;
	}
	unset ($_SESSION['sel_L_EF'],$_SESSION['sel_L_type'],$_SESSION['sel_L_expr']);
}


if (isset($_GET['del'] )) if ( $_GET['del'] == 'reset')  unset ($_SESSION['EFS'], $_SESSION['sel_L_expr'], $_SESSION['sel_L_type'],$_SESSION['sel_L_EF']);

if (! isset($_SESSION['EFS']) ) $_SESSION['EFS'] = array();

function largest_type($ef)
{
	$type = array("",0);
	foreach ($_SESSION['EFS'][$ef] as $k => $v ) if (sizeof($v) > $type[1]) $type = array($k,sizeof($v)) ;
	return $type[0];
}

function cherche_expr($e ){
	foreach ($_SESSION['EFS'] as $ef => $t ) {
		foreach ($t as $type => $expr) {
			if (in_array($e,$expr)) return array($ef,$type);
		}
	}
}

function ajoute_expr($expr) {
	if ( !  cherche_expr($expr)) {
	//ajoute representant s'il n'existe pas encore
		array_push($_SESSION['EFS'][$_SESSION['sel_L_EF']][$_SESSION['sel_L_type']],$expr);
		sort($_SESSION['EFS'][$_SESSION['sel_L_EF']][$_SESSION['sel_L_type']]);
		return 1;
	}
}

function fusionne_type() 
{
	global $local; 

	if (! $_SESSION['sel_L_type']) { 
	//si le type n'est pas selectionne a gauche 

		if ( ! in_array($_SESSION['sel_R_type'], array_keys($_SESSION['EFS'][$_SESSION['sel_L_EF']])) ) { 
		// si le type de droite est nouveau a gauche
			$_SESSION['EFS'][$_SESSION['sel_L_EF']][$_SESSION['sel_R_type']] = array();
		}

		$_SESSION['sel_L_type'] = $_SESSION['sel_R_type'];
	}


	foreach($local->EFS[$_SESSION['sel_R_EF']][$_SESSION['sel_R_type']] as $expr) ajoute_expr($expr);
}


function fusionne_type_EF() 
{
	global $local; 

	if ( ! in_array($_SESSION['sel_R_EF'], array_keys($_SESSION['EFS']))) {
	//si l'EF de droite nouveau a gauche
		$_SESSION['EFS'][$_SESSION['sel_R_EF']] = array($_SESSION['sel_R_type'] => array());
	} 
	$_SESSION['sel_L_EF'] = $_SESSION['sel_R_EF'];
	$_SESSION['sel_L_type'] = $_SESSION['sel_R_type'];

	if ( ! in_array($_SESSION['sel_R_type'], array_keys($_SESSION['EFS'][$_SESSION['sel_L_EF']])) ) { 
	// si le type de droite est nouveau a gauche
		$_SESSION['EFS'][$_SESSION['sel_L_EF']][$_SESSION['sel_R_type']] = array();
	}


	fusionne_type();

}

function fusionne_EF() 
{
	global $local; 

	foreach (array_keys($local->EFS[$_SESSION['sel_R_EF']]) as $type) {
		$_SESSION['sel_R_type'] = $type;		
		fusionne_type_EF();
		ksort($_SESSION['EFS']);
	} 
} 

function delete_expression($e) 
{
	$key = array_search($e,$_SESSION['EFS'][$_SESSION['sel_L_EF']][$_SESSION['sel_L_type']]);
	unset($_SESSION['EFS'][$_SESSION['sel_L_EF']][$_SESSION['sel_L_type']][$key]);
}

function delete_type()
{
	unset($_SESSION['EFS'][$_SESSION['sel_L_EF']][$_SESSION['sel_L_type']]);
	unset($_SESSION['sel_L_type']);
}

function delete_EF()
{
	unset($_SESSION['EFS'][$_SESSION['sel_L_EF']]);
	unset($_SESSION['sel_L_EF']);
	unset($_SESSION['sel_L_type']);
}



if (isset($_GET['diff'] )) if ($_GET['diff'] == "new") {
	unset($_SESSION['sel_R_expr']);
	unset($_SESSION['sel_R_EF']);
	unset($_SESSION['sel_R_type']);
	foreach ( $local->EFS as $EF => $tp ) {
		foreach ($tp as $t => $es) {
			$i = 0;
			foreach ($es as $e) {
				if (cherche_expr($e)) {
					$i += 1;
					//unset($local->EFS[$EF][$t][$e]); n'efface pas
					$k = array_search($e,$local->EFS[$EF][$t]);
					unset($local->EFS[$EF][$t][$k]); 
				}
			}
			if ($i == sizeof($es)) unset($local->EFS[$EF][$t]);	
		}	
		if (! sizeof($local->EFS[$EF])) unset ($local->EFS[$EF]);
	}
	unset($_SESSION['local']);  //il n'ecrase pas pourquoi ?
	$_SESSION['local'] = serialize($local); 
}



if (isset($_GET['fusion'])) if ($_GET['fusion'] == "left" and ($local)) {
	if (! $_SESSION['sel_R_type']) { 
		if ($_SESSION['sel_R_EF']) { 
		//si tout l'EF est selection a droite
			if ($_SESSION['sel_L_EF']) {
			//EF selectionne a gauche
				if ($_SESSION['sel_L_type']) {
				//tester type selectionne a gauche > tout va dans ce type
					foreach ($local->EFS[$_SESSION['sel_R_EF']] as $R_t => $v) {
						$_SESSION['sel_R_type'] = $R_t;
						fusionne_type();
					}
				} else {
				//sinon fusionner type par type
					foreach ($local->EFS[$_SESSION['sel_R_EF']] as $R_t => $v) {
						if (! in_array($R_t,array_keys($_SESSION['EFS'][$_SESSION['sel_L_EF']]))) {
						//creer le nouveau type a gauche
							$_SESSION['EFS'][$_SESSION['sel_L_EF']][$R_t] = array();
						}
						$_SESSION['sel_L_type'] = $R_t;
						foreach($local->EFS[$_SESSION['sel_R_EF']][$R_t] as $expr) ajoute_expr($expr);
					} 
				}
			} else fusionne_EF();
		} else {
		//si pas d'EF selectionne a droite
			if (! $_SESSION['sel_L_EF']) {
			//si pas d'EF selectionne a gauche
				//fusionner l'integralite  des EF
				foreach($local->EFS as $ef => $t) { 
					$_SESSION['sel_R_EF'] = $ef;	
					fusionne_EF(); 
					unset($_SESSION['sel_L_EF']) ;	
					unset($_SESSION['sel_R_EF']) ;	
					unset ($_SESSION['sel_L_type']);
					unset($_SESSION['sel_R_type']);
				}
			} else {
			//si un EF est selectionne a gauche
				if ($_SESSION['sel_L_type']) {
				//si un type est selectionne a gauche, on met tous les representants dedans
					foreach($local->EFS as $ef => $types) foreach($types as $t => $expr) foreach($expr as $e) ajoute_expr($e); 
				} else {
				//sinon on conserve les types
					foreach($local->EFS as $ef => $types) {
						foreach($types as $t => $expr) {
							if (! in_array($t,array_keys($_SESSION['EFS'][$_SESSION['sel_L_EF']]))) {
							//creer le nouveau type a gauche
								$_SESSION['EFS'][$_SESSION['sel_L_EF']][$t] = array();
							}
							$_SESSION['sel_L_type'] = $t;
							foreach($expr as $e) ajoute_expr($e);
						}
					}
				}
			}
		}
	} elseif (! $_SESSION['sel_R_expr']) { 
	// si le type est selectionne a droite mais pas les representant
		if ($_SESSION['sel_L_EF']) {
		//si l'EF est selectionne a gauche
			fusionne_type(); 
		}else { 
		//EF non selectionne a gauche
			fusionne_type_EF();
		}
	}else{
	// si un representant est selectionne a droite
		if ( ! $_SESSION['sel_L_type']) {
		//si pas de type de destination selection a gauche
			print "je le mets ou ?"; 
		} else {
		//si un type est selectionne a gauche
			foreach($_SESSION['sel_R_expr'] as $expr) ajoute_expr($expr);
		}

	} 
}



if (isset($_GET['sel_L_EF'])) { 
	unset ($_SESSION['sel_L_type']);
	unset ($_SESSION['sel_L_expr']);
	if ($_SESSION['sel_L_EF'] == $_GET['sel_L_EF']) {
		unset ($_SESSION['sel_L_EF']);
	} else {
		$_SESSION['sel_L_EF'] = $_GET['sel_L_EF'];
                //preselection of largest, not for CAT
                if ($_SESSION['Lficcatcol'] != "Cat&eacute;gories") {
                    $_SESSION['sel_L_type'] = largest_type($_SESSION['sel_L_EF']);	
                }
	}
}



if (isset($_GET['sel_L_type'])) {
	unset ($_SESSION['sel_L_expr']);
	if ($_SESSION['sel_L_type'] == $_GET['sel_L_type']) unset ($_SESSION['sel_L_type']);
	else $_SESSION['sel_L_type'] = $_GET['sel_L_type'];
}


if (isset($_GET['sel_L_expr'])) {
	//$_GET['sel_L_expr'] = stripslashes($_GET['sel_L_expr']);
	if (	$_SESSION['sel_L_expr'] ) 
		if (in_array($_GET['sel_L_expr'], $_SESSION['sel_L_expr'])) {
			$key=array_search($_GET['sel_L_expr'],$_SESSION['sel_L_expr']); //necessaire parce le tableau est dans $_SESSION
			unset($_SESSION['sel_L_expr'][$key]);
		} else array_push($_SESSION['sel_L_expr'], $_GET['sel_L_expr']);
	else $_SESSION['sel_L_expr'] = array($_GET['sel_L_expr']);
}




if (isset($_GET['sel_R_EF'])) { 
	unset ($_SESSION['sel_R_type']);
	unset ($_SESSION['sel_R_expr']);
	if ($_SESSION['sel_R_EF'] == $_GET['sel_R_EF']) {
		unset ($_SESSION['sel_R_EF']);
	} else {
		$_SESSION['sel_R_EF'] = $_GET['sel_R_EF'];
                //preselection of largest, not for CAT
                if ($local->ficcatcol != "Cat&eacute;gories") {
                    $_SESSION['sel_R_type'] = $local->largest_type($_SESSION['sel_R_EF']);	
                }
	}
}



if (isset($_GET['sel_R_type'])) {
	unset ($_SESSION['sel_R_expr']);
	if ($_SESSION['sel_R_type'] == $_GET['sel_R_type']) unset ($_SESSION['sel_R_type']);
	else $_SESSION['sel_R_type'] = $_GET['sel_R_type'];
}


if (isset($_GET['sel_R_expr'])) {
	$_GET['sel_R_expr'] = stripslashes($_GET['sel_R_expr']);
	if (	$_SESSION['sel_R_expr'] ) 
		if (in_array($_GET['sel_R_expr'], $_SESSION['sel_R_expr'])) {
			$key=array_search($_GET['sel_R_expr'],$_SESSION['sel_R_expr']); //necessaire parce le tableau est dans $_SESSION
			unset($_SESSION['sel_R_expr'][$key]);
		} else array_push($_SESSION['sel_R_expr'], $_GET['sel_R_expr']);
	else $_SESSION['sel_R_expr'] = array($_GET['sel_R_expr']);
}


if (isset($_POST['cherche_expression']))  {
    if ( ! preg_match('/^\s*$/',$_POST['cherche_expression']) )  {
            if (isset($local)) list($_SESSION['sel_R_EF'], $_SESSION['sel_R_type']) = preg_split ("/\|/", $local->index[$_POST['cherche_expression']]); 
            if ($_SESSION['sel_R_EF']) {
                    $_SESSION['sel_R_expr'] = array($_POST['cherche_expression']);
            }
            list($_SESSION['sel_L_EF'],$_SESSION['sel_L_type']) = cherche_expr($_POST['cherche_expression']);
            if ($_SESSION['sel_L_EF']) {
                    $_SESSION['sel_L_expr'] = array($_POST['cherche_expression']);
            }
    }
}

if (isset($_GET['add_expression'])) {

    $AE = $_GET['add_expression'];

    if (( ! preg_match('/^\s*$/', $AE)) AND ($AE != "null") ) {
            $add_expression_fail = 0;
            if ( ! $_SESSION['sel_L_EF'] ) {
                    if (in_array( $AE,array_keys($_SESSION['EFS']))) $add_expression_fail = 1;
                    else {
                            $_SESSION['EFS'][$AE] = array();
                            ksort($_SESSION['EFS']);
                            $_SESSION['sel_L_EF'] = $AE;
                    }
            } elseif ( ! $_SESSION['sel_L_type'] ) {
                    if (in_array( $AE,array_keys($_SESSION['EFS'][$_SESSION['sel_L_EF']]))) $add_expression_fail = 1;
                    else {
                            $_SESSION['EFS'][$_SESSION['sel_L_EF']][$AE] = array();
                            ksort($_SESSION['EFS'][$_SESSION['sel_L_EF']]);
                            $_SESSION['sel_L_type'] = $AE;
                    }
            } else {
                    if ( !  ajoute_expr($AE)) $add_expression_fail = 1; 
            }
    }
}


if (isset($_GET['del'])){
    if ($_GET['del'] == 'left') {
            if ($_SESSION['sel_L_expr']) {
                    foreach($_SESSION['sel_L_expr'] as $e) delete_expression($e);
                    unset($_SESSION['sel_L_expr']);
            } elseif ($_SESSION['sel_L_type']) {
                    delete_type();
            } else {
                    delete_EF();
            }
    }
}

if (isset($_GET['modif']) AND ($_GET['modif'] != "null")) {
	if (sizeof($_SESSION['sel_L_expr']) == 1) {
		if ( ! cherche_expr($_GET['modif']) ) {
			ajoute_expr($_GET['modif']);
			delete_expression($_SESSION['sel_L_expr'][0]);
			$_SESSION['sel_L_expr'] = array($_GET['modif']);
		} 
	} elseif ( ! sizeof($_SESSION['sel_L_expr']) ) {
		if (isset($_SESSION['sel_L_type']) ) {
			if (! in_array( $_GET['modif'],array_keys($_SESSION['EFS'][$_SESSION['sel_L_EF']])) ) {
				$_SESSION['EFS'][$_SESSION['sel_L_EF']][$_GET['modif']] = $_SESSION['EFS'][$_SESSION['sel_L_EF']][$_SESSION['sel_L_type']];
				delete_type();	
				$_SESSION['sel_L_type'] = $_GET['modif'];
			}
		} elseif (sizeof($_SESSION['sel_L_EF']) == 1) {
			if (! in_array($_GET['modif'],array_keys($_SESSION['EFS']))) {
				$_SESSION['EFS'][$_GET['modif']] = $_SESSION['EFS'][$_SESSION['sel_L_EF']];
				delete_EF();
				ksort($_SESSION['EFS']);
				$_SESSION['sel_L_EF'] = $_GET['modif'];
			}
		}
	}
	
}


print "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">
<html  xmlns=\"http://www.w3.org/1999/xhtml\" lang=\"fr\" xml:lang=\"fr\">
<head>
	<title>Interface de partage des concepts</title>
	<meta http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859-1\" /> 
	<link type=\"text/css\" rel=\"stylesheet\" href=\"chelone_concepts.css\" title=\"styles\" />
</head>

<body>
<div id=\"head\">
 <span class=\"headtitle\">&nbsp;SycoraxWeb</span>";
 
if (isset($_SESSION['email'])) print $_SESSION['email'];

print"</div>

<div id=\"subhead\">

<button type=\"button\" onclick=\"var ADD = prompt('add','');window.location='?add=1&add_expression='+ADD\">+</button>
<button type=\"button\" onclick=\"window.location='?del=left'\">-</button>
<button type=\"button\" ";

if (!isset($_SESSION['sel_L_expr'])) $_SESSION['sel_L_expr'] = Null;
if (!isset($_SESSION['sel_L_type'])) $_SESSION['sel_L_type'] = Null;
if (!isset($_SESSION['sel_L_EF'])) $_SESSION['sel_L_EF'] = Null;

if(isset($_SESSION['sel_L_expr'])) {
    if (sizeof($_SESSION['sel_L_expr']) == 1) print " onclick=\"var mod = prompt('modifier','".$_SESSION['sel_L_expr'][0]."');window.location='?modif='+mod\"";
    elseif ( ! sizeof($_SESSION['sel_L_expr']) ) {
        if (sizeof($_SESSION['sel_L_type']) == 1) print " onclick=\"var mod = prompt('modifier','".$_SESSION['sel_L_type']."');window.location='?modif='+mod\"";
        elseif (sizeof($_SESSION['sel_L_EF']) == 1) print " onclick=\"var mod = prompt('modifier','".$_SESSION['sel_L_EF']."');window.location='?modif='+mod\"";
    } else print " disabled"; 
}

print ">M</button>
<button type=\"button\" onclick=\"window.location='?del=reset'\">&empty;</button> 
";


print "
<form method=\"post\">
<select onchange=\"window.location='?server='+this.options[this.selectedIndex].value\">
<option value=\"\">New dictionary</option>
<option>new.fic</option>
<option>new.col</option>
<option>new.cat</option>
</select>
</form>";


print "
<form method=\"post\">
<select onchange=\"window.location='?server='+this.options[this.selectedIndex].value\">
<option value=\"0\">Select dictionary</option>";

$dir = opendir("concepts") or die ("pb ouverture rep concepts");
if ( ! isset($_SESSION['server'])) $_SESSION['server'] = Null; 
while (!(($file = readdir($dir)) === false ) ) {
	if (! is_dir("concepts/$file")) {
		print "<option";
		if ($file == $_SESSION['server']) print " selected=\"selected\" "; 
		print " >$file</option>";
	}
}

print "
</select>
</form>
<button type=\"button\" onclick=\"var nmf = prompt('file name ?','".$_SESSION['server']."');window.location='?save=1&amp;nmf='+nmf\" ";
if (!isset($_SESSION['editeur'])) print " disabled ";

if ( ! isset($_POST['cherche_expression'])) $_POST['cherche_expression'] = Null; 
if ( ! isset( $_SESSION['localfilename'] )) $_SESSION['localfilename'] = Null;

print "> S </button>

<button type=\"button\" onclick=\"";
if (!isset($_SESSION['server'])) {
    if (isset($_SESSION['Lficcatcol'])) { 
        if ($_SESSION['Lficcatcol'] == "Fictions") $extension = ".fic";
        elseif ($_SESSION['Lficcatcol'] == "Collections") $extension = ".col";
        elseif ($_SESSION['Lficcatcol'] == "Cat&eacute;gories") $extension = ".cat";
    } else $extension = "";
    print "var nmf = prompt('file name ?','".$extension."');window.location='export_concepts.php?nmf='+nmf;\"";
} else print "window.location='export_concepts.php'\"";

if (! sizeof($_SESSION['EFS']) ) print " disabled ";
print "> &#8595; </button>


<form method=\"post\" action=\"?cherche=1\">
<input name=\"cherche_expression\" type=\"text\" value=\"".$_POST['cherche_expression']."\" ";
if ($_POST['cherche_expression']){ 
	if ( ( ! $_SESSION['sel_R_EF'] ) AND (! $_SESSION['sel_L_EF']) ) print " class=\"absent\"" ; 
}
print "> <input type=\"submit\" value=\"find\" /></form>
<button type=\"button\" onclick=\"window.location='?fusion=left'\"";

if ( ! isset($local)) print " disabled";

print ">&larr;</button>
<button type=\"button\" onclick=\"window.location='?diff=new'\"";
if ( ! isset($local)) print " disabled";
print " >&notin;</button> 


<form enctype=\"multipart/form-data\" action=\"?import=1\" method=\"post\">
	<span class=\"cartouche\">".$_SESSION['localfilename']."</span> 
	<input type=\"hidden\" name=\"MAX_FILE_SIZE\" value=\"300000\" />
	<input type=\"file\" name=\"localfile\">
        <input type=\"submit\" value=\"&#8593;\" /> 
</form>
</div>

<div id=\"farleft\">
<h3>";

if (isset($_SESSION['Lficcatcol'])) print $_SESSION['Lficcatcol'];
else print "Server";

print " (".sizeof($_SESSION['EFS']).")</h3>
<ul id=\"liste_EF\">
";

$i = 0;
foreach(array_keys($_SESSION['EFS']) as $EF ) {
	if ($_SESSION['sel_L_EF'] == $EF) {
		print " <li id=\"selected_L_EF\" class=\"selected\">";
	} else {
		if  ($i%2) print  " <li class=\"bis\">";
		else print " <li>" ; 
	}
	print "<a href=\"?sel_L_EF=$EF\">";
		if (isset($local)) {
			if (! in_array($EF,array_keys($local->EFS))) print "<span class=\"red\">$EF</span>";
			else print "$EF"; 
		} else print "$EF"; 
	print "</a></li>\n";
	$i += 1;
	if ($_SESSION['sel_L_EF'] == $EF) {
		print "  <ul id=\"liste_types\">\n";
		foreach ($_SESSION['EFS'][$EF] as $type => $expressions)  {
			print  ($_SESSION['sel_L_type'] == $type) ?  "   <li id=\"selected_L_type\"  class=\"selected\">": "   <li>";
			print "<a href=\"?sel_L_type=$type\">";
			 if (isset($local)) {
				if (isset($local->EFS[$_SESSION['sel_L_EF']])) {
					if (! in_array($type,array_keys($local->EFS[$_SESSION['sel_L_EF']]))) print "<span class=\"red\">$type</span>";
					else print "$type";
				} else print "<span class=\"red\">$type</span>"; 
			} else print "$type"; 
			print " (".sizeof($expressions).")</a></li>\n";
		}
		print "  </ul>\n"; 
	}

}

print "
</ul>
<script> document.getElementById('selected_L_EF').scrollIntoView();</script> 
<!-- <script> document.getElementById('selected_L_type').scrollIntoView();</script> -->

</div>

<div id=\"left\">
<h3>Expressions</h3>
";

if ($_SESSION['sel_L_type']) {
	print "<ul id=\"liste_EF\">\n";
	$i = 0;
	foreach ($_SESSION['EFS'][$_SESSION['sel_L_EF']][$_SESSION['sel_L_type']] as $expr){
		if ( ! $_SESSION['sel_L_expr']) $_SESSION['sel_L_expr'] = array(); //evite un message d'erreur sur in_array infra
		if (in_array($expr,$_SESSION['sel_L_expr']) ) print "<li class=\"selected\" >";
		elseif ($i%2) print  " <li class=\"bis\">" ;
		else print "<li>";
		print "<a href=\"?sel_L_expr=$expr\" >";
		 if (isset($local)) {
			if (! $local->index[$expr]) print "<span class=\"red\">$expr</span>";
			else print "$expr";
		}else print "$expr"; 
		print "</a></li>\n";
		$i += 1;
	}
	print "</ul>\n";
}

print "

</div>


<div id=\"right\">
<h3>";

if (isset($local))  print $local->ficcatcol." (".sizeof($local->EFS).")";
else print "Local";

print "</h3>
<ul id=\"liste_EF\">
";
if (isset($local)) {
	$i = 0;
	foreach (array_keys($local->EFS) as $EF) {
                if ($_SESSION['sel_R_EF'] == $EF) print " <li id=\"selected_R_EF\" class=\"selected\">";
		else {
			if ($i%2) print  " <li class=\"bis\">";
			else print " <li>" ; 
		}
		print "<a href=\"?sel_R_EF=$EF\">";
		if (! in_array($EF,array_keys($_SESSION['EFS']))) print "<span class=\"green\">$EF</span>";
		else print "$EF";
		print "</a></li>\n";
		$i += 1;
		if (isset($_SESSION['sel_R_EF'])){
                    if ($_SESSION['sel_R_EF'] == $EF) {
                            print "  <ul id=\"liste_types\">\n";
                            foreach ($local->EFS[$EF] as $type => $expressions)  {
                                    print  ($_SESSION['sel_R_type'] == $type) ?  "   <li  id=\"selected_R_type\" class=\"selected\">": "   <li>";
                                    print "<a href=\"?sel_R_type=$type\">";
                                    if (isset($_SESSION['EFS'][$_SESSION['sel_R_EF']])) {
                                            if (! in_array($type,array_keys($_SESSION['EFS'][$_SESSION['sel_R_EF']]))) print "<span class=\"green\">$type</span>";
                                            else print "$type";
                                    } else print "<span class=\"green\">$type</span>";
                                    print " (".sizeof($expressions).")</a></li>\n";
                            }
                            print "  </ul>\n"; 
                    } 
                }
	}
}
print "
</ul>
<script> document.getElementById('selected_R_EF').scrollIntoView();</script> 
<!-- <script> document.getElementById('selected_R_type').scrollIntoView();</script> -->

</div>


<div id=\"farright\">
";
print (isset($local)) ? "<h3>Expressions</h3>\n" : "<h3>&nbsp;</h3>\n";

if ($_SESSION['sel_R_type']) {
	print "<ul id=\"liste_EF\">\n";
	$i = 0;
	foreach ($local->repr_type($_SESSION['sel_R_EF'],$_SESSION['sel_R_type']) as $expr){
		if ( ! $_SESSION['sel_R_expr']) $_SESSION['sel_R_expr'] = array(); //evite un message d'erreur sur in_array infra
		if (in_array($expr,$_SESSION['sel_R_expr']) ) print "<li class=\"selected\">";
		elseif ($i%2) print  " <li class=\"bis\">" ;
		else print "<li>";
		print "<a href=\"?sel_R_expr=$expr\">";
		if (! cherche_expr($expr)) print "<span class=\"green\">$expr</span>";
		else print "$expr";
		print "</a></li>\n";
		$i += 1;
	}
	print "</ul>\n";
}

print "

</div>
</body>
</html> 
" ;

?>
