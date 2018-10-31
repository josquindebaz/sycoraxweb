<?php

class parse_concept
{
	#var $filename;
	#var $EFS ;//le tableau des EF
	#var $index ;//l'index associant un représentant à son EF
	#var $dEF ;//tableau nombre de types / nombre de représentants d'un EF

	function parse_concept($f) 
	{
            $o = fopen($f, "r");
            $t = fgets($o);
            $indicP1 = preg_match("/(fic|cat|col)0001/i", $t);
            if (! ($indicP1) ) {
                //parse P2 dic
                $this->dom = new DomDocument();
                if ($this->dom->load($f))
                $racine = $this->dom->documentElement;
                $G = $racine->getElementsByTagName('objet-gestionnaire')->item(0);
                $dicType = utf8_decode($G->getAttribute('type-gestionnaire'));
                if ($dicType == "concept_fiction") $this->ficcatcol = "Fictions"; //etres fictifs
                if ($dicType == "concept_collection") $this->ficcatcol = "Collections"; //collections
                if ($dicType == "concept_entité") $this->ficcatcol = "Cat&eacute;gories";// catégories
                if ($this->ficcatcol == "Cat&eacute;gories")
                {
                    $ef = "";
                    $this->EFS[$ef] = array(); 
                    foreach ($G->childNodes as $C)
                    {
                        if ($C->nodeType == 1)
                        {
                            $type = utf8_decode($C->getAttribute('concept'));
                            //$this->EFS[$ef][$type] = $array; //ajoute le type
                            $this->EFS[$ef][$type] = array(); //ajoute le type
                            foreach ($C->childNodes as $R)
                            {
                                if ($R->nodeType == 1)
                                {
                                    $rep = utf8_decode($R->getAttribute('nom'));
                                    $this->EFS[$ef][$type][] = $rep; //ajoute le représentant
                                }
                            }
                        }
                    }
                }else{
                    foreach ($G->childNodes as $C)
                    {
                        if ($C->nodeType == 1)
                        {
                            $ef = utf8_decode($C->getAttribute('concept'));
                            $this->EFS[$ef] = array(); 
                            
                            if ($this->ficcatcol == "Fictions" or $this->ficcatcol == "Collections")
                            { 
                                foreach ($C->childNodes as $T)
                                {
                                    if ($T->nodeType == 1)
                                    {
                                        $type = utf8_decode($T->getAttribute('concept'));
                                        #$this->EFS[$ef][$type] = $array; //ajoute le type
                                        $this->EFS[$ef][$type] = array(); //ajoute le type
                                        foreach ($T->childNodes as $R)
                                        {
                                            if ($R->nodeType == 1)
                                            {
                                                $rep = utf8_decode($R->getAttribute('nom'));
                                                $this->EFS[$ef][$type][] = $rep; //ajoute le représentant
                                            }
                                        } 
                                    }
                                }
                            }
                        }
                    }
                }
            }else{ 
                //parse P1 dic
#		$this->filename = $f;
		$c = file($f, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES); //transforme le fichier en tableau ligne par ligne sans les lignes vides et les sauts de lignes
		if ($c)
		{
			$ef = 0;
			$type = 0;
                        $cattype = 0;
                        $nomcat = 0;
			foreach($c as $v)
                        {
                                //type de dictionnaire 
				if ($v == "fic0001") $this->ficcatcol = "Fictions"; //etres fictifs
                                elseif ($v == "col0001") $this->ficcatcol = "Collections";// collections
                                elseif ($v == "cat0001") 
                                {
                                    $this->ficcatcol = "Cat&eacute;gories";// catégories, l'accent pose parfois pb pour les IF == 
                                    $this->EFS["*ENTITE*"] = array();
                                    $this->EFS["*QUALITE*"] = array();
                                    $this->EFS["*MARQUEUR*"] = array();
                                    $this->EFS["*EPREUVE*"] = array();
                                }

                                elseif ($v == "FICTION")  $ef = 1; //debute un EF ou une collection
                                elseif ($v == "*ENTITE*"  or $v == "*QUALITE*"  or $v == "*MARQUEUR*" or $v == "*EPREUVE*" ) 
                                {
                                    //debute une categorie
                                    $cattype = $v;
                                }

				else //alimente concept ou ferme
				{
					if ($ef) 
					{
						if ($ef == 1) 
						{       //ouvre le concept
                                                        $ef = $v;
                                                        $n1 = 0; //compteur de types
                                                        $n2 = 0 ; //compteur de représentants
							$this->EFS[$ef] = array(); 
						} elseif ($v == "ENDFICTION") 
						{       //ferme le concept
							$this->dEF[$ef] = array($n1, $n2);
							$ef = 0; 
						} else 
						{
							if  ($type)  
							{
								if ($v == "END") $type = 0; //ferme le type
								else 
								{
									$this->EFS[$ef][$type][] = $v; //ajoute le représentant
									$this->index[$v] = "$ef|$type";
									$n2 += 1;
								}
							} else 
							{
                                                                $type = $v;
								$this->EFS[$ef][$type] = array(); //ouvre le type
								$n1 += 1;
							}
						} 
					} elseif ($cattype)
                                        {   
                                            if ($v == "ENDCAT") 
                                            {       //ferme la cattype
                                                $this->dEF[$cattype] = array($n1, $n2);
                                                $cattype = 0; 
                                            } else 
                                            {
                                                if ($nomcat)
                                                {
                                                    if ($v == "END") $nomcat = 0; //ferme la cat
                                                    else 
                                                    {
                                                        $this->EFS[$cattype][$nomcat][] = $v; //ajoute le représentant
                                                        $this->index[$v] = "$cattype|$nomcat";
                                                        if (!isset($n2)) $n2 = 0;
                                                        $n2 += 1;
                                                    }
                                                } else
                                                {
                                                    $nomcat = $v;
                                                    $this->EFS[$cattype][$nomcat] = array(); //ouvre la cat
                                                    if (!isset($n1)) $n1 = 0;
                                                    $n1 += 1;
                                                }
                                            }
                                        }
				}
			}
		}
            }
	}

	function affiche_EFS()
	{
		$html = "<ul>\n";
		foreach ($this->EFS as $k1 => $v1) 
		{	
			$n = $this->dEF[$k1];
			$html .=  " <li>$k1 {".$n[0]."|".$n[1]."}</li>\n  <ul>\n";
			foreach($v1 as  $k2 => $v2)
			{
				$html .=  "   <li>$k2 {".sizeof($v2)."}</li>\n    <ul>\n";
				foreach($v2 as  $v3) $html .=  "     <li>$v3</li>\n";
				$html .=  "    </ul>\n";
			}
			$html .=  "  </ul>\n";
		}
		return $html;
	}

	function content_file_fic()
	{
		$content =  "fic0001\n";
		foreach ($this->EFS as $k1 => $v1) 
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
		return $content;
	}
	

	function liste_repr($ef)
	{
		if ( array_key_exists($ef,$this->EFS) )
		{
			$l = array();
			foreach ($this->EFS[$ef] as $k1 => $v1) foreach($v1 as $k2 => $v2) $l[] = $v2;
			return $l;
		}
		else return false;
	}
	
	function repr_type($ef,$cl)
	{
		return $this->EFS[$ef][$cl];
	}	
	
	function largest_type($ef)
	{
		$type = array("",0);
		foreach ($this->EFS[$ef] as $k => $v ) if (sizeof($v) > $type[1]) $type = array($k,sizeof($v)) ;
		return $type[0];
	}
	
}


#print "test : nb de repr de ABEILLES@ : ".sizeof($test->liste_repr("ABEILLES@"))."<br/>";
#print "test : EF dans lequel se trouve Afsset : ".$test->index["Afsset"]."<br/>";
#print "liste intégrale des EF : ". $test->affiche_EFS();



?>


