﻿<?PHP
/*
Variant för test med gemensam dubblettlista.
Avsedd att ge längre lista och därmed också fler falsklarm.
Därför ej lämplig till manuell felkontroll. 
Manuella XL-listan avgränsas vi ett bestämt värde.

Programmet skall genomsöka GEDCOM filen efter individer med lika
eller snarlika uppgifter för att hitta eventuella dubbletter eller
andra felregistreringar.
Programmet är uppdelat i 8 block för att korta körtiden då körtiden
ökar katastrofalt med antalet individer i filen.
Blocken är uppdelade efter födelseår.

Poängsättning och bonus på jämförda likheter
Avvikelser adderas i $neg, endast 1 avvikelse tillåten
Även familjekombinationen kan påverka resultatet.

Utdata, en sorterad kandidatlista avsedd för egenkontroll.

Antalsspärr, f.n. 100 000 inlagd
Optimering av loop 1 tillkommer 

*/
require 'initbas.php';
//
$filename=$directory . "RGD9.GED";
//
$fileut=$directory . "RGDXL.txt";
$filedub=$directory . "dbxl.dat";
//	Disgen Ask
$filein=$directory . "RGD1.GED";
$fileu2=$directory . "DgDub.txt";
//	Ask end
//
if(file_exists($fileut))
{
	echo $fileut	." finns redan, programmet avbruts<br/>";
}
else
{
//
	if(file_exists($filename))
	{
		echo $filename." finns<br/>";
		echo "$filename har storleken ".filesize($filename)."<br/>";
//
		echo "Program startar ".date('Y-m-d')." / ".date('H:i:s')."<br/>";
//****
//	Extra snurra för att kunna begränsa volymen
// ändrad till RGD1 inför Ask-versionen i stället för en extra genomläsning.
	$zlen = 0;
	$zind = 0;
	$zfam = 0;
	$ztst = '';
	$stig = '?';
	$dg = '';
	$handin=fopen($filein,"r");
//	Läs in indatafilen				
	$lines = file($filein,FILE_IGNORE_NEW_LINES);
	foreach($lines as $radnummer => $str)
	{
		$zlen = strlen($str);
		$tagg = substr($str,2,4);
		$tag6 = substr($str,0,6);
		$tags = substr($str,7,6);
		$tagv = substr($str,7,1);
		if($tag6 == '1 SOUR') {
			if($tags == 'Disgen') {
				$dg = 'JA';
			}
		}
		if(($tag6 == '2 VERS') && ($dg == 'JA')) {
			if(($tagv == '8') || ($tagv == '7')) {
				$dg = '';
			}
		}
		if($tagg == 'FILE') {
			$stig = substr($str,2,$zlen);
		}
		$ztst = substr($str,$zlen-6,6);
		if($ztst == '@ INDI') {
			$zind++;
		}
		$ztst = substr($str,$zlen-5,5);
		if($ztst == '@ FAM') {
			$zfam++;
		}
	}
	fclose($handin);
//
	if($zind >= 100000) 
	{
		$handut=fopen($fileut,"w");
		fwrite($handut,"Dubblett Sökning \r\n");
		fwrite($handut," \r\n");
		fwrite($handut,"Antalet individer är för stort för dubblettkontrollen.  \r\n");
		fwrite($handut," \r\n");
		fwrite($handut,"Kontakta administratören som anges på inloggningssidan  \r\n");
		fwrite($handut,"för att få hjälp med dubblettkontrollen på annat sätt.   \r\n");
		fwrite($handut," \r\n");
		fwrite($handut,"Antal individer = ".$zind." \r\n");
		fwrite($handut,"Antal familjer  = ".$zfam." \r\n");
		fclose($handut);
		echo "<br/>";
		echo 'Antal individer = '.$zind.'<br/>';
		echo 'Antal familjer  = '.$zfam.'<br/>';
		echo "<br/>";
		echo 'Dubblettkontrollen inte körd.<br/>';
	}
	else
	{	
//****
		$handut=fopen($fileut,"w");
//
		echo "<br/>";
		echo 'Antal individer = '.$zind.'<br/>';
		echo 'Antal familjer  = '.$zfam.'<br/>';
		echo "<br/>";
//
		fwrite($handut,"Dubblett Sökning XL-version  \r\n");
		fwrite($handut," \r\n");
		fwrite($handut,"Individer med lika eller snarlika uppgifter, ");
		fwrite($handut,"som bör kollas genom en rimlighetsbedömning .  \r\n");
		fwrite($handut," \r\n");
//	
		$n1 = 0;
		$min = 0;
		$max = 0;
		$kant = 0;
//	
//	Steg 1M startar	
//	
		$handle=fopen($filename,"r");
//	
		$n1 = $min;
		$nsist = 0;
		$znum = '';
		$kand = '';
		$birt = '';
		$deat = '';
		$ifnx = '';
		$ienx = '';
		$ifdx = '';
		$ifpx = '';
		$iddx = '';
		$idpx = '';
		$famc = '';
		$nradx = '';
		$dradx = '';
		$pradx = '';
		$dpref = '';
		$sexx = '';
		$fam = ''; 
		$ind = '';
		$head = 'ON';	
//	Läs in indatafilen				
		$lines = file($filename,FILE_IGNORE_NEW_LINES);
		foreach($lines as $radnummer => $str)
		{
//	Huvud börjar - läs tills första individ/relation
			if($head == 'ON') {
				$ztag = substr($str,0,3);
				if($ztag == '0 @') {
					$zlen = strlen($str);
					$zmax = 3;
					while($zmax <= $zlen) {
						$ztal = substr($str,$zmax,1);
						if($ztal == '@')  {
							if((substr($str,$zmax,5) == '@ IND') || (substr($str,$zmax,5) == '@ FAM')) {
								$head = 'OFF';
//							echo "Första individen/relationen = ".$str." <br/>";
							}
							$zmax = $zlen; 
						}	
						$zmax++;
					}
				}	
			}
//	Första individ/relation börjar	
			if($head == 'OFF')
			{
//	hitta idnummer för individ/relation
				$ztag = substr($str,0,3);
				if($ztag == '0 @') {
//	Ny post, ladda upp tidigare data
					$aar = 0;
					$aar = (int)substr($ifdx,0,4);
					if(($znum != '') && ($ind == 'J') && 
					($aar <= 1799) && ($sexx == 'M')) {
						$n1++;
						$num[$n1]=$znum;
						$ifn[$n1]=$ifnx;
						$ien[$n1]=$ienx;
						$ifd[$n1]=$ifdx;
						$ifp[$n1]=$ifpx;
						$idd[$n1]=$iddx;
						$idp[$n1]=$idpx;
						$fmc[$n1]=$famc;
						$nrad[$n1]=$nradx;
						$drad[$n1]=$dradx;
						$prad[$n1]=$pradx;
						$isex[$n1]=$sexx;
					}
					$ifnx = '';
					$ienx = '';
					$ifdx = '';
					$ifpx = '';
					$iddx = '';
					$idpx = '';
					$famc = '';
					$nradx = '';
					$dradx = '';
					$pradx = '';
					$dpref = '';
					$sexx = '';
//	Ny post, sök identitet
					$znum = '';
					$zlen = strlen($str);
					$zmax = 3;
					while($zmax <= $zlen) {
						$ztal = substr($str,$zmax,1);
						if($ztal == '@') {
							if(substr($str,$zmax,5) == '@ IND') {
								$ind = 'J';
								$fam = '';
								}
							elseif(substr($str,$zmax,5) == '@ FAM') {
								$ind = '';
								$fam = 'J';
							}
							else {
								$ind = '';
								$fam = '';
								}
						}	
						if($ztal != '@') {
							$znum = $znum.$ztal;
						}
						else {
							$zmax = $zlen;
							$zmax++;
						}
						$zmax++;
					}
				}
//		
//	Fortsätt samla data
				$tag1 = substr($str,0,1);
				$tagk = substr($str,2,3);
				$tagl = substr($str,2,4);
				$tag7 = substr($str,6,1);
				$tlen = strlen($str);
				$str = substr($str,7,($tlen-7));
//
				if($tag1 == '1') {
					$birt = '';
					$deat = '';
				}
//
				if($tagk == 'SEX') {
					$sexx = $tag7;
				}
				elseif($tagl == 'BIRT') {
					$birt = 'J';
				}
				elseif($tagk == 'CHR') {
					if(($ifdx == '') && ($ifpx == '')) {
						$birt = 'J';
//						echo "Enbart döpt <br/>";
					}	
				}
				elseif($tagl == 'DEAT') {
					$deat = 'J';
				}
				elseif($tagl == 'BURI') {
					if(($iddx == '') && ($idpx == '')) {
						$deat = 'J';
//						echo "Enbart begravd <br/>";
					}	
				}
				elseif($tagl == 'RGDF') {
					$ifnx = $str;
				}
				elseif($tagl == 'RGDE') {
					$ienx = $str;
				}
				elseif(($tagl == 'RGDD') && ($birt == 'J')) {
					$ifdx = $str;
				}
				elseif(($tagl == 'RGDP') && ($birt == 'J')) {
					$ifpx = $str;
				}
				elseif(($tagl == 'RGDD') && ($deat == 'J')) {
					$iddx = $str;
				}
				elseif(($tagl == 'RGDP') && ($deat == 'J')) {
					$idpx = $str;
				}
				elseif($tagl == 'FAMC') {
					$famc = $str;
				}
				elseif($tagl == 'NAME') {
					$nradx = $str;
				}
				elseif(($tagl == 'DATE') && ($birt == 'J')) {
					$tstx = $str; // fimpa ()
					$lend = strlen($str);
					$tstp = substr($str,0,1);
					if($tstp == '(') {
						$tstx = substr($str,1,$lend-2); }
					$dpref = 'f. ';
					$dradx = $dpref.$tstx;
				}
				elseif(($tagl == 'PLAC') && ($birt == 'J')) {
					if($dpref == '') {
						$dpref = 'f. ';
						$pradx = $dpref.$str;
					}
					else {
						$pradx = $str;
					}
				}
				elseif(($tagl == 'DATE') && ($deat == 'J') && ($dpref == '')) {
					$tstx = $str; // fimpa ()
					$lend = strlen($str);
					$tstp = substr($str,0,1);
					if($tstp == '(') {
						$tstx = substr($str,1,$lend-2); }
					$dpref = 'd. ';
					$dradx = $dpref.$tstx;
				}
				elseif(($tagl == 'PLAC') && ($deat == 'J')) {
					if($dpref == '') {
						$pradx = 'd. '.$str;
					}	
					if($dpref == 'd. ') {
						$pradx = $str;
					}	
				}
				else {
//	ointressant rad
				}
			}		
		}
		fclose($handle);
//
//		echo $n1." individer inlästa för bearbetning efter block 1.<br/>";
//		echo "<br/>";
//	
//////////////////////////////////
		$max = $n1;
		$n1 = $min + 1;
//
		while($n1 <= $max) {
				$kp1[$n1]='';
				$kp2[$n1]='';
				if(($ifd[$n1] != '') && ($ifp[$n1] != '')); {
					$kp1[$n1]=$ifd[$n1].$ifp[$n1];
				}	
				if(($idd[$n1] != '') && ($idp[$n1] != '')); {
					$kp2[$n1]=$idd[$n1].$idp[$n1];
				}	
//						
				$flen = strlen($ifn[$n1]);		
				$ifn1[$n1] = '';
				$ifn2[$n1] = '';
				$ifn3[$n1] = '';
				$ifn4[$n1] = '';
				$ifn5[$n1] = '';
//			
				$fx = 0;
				while(($fx <= $flen) && (substr($ifn[$n1],$fx,1) != ',')) {
					$ifn1[$n1] = $ifn1[$n1].substr($ifn[$n1],$fx,1);
					$fx++;
				}
				$fx++;
				while(($fx <= $flen) && (substr($ifn[$n1],$fx,1) != ',')) {
					$ifn2[$n1] = $ifn2[$n1].substr($ifn[$n1],$fx,1);
					$fx++;
				}
				$fx++;
				while(($fx <= $flen) && (substr($ifn[$n1],$fx,1) != ',')) {
					$ifn3[$n1] = $ifn3[$n1].substr($ifn[$n1],$fx,1);
					$fx++;
				}
				$fx++;
				while(($fx <= $flen) && (substr($ifn[$n1],$fx,1) != ',')) {
					$ifn4[$n1] = $ifn4[$n1].substr($ifn[$n1],$fx,1);
					$fx++;
				}
				$fx++;
				if($fx <= $flen) {
					$ifn5[$n1] = substr($ifn[$n1],$fx,($flen-$fx));
				}
//						
				$elen = strlen($ien[$n1]);		
				$ien1[$n1] = '';
				$ien2[$n1] = '';
				$ien3[$n1] = '';
//
				$ex = 0;
				while(($ex <= $elen) && (substr($ien[$n1],$ex,1) != ',')) {
					$ien1[$n1] = $ien1[$n1].substr($ien[$n1],$ex,1);
					$ex++;
				}
				$ex++;
				while(($ex <= $elen) && (substr($ien[$n1],$ex,1) != ',')) {
					$ien2[$n1] = $ien2[$n1].substr($ien[$n1],$ex,1);
					$ex++;
				}
				$ex++;
				if($ex <= $elen) {
					$ien3[$n1] = substr($ien[$n1],$ex,($elen-$ex));
				}
//						
				$blen = strlen($ifd[$n1]);		
				$ifd1[$n1] = '';
				$ifd2[$n1] = '';
				$ifd3[$n1] = '';
//			
				$bx = 0;
				if(($bx < $blen) && (substr($ifd[$n1],$bx,4) != '')) {
					$ifd1[$n1] = substr($ifd[$n1],$bx,4);
				}
				$bx = 4;
				if(($bx <= $blen) && (substr($ifd[$n1],$bx,2) != '')) {
					$ifd2[$n1] = substr($ifd[$n1],$bx,2);
				}
				$bx = 6;
				if($bx <= $blen) {
					$ifd3[$n1] = substr($ifd[$n1],$bx,2);
				}	
//						
				$dlen = strlen($idd[$n1]);		
				$idd1[$n1] = '';
				$idd2[$n1] = '';
				$idd3[$n1] = '';
//			
				$dx = 0;
				if(($dx < $dlen) && (substr($idd[$n1],$dx,4) != '')) {
					$idd1[$n1] = substr($idd[$n1],$dx,4);
				}
				$dx = 4;
				if(($dx <= $dlen) && (substr($idd[$n1],$dx,2) != '')) {
					$idd2[$n1] = substr($idd[$n1],$dx,2);
				}
				$dx = 6;
				if($dx <= $dlen) {
					$idd3[$n1] = substr($idd[$n1],$dx,2);
				}	
				$kp3[$n1]='';
				$kp4[$n1]='';
				if(($ifd1[$n1] != '') && ($ifp[$n1] != '')); {
					$kp3[$n1]=$ifd1[$n1].$ifp[$n1];
				}	
				if(($idd1[$n1] != '') && ($idp[$n1] != '')); {
					$kp4[$n1]=$idd1[$n1].$idp[$n1];
				}	
				$kp5[$n1]='';
				$kp6[$n1]='';
				if(($ifd2[$n1] != '') && ($ifd3[$n1] != '') && ($ifp[$n1] != '')); {
					$kp5[$n1]=$ifd2[$n1].$ifd3[$n1].$ifp[$n1];
				}	
				if(($idd2[$n1] != '') && ($idd3[$n1] != '') && ($idp[$n1] != '')); {
					$kp6[$n1]=$idd2[$n1].$idd3[$n1].$idp[$n1];
				}
			$n1++;
		}			
//
///////////////////////////
//	Loop 1		
		$n1 = $min + 1;
		$min = $max;
		$nsist = $max - 1;
		while($n1 <= $nsist) {
//	Ladda z
			$numz = $num[$n1];
			$nradz = $nrad[$n1];
			$dradz = $drad[$n1];
			$pradz = $prad[$n1];
//
			$isexz = $isex[$n1];
			$fmcz = $fmc[$n1];
			$ifpz = $ifp[$n1];
			$ifdz = $ifd[$n1];
			$ifd1z = $ifd1[$n1];
			$ifd2z = $ifd2[$n1];
			$ifd3z = $ifd3[$n1];
			$ifnz = $ifn[$n1];
			$ifn1z = $ifn1[$n1];
			$ifn2z = $ifn2[$n1];
			$ifn3z = $ifn3[$n1];
			$ifn4z = $ifn4[$n1];
			$ifn5z = $ifn5[$n1];
			$idpz = $idp[$n1];
			$iddz = $idd[$n1];
			$idd1z = $idd1[$n1];
			$idd2z = $idd2[$n1];
			$idd3z = $idd3[$n1];
			$ienz = $ien[$n1];
			$ien1z = $ien1[$n1];
			$ien2z = $ien2[$n1];
			$ien3z = $ien3[$n1];
			$kp1z = $kp1[$n1];
			$kp2z = $kp2[$n1];
			$kp3z = $kp3[$n1];
			$kp4z = $kp4[$n1];
			$kp5z = $kp5[$n1];
			$kp6z = $kp6[$n1];
//		
//	Loop 2	
			$n2 = $n1 + 1;
			while($n2 <= $max) {
//	
				$bon = 0;
				$pos = 0;
				$neg = 0;
				$ant = 0;
				$halv = 0;
				$plus = ' ';
//	Båda har barnfamilj och dessutom samma familj (syskon)					
				if($fmcz != '') {
					if($fmc[$n2] != '') {
						if($fmcz == $fmc[$n2]) {
							$plus = '-';
							$neg++;
							$neg++;
// ?						$halv++;
						}		
					}
				}
//	Boost					
				if(($ifdz != '') && (strlen($ifdz) == 8)) {
					if($ifd[$n2] != '') {
						if($ifdz == $ifd[$n2]) {
							$bon++;
						}
					}
				}
				if($ifd1z != '') {
					if($ifd1[$n2] != '') {
						$ant++;
						if($ifd1z == $ifd1[$n2]) {
							$pos++;
//	Extra bonus
							$bon++;
						}
						else {
							$neg++;
							if($ifd1z < $ifd1[$n2]) {
								if(($ifd1z + 10) < $ifd1[$n2]) {
									$neg++;
									$neg++;
								}
							}	
							else {
								if(($ifd1[$n2] + 10) < $ifd1z) {
									$neg++;
									$neg++;
								}
							}
						}
					}
				}
//	Bryt om ett dödår är mindre än det andra födelseåret
				if($ifd1z != '') {
					if($idd1[$n2] != '') {
						if($idd1[$n2] < $ifd1z) {
							$neg++;
							$neg++;
							$neg++;
						}
					}
				}
				if($ifd1[$n2] != '') {
					if($idd1z != '') {
						if($idd1z < $ifd1[$n2]) {
							$neg++;
							$neg++;
							$neg++;
						}
					}
				}
//			
				if($neg < 3){
//						
//	Boost					
					if(($iddz != '') && (strlen($iddz) == 8)) {
						if($idd[$n2] != '') {
							if($iddz == $idd[$n2]) {
								$bon++;
							}
						}
					}
//						
					if($idd1z != '') {
						if($idd1[$n2] != '') {
							$ant++;
							if($idd1z == $idd1[$n2]) {
								$pos++;
//	Extra bonus
								$bon++;
							}
							else {
								$neg++;
								if($idd1z < $idd1[$n2]) {
									if(($idd1z + 10) < $idd1[$n2]) {
										$neg++;
										$neg++;
									}
								}	
								else {
									if(($idd1[$n2] + 10) < $idd1z) {
										$neg++;
										$neg++;
									}
								}
							}
						}
					}
//			
					if($neg < 3){
//						
//	Boost		
						$ntest = 0;
						if($ifnz != '') {
							if($ifn[$n2] != '') {
								if($ifnz == $ifn[$n2]) {
									$bon++;
									if($ifnz != $ifn1z) {
										$bon++;
									}	
									$ntest++;
								}
							}
						}
//						
						if($ifn1z != '') {
							if($ifn1[$n2] != '') {
								$ant++;
								if(($ifn1z == $ifn1[$n2]) || ($ifn1z == $ifn2[$n2]) || ($ifn1z == $ifn3[$n2])
								 || ($ifn1z == $ifn4[$n2]) || ($ifn1z == $ifn5[$n2]) ) {
									$pos++;
									$ntest++;
								}
							}	
						}
//						
						if($ifn2z != '') {
							if($ifn1[$n2] != '') {
								$ant++;
								if(($ifn2z == $ifn1[$n2]) || ($ifn2z == $ifn2[$n2]) || ($ifn2z == $ifn3[$n2])
								 || ($ifn2z == $ifn4[$n2]) || ($ifn2z == $ifn5[$n2]) ) {
									$pos++;
									$ntest++;
								}
							}
						}
//					
						if($ifn3z != '') {
							if($ifn1[$n2] != '') {
								$ant++;
								if(($ifn3z == $ifn1[$n2]) || ($ifn3z == $ifn2[$n2]) || ($ifn3z == $ifn3[$n2])
								 || ($ifn3z == $ifn4[$n2]) || ($ifn3z == $ifn5[$n2]) ) {
									$pos++;
									$ntest++;
								}
							}
						}
//					
						if($ifn4z != '') {
							if($ifn1[$n2] != '') {
								$ant++;
								if(($ifn4z == $ifn1[$n2]) || ($ifn4z == $ifn2[$n2]) || ($ifn4z == $ifn3[$n2])
								 || ($ifn4z == $ifn4[$n2]) || ($ifn4z == $ifn5[$n2]) ) {
									$pos++;
									$ntest++;
								}
							}
						}
//					
						if($ifn5z != '') {
							if($ifn1[$n2] != '') {
								$ant++;
								if(($ifn5z == $ifn1[$n2]) || ($ifn5z == $ifn2[$n2]) || ($ifn5z == $ifn3[$n2])
								 || ($ifn5z == $ifn4[$n2]) || ($ifn5z == $ifn5[$n2]) ) {
									$pos++;
									$ntest++;
								}
							}
						}
						if($ntest == 0) {
							$neg++;
							$neg++;
						}
//			
						if($neg < 3) {
//						
							if($ifd2z != '') {
								if($ifd2[$n2] != '') {
									$ant++;
									if($ifd2z == $ifd2[$n2]) {
										$pos++;
									}
									else {
										$neg++;
									}
								}
							}
//						
							if($ifd3z != '') {
								if($ifd3[$n2] != '') {
									$ant++;
									if($ifd3z == $ifd3[$n2]) {
										$pos++;
									}
									else {
										$neg++;
									}
								}
							}
//						
							if($ifpz != '') {
								if($ifp[$n2] != '') {
									$ant++;
									if($ifpz == $ifp[$n2]) {
										$pos++;
									}
									else {
										$neg++;
										$neg++;
									}
								}
							}
//			
							if($neg < 3) {
//						
								if($idd2z != '') {
									if($idd2[$n2] != '') {
										$ant++;
										if($idd2z == $idd2[$n2]) {
											$pos++;
										}
										else {
											$neg++;
										}
									}
								}
//						
								if($idd3z != '') {
									if($idd3[$n2] != '') {
										$ant++;
										if($idd3z == $idd3[$n2]) {
											$pos++;
										}
										else {
											$neg++;
										}
									}
								}
//						
								if($idpz != '') {
									if($idp[$n2] != '') {
										$ant++;
										if($idpz == $idp[$n2]) {
											$pos++;
										}
										else {
											$neg++;
											$halv++;
										}
									}
								}
//			
								if($neg < 3) {
//	Boost					
									$ntest = 0;
									if($ienz != '') {
										if($ien[$n2] != '') {
											if($ienz == $ien[$n2]) {
												$bon++;
												if($ienz != $ien1z) {
													$bon++;
												}	
												$ntest++;
											}
										}
									}
//						
									if($ien1z != '') {
										if($ien1[$n2] != '') {
											$ant++;
											if(($ien1z == $ien1[$n2]) || ($ien1z == $ien2[$n2]) || ($ien1z == $ien3[$n2])) {
												$pos++;
											}
											$ntest++;
										}
									}
//						
									if($ien2z != '') {
										if($ien1[$n2] != '') {
											$ant++;
											if(($ien2z == $ien1[$n2]) || ($ien2z == $ien2[$n2]) || ($ien2z == $ien3[$n2])) {
												$pos++;
											}
											$ntest++;
										}
									}
//						
									if($ien3z != '') {
										if($ien1[$n2] != '') {
											$ant++;
											if(($ien3z == $ien1[$n2]) || ($ien3z == $ien2[$n2]) || ($ien3z == $ien3[$n2])) {
												$pos++;
											}
											$ntest++;
										}
									}
									if($ntest == 0) {
										$halv++;
									}
//	Boost					
									if($kp1z != '') {
										if($kp1[$n2] != '') {
											if(($kp1z == $kp1[$n2]) && (strlen($ifdz) == 8)) {
												$bon++;
											}
										}
									}
//	Boost					
									if($kp2z != '') {
										if($kp2[$n2] != '') {
											if(($kp2z == $kp2[$n2]) && (strlen($iddz) == 8)) {
												$bon++;
											}
										}
									}
//	Boost					
									if($kp3z != '') {
										if($kp3[$n2] != '') {
											if($kp3[$n1] == $kp3[$n2]) {
												$bon++;
											}
										}
									}
//	Boost					
									if($kp4[$n1] != '') {
										if($kp4[$n2] != '') {
											if($kp4[$n1] == $kp4[$n2]) {
												$bon++;
											}
										}
									}
//	Boost					
									if($kp5[$n1] != '') {
										if($kp5[$n2] != '') {
											if($kp5[$n1] == $kp5[$n2]) {
												$bon++;
											}
										}
									}
//	Boost					
									if($kp6[$n1] != '') {
										if($kp6[$n2] != '') {
											if($kp6[$n1] == $kp6[$n2]) {
												$bon++;
											}
										}
									}
//	Tolka betydelsen av familj FAMC 
//					
//	Båda har barmfamilj men olika familjer
									if($fmc[$n1] != '') {
										if($fmc[$n2] != '') {
											if($fmc[$n1] != $fmc[$n2]) {
												$plus = '?';
												$halv++;
											}
										}
									}
//	En saknar barnfamilj (mor/dotter - far/som)					
									if($fmc[$n1] != '') {
										if($fmc[$n2] == '') {
											$plus = '+';
											$bon++;
										}
									}
//	En saknar barnfamilj (mor/dotter - far/som)					
									if($fmc[$n1] == '') {
										if($fmc[$n2] != '') {
											$plus = '+';
											$bon++;
										}
									}
								}
							}
						}
					}	
				}
				if($halv >= 2) {
					$neg++;
				}
//	
				if(($bon > 2) && ($pos > 2) && ($pos > $neg) && ($neg < 3) && ($ant > 2)) {
					$tmp = $pos/$ant;
					if($tmp >= 0.5) {
						$diff = $ant - $pos;
						$totp = $bon + $pos - $diff - $neg;
						if($plus == '+') {
							$totp = $totp + 3;
						}	
						if($plus == ' ') {
							$totp = $totp + 2;
						}	
						if($plus == '?') {
							$totp = $totp + 1;
						}	
						if($tmp >= 0.65) {
							$totp = $totp + 1;
						}	
						if($tmp >= 0.75) {
							$totp = $totp + 1;
						}	
						if($tmp >= 0.8) {
							$totp = $totp + 1;
						}	
						if($tmp >= 0.9) {
							$totp = $totp + 1;
						}	
						if($totp > 7) {
							if($totp <= 9) {
								$txtp = '0'.$totp;
							}
							else {
								$txtp = $totp;
							}
							$fellista[]=$txtp.":"
							.$num[$n1].", ".$nrad[$n1].", ".$drad[$n1].", ".$prad[$n1].":"
							.$num[$n2].", ".$nrad[$n2].", ".$drad[$n2].", ".$prad[$n2];
							$kant++;
							$nyckel = $num[$n1].';'.$num[$n2];
							$dublData[$nyckel] = $totp;
						}
						else {
							$nyckel = $num[$n1].';'.$num[$n2];
							$dublData[$nyckel] = $totp;
						}
					}	
				}
//
				$n2++;
			}
			$n1++;
		}
//	
//	steg 1M avslutat	
//	
//	Steg 2M startar	
//	
		$handle=fopen($filename,"r");
//	
		$n1 = $min;
		$nsist = 0;
		$znum = '';
		$kand = '';
		$birt = '';
		$deat = '';
		$ifnx = '';
		$ienx = '';
		$ifdx = '';
		$ifpx = '';
		$iddx = '';
		$idpx = '';
		$famc = '';
		$nradx = '';
		$dradx = '';
		$pradx = '';
		$dpref = '';
		$sexx = '';
		$fam = ''; 
		$ind = '';
		$head = 'ON';	
//	Läs in indatafilen				
		$lines = file($filename,FILE_IGNORE_NEW_LINES);
		foreach($lines as $radnummer => $str)
		{
//	Huvud börjar - läs tills första individ/relation
			if($head == 'ON') {
				$ztag = substr($str,0,3);
				if($ztag == '0 @') {
					$zlen = strlen($str);
					$zmax = 3;
					while($zmax <= $zlen) {
						$ztal = substr($str,$zmax,1);
						if($ztal == '@')  {
							if((substr($str,$zmax,5) == '@ IND') || (substr($str,$zmax,5) == '@ FAM')) {
								$head = 'OFF';
//							echo "Första individen/relationen = ".$str." <br/>";
							}
							$zmax = $zlen; 
						}	
						$zmax++;
					}
				}	
			}
//	Första individ/relation börjar	
			if($head == 'OFF')
			{
//	hitta idnummer för individ/relation
				$ztag = substr($str,0,3);
				if($ztag == '0 @') {
//	Ny post, ladda upp tidigare data
					$aar = 0;
					$aar = (int)substr($ifdx,0,4);
					if(($znum != '') && ($ind == 'J') && 
					($aar >= 1800) && ($aar <= 1849) && ($sexx == 'M')) {
						$n1++;
						$num[$n1]=$znum;
						$ifn[$n1]=$ifnx;
						$ien[$n1]=$ienx;
						$ifd[$n1]=$ifdx;
						$ifp[$n1]=$ifpx;
						$idd[$n1]=$iddx;
						$idp[$n1]=$idpx;
						$fmc[$n1]=$famc;
						$nrad[$n1]=$nradx;
						$drad[$n1]=$dradx;
						$prad[$n1]=$pradx;
						$isex[$n1]=$sexx;
					}
					$ifnx = '';
					$ienx = '';
					$ifdx = '';
					$ifpx = '';
					$iddx = '';
					$idpx = '';
					$famc = '';
					$nradx = '';
					$dradx = '';
					$pradx = '';
					$dpref = '';
					$sexx = '';
//	Ny post, sök identitet
					$znum = '';
					$zlen = strlen($str);
					$zmax = 3;
					while($zmax <= $zlen) {
						$ztal = substr($str,$zmax,1);
						if($ztal == '@') {
							if(substr($str,$zmax,5) == '@ IND') {
								$ind = 'J';
								$fam = '';
								}
							elseif(substr($str,$zmax,5) == '@ FAM') {
								$ind = '';
								$fam = 'J';
							}
							else {
								$ind = '';
								$fam = '';
								}
						}	
						if($ztal != '@') {
							$znum = $znum.$ztal;
						}
						else {
							$zmax = $zlen;
							$zmax++;
						}
						$zmax++;
					}
				}
//		
//	Fortsätt samla data
				$tag1 = substr($str,0,1);
				$tagk = substr($str,2,3);
				$tagl = substr($str,2,4);
				$tag7 = substr($str,6,1);
				$tlen = strlen($str);
				$str = substr($str,7,($tlen-7));
//
				if($tag1 == '1') {
					$birt = '';
					$deat = '';
				}
//
				if($tagk == 'SEX') {
					$sexx = $tag7;
				}
				elseif($tagl == 'BIRT') {
					$birt = 'J';
				}
				elseif($tagk == 'CHR') {
					if(($ifdx == '') && ($ifpx == '')) {
						$birt = 'J';
//						echo "Enbart döpt <br/>";
					}	
				}
				elseif($tagl == 'DEAT') {
					$deat = 'J';
				}
				elseif($tagl == 'BURI') {
					if(($iddx == '') && ($idpx == '')) {
						$deat = 'J';
//						echo "Enbart begravd <br/>";
					}	
				}
				elseif($tagl == 'RGDF') {
					$ifnx = $str;
				}
				elseif($tagl == 'RGDE') {
					$ienx = $str;
				}
				elseif(($tagl == 'RGDD') && ($birt == 'J')) {
					$ifdx = $str;
				}
				elseif(($tagl == 'RGDP') && ($birt == 'J')) {
					$ifpx = $str;
				}
				elseif(($tagl == 'RGDD') && ($deat == 'J')) {
					$iddx = $str;
				}
				elseif(($tagl == 'RGDP') && ($deat == 'J')) {
					$idpx = $str;
				}
				elseif($tagl == 'FAMC') {
					$famc = $str;
				}
				elseif($tagl == 'NAME') {
					$nradx = $str;
				}
				elseif(($tagl == 'DATE') && ($birt == 'J')) {
					$tstx = $str; // fimpa ()
					$lend = strlen($str);
					$tstp = substr($str,0,1);
					if($tstp == '(') {
						$tstx = substr($str,1,$lend-2); }
					$dpref = 'f. ';
					$dradx = $dpref.$tstx;
				}
				elseif(($tagl == 'PLAC') && ($birt == 'J')) {
					if($dpref == '') {
						$dpref = 'f. ';
						$pradx = $dpref.$str;
					}
					else {
						$pradx = $str;
					}
				}
				elseif(($tagl == 'DATE') && ($deat == 'J') && ($dpref == '')) {
					$tstx = $str; // fimpa ()
					$lend = strlen($str);
					$tstp = substr($str,0,1);
					if($tstp == '(') {
						$tstx = substr($str,1,$lend-2); }
					$dpref = 'd. ';
					$dradx = $dpref.$tstx;
				}
				elseif(($tagl == 'PLAC') && ($deat == 'J')) {
					if($dpref == '') {
						$pradx = 'd. '.$str;
					}	
					if($dpref == 'd. ') {
						$pradx = $str;
					}	
				}
				else {
//	ointressant rad
				}
			}		
		}
		fclose($handle);
//
//		echo $n1." individer inlästa för bearbetning efter block 2.<br/>";
//		echo "<br/>";
//
	
//////////////////////////////////
		$max = $n1;
		$n1 = $min + 1;
//
		while($n1 <= $max) {
				$kp1[$n1]='';
				$kp2[$n1]='';
				if(($ifd[$n1] != '') && ($ifp[$n1] != '')); {
					$kp1[$n1]=$ifd[$n1].$ifp[$n1];
				}	
				if(($idd[$n1] != '') && ($idp[$n1] != '')); {
					$kp2[$n1]=$idd[$n1].$idp[$n1];
				}	
//						
				$flen = strlen($ifn[$n1]);		
				$ifn1[$n1] = '';
				$ifn2[$n1] = '';
				$ifn3[$n1] = '';
				$ifn4[$n1] = '';
				$ifn5[$n1] = '';
//			
				$fx = 0;
				while(($fx <= $flen) && (substr($ifn[$n1],$fx,1) != ',')) {
					$ifn1[$n1] = $ifn1[$n1].substr($ifn[$n1],$fx,1);
					$fx++;
				}
				$fx++;
				while(($fx <= $flen) && (substr($ifn[$n1],$fx,1) != ',')) {
					$ifn2[$n1] = $ifn2[$n1].substr($ifn[$n1],$fx,1);
					$fx++;
				}
				$fx++;
				while(($fx <= $flen) && (substr($ifn[$n1],$fx,1) != ',')) {
					$ifn3[$n1] = $ifn3[$n1].substr($ifn[$n1],$fx,1);
					$fx++;
				}
				$fx++;
				while(($fx <= $flen) && (substr($ifn[$n1],$fx,1) != ',')) {
					$ifn4[$n1] = $ifn4[$n1].substr($ifn[$n1],$fx,1);
					$fx++;
				}
				$fx++;
				if($fx <= $flen) {
					$ifn5[$n1] = substr($ifn[$n1],$fx,($flen-$fx));
				}
//						
				$elen = strlen($ien[$n1]);		
				$ien1[$n1] = '';
				$ien2[$n1] = '';
				$ien3[$n1] = '';
//
				$ex = 0;
				while(($ex <= $elen) && (substr($ien[$n1],$ex,1) != ',')) {
					$ien1[$n1] = $ien1[$n1].substr($ien[$n1],$ex,1);
					$ex++;
				}
				$ex++;
				while(($ex <= $elen) && (substr($ien[$n1],$ex,1) != ',')) {
					$ien2[$n1] = $ien2[$n1].substr($ien[$n1],$ex,1);
					$ex++;
				}
				$ex++;
				if($ex <= $elen) {
					$ien3[$n1] = substr($ien[$n1],$ex,($elen-$ex));
				}
//						
				$blen = strlen($ifd[$n1]);		
				$ifd1[$n1] = '';
				$ifd2[$n1] = '';
				$ifd3[$n1] = '';
//			
				$bx = 0;
				if(($bx < $blen) && (substr($ifd[$n1],$bx,4) != '')) {
					$ifd1[$n1] = substr($ifd[$n1],$bx,4);
				}
				$bx = 4;
				if(($bx <= $blen) && (substr($ifd[$n1],$bx,2) != '')) {
					$ifd2[$n1] = substr($ifd[$n1],$bx,2);
				}
				$bx = 6;
				if($bx <= $blen) {
					$ifd3[$n1] = substr($ifd[$n1],$bx,2);
				}	
//						
				$dlen = strlen($idd[$n1]);		
				$idd1[$n1] = '';
				$idd2[$n1] = '';
				$idd3[$n1] = '';
//			
				$dx = 0;
				if(($dx < $dlen) && (substr($idd[$n1],$dx,4) != '')) {
					$idd1[$n1] = substr($idd[$n1],$dx,4);
				}
				$dx = 4;
				if(($dx <= $dlen) && (substr($idd[$n1],$dx,2) != '')) {
					$idd2[$n1] = substr($idd[$n1],$dx,2);
				}
				$dx = 6;
				if($dx <= $dlen) {
					$idd3[$n1] = substr($idd[$n1],$dx,2);
				}	
				$kp3[$n1]='';
				$kp4[$n1]='';
				if(($ifd1[$n1] != '') && ($ifp[$n1] != '')); {
					$kp3[$n1]=$ifd1[$n1].$ifp[$n1];
				}	
				if(($idd1[$n1] != '') && ($idp[$n1] != '')); {
					$kp4[$n1]=$idd1[$n1].$idp[$n1];
				}	
				$kp5[$n1]='';
				$kp6[$n1]='';
				if(($ifd2[$n1] != '') && ($ifd3[$n1] != '') && ($ifp[$n1] != '')); {
					$kp5[$n1]=$ifd2[$n1].$ifd3[$n1].$ifp[$n1];
				}	
				if(($idd2[$n1] != '') && ($idd3[$n1] != '') && ($idp[$n1] != '')); {
					$kp6[$n1]=$idd2[$n1].$idd3[$n1].$idp[$n1];
				}
			$n1++;
		}			
//
///////////////////////////
//	Loop 1		
		$n1 = $min + 1;
		$min = $max;
		$nsist = $max - 1;
		while($n1 <= $nsist) {
//	Ladda z
			$numz = $num[$n1];
			$nradz = $nrad[$n1];
			$dradz = $drad[$n1];
			$pradz = $prad[$n1];
//
			$isexz = $isex[$n1];
			$fmcz = $fmc[$n1];
			$ifpz = $ifp[$n1];
			$ifdz = $ifd[$n1];
			$ifd1z = $ifd1[$n1];
			$ifd2z = $ifd2[$n1];
			$ifd3z = $ifd3[$n1];
			$ifnz = $ifn[$n1];
			$ifn1z = $ifn1[$n1];
			$ifn2z = $ifn2[$n1];
			$ifn3z = $ifn3[$n1];
			$ifn4z = $ifn4[$n1];
			$ifn5z = $ifn5[$n1];
			$idpz = $idp[$n1];
			$iddz = $idd[$n1];
			$idd1z = $idd1[$n1];
			$idd2z = $idd2[$n1];
			$idd3z = $idd3[$n1];
			$ienz = $ien[$n1];
			$ien1z = $ien1[$n1];
			$ien2z = $ien2[$n1];
			$ien3z = $ien3[$n1];
			$kp1z = $kp1[$n1];
			$kp2z = $kp2[$n1];
			$kp3z = $kp3[$n1];
			$kp4z = $kp4[$n1];
			$kp5z = $kp5[$n1];
			$kp6z = $kp6[$n1];
//		
//	Loop 2	
			$n2 = $n1 + 1;
			while($n2 <= $max) {
//	
				$bon = 0;
				$pos = 0;
				$neg = 0;
				$ant = 0;
				$halv = 0;
				$plus = ' ';
//	Båda har barnfamilj och dessutom samma familj (syskon)					
				if($fmcz != '') {
					if($fmc[$n2] != '') {
						if($fmcz == $fmc[$n2]) {
							$plus = '-';
							$neg++;
							$neg++;
						}		
					}
				}
//	Boost					
				if(($ifdz != '') && (strlen($ifdz) == 8)) {
					if($ifd[$n2] != '') {
						if($ifdz == $ifd[$n2]) {
							$bon++;
						}
					}
				}
				if($ifd1z != '') {
					if($ifd1[$n2] != '') {
						$ant++;
						if($ifd1z == $ifd1[$n2]) {
							$pos++;
//	Extra bonus
							$bon++;
						}
						else {
							$neg++;
							if($ifd1z < $ifd1[$n2]) {
								if(($ifd1z + 10) < $ifd1[$n2]) {
									$neg++;
									$neg++;
								}
							}	
							else {
								if(($ifd1[$n2] + 10) < $ifd1z) {
									$neg++;
									$neg++;
								}
							}
						}
					}
				}
//	Bryt om ett dödår är mindre än det andra födelseåret
				if($ifd1z != '') {
					if($idd1[$n2] != '') {
						if($idd1[$n2] < $ifd1z) {
							$neg++;
							$neg++;
							$neg++;
						}
					}
				}
				if($ifd1[$n2] != '') {
					if($idd1z != '') {
						if($idd1z < $ifd1[$n2]) {
							$neg++;
							$neg++;
							$neg++;
						}
					}
				}
//			
				if($neg < 3){
//						
//	Boost					
					if(($iddz != '') && (strlen($iddz) == 8)) {
						if($idd[$n2] != '') {
							if($iddz == $idd[$n2]) {
								$bon++;
							}
						}
					}
//						
					if($idd1z != '') {
						if($idd1[$n2] != '') {
							$ant++;
							if($idd1z == $idd1[$n2]) {
								$pos++;
//	Extra bonus
								$bon++;
							}
							else {
								$neg++;
								if($idd1z < $idd1[$n2]) {
									if(($idd1z + 10) < $idd1[$n2]) {
										$neg++;
										$neg++;
									}
								}	
								else {
									if(($idd1[$n2] + 10) < $idd1z) {
										$neg++;
										$neg++;
									}
								}
							}
						}
					}
//			
					if($neg < 3){
//						
//	Boost		
						$ntest = 0;
						if($ifnz != '') {
							if($ifn[$n2] != '') {
								if($ifnz == $ifn[$n2]) {
									$bon++;
									if($ifnz != $ifn1z) {
										$bon++;
									}	
									$ntest++;
								}
							}
						}
//						
						if($ifn1z != '') {
							if($ifn1[$n2] != '') {
								$ant++;
								if(($ifn1z == $ifn1[$n2]) || ($ifn1z == $ifn2[$n2]) || ($ifn1z == $ifn3[$n2])
								 || ($ifn1z == $ifn4[$n2]) || ($ifn1z == $ifn5[$n2]) ) {
									$pos++;
									$ntest++;
								}
							}	
						}
//						
						if($ifn2z != '') {
							if($ifn1[$n2] != '') {
								$ant++;
								if(($ifn2z == $ifn1[$n2]) || ($ifn2z == $ifn2[$n2]) || ($ifn2z == $ifn3[$n2])
								 || ($ifn2z == $ifn4[$n2]) || ($ifn2z == $ifn5[$n2]) ) {
									$pos++;
									$ntest++;
								}
							}
						}
//					
						if($ifn3z != '') {
							if($ifn1[$n2] != '') {
								$ant++;
								if(($ifn3z == $ifn1[$n2]) || ($ifn3z == $ifn2[$n2]) || ($ifn3z == $ifn3[$n2])
								 || ($ifn3z == $ifn4[$n2]) || ($ifn3z == $ifn5[$n2]) ) {
									$pos++;
									$ntest++;
								}
							}
						}
//					
						if($ifn4z != '') {
							if($ifn1[$n2] != '') {
								$ant++;
								if(($ifn4z == $ifn1[$n2]) || ($ifn4z == $ifn2[$n2]) || ($ifn4z == $ifn3[$n2])
								 || ($ifn4z == $ifn4[$n2]) || ($ifn4z == $ifn5[$n2]) ) {
									$pos++;
									$ntest++;
								}
							}
						}
//					
						if($ifn5z != '') {
							if($ifn1[$n2] != '') {
								$ant++;
								if(($ifn5z == $ifn1[$n2]) || ($ifn5z == $ifn2[$n2]) || ($ifn5z == $ifn3[$n2])
								 || ($ifn5z == $ifn4[$n2]) || ($ifn5z == $ifn5[$n2]) ) {
									$pos++;
									$ntest++;
								}
							}
						}
						if($ntest == 0) {
							$neg++;
							$neg++;
						}
//			
						if($neg < 3) {
//						
							if($ifd2z != '') {
								if($ifd2[$n2] != '') {
									$ant++;
									if($ifd2z == $ifd2[$n2]) {
										$pos++;
									}
									else {
										$neg++;
									}
								}
							}
//						
							if($ifd3z != '') {
								if($ifd3[$n2] != '') {
									$ant++;
									if($ifd3z == $ifd3[$n2]) {
										$pos++;
									}
									else {
										$neg++;
									}
								}
							}
//						
							if($ifpz != '') {
								if($ifp[$n2] != '') {
									$ant++;
									if($ifpz == $ifp[$n2]) {
										$pos++;
									}
									else {
										$neg++;
										$neg++;
									}
								}
							}
//			
							if($neg < 3) {
//						
								if($idd2z != '') {
									if($idd2[$n2] != '') {
										$ant++;
										if($idd2z == $idd2[$n2]) {
											$pos++;
										}
										else {
											$neg++;
										}
									}
								}
//						
								if($idd3z != '') {
									if($idd3[$n2] != '') {
										$ant++;
										if($idd3z == $idd3[$n2]) {
											$pos++;
										}
										else {
											$neg++;
										}
									}
								}
//						
								if($idpz != '') {
									if($idp[$n2] != '') {
										$ant++;
										if($idpz == $idp[$n2]) {
											$pos++;
										}
										else {
											$neg++;
											$halv++;
										}
									}
								}
//			
								if($neg < 3) {
//	Boost					
									$ntest = 0;
									if($ienz != '') {
										if($ien[$n2] != '') {
											if($ienz == $ien[$n2]) {
												$bon++;
												if($ienz != $ien1z) {
													$bon++;
												}	
												$ntest++;
											}
										}
									}
//						
									if($ien1z != '') {
										if($ien1[$n2] != '') {
											$ant++;
											if(($ien1z == $ien1[$n2]) || ($ien1z == $ien2[$n2]) || ($ien1z == $ien3[$n2])) {
												$pos++;
											}
											$ntest++;
										}
									}
//						
									if($ien2z != '') {
										if($ien1[$n2] != '') {
											$ant++;
											if(($ien2z == $ien1[$n2]) || ($ien2z == $ien2[$n2]) || ($ien2z == $ien3[$n2])) {
												$pos++;
											}
											$ntest++;
										}
									}
//						
									if($ien3z != '') {
										if($ien1[$n2] != '') {
											$ant++;
											if(($ien3z == $ien1[$n2]) || ($ien3z == $ien2[$n2]) || ($ien3z == $ien3[$n2])) {
												$pos++;
											}
											$ntest++;
										}
									}
									if($ntest == 0) {
										$halv++;
									}
//	Boost					
									if($kp1z != '') {
										if($kp1[$n2] != '') {
											if(($kp1z == $kp1[$n2]) && (strlen($ifdz) == 8)) {
												$bon++;
											}
										}
									}
//	Boost					
									if($kp2z != '') {
										if($kp2[$n2] != '') {
											if(($kp2z == $kp2[$n2]) && (strlen($iddz) == 8)) {
												$bon++;
											}
										}
									}
//	Boost					
									if($kp3z != '') {
										if($kp3[$n2] != '') {
											if($kp3z == $kp3[$n2]) {
												$bon++;
											}
										}
									}
//	Boost					
									if($kp4z != '') {
										if($kp4[$n2] != '') {
											if($kp4z == $kp4[$n2]) {
												$bon++;
											}
										}
									}
//	Boost					
									if($kp5z != '') {
										if($kp5[$n2] != '') {
											if($kp5z == $kp5[$n2]) {
												$bon++;
											}
										}
									}
//	Boost					
									if($kp6z != '') {
										if($kp6[$n2] != '') {
											if($kp6z == $kp6[$n2]) {
												$bon++;
											}
										}
									}
//	Tolka betydelsen av familj FAMC 
//					
//	Båda har barmfamilj men olika familjer
									if($fmcz != '') {
										if($fmc[$n2] != '') {
											if($fmcz != $fmc[$n2]) {
												$plus = '?';
												$halv++;
											}
										}
									}
//	En saknar barnfamilj (mor/dotter - far/som)					
									if($fmcz != '') {
										if($fmc[$n2] == '') {
											$plus = '+';
											$bon++;
										}
									}
//	En saknar barnfamilj (mor/dotter - far/som)					
									if($fmcz == '') {
										if($fmc[$n2] != '') {
											$plus = '+';
											$bon++;
										}
									}
								}
							}
						}
					}	
				}
				if($halv >= 2) {
					$neg++;
				}
//	
				if(($bon > 2) && ($pos > 2) && ($pos > $neg) && ($neg < 3) && ($ant > 2)) {
					$tmp = $pos/$ant;
					if($tmp >= 0.5) {
						$diff = $ant - $pos;
						$totp = $bon + $pos - $diff - $neg;
						if($plus == '+') {
							$totp = $totp + 3;
						}	
						if($plus == ' ') {
							$totp = $totp + 2;
						}	
						if($plus == '?') {
							$totp = $totp + 1;
						}	
						if($tmp >= 0.65) {
							$totp = $totp + 1;
						}	
						if($tmp >= 0.75) {
							$totp = $totp + 1;
						}	
						if($tmp >= 0.8) {
							$totp = $totp + 1;
						}	
						if($tmp >= 0.9) {
							$totp = $totp + 1;
						}	
						if($totp > 7) {
							if($totp <= 9) {
								$txtp = '0'.$totp;
							}
							else {
								$txtp = $totp;
							}
							$fellista[]=$txtp.":"
							.$numz.", ".$nradz.", ".$dradz.", ".$pradz.":"
							.$num[$n2].", ".$nrad[$n2].", ".$drad[$n2].", ".$prad[$n2];
							$kant++;
							$nyckel = $numz.';'.$num[$n2];
							$dublData[$nyckel] = $totp;
						}
						else {
							$nyckel = $numz.';'.$num[$n2];
							$dublData[$nyckel] = $totp;
						}
					}	
				}
//
				$n2++;
			}
			$n1++;
		}
//	
//	steg 2M avslutat	
//
//	Steg 3M startar	
//	
		$handle=fopen($filename,"r");
//	
		$n1 = $min;
		$nsist = 0;
		$znum = '';
		$kand = '';
		$birt = '';
		$deat = '';
		$ifnx = '';
		$ienx = '';
		$ifdx = '';
		$ifpx = '';
		$iddx = '';
		$idpx = '';
		$famc = '';
		$nradx = '';
		$dradx = '';
		$pradx = '';
		$dpref = '';
		$sexx = '';
		$fam = ''; 
		$ind = '';
		$head = 'ON';	
//	Läs in indatafilen				
		$lines = file($filename,FILE_IGNORE_NEW_LINES);
		foreach($lines as $radnummer => $str)
		{
//	Huvud börjar - läs tills första individ/relation
			if($head == 'ON') {
				$ztag = substr($str,0,3);
				if($ztag == '0 @') {
					$zlen = strlen($str);
					$zmax = 3;
					while($zmax <= $zlen) {
						$ztal = substr($str,$zmax,1);
						if($ztal == '@')  {
							if((substr($str,$zmax,5) == '@ IND') || (substr($str,$zmax,5) == '@ FAM')) {
								$head = 'OFF';
//							echo "Första individen/relationen = ".$str." <br/>";
							}
							$zmax = $zlen; 
						}	
						$zmax++;
					}
				}	
			}
//	Första individ/relation börjar	
			if($head == 'OFF')
			{
//	hitta idnummer för individ/relation
				$ztag = substr($str,0,3);
				if($ztag == '0 @') {
//	Ny post, ladda upp tidigare data
					$aar = 0;
					$aar = (int)substr($ifdx,0,4);
					if(($znum != '') && ($ind == 'J') && 
					($aar >= 1850) && ($aar <= 1899) && ($sexx == 'M')) {
						$n1++;
						$num[$n1]=$znum;
						$ifn[$n1]=$ifnx;
						$ien[$n1]=$ienx;
						$ifd[$n1]=$ifdx;
						$ifp[$n1]=$ifpx;
						$idd[$n1]=$iddx;
						$idp[$n1]=$idpx;
						$fmc[$n1]=$famc;
						$nrad[$n1]=$nradx;
						$drad[$n1]=$dradx;
						$prad[$n1]=$pradx;
						$isex[$n1]=$sexx;
					}
					$ifnx = '';
					$ienx = '';
					$ifdx = '';
					$ifpx = '';
					$iddx = '';
					$idpx = '';
					$famc = '';
					$nradx = '';
					$dradx = '';
					$pradx = '';
					$dpref = '';
					$sexx = '';
//	Ny post, sök identitet
					$znum = '';
					$zlen = strlen($str);
					$zmax = 3;
					while($zmax <= $zlen) {
						$ztal = substr($str,$zmax,1);
						if($ztal == '@') {
							if(substr($str,$zmax,5) == '@ IND') {
								$ind = 'J';
								$fam = '';
							}
							elseif(substr($str,$zmax,5) == '@ FAM') {
								$ind = '';
								$fam = 'J';
							}
							else {
								$ind = '';
								$fam = '';
								}
						}	
						if($ztal != '@') {
							$znum = $znum.$ztal;
						}
						else {
							$zmax = $zlen;
							$zmax++;
						}
						$zmax++;
					}
				}
//		
//	Fortsätt samla data
				$tag1 = substr($str,0,1);
				$tagk = substr($str,2,3);
				$tagl = substr($str,2,4);
				$tag7 = substr($str,6,1);
				$tlen = strlen($str);
				$str = substr($str,7,($tlen-7));
//
				if($tag1 == '1') {
					$birt = '';
					$deat = '';
				}
//
				if($tagk == 'SEX') {
					$sexx = $tag7;
				}
				elseif($tagl == 'BIRT') {
					$birt = 'J';
				}
				elseif($tagk == 'CHR') {
					if(($ifdx == '') && ($ifpx == '')) {
						$birt = 'J';
//						echo "Enbart döpt <br/>";
					}	
				}
				elseif($tagl == 'DEAT') {
					$deat = 'J';
				}
				elseif($tagl == 'BURI') {
					if(($iddx == '') && ($idpx == '')) {
						$deat = 'J';
//						echo "Enbart begravd <br/>";
					}	
				}
				elseif($tagl == 'RGDF') {
					$ifnx = $str;
				}
				elseif($tagl == 'RGDE') {
					$ienx = $str;
				}
				elseif(($tagl == 'RGDD') && ($birt == 'J')) {
					$ifdx = $str;
				}
				elseif(($tagl == 'RGDP') && ($birt == 'J')) {
					$ifpx = $str;
				}
				elseif(($tagl == 'RGDD') && ($deat == 'J')) {
					$iddx = $str;
				}
				elseif(($tagl == 'RGDP') && ($deat == 'J')) {
					$idpx = $str;
				}
				elseif($tagl == 'FAMC') {
					$famc = $str;
				}
				elseif($tagl == 'NAME') {
					$nradx = $str;
				}
				elseif(($tagl == 'DATE') && ($birt == 'J')) {
					$tstx = $str; // fimpa ()
					$lend = strlen($str);
					$tstp = substr($str,0,1);
					if($tstp == '(') {
						$tstx = substr($str,1,$lend-2); }
					$dpref = 'f. ';
					$dradx = $dpref.$tstx;
				}
				elseif(($tagl == 'PLAC') && ($birt == 'J')) {
					if($dpref == '') {
						$dpref = 'f. ';
						$pradx = $dpref.$str;
					}
					else {
						$pradx = $str;
					}
				}
				elseif(($tagl == 'DATE') && ($deat == 'J') && ($dpref == '')) {
					$tstx = $str; // fimpa ()
					$lend = strlen($str);
					$tstp = substr($str,0,1);
					if($tstp == '(') {
						$tstx = substr($str,1,$lend-2); }
					$dpref = 'd. ';
					$dradx = $dpref.$tstx;
				}
				elseif(($tagl == 'PLAC') && ($deat == 'J')) {
					if($dpref == '') {
						$pradx = 'd. '.$str;
					}	
					if($dpref == 'd. ') {
						$pradx = $str;
					}	
				}
				else {
//	ointressant rad
				}
			}		
		}
		fclose($handle);
//
//		echo $n1." individer inlästa för bearbetning efter block 3.<br/>";
//		echo "<br/>";
//
	
//////////////////////////////////
		$max = $n1;
		$n1 = $min + 1;
//
		while($n1 <= $max) {
				$kp1[$n1]='';
				$kp2[$n1]='';
				if(($ifd[$n1] != '') && ($ifp[$n1] != '')); {
					$kp1[$n1]=$ifd[$n1].$ifp[$n1];
				}	
				if(($idd[$n1] != '') && ($idp[$n1] != '')); {
					$kp2[$n1]=$idd[$n1].$idp[$n1];
				}	
//						
				$flen = strlen($ifn[$n1]);		
				$ifn1[$n1] = '';
				$ifn2[$n1] = '';
				$ifn3[$n1] = '';
				$ifn4[$n1] = '';
				$ifn5[$n1] = '';
//			
				$fx = 0;
				while(($fx <= $flen) && (substr($ifn[$n1],$fx,1) != ',')) {
					$ifn1[$n1] = $ifn1[$n1].substr($ifn[$n1],$fx,1);
					$fx++;
				}
				$fx++;
				while(($fx <= $flen) && (substr($ifn[$n1],$fx,1) != ',')) {
					$ifn2[$n1] = $ifn2[$n1].substr($ifn[$n1],$fx,1);
					$fx++;
				}
				$fx++;
				while(($fx <= $flen) && (substr($ifn[$n1],$fx,1) != ',')) {
					$ifn3[$n1] = $ifn3[$n1].substr($ifn[$n1],$fx,1);
					$fx++;
				}
				$fx++;
				while(($fx <= $flen) && (substr($ifn[$n1],$fx,1) != ',')) {
					$ifn4[$n1] = $ifn4[$n1].substr($ifn[$n1],$fx,1);
					$fx++;
				}
				$fx++;
				if($fx <= $flen) {
					$ifn5[$n1] = substr($ifn[$n1],$fx,($flen-$fx));
				}
//						
				$elen = strlen($ien[$n1]);		
				$ien1[$n1] = '';
				$ien2[$n1] = '';
				$ien3[$n1] = '';
//
				$ex = 0;
				while(($ex <= $elen) && (substr($ien[$n1],$ex,1) != ',')) {
					$ien1[$n1] = $ien1[$n1].substr($ien[$n1],$ex,1);
					$ex++;
				}
				$ex++;
				while(($ex <= $elen) && (substr($ien[$n1],$ex,1) != ',')) {
					$ien2[$n1] = $ien2[$n1].substr($ien[$n1],$ex,1);
					$ex++;
				}
				$ex++;
				if($ex <= $elen) {
					$ien3[$n1] = substr($ien[$n1],$ex,($elen-$ex));
				}
//						
				$blen = strlen($ifd[$n1]);		
				$ifd1[$n1] = '';
				$ifd2[$n1] = '';
				$ifd3[$n1] = '';
//			
				$bx = 0;
				if(($bx < $blen) && (substr($ifd[$n1],$bx,4) != '')) {
					$ifd1[$n1] = substr($ifd[$n1],$bx,4);
				}
				$bx = 4;
				if(($bx <= $blen) && (substr($ifd[$n1],$bx,2) != '')) {
					$ifd2[$n1] = substr($ifd[$n1],$bx,2);
				}
				$bx = 6;
				if($bx <= $blen) {
					$ifd3[$n1] = substr($ifd[$n1],$bx,2);
				}	
//						
				$dlen = strlen($idd[$n1]);		
				$idd1[$n1] = '';
				$idd2[$n1] = '';
				$idd3[$n1] = '';
//			
				$dx = 0;
				if(($dx < $dlen) && (substr($idd[$n1],$dx,4) != '')) {
					$idd1[$n1] = substr($idd[$n1],$dx,4);
				}
				$dx = 4;
				if(($dx <= $dlen) && (substr($idd[$n1],$dx,2) != '')) {
					$idd2[$n1] = substr($idd[$n1],$dx,2);
				}
				$dx = 6;
				if($dx <= $dlen) {
					$idd3[$n1] = substr($idd[$n1],$dx,2);
				}	
				$kp3[$n1]='';
				$kp4[$n1]='';
				if(($ifd1[$n1] != '') && ($ifp[$n1] != '')); {
					$kp3[$n1]=$ifd1[$n1].$ifp[$n1];
				}	
				if(($idd1[$n1] != '') && ($idp[$n1] != '')); {
					$kp4[$n1]=$idd1[$n1].$idp[$n1];
				}	
				$kp5[$n1]='';
				$kp6[$n1]='';
				if(($ifd2[$n1] != '') && ($ifd3[$n1] != '') && ($ifp[$n1] != '')); {
					$kp5[$n1]=$ifd2[$n1].$ifd3[$n1].$ifp[$n1];
				}	
				if(($idd2[$n1] != '') && ($idd3[$n1] != '') && ($idp[$n1] != '')); {
					$kp6[$n1]=$idd2[$n1].$idd3[$n1].$idp[$n1];
				}
			$n1++;
		}			
//
///////////////////////////
//	Loop 1		
		$n1 = $min + 1;
		$min = $max;
		$nsist = $max - 1;
		while($n1 <= $nsist) {
//	Ladda z
			$numz = $num[$n1];
			$nradz = $nrad[$n1];
			$dradz = $drad[$n1];
			$pradz = $prad[$n1];
//
			$isexz = $isex[$n1];
			$fmcz = $fmc[$n1];
			$ifpz = $ifp[$n1];
			$ifdz = $ifd[$n1];
			$ifd1z = $ifd1[$n1];
			$ifd2z = $ifd2[$n1];
			$ifd3z = $ifd3[$n1];
			$ifnz = $ifn[$n1];
			$ifn1z = $ifn1[$n1];
			$ifn2z = $ifn2[$n1];
			$ifn3z = $ifn3[$n1];
			$ifn4z = $ifn4[$n1];
			$ifn5z = $ifn5[$n1];
			$idpz = $idp[$n1];
			$iddz = $idd[$n1];
			$idd1z = $idd1[$n1];
			$idd2z = $idd2[$n1];
			$idd3z = $idd3[$n1];
			$ienz = $ien[$n1];
			$ien1z = $ien1[$n1];
			$ien2z = $ien2[$n1];
			$ien3z = $ien3[$n1];
			$kp1z = $kp1[$n1];
			$kp2z = $kp2[$n1];
			$kp3z = $kp3[$n1];
			$kp4z = $kp4[$n1];
			$kp5z = $kp5[$n1];
			$kp6z = $kp6[$n1];
//		
//	Loop 2	
			$n2 = $n1 + 1;
			while($n2 <= $max) {
//	
				$bon = 0;
				$pos = 0;
				$neg = 0;
				$ant = 0;
				$halv = 0;
				$plus = ' ';
//	Båda har barnfamilj och dessutom samma familj (syskon)					
				if($fmcz != '') {
					if($fmc[$n2] != '') {
						if($fmcz == $fmc[$n2]) {
							$plus = '-';
							$neg++;
							$neg++;
						}		
					}
				}
//	Boost					
				if(($ifdz != '') && (strlen($ifdz) == 8)) {
					if($ifd[$n2] != '') {
						if($ifdz == $ifd[$n2]) {
							$bon++;
						}
					}
				}
				if($ifd1z != '') {
					if($ifd1[$n2] != '') {
						$ant++;
						if($ifd1z == $ifd1[$n2]) {
							$pos++;
//	Extra bonus
							$bon++;
						}
						else {
							$neg++;
							if($ifd1z < $ifd1[$n2]) {
								if(($ifd1z + 10) < $ifd1[$n2]) {
									$neg++;
									$neg++;
								}
							}	
							else {
								if(($ifd1[$n2] + 10) < $ifd1z) {
									$neg++;
									$neg++;
								}
							}
						}
					}
				}
//	Bryt om ett dödår är mindre än det andra födelseåret
				if($ifd1z != '') {
					if($idd1[$n2] != '') {
						if($idd1[$n2] < $ifd1z) {
							$neg++;
							$neg++;
							$neg++;
						}
					}
				}
				if($ifd1[$n2] != '') {
					if($idd1z != '') {
						if($idd1z < $ifd1[$n2]) {
							$neg++;
							$neg++;
							$neg++;
						}
					}
				}
//			
				if($neg < 3){
//						
//	Boost					
					if(($iddz != '') && (strlen($iddz) == 8)) {
						if($idd[$n2] != '') {
							if($iddz == $idd[$n2]) {
								$bon++;
							}
						}
					}
//						
					if($idd1z != '') {
						if($idd1[$n2] != '') {
							$ant++;
							if($idd1z == $idd1[$n2]) {
								$pos++;
//	Extra bonus
								$bon++;
							}
							else {
								$neg++;
								if($idd1z < $idd1[$n2]) {
									if(($idd1z + 10) < $idd1[$n2]) {
										$neg++;
										$neg++;
									}
								}	
								else {
									if(($idd1[$n2] + 10) < $idd1z) {
										$neg++;
										$neg++;
									}
								}
							}
						}
					}
//			
					if($neg < 3){
//						
//	Boost		
						$ntest = 0;
						if($ifnz != '') {
							if($ifn[$n2] != '') {
								if($ifnz == $ifn[$n2]) {
									$bon++;
									if($ifnz != $ifn1z) {
										$bon++;
									}	
									$ntest++;
								}
							}
						}
//						
						if($ifn1z != '') {
							if($ifn1[$n2] != '') {
								$ant++;
								if(($ifn1z == $ifn1[$n2]) || ($ifn1z == $ifn2[$n2]) || ($ifn1z == $ifn3[$n2])
								 || ($ifn1z == $ifn4[$n2]) || ($ifn1z == $ifn5[$n2]) ) {
									$pos++;
									$ntest++;
								}
							}	
						}
//						
						if($ifn2z != '') {
							if($ifn1[$n2] != '') {
								$ant++;
								if(($ifn2z == $ifn1[$n2]) || ($ifn2z == $ifn2[$n2]) || ($ifn2z == $ifn3[$n2])
								 || ($ifn2z == $ifn4[$n2]) || ($ifn2z == $ifn5[$n2]) ) {
									$pos++;
									$ntest++;
								}
							}
						}
//					
						if($ifn3z != '') {
							if($ifn1[$n2] != '') {
								$ant++;
								if(($ifn3z == $ifn1[$n2]) || ($ifn3z == $ifn2[$n2]) || ($ifn3z == $ifn3[$n2])
								 || ($ifn3z == $ifn4[$n2]) || ($ifn3z == $ifn5[$n2]) ) {
									$pos++;
									$ntest++;
								}
							}
						}
//					
						if($ifn4z != '') {
							if($ifn1[$n2] != '') {
								$ant++;
								if(($ifn4z == $ifn1[$n2]) || ($ifn4z == $ifn2[$n2]) || ($ifn4z == $ifn3[$n2])
								 || ($ifn4z == $ifn4[$n2]) || ($ifn4z == $ifn5[$n2]) ) {
									$pos++;
									$ntest++;
								}
							}
						}
//					
						if($ifn5z != '') {
							if($ifn1[$n2] != '') {
								$ant++;
								if(($ifn5z == $ifn1[$n2]) || ($ifn5z == $ifn2[$n2]) || ($ifn5z == $ifn3[$n2])
								 || ($ifn5z == $ifn4[$n2]) || ($ifn5z == $ifn5[$n2]) ) {
									$pos++;
									$ntest++;
								}
							}
						}
						if($ntest == 0) {
							$neg++;
							$neg++;
						}
//			
						if($neg < 3) {
//						
							if($ifd2z != '') {
								if($ifd2[$n2] != '') {
									$ant++;
									if($ifd2z == $ifd2[$n2]) {
										$pos++;
									}
									else {
										$neg++;
									}
								}
							}
//						
							if($ifd3z != '') {
								if($ifd3[$n2] != '') {
									$ant++;
									if($ifd3z == $ifd3[$n2]) {
										$pos++;
									}
									else {
										$neg++;
									}
								}
							}
//						
							if($ifpz != '') {
								if($ifp[$n2] != '') {
									$ant++;
									if($ifpz == $ifp[$n2]) {
										$pos++;
									}
									else {
										$neg++;
										$neg++;
									}
								}
							}
//			
							if($neg < 3) {
//						
								if($idd2z != '') {
									if($idd2[$n2] != '') {
										$ant++;
										if($idd2z == $idd2[$n2]) {
											$pos++;
										}
										else {
											$neg++;
										}
									}
								}
//						
								if($idd3z != '') {
									if($idd3[$n2] != '') {
										$ant++;
										if($idd3z == $idd3[$n2]) {
											$pos++;
										}
										else {
											$neg++;
										}
									}
								}
//						
								if($idpz != '') {
									if($idp[$n2] != '') {
										$ant++;
										if($idpz == $idp[$n2]) {
											$pos++;
										}
										else {
											$neg++;
											$halv++;
										}
									}
								}
//			
								if($neg < 3) {
//	Boost					
									$ntest = 0;
									if($ienz != '') {
										if($ien[$n2] != '') {
											if($ienz == $ien[$n2]) {
												$bon++;
												if($ienz != $ien1z) {
													$bon++;
												}	
												$ntest++;
											}
										}
									}
//						
									if($ien1z != '') {
										if($ien1[$n2] != '') {
											$ant++;
											if(($ien1z == $ien1[$n2]) || ($ien1z == $ien2[$n2]) || ($ien1z == $ien3[$n2])) {
												$pos++;
											}
											$ntest++;
										}
									}
//						
									if($ien2z != '') {
										if($ien1[$n2] != '') {
											$ant++;
											if(($ien2z == $ien1[$n2]) || ($ien2z == $ien2[$n2]) || ($ien2z == $ien3[$n2])) {
												$pos++;
											}
											$ntest++;
										}
									}
//						
									if($ien3z != '') {
										if($ien1[$n2] != '') {
											$ant++;
											if(($ien3z == $ien1[$n2]) || ($ien3z == $ien2[$n2]) || ($ien3z == $ien3[$n2])) {
												$pos++;
											}
											$ntest++;
										}
									}
									if($ntest == 0) {
										$halv++;
									}
//	Boost					
									if($kp1z != '') {
										if($kp1[$n2] != '') {
											if(($kp1z == $kp1[$n2]) && (strlen($ifdz) == 8)) {
												$bon++;
											}
										}
									}
//	Boost					
									if($kp2z != '') {
										if($kp2[$n2] != '') {
											if(($kp2z == $kp2[$n2]) && (strlen($iddz) == 8)) {
												$bon++;
											}
										}
									}
//	Boost					
									if($kp3z != '') {
										if($kp3[$n2] != '') {
											if($kp3z == $kp3[$n2]) {
												$bon++;
											}
										}
									}
//	Boost					
									if($kp4z != '') {
										if($kp4[$n2] != '') {
											if($kp4z == $kp4[$n2]) {
												$bon++;
											}
										}
									}
//	Boost					
									if($kp5z != '') {
										if($kp5[$n2] != '') {
											if($kp5z == $kp5[$n2]) {
												$bon++;
											}
										}
									}
//	Boost					
									if($kp6z != '') {
										if($kp6[$n2] != '') {
											if($kp6z == $kp6[$n2]) {
												$bon++;
											}
										}
									}
//	Tolka betydelsen av familj FAMC 
//					
//	Båda har barmfamilj men olika familjer
									if($fmcz != '') {
										if($fmc[$n2] != '') {
											if($fmcz != $fmc[$n2]) {
												$plus = '?';
												$halv++;
											}
										}
									}
//	En saknar barnfamilj (mor/dotter - far/som)					
									if($fmcz != '') {
										if($fmc[$n2] == '') {
											$plus = '+';
											$bon++;
										}
									}
//	En saknar barnfamilj (mor/dotter - far/som)					
									if($fmcz == '') {
										if($fmc[$n2] != '') {
											$plus = '+';
											$bon++;
										}
									}
								}
							}
						}
					}	
				}
				if($halv >= 2) {
					$neg++;
				}
//	
				if(($bon > 2) && ($pos > 2) && ($pos > $neg) && ($neg < 3) && ($ant > 2)) {
					$tmp = $pos/$ant;
					if($tmp >= 0.5) {
						$diff = $ant - $pos;
						$totp = $bon + $pos - $diff - $neg;
						if($plus == '+') {
							$totp = $totp + 3;
						}	
						if($plus == ' ') {
							$totp = $totp + 2;
						}	
						if($plus == '?') {
							$totp = $totp + 1;
						}	
						if($tmp >= 0.65) {
							$totp = $totp + 1;
						}	
						if($tmp >= 0.75) {
							$totp = $totp + 1;
						}	
						if($tmp >= 0.8) {
							$totp = $totp + 1;
						}	
						if($tmp >= 0.9) {
							$totp = $totp + 1;
						}	
						if($totp > 7) {
							if($totp <= 9) {
								$txtp = '0'.$totp;
							}
							else {
								$txtp = $totp;
							}
							$fellista[]=$txtp.":"
							.$numz.", ".$nradz.", ".$dradz.", ".$pradz.":"
							.$num[$n2].", ".$nrad[$n2].", ".$drad[$n2].", ".$prad[$n2];
							$kant++;
							$nyckel = $numz.';'.$num[$n2];
							$dublData[$nyckel] = $totp;
						}
						else {
							$nyckel = $numz.';'.$num[$n2];
							$dublData[$nyckel] = $totp;
						}
					}	
				}
//
				$n2++;
			}
			$n1++;
		}
//	
//	steg 3M avslutat	
//
//	Steg 4M startar	
//	
		$handle=fopen($filename,"r");
//	
		$n1 = $min;
		$nsist = 0;
		$znum = '';
		$kand = '';
		$birt = '';
		$deat = '';
		$ifnx = '';
		$ienx = '';
		$ifdx = '';
		$ifpx = '';
		$iddx = '';
		$idpx = '';
		$famc = '';
		$nradx = '';
		$dradx = '';
		$pradx = '';
		$dpref = '';
		$sexx = '';
		$fam = ''; 
		$ind = '';
		$head = 'ON';	
//	Läs in indatafilen				
		$lines = file($filename,FILE_IGNORE_NEW_LINES);
		foreach($lines as $radnummer => $str)
		{
//	Huvud börjar - läs tills första individ/relation
			if($head == 'ON') {
				$ztag = substr($str,0,3);
				if($ztag == '0 @') {
					$zlen = strlen($str);
					$zmax = 3;
					while($zmax <= $zlen) {
						$ztal = substr($str,$zmax,1);
						if($ztal == '@')  {
							if((substr($str,$zmax,5) == '@ IND') || (substr($str,$zmax,5) == '@ FAM')) {
								$head = 'OFF';
//							echo "Första individen/relationen = ".$str." <br/>";
							}
							$zmax = $zlen; 
						}	
						$zmax++;
					}
				}	
			}
//	Första individ/relation börjar	
			if($head == 'OFF')
			{
//	hitta idnummer för individ/relation
				$ztag = substr($str,0,3);
				if($ztag == '0 @') {
//	Ny post, ladda upp tidigare data
					$aar = 0;
					$aar = (int)substr($ifdx,0,4);
					if(($znum != '') && ($ind == 'J') && 
					($aar >= 1900) && ($sexx == 'M')) {
						$n1++;
						$num[$n1]=$znum;
						$ifn[$n1]=$ifnx;
						$ien[$n1]=$ienx;
						$ifd[$n1]=$ifdx;
						$ifp[$n1]=$ifpx;
						$idd[$n1]=$iddx;
						$idp[$n1]=$idpx;
						$fmc[$n1]=$famc;
						$nrad[$n1]=$nradx;
						$drad[$n1]=$dradx;
						$prad[$n1]=$pradx;
						$isex[$n1]=$sexx;
					}
					$ifnx = '';
					$ienx = '';
					$ifdx = '';
					$ifpx = '';
					$iddx = '';
					$idpx = '';
					$famc = '';
					$nradx = '';
					$dradx = '';
					$pradx = '';
					$dpref = '';
					$sexx = '';
//	Ny post, sök identitet
					$znum = '';
					$zlen = strlen($str);
					$zmax = 3;
					while($zmax <= $zlen) {
						$ztal = substr($str,$zmax,1);
						if($ztal == '@') {
							if(substr($str,$zmax,5) == '@ IND') {
								$ind = 'J';
								$fam = '';
							}
							elseif(substr($str,$zmax,5) == '@ FAM') {
								$ind = '';
								$fam = 'J';
							}
							else {
								$ind = '';
								$fam = '';
								}
						}	
						if($ztal != '@') {
							$znum = $znum.$ztal;
						}
						else {
							$zmax = $zlen;
							$zmax++;
						}
						$zmax++;
					}
				}
//		
//	Fortsätt samla data
				$tag1 = substr($str,0,1);
				$tagk = substr($str,2,3);
				$tagl = substr($str,2,4);
				$tag7 = substr($str,6,1);
				$tlen = strlen($str);
				$str = substr($str,7,($tlen-7));
//
				if($tag1 == '1') {
					$birt = '';
					$deat = '';
				}
//
				if($tagk == 'SEX') {
					$sexx = $tag7;
				}
				elseif($tagl == 'BIRT') {
					$birt = 'J';
				}
				elseif($tagk == 'CHR') {
					if(($ifdx == '') && ($ifpx == '')) {
						$birt = 'J';
//						echo "Enbart döpt <br/>";
					}	
				}
				elseif($tagl == 'DEAT') {
					$deat = 'J';
				}
				elseif($tagl == 'BURI') {
					if(($iddx == '') && ($idpx == '')) {
						$deat = 'J';
//						echo "Enbart begravd <br/>";
					}	
				}
				elseif($tagl == 'RGDF') {
					$ifnx = $str;
				}
				elseif($tagl == 'RGDE') {
					$ienx = $str;
				}
				elseif(($tagl == 'RGDD') && ($birt == 'J')) {
					$ifdx = $str;
				}
				elseif(($tagl == 'RGDP') && ($birt == 'J')) {
					$ifpx = $str;
				}
				elseif(($tagl == 'RGDD') && ($deat == 'J')) {
					$iddx = $str;
				}
				elseif(($tagl == 'RGDP') && ($deat == 'J')) {
					$idpx = $str;
				}
				elseif($tagl == 'FAMC') {
					$famc = $str;
				}
				elseif($tagl == 'NAME') {
					$nradx = $str;
				}
				elseif(($tagl == 'DATE') && ($birt == 'J')) {
					$tstx = $str; // fimpa ()
					$lend = strlen($str);
					$tstp = substr($str,0,1);
					if($tstp == '(') {
						$tstx = substr($str,1,$lend-2); }
					$dpref = 'f. ';
					$dradx = $dpref.$tstx;
				}
				elseif(($tagl == 'PLAC') && ($birt == 'J')) {
					if($dpref == '') {
						$dpref = 'f. ';
						$pradx = $dpref.$str;
					}
					else {
						$pradx = $str;
					}
				}
				elseif(($tagl == 'DATE') && ($deat == 'J') && ($dpref == '')) {
					$tstx = $str; // fimpa ()
					$lend = strlen($str);
					$tstp = substr($str,0,1);
					if($tstp == '(') {
						$tstx = substr($str,1,$lend-2); }
					$dpref = 'd. ';
					$dradx = $dpref.$tstx;
				}
				elseif(($tagl == 'PLAC') && ($deat == 'J')) {
					if($dpref == '') {
						$pradx = 'd. '.$str;
					}	
					if($dpref == 'd. ') {
						$pradx = $str;
					}	
				}
				else {
//	ointressant rad
				}
			}		
		}
		fclose($handle);
//
//		echo $n1." individer inlästa för bearbetning efter block 4.<br/>";
//		echo "<br/>";
//
	
//////////////////////////////////
		$max = $n1;
		$n1 = $min + 1;
//
		while($n1 <= $max) {
				$kp1[$n1]='';
				$kp2[$n1]='';
				if(($ifd[$n1] != '') && ($ifp[$n1] != '')); {
					$kp1[$n1]=$ifd[$n1].$ifp[$n1];
				}	
				if(($idd[$n1] != '') && ($idp[$n1] != '')); {
					$kp2[$n1]=$idd[$n1].$idp[$n1];
				}	
//						
				$flen = strlen($ifn[$n1]);		
				$ifn1[$n1] = '';
				$ifn2[$n1] = '';
				$ifn3[$n1] = '';
				$ifn4[$n1] = '';
				$ifn5[$n1] = '';
//			
				$fx = 0;
				while(($fx <= $flen) && (substr($ifn[$n1],$fx,1) != ',')) {
					$ifn1[$n1] = $ifn1[$n1].substr($ifn[$n1],$fx,1);
					$fx++;
				}
				$fx++;
				while(($fx <= $flen) && (substr($ifn[$n1],$fx,1) != ',')) {
					$ifn2[$n1] = $ifn2[$n1].substr($ifn[$n1],$fx,1);
					$fx++;
				}
				$fx++;
				while(($fx <= $flen) && (substr($ifn[$n1],$fx,1) != ',')) {
					$ifn3[$n1] = $ifn3[$n1].substr($ifn[$n1],$fx,1);
					$fx++;
				}
				$fx++;
				while(($fx <= $flen) && (substr($ifn[$n1],$fx,1) != ',')) {
					$ifn4[$n1] = $ifn4[$n1].substr($ifn[$n1],$fx,1);
					$fx++;
				}
				$fx++;
				if($fx <= $flen) {
					$ifn5[$n1] = substr($ifn[$n1],$fx,($flen-$fx));
				}
//						
				$elen = strlen($ien[$n1]);		
				$ien1[$n1] = '';
				$ien2[$n1] = '';
				$ien3[$n1] = '';
//
				$ex = 0;
				while(($ex <= $elen) && (substr($ien[$n1],$ex,1) != ',')) {
					$ien1[$n1] = $ien1[$n1].substr($ien[$n1],$ex,1);
					$ex++;
				}
				$ex++;
				while(($ex <= $elen) && (substr($ien[$n1],$ex,1) != ',')) {
					$ien2[$n1] = $ien2[$n1].substr($ien[$n1],$ex,1);
					$ex++;
				}
				$ex++;
				if($ex <= $elen) {
					$ien3[$n1] = substr($ien[$n1],$ex,($elen-$ex));
				}
//						
				$blen = strlen($ifd[$n1]);		
				$ifd1[$n1] = '';
				$ifd2[$n1] = '';
				$ifd3[$n1] = '';
//			
				$bx = 0;
				if(($bx < $blen) && (substr($ifd[$n1],$bx,4) != '')) {
					$ifd1[$n1] = substr($ifd[$n1],$bx,4);
				}
				$bx = 4;
				if(($bx <= $blen) && (substr($ifd[$n1],$bx,2) != '')) {
					$ifd2[$n1] = substr($ifd[$n1],$bx,2);
				}
				$bx = 6;
				if($bx <= $blen) {
					$ifd3[$n1] = substr($ifd[$n1],$bx,2);
				}	
//						
				$dlen = strlen($idd[$n1]);		
				$idd1[$n1] = '';
				$idd2[$n1] = '';
				$idd3[$n1] = '';
//			
				$dx = 0;
				if(($dx < $dlen) && (substr($idd[$n1],$dx,4) != '')) {
					$idd1[$n1] = substr($idd[$n1],$dx,4);
				}
				$dx = 4;
				if(($dx <= $dlen) && (substr($idd[$n1],$dx,2) != '')) {
					$idd2[$n1] = substr($idd[$n1],$dx,2);
				}
				$dx = 6;
				if($dx <= $dlen) {
					$idd3[$n1] = substr($idd[$n1],$dx,2);
				}	
				$kp3[$n1]='';
				$kp4[$n1]='';
				if(($ifd1[$n1] != '') && ($ifp[$n1] != '')); {
					$kp3[$n1]=$ifd1[$n1].$ifp[$n1];
				}	
				if(($idd1[$n1] != '') && ($idp[$n1] != '')); {
					$kp4[$n1]=$idd1[$n1].$idp[$n1];
				}	
				$kp5[$n1]='';
				$kp6[$n1]='';
				if(($ifd2[$n1] != '') && ($ifd3[$n1] != '') && ($ifp[$n1] != '')); {
					$kp5[$n1]=$ifd2[$n1].$ifd3[$n1].$ifp[$n1];
				}	
				if(($idd2[$n1] != '') && ($idd3[$n1] != '') && ($idp[$n1] != '')); {
					$kp6[$n1]=$idd2[$n1].$idd3[$n1].$idp[$n1];
				}
			$n1++;
		}			
//
///////////////////////////
//	Loop 1		
		$n1 = $min + 1;
		$min = $max;
		$nsist = $max - 1;
		while($n1 <= $nsist) {
//	Ladda z
			$numz = $num[$n1];
			$nradz = $nrad[$n1];
			$dradz = $drad[$n1];
			$pradz = $prad[$n1];
//
			$isexz = $isex[$n1];
			$fmcz = $fmc[$n1];
			$ifpz = $ifp[$n1];
			$ifdz = $ifd[$n1];
			$ifd1z = $ifd1[$n1];
			$ifd2z = $ifd2[$n1];
			$ifd3z = $ifd3[$n1];
			$ifnz = $ifn[$n1];
			$ifn1z = $ifn1[$n1];
			$ifn2z = $ifn2[$n1];
			$ifn3z = $ifn3[$n1];
			$ifn4z = $ifn4[$n1];
			$ifn5z = $ifn5[$n1];
			$idpz = $idp[$n1];
			$iddz = $idd[$n1];
			$idd1z = $idd1[$n1];
			$idd2z = $idd2[$n1];
			$idd3z = $idd3[$n1];
			$ienz = $ien[$n1];
			$ien1z = $ien1[$n1];
			$ien2z = $ien2[$n1];
			$ien3z = $ien3[$n1];
			$kp1z = $kp1[$n1];
			$kp2z = $kp2[$n1];
			$kp3z = $kp3[$n1];
			$kp4z = $kp4[$n1];
			$kp5z = $kp5[$n1];
			$kp6z = $kp6[$n1];
//		
//	Loop 2	
			$n2 = $n1 + 1;
			while($n2 <= $max) {
//	
				$bon = 0;
				$pos = 0;
				$neg = 0;
				$ant = 0;
				$halv = 0;
				$plus = ' ';
//	Båda har barnfamilj och dessutom samma familj (syskon)					
				if($fmcz != '') {
					if($fmc[$n2] != '') {
						if($fmcz == $fmc[$n2]) {
							$plus = '-';
							$neg++;
							$neg++;
						}		
					}
				}
//	Boost					
				if(($ifdz != '') && (strlen($ifdz) == 8)) {
					if($ifd[$n2] != '') {
						if($ifdz == $ifd[$n2]) {
							$bon++;
						}
					}
				}
				if($ifd1z != '') {
					if($ifd1[$n2] != '') {
						$ant++;
						if($ifd1z == $ifd1[$n2]) {
							$pos++;
//	Extra bonus
							$bon++;
						}
						else {
							$neg++;
							if($ifd1z < $ifd1[$n2]) {
								if(($ifd1z + 10) < $ifd1[$n2]) {
									$neg++;
									$neg++;
								}
							}	
							else {
								if(($ifd1[$n2] + 10) < $ifd1z) {
									$neg++;
									$neg++;
								}
							}
						}
					}
				}
//	Bryt om ett dödår är mindre än det andra födelseåret
				if($ifd1z != '') {
					if($idd1[$n2] != '') {
						if($idd1[$n2] < $ifd1z) {
							$neg++;
							$neg++;
							$neg++;
						}
					}
				}
				if($ifd1[$n2] != '') {
					if($idd1z != '') {
						if($idd1z < $ifd1[$n2]) {
							$neg++;
							$neg++;
							$neg++;
						}
					}
				}
//			
				if($neg < 3){
//						
//	Boost					
					if(($iddz != '') && (strlen($iddz) == 8)) {
						if($idd[$n2] != '') {
							if($iddz == $idd[$n2]) {
								$bon++;
							}
						}
					}
//						
					if($idd1z != '') {
						if($idd1[$n2] != '') {
							$ant++;
							if($idd1z == $idd1[$n2]) {
								$pos++;
//	Extra bonus
								$bon++;
							}
							else {
								$neg++;
								if($idd1z < $idd1[$n2]) {
									if(($idd1z + 10) < $idd1[$n2]) {
										$neg++;
										$neg++;
									}
								}	
								else {
									if(($idd1[$n2] + 10) < $idd1z) {
										$neg++;
										$neg++;
									}
								}
							}
						}
					}
//			
					if($neg < 3){
//						
//	Boost		
						$ntest = 0;
						if($ifnz != '') {
							if($ifn[$n2] != '') {
								if($ifnz == $ifn[$n2]) {
									$bon++;
									if($ifnz != $ifn1z) {
										$bon++;
									}	
									$ntest++;
								}
							}
						}
//						
						if($ifn1z != '') {
							if($ifn1[$n2] != '') {
								$ant++;
								if(($ifn1z == $ifn1[$n2]) || ($ifn1z == $ifn2[$n2]) || ($ifn1z == $ifn3[$n2])
								 || ($ifn1z == $ifn4[$n2]) || ($ifn1z == $ifn5[$n2]) ) {
									$pos++;
									$ntest++;
								}
							}	
						}
//						
						if($ifn2z != '') {
							if($ifn1[$n2] != '') {
								$ant++;
								if(($ifn2z == $ifn1[$n2]) || ($ifn2z == $ifn2[$n2]) || ($ifn2z == $ifn3[$n2])
								 || ($ifn2z == $ifn4[$n2]) || ($ifn2z == $ifn5[$n2]) ) {
									$pos++;
									$ntest++;
								}
							}
						}
//					
						if($ifn3z != '') {
							if($ifn1[$n2] != '') {
								$ant++;
								if(($ifn3z == $ifn1[$n2]) || ($ifn3z == $ifn2[$n2]) || ($ifn3z == $ifn3[$n2])
								 || ($ifn3z == $ifn4[$n2]) || ($ifn3z == $ifn5[$n2]) ) {
									$pos++;
									$ntest++;
								}
							}
						}
//					
						if($ifn4z != '') {
							if($ifn1[$n2] != '') {
								$ant++;
								if(($ifn4z == $ifn1[$n2]) || ($ifn4z == $ifn2[$n2]) || ($ifn4z == $ifn3[$n2])
								 || ($ifn4z == $ifn4[$n2]) || ($ifn4z == $ifn5[$n2]) ) {
									$pos++;
									$ntest++;
								}
							}
						}
//					
						if($ifn5z != '') {
							if($ifn1[$n2] != '') {
								$ant++;
								if(($ifn5z == $ifn1[$n2]) || ($ifn5z == $ifn2[$n2]) || ($ifn5z == $ifn3[$n2])
								 || ($ifn5z == $ifn4[$n2]) || ($ifn5z == $ifn5[$n2]) ) {
									$pos++;
									$ntest++;
								}
							}
						}
						if($ntest == 0) {
							$neg++;
							$neg++;
						}
//			
						if($neg < 3) {
//						
							if($ifd2z != '') {
								if($ifd2[$n2] != '') {
									$ant++;
									if($ifd2z == $ifd2[$n2]) {
										$pos++;
									}
									else {
										$neg++;
									}
								}
							}
//						
							if($ifd3z != '') {
								if($ifd3[$n2] != '') {
									$ant++;
									if($ifd3z == $ifd3[$n2]) {
										$pos++;
									}
									else {
										$neg++;
									}
								}
							}
//						
							if($ifpz != '') {
								if($ifp[$n2] != '') {
									$ant++;
									if($ifpz == $ifp[$n2]) {
										$pos++;
									}
									else {
										$neg++;
										$neg++;
									}
								}
							}
//			
							if($neg < 3) {
//						
								if($idd2z != '') {
									if($idd2[$n2] != '') {
										$ant++;
										if($idd2z == $idd2[$n2]) {
											$pos++;
										}
										else {
											$neg++;
										}
									}
								}
//						
								if($idd3z != '') {
									if($idd3[$n2] != '') {
										$ant++;
										if($idd3z == $idd3[$n2]) {
											$pos++;
										}
										else {
											$neg++;
										}
									}
								}
//						
								if($idpz != '') {
									if($idp[$n2] != '') {
										$ant++;
										if($idpz == $idp[$n2]) {
											$pos++;
										}
										else {
											$neg++;
											$halv++;
										}
									}
								}
//			
								if($neg < 3) {
//	Boost				
									$ntest = 0;
									if($ienz != '') {
										if($ien[$n2] != '') {
											if($ienz == $ien[$n2]) {
												$bon++;
												if($ienz != $ien1z) {
													$bon++;
												}	
												$ntest++;
											}
										}
									}
//						
									if($ien1z != '') {
										if($ien1[$n2] != '') {
											$ant++;
											if(($ien1z == $ien1[$n2]) || ($ien1z == $ien2[$n2]) || ($ien1z == $ien3[$n2])) {
												$pos++;
											}
											$ntest++;
										}
									}
//						
									if($ien2z != '') {
										if($ien1[$n2] != '') {
											$ant++;
											if(($ien2z == $ien1[$n2]) || ($ien2z == $ien2[$n2]) || ($ien2z == $ien3[$n2])) {
												$pos++;
											}
											$ntest++;
										}
									}
//						
									if($ien3z != '') {
										if($ien1[$n2] != '') {
											$ant++;
											if(($ien3z == $ien1[$n2]) || ($ien3z == $ien2[$n2]) || ($ien3z == $ien3[$n2])) {
												$pos++;
											}
											$ntest++;
										}
									}
									if($ntest == 0) {
										$halv++;
									}
//	Boost					
									if($kp1z != '') {
										if($kp1[$n2] != '') {
											if(($kp1z == $kp1[$n2]) && (strlen($ifdz) == 8)) {
												$bon++;
											}
										}
									}
//	Boost					
									if($kp2z != '') {
										if($kp2[$n2] != '') {
											if(($kp2z == $kp2[$n2]) && (strlen($iddz) == 8)) {
												$bon++;
											}
										}
									}
//	Boost					
									if($kp3z != '') {
										if($kp3[$n2] != '') {
											if($kp3z == $kp3[$n2]) {
												$bon++;
											}
										}
									}
//	Boost					
									if($kp4z != '') {
										if($kp4[$n2] != '') {
											if($kp4z == $kp4[$n2]) {
												$bon++;
											}
										}
									}
//	Boost					
									if($kp5z != '') {
										if($kp5[$n2] != '') {
											if($kp5z == $kp5[$n2]) {
												$bon++;
											}
										}
									}
//	Boost					
									if($kp6z != '') {
										if($kp6[$n2] != '') {
											if($kp6z == $kp6[$n2]) {
												$bon++;
											}
										}
									}
//	Tolka betydelsen av familj FAMC 
//					
//	Båda har barmfamilj men olika familjer
									if($fmcz != '') {
										if($fmc[$n2] != '') {
											if($fmcz != $fmc[$n2]) {
												$plus = '?';
												$halv++;
											}
										}
									}
//	En saknar barnfamilj (mor/dotter - far/som)					
									if($fmcz != '') {
										if($fmc[$n2] == '') {
											$plus = '+';
											$bon++;
										}
									}
//	En saknar barnfamilj (mor/dotter - far/som)					
									if($fmcz == '') {
										if($fmc[$n2] != '') {
											$plus = '+';
											$bon++;
										}
									}
								}
							}
						}
					}	
				}
				if($halv >= 2) {
					$neg++;
				}
//	
				if(($bon > 2) && ($pos > 2) && ($pos > $neg) && ($neg < 3) && ($ant > 2)) {
					$tmp = $pos/$ant;
					if($tmp >= 0.5) {
						$diff = $ant - $pos;
						$totp = $bon + $pos - $diff - $neg;
						if($plus == '+') {
							$totp = $totp + 3;
						}	
						if($plus == ' ') {
							$totp = $totp + 2;
						}	
						if($plus == '?') {
							$totp = $totp + 1;
						}	
						if($tmp >= 0.65) {
							$totp = $totp + 1;
						}	
						if($tmp >= 0.75) {
							$totp = $totp + 1;
						}	
						if($tmp >= 0.8) {
							$totp = $totp + 1;
						}	
						if($tmp >= 0.9) {
							$totp = $totp + 1;
						}	
						if($totp > 7) {
							if($totp <= 9) {
								$txtp = '0'.$totp;
							}
							else {
								$txtp = $totp;
							}
							$fellista[]=$txtp.":"
							.$numz.", ".$nradz.", ".$dradz.", ".$pradz.":"
							.$num[$n2].", ".$nrad[$n2].", ".$drad[$n2].", ".$prad[$n2];
							$kant++;
							$nyckel = $numz.';'.$num[$n2];
							$dublData[$nyckel] = $totp;
						}
						else {
							$nyckel = $numz.';'.$num[$n2];
							$dublData[$nyckel] = $totp;
						}
					}	
				}
//
				$n2++;
			}
			$n1++;
		}
//	
//	steg 4M avslutat	
//
//	
//	Steg 1F startar	
//	
		$handle=fopen($filename,"r");
//	
		$n1 = $min;
		$nsist = 0;
		$znum = '';
		$kand = '';
		$birt = '';
		$deat = '';
		$ifnx = '';
		$ienx = '';
		$ifdx = '';
		$ifpx = '';
		$iddx = '';
		$idpx = '';
		$famc = '';
		$nradx = '';
		$dradx = '';
		$pradx = '';
		$dpref = '';
		$sexx = '';
		$fam = ''; 
		$ind = '';
		$head = 'ON';	
//	Läs in indatafilen				
		$lines = file($filename,FILE_IGNORE_NEW_LINES);
		foreach($lines as $radnummer => $str)
		{
//	Huvud börjar - läs tills första individ/relation
			if($head == 'ON') {
				$ztag = substr($str,0,3);
				if($ztag == '0 @') {
					$zlen = strlen($str);
					$zmax = 3;
					while($zmax <= $zlen) {
						$ztal = substr($str,$zmax,1);
						if($ztal == '@')  {
							if((substr($str,$zmax,5) == '@ IND') || (substr($str,$zmax,5) == '@ FAM')) {
								$head = 'OFF';
//							echo "Första individen/relationen = ".$str." <br/>";
							}
							$zmax = $zlen; 
						}	
						$zmax++;
					}
				}	
			}
//	Första individ/relation börjar	
			if($head == 'OFF')
			{
//	hitta idnummer för individ/relation
				$ztag = substr($str,0,3);
				if($ztag == '0 @') {
//	Ny post, ladda upp tidigare data
					$aar = 0;
					$aar = (int)substr($ifdx,0,4);
					if(($znum != '') && ($ind == 'J') && 
					($aar <= 1799) && ($sexx == 'F')) {
						$n1++;
						$num[$n1]=$znum;
						$ifn[$n1]=$ifnx;
						$ien[$n1]=$ienx;
						$ifd[$n1]=$ifdx;
						$ifp[$n1]=$ifpx;
						$idd[$n1]=$iddx;
						$idp[$n1]=$idpx;
						$fmc[$n1]=$famc;
						$nrad[$n1]=$nradx;
						$drad[$n1]=$dradx;
						$prad[$n1]=$pradx;
						$isex[$n1]=$sexx;
					}
					$ifnx = '';
					$ienx = '';
					$ifdx = '';
					$ifpx = '';
					$iddx = '';
					$idpx = '';
					$famc = '';
					$nradx = '';
					$dradx = '';
					$pradx = '';
					$dpref = '';
					$sexx = '';
//	Ny post, sök identitet
					$znum = '';
					$zlen = strlen($str);
					$zmax = 3;
					while($zmax <= $zlen) {
						$ztal = substr($str,$zmax,1);
						if($ztal == '@') {
							if(substr($str,$zmax,5) == '@ IND') {
								$ind = 'J';
								$fam = '';
								}
							elseif(substr($str,$zmax,5) == '@ FAM') {
								$ind = '';
								$fam = 'J';
							}
							else {
								$ind = '';
								$fam = '';
								}
						}	
						if($ztal != '@') {
							$znum = $znum.$ztal;
						}
						else {
							$zmax = $zlen;
							$zmax++;
						}
						$zmax++;
					}
				}
//		
//	Fortsätt samla data
				$tag1 = substr($str,0,1);
				$tagk = substr($str,2,3);
				$tagl = substr($str,2,4);
				$tag7 = substr($str,6,1);
				$tlen = strlen($str);
				$str = substr($str,7,($tlen-7));
//
				if($tag1 == '1') {
					$birt = '';
					$deat = '';
				}
//
				if($tagk == 'SEX') {
					$sexx = $tag7;
				}
				elseif($tagl == 'BIRT') {
					$birt = 'J';
				}
				elseif($tagk == 'CHR') {
					if(($ifdx == '') && ($ifpx == '')) {
						$birt = 'J';
//						echo "Enbart döpt <br/>";
					}	
				}
				elseif($tagl == 'DEAT') {
					$deat = 'J';
				}
				elseif($tagl == 'BURI') {
					if(($iddx == '') && ($idpx == '')) {
						$deat = 'J';
//						echo "Enbart begravd <br/>";
					}	
				}
				elseif($tagl == 'RGDF') {
					$ifnx = $str;
				}
				elseif($tagl == 'RGDE') {
					$ienx = $str;
				}
				elseif(($tagl == 'RGDD') && ($birt == 'J')) {
					$ifdx = $str;
				}
				elseif(($tagl == 'RGDP') && ($birt == 'J')) {
					$ifpx = $str;
				}
				elseif(($tagl == 'RGDD') && ($deat == 'J')) {
					$iddx = $str;
				}
				elseif(($tagl == 'RGDP') && ($deat == 'J')) {
					$idpx = $str;
				}
				elseif($tagl == 'FAMC') {
					$famc = $str;
				}
				elseif($tagl == 'NAME') {
					$nradx = $str;
				}
				elseif(($tagl == 'DATE') && ($birt == 'J')) {
					$tstx = $str; // fimpa ()
					$lend = strlen($str);
					$tstp = substr($str,0,1);
					if($tstp == '(') {
						$tstx = substr($str,1,$lend-2); }
					$dpref = 'f. ';
					$dradx = $dpref.$tstx;
				}
				elseif(($tagl == 'PLAC') && ($birt == 'J')) {
					if($dpref == '') {
						$dpref = 'f. ';
						$pradx = $dpref.$str;
					}
					else {
						$pradx = $str;
					}
				}
				elseif(($tagl == 'DATE') && ($deat == 'J') && ($dpref == '')) {
					$tstx = $str; // fimpa ()
					$lend = strlen($str);
					$tstp = substr($str,0,1);
					if($tstp == '(') {
						$tstx = substr($str,1,$lend-2); }
					$dpref = 'd. ';
					$dradx = $dpref.$tstx;
				}
				elseif(($tagl == 'PLAC') && ($deat == 'J')) {
					if($dpref == '') {
						$pradx = 'd. '.$str;
					}	
					if($dpref == 'd. ') {
						$pradx = $str;
					}	
				}
				else {
//	ointressant rad
				}
			}		
		}
		fclose($handle);
//
//		echo $n1." individer inlästa för bearbetning efter block 1.<br/>";
//		echo "<br/>";
//	
//////////////////////////////////
		$max = $n1;
		$n1 = $min + 1;
//
		while($n1 <= $max) {
				$kp1[$n1]='';
				$kp2[$n1]='';
				if(($ifd[$n1] != '') && ($ifp[$n1] != '')); {
					$kp1[$n1]=$ifd[$n1].$ifp[$n1];
				}	
				if(($idd[$n1] != '') && ($idp[$n1] != '')); {
					$kp2[$n1]=$idd[$n1].$idp[$n1];
				}	
//						
				$flen = strlen($ifn[$n1]);		
				$ifn1[$n1] = '';
				$ifn2[$n1] = '';
				$ifn3[$n1] = '';
				$ifn4[$n1] = '';
				$ifn5[$n1] = '';
//			
				$fx = 0;
				while(($fx <= $flen) && (substr($ifn[$n1],$fx,1) != ',')) {
					$ifn1[$n1] = $ifn1[$n1].substr($ifn[$n1],$fx,1);
					$fx++;
				}
				$fx++;
				while(($fx <= $flen) && (substr($ifn[$n1],$fx,1) != ',')) {
					$ifn2[$n1] = $ifn2[$n1].substr($ifn[$n1],$fx,1);
					$fx++;
				}
				$fx++;
				while(($fx <= $flen) && (substr($ifn[$n1],$fx,1) != ',')) {
					$ifn3[$n1] = $ifn3[$n1].substr($ifn[$n1],$fx,1);
					$fx++;
				}
				$fx++;
				while(($fx <= $flen) && (substr($ifn[$n1],$fx,1) != ',')) {
					$ifn4[$n1] = $ifn4[$n1].substr($ifn[$n1],$fx,1);
					$fx++;
				}
				$fx++;
				if($fx <= $flen) {
					$ifn5[$n1] = substr($ifn[$n1],$fx,($flen-$fx));
				}
//						
				$elen = strlen($ien[$n1]);		
				$ien1[$n1] = '';
				$ien2[$n1] = '';
				$ien3[$n1] = '';
//
				$ex = 0;
				while(($ex <= $elen) && (substr($ien[$n1],$ex,1) != ',')) {
					$ien1[$n1] = $ien1[$n1].substr($ien[$n1],$ex,1);
					$ex++;
				}
				$ex++;
				while(($ex <= $elen) && (substr($ien[$n1],$ex,1) != ',')) {
					$ien2[$n1] = $ien2[$n1].substr($ien[$n1],$ex,1);
					$ex++;
				}
				$ex++;
				if($ex <= $elen) {
					$ien3[$n1] = substr($ien[$n1],$ex,($elen-$ex));
				}
//						
				$blen = strlen($ifd[$n1]);		
				$ifd1[$n1] = '';
				$ifd2[$n1] = '';
				$ifd3[$n1] = '';
//			
				$bx = 0;
				if(($bx < $blen) && (substr($ifd[$n1],$bx,4) != '')) {
					$ifd1[$n1] = substr($ifd[$n1],$bx,4);
				}
				$bx = 4;
				if(($bx <= $blen) && (substr($ifd[$n1],$bx,2) != '')) {
					$ifd2[$n1] = substr($ifd[$n1],$bx,2);
				}
				$bx = 6;
				if($bx <= $blen) {
					$ifd3[$n1] = substr($ifd[$n1],$bx,2);
				}	
//						
				$dlen = strlen($idd[$n1]);		
				$idd1[$n1] = '';
				$idd2[$n1] = '';
				$idd3[$n1] = '';
//			
				$dx = 0;
				if(($dx < $dlen) && (substr($idd[$n1],$dx,4) != '')) {
					$idd1[$n1] = substr($idd[$n1],$dx,4);
				}
				$dx = 4;
				if(($dx <= $dlen) && (substr($idd[$n1],$dx,2) != '')) {
					$idd2[$n1] = substr($idd[$n1],$dx,2);
				}
				$dx = 6;
				if($dx <= $dlen) {
					$idd3[$n1] = substr($idd[$n1],$dx,2);
				}	
				$kp3[$n1]='';
				$kp4[$n1]='';
				if(($ifd1[$n1] != '') && ($ifp[$n1] != '')); {
					$kp3[$n1]=$ifd1[$n1].$ifp[$n1];
				}	
				if(($idd1[$n1] != '') && ($idp[$n1] != '')); {
					$kp4[$n1]=$idd1[$n1].$idp[$n1];
				}	
				$kp5[$n1]='';
				$kp6[$n1]='';
				if(($ifd2[$n1] != '') && ($ifd3[$n1] != '') && ($ifp[$n1] != '')); {
					$kp5[$n1]=$ifd2[$n1].$ifd3[$n1].$ifp[$n1];
				}	
				if(($idd2[$n1] != '') && ($idd3[$n1] != '') && ($idp[$n1] != '')); {
					$kp6[$n1]=$idd2[$n1].$idd3[$n1].$idp[$n1];
				}
			$n1++;
		}			
//
///////////////////////////
//	Loop 1		
		$n1 = $min + 1;
		$min = $max;
		$nsist = $max - 1;
		while($n1 <= $nsist) {
//	Ladda z
			$numz = $num[$n1];
			$nradz = $nrad[$n1];
			$dradz = $drad[$n1];
			$pradz = $prad[$n1];
//
			$isexz = $isex[$n1];
			$fmcz = $fmc[$n1];
			$ifpz = $ifp[$n1];
			$ifdz = $ifd[$n1];
			$ifd1z = $ifd1[$n1];
			$ifd2z = $ifd2[$n1];
			$ifd3z = $ifd3[$n1];
			$ifnz = $ifn[$n1];
			$ifn1z = $ifn1[$n1];
			$ifn2z = $ifn2[$n1];
			$ifn3z = $ifn3[$n1];
			$ifn4z = $ifn4[$n1];
			$ifn5z = $ifn5[$n1];
			$idpz = $idp[$n1];
			$iddz = $idd[$n1];
			$idd1z = $idd1[$n1];
			$idd2z = $idd2[$n1];
			$idd3z = $idd3[$n1];
			$ienz = $ien[$n1];
			$ien1z = $ien1[$n1];
			$ien2z = $ien2[$n1];
			$ien3z = $ien3[$n1];
			$kp1z = $kp1[$n1];
			$kp2z = $kp2[$n1];
			$kp3z = $kp3[$n1];
			$kp4z = $kp4[$n1];
			$kp5z = $kp5[$n1];
			$kp6z = $kp6[$n1];
//		
//	Loop 2	
			$n2 = $n1 + 1;
			while($n2 <= $max) {
//	
				$bon = 0;
				$pos = 0;
				$neg = 0;
				$ant = 0;
				$halv = 0;
				$plus = ' ';
//	Båda har barnfamilj och dessutom samma familj (syskon)					
				if($fmcz != '') {
					if($fmc[$n2] != '') {
						if($fmcz == $fmc[$n2]) {
							$plus = '-';
							$neg++;
							$neg++;
						}		
					}
				}
//	Boost					
				if(($ifdz != '') && (strlen($ifdz) == 8)) {
					if($ifd[$n2] != '') {
						if($ifdz == $ifd[$n2]) {
							$bon++;
						}
					}
				}
				if($ifd1z != '') {
					if($ifd1[$n2] != '') {
						$ant++;
						if($ifd1z == $ifd1[$n2]) {
							$pos++;
//	Extra bonus
							$bon++;
						}
						else {
							$neg++;
							if($ifd1z < $ifd1[$n2]) {
								if(($ifd1z + 10) < $ifd1[$n2]) {
									$neg++;
									$neg++;
								}
							}	
							else {
								if(($ifd1[$n2] + 10) < $ifd1z) {
									$neg++;
									$neg++;
								}
							}
						}
					}
				}
//	Bryt om ett dödår är mindre än det andra födelseåret
				if($ifd1z != '') {
					if($idd1[$n2] != '') {
						if($idd1[$n2] < $ifd1z) {
							$neg++;
							$neg++;
							$neg++;
						}
					}
				}
				if($ifd1[$n2] != '') {
					if($idd1z != '') {
						if($idd1z < $ifd1[$n2]) {
							$neg++;
							$neg++;
							$neg++;
						}
					}
				}
//			
				if($neg < 3){
//						
//	Boost					
					if(($iddz != '') && (strlen($iddz) == 8)) {
						if($idd[$n2] != '') {
							if($iddz == $idd[$n2]) {
								$bon++;
							}
						}
					}
//						
					if($idd1z != '') {
						if($idd1[$n2] != '') {
							$ant++;
							if($idd1z == $idd1[$n2]) {
								$pos++;
//	Extra bonus
								$bon++;
							}
							else {
								$neg++;
								if($idd1z < $idd1[$n2]) {
									if(($idd1z + 10) < $idd1[$n2]) {
										$neg++;
										$neg++;
									}
								}	
								else {
									if(($idd1[$n2] + 10) < $idd1z) {
										$neg++;
										$neg++;
									}
								}
							}
						}
					}
//			
					if($neg < 3){
//						
//	Boost		
						$ntest = 0;
						if($ifnz != '') {
							if($ifn[$n2] != '') {
								if($ifnz == $ifn[$n2]) {
									$bon++;
									if($ifnz != $ifn1z) {
										$bon++;
									}	
									$ntest++;
								}
							}
						}
//						
						if($ifn1z != '') {
							if($ifn1[$n2] != '') {
								$ant++;
								if(($ifn1z == $ifn1[$n2]) || ($ifn1z == $ifn2[$n2]) || ($ifn1z == $ifn3[$n2])
								 || ($ifn1z == $ifn4[$n2]) || ($ifn1z == $ifn5[$n2]) ) {
									$pos++;
									$ntest++;
								}
							}	
						}
//						
						if($ifn2z != '') {
							if($ifn1[$n2] != '') {
								$ant++;
								if(($ifn2z == $ifn1[$n2]) || ($ifn2z == $ifn2[$n2]) || ($ifn2z == $ifn3[$n2])
								 || ($ifn2z == $ifn4[$n2]) || ($ifn2z == $ifn5[$n2]) ) {
									$pos++;
									$ntest++;
								}
							}
						}
//					
						if($ifn3z != '') {
							if($ifn1[$n2] != '') {
								$ant++;
								if(($ifn3z == $ifn1[$n2]) || ($ifn3z == $ifn2[$n2]) || ($ifn3z == $ifn3[$n2])
								 || ($ifn3z == $ifn4[$n2]) || ($ifn3z == $ifn5[$n2]) ) {
									$pos++;
									$ntest++;
								}
							}
						}
//					
						if($ifn4z != '') {
							if($ifn1[$n2] != '') {
								$ant++;
								if(($ifn4z == $ifn1[$n2]) || ($ifn4z == $ifn2[$n2]) || ($ifn4z == $ifn3[$n2])
								 || ($ifn4z == $ifn4[$n2]) || ($ifn4z == $ifn5[$n2]) ) {
									$pos++;
									$ntest++;
								}
							}
						}
//					
						if($ifn5z != '') {
							if($ifn1[$n2] != '') {
								$ant++;
								if(($ifn5z == $ifn1[$n2]) || ($ifn5z == $ifn2[$n2]) || ($ifn5z == $ifn3[$n2])
								 || ($ifn5z == $ifn4[$n2]) || ($ifn5z == $ifn5[$n2]) ) {
									$pos++;
									$ntest++;
								}
							}
						}
						if($ntest == 0) {
							$neg++;
							$neg++;
						}
//			
						if($neg < 3) {
//						
							if($ifd2z != '') {
								if($ifd2[$n2] != '') {
									$ant++;
									if($ifd2z == $ifd2[$n2]) {
										$pos++;
									}
									else {
										$neg++;
									}
								}
							}
//						
							if($ifd3z != '') {
								if($ifd3[$n2] != '') {
									$ant++;
									if($ifd3z == $ifd3[$n2]) {
										$pos++;
									}
									else {
										$neg++;
									}
								}
							}
//						
							if($ifpz != '') {
								if($ifp[$n2] != '') {
									$ant++;
									if($ifpz == $ifp[$n2]) {
										$pos++;
									}
									else {
										$neg++;
										$neg++;
									}
								}
							}
//			
							if($neg < 3) {
//						
								if($idd2z != '') {
									if($idd2[$n2] != '') {
										$ant++;
										if($idd2z == $idd2[$n2]) {
											$pos++;
										}
										else {
											$neg++;
										}
									}
								}
//						
								if($idd3z != '') {
									if($idd3[$n2] != '') {
										$ant++;
										if($idd3z == $idd3[$n2]) {
											$pos++;
										}
										else {
											$neg++;
										}
									}
								}
//						
								if($idpz != '') {
									if($idp[$n2] != '') {
										$ant++;
										if($idpz == $idp[$n2]) {
											$pos++;
										}
										else {
											$neg++;
											$halv++;
										}
									}
								}
//			
								if($neg < 3) {
//	Boost					
									$ntest = 0;
									if($ienz != '') {
										if($ien[$n2] != '') {
											if($ienz == $ien[$n2]) {
												$bon++;
												if($ienz != $ien1z) {
													$bon++;
												}	
												$ntest++;
											}
										}
									}
//						
									if($ien1z != '') {
										if($ien1[$n2] != '') {
											$ant++;
											if(($ien1z == $ien1[$n2]) || ($ien1z == $ien2[$n2]) || ($ien1z == $ien3[$n2])) {
												$pos++;
											}
											$ntest++;
										}
									}
//						
									if($ien2z != '') {
										if($ien1[$n2] != '') {
											$ant++;
											if(($ien2z == $ien1[$n2]) || ($ien2z == $ien2[$n2]) || ($ien2z == $ien3[$n2])) {
												$pos++;
											}
											$ntest++;
										}
									}
//						
									if($ien3z != '') {
										if($ien1[$n2] != '') {
											$ant++;
											if(($ien3z == $ien1[$n2]) || ($ien3z == $ien2[$n2]) || ($ien3z == $ien3[$n2])) {
												$pos++;
											}
											$ntest++;
										}
									}
									if($ntest == 0) {
										$halv++;
									}
//	Boost					
									if($kp1z != '') {
										if($kp1[$n2] != '') {
											if(($kp1z == $kp1[$n2]) && (strlen($ifdz) == 8)) {
												$bon++;
											}
										}
									}
//	Boost					
									if($kp2z != '') {
										if($kp2[$n2] != '') {
											if(($kp2z == $kp2[$n2]) && (strlen($iddz) == 8)) {
												$bon++;
											}
										}
									}
//	Boost					
									if($kp3z != '') {
										if($kp3[$n2] != '') {
											if($kp3z == $kp3[$n2]) {
												$bon++;
											}
										}
									}
//	Boost					
									if($kp4z != '') {
										if($kp4[$n2] != '') {
											if($kp4z == $kp4[$n2]) {
												$bon++;
											}
										}
									}
//	Boost					
									if($kp5z != '') {
										if($kp5[$n2] != '') {
											if($kp5z == $kp5[$n2]) {
												$bon++;
											}
										}
									}
//	Boost					
									if($kp6z != '') {
										if($kp6[$n2] != '') {
											if($kp6z == $kp6[$n2]) {
												$bon++;
											}
										}
									}
//	Tolka betydelsen av familj FAMC 
//					
//	Båda har barmfamilj men olika familjer
									if($fmcz != '') {
										if($fmc[$n2] != '') {
											if($fmcz != $fmc[$n2]) {
												$plus = '?';
												$halv++;
											}
										}
									}
//	En saknar barnfamilj (mor/dotter - far/som)					
									if($fmcz != '') {
										if($fmc[$n2] == '') {
											$plus = '+';
											$bon++;
										}
									}
//	En saknar barnfamilj (mor/dotter - far/som)					
									if($fmcz == '') {
										if($fmc[$n2] != '') {
											$plus = '+';
											$bon++;
										}
									}
								}
							}
						}
					}	
				}
				if($halv >= 2) {
					$neg++;
				}
//	
				if(($bon > 2) && ($pos > 2) && ($pos > $neg) && ($neg < 3) && ($ant > 2)) {
					$tmp = $pos/$ant;
					if($tmp >= 0.5) {
						$diff = $ant - $pos;
						$totp = $bon + $pos - $diff - $neg;
						if($plus == '+') {
							$totp = $totp + 3;
						}	
						if($plus == ' ') {
							$totp = $totp + 2;
						}	
						if($plus == '?') {
							$totp = $totp + 1;
						}	
						if($tmp >= 0.65) {
							$totp = $totp + 1;
						}	
						if($tmp >= 0.75) {
							$totp = $totp + 1;
						}	
						if($tmp >= 0.8) {
							$totp = $totp + 1;
						}	
						if($tmp >= 0.9) {
							$totp = $totp + 1;
						}	
						if($totp > 7) {
							if($totp <= 9) {
								$txtp = '0'.$totp;
							}
							else {
								$txtp = $totp;
							}
							$fellista[]=$txtp.":"
							.$numz.", ".$nradz.", ".$dradz.", ".$pradz.":"
							.$num[$n2].", ".$nrad[$n2].", ".$drad[$n2].", ".$prad[$n2];
							$kant++;
							$nyckel = $numz.';'.$num[$n2];
							$dublData[$nyckel] = $totp;
						}
						else {
							$nyckel = $numz.';'.$num[$n2];
							$dublData[$nyckel] = $totp;
						}
					}	
				}
//
				$n2++;
			}
			$n1++;
		}
//	
//	steg 1F avslutat	
//	
//	Steg 2F startar	
//	
		$handle=fopen($filename,"r");
//	
		$n1 = $min;
		$nsist = 0;
		$znum = '';
		$kand = '';
		$birt = '';
		$deat = '';
		$ifnx = '';
		$ienx = '';
		$ifdx = '';
		$ifpx = '';
		$iddx = '';
		$idpx = '';
		$famc = '';
		$nradx = '';
		$dradx = '';
		$pradx = '';
		$dpref = '';
		$sexx = '';
		$fam = ''; 
		$ind = '';
		$head = 'ON';	
//	Läs in indatafilen				
		$lines = file($filename,FILE_IGNORE_NEW_LINES);
		foreach($lines as $radnummer => $str)
		{
//	Huvud börjar - läs tills första individ/relation
			if($head == 'ON') {
				$ztag = substr($str,0,3);
				if($ztag == '0 @') {
					$zlen = strlen($str);
					$zmax = 3;
					while($zmax <= $zlen) {
						$ztal = substr($str,$zmax,1);
						if($ztal == '@')  {
							if((substr($str,$zmax,5) == '@ IND') || (substr($str,$zmax,5) == '@ FAM')) {
								$head = 'OFF';
//							echo "Första individen/relationen = ".$str." <br/>";
							}
							$zmax = $zlen; 
						}	
						$zmax++;
					}
				}	
			}
//	Första individ/relation börjar	
			if($head == 'OFF')
			{
//	hitta idnummer för individ/relation
				$ztag = substr($str,0,3);
				if($ztag == '0 @') {
//	Ny post, ladda upp tidigare data
					$aar = 0;
					$aar = (int)substr($ifdx,0,4);
					if(($znum != '') && ($ind == 'J') && 
					($aar >= 1800) && ($aar <= 1849) && ($sexx == 'F')) {
						$n1++;
						$num[$n1]=$znum;
						$ifn[$n1]=$ifnx;
						$ien[$n1]=$ienx;
						$ifd[$n1]=$ifdx;
						$ifp[$n1]=$ifpx;
						$idd[$n1]=$iddx;
						$idp[$n1]=$idpx;
						$fmc[$n1]=$famc;
						$nrad[$n1]=$nradx;
						$drad[$n1]=$dradx;
						$prad[$n1]=$pradx;
						$isex[$n1]=$sexx;
					}
					$ifnx = '';
					$ienx = '';
					$ifdx = '';
					$ifpx = '';
					$iddx = '';
					$idpx = '';
					$famc = '';
					$nradx = '';
					$dradx = '';
					$pradx = '';
					$dpref = '';
					$sexx = '';
//	Ny post, sök identitet
					$znum = '';
					$zlen = strlen($str);
					$zmax = 3;
					while($zmax <= $zlen) {
						$ztal = substr($str,$zmax,1);
						if($ztal == '@') {
							if(substr($str,$zmax,5) == '@ IND') {
								$ind = 'J';
								$fam = '';
								}
							elseif(substr($str,$zmax,5) == '@ FAM') {
								$ind = '';
								$fam = 'J';
							}
							else {
								$ind = '';
								$fam = '';
								}
						}	
						if($ztal != '@') {
							$znum = $znum.$ztal;
						}
						else {
							$zmax = $zlen;
							$zmax++;
						}
						$zmax++;
					}
				}
//		
//	Fortsätt samla data
				$tag1 = substr($str,0,1);
				$tagk = substr($str,2,3);
				$tagl = substr($str,2,4);
				$tag7 = substr($str,6,1);
				$tlen = strlen($str);
				$str = substr($str,7,($tlen-7));
//
				if($tag1 == '1') {
					$birt = '';
					$deat = '';
				}
//
				if($tagk == 'SEX') {
					$sexx = $tag7;
				}
				elseif($tagl == 'BIRT') {
					$birt = 'J';
				}
				elseif($tagk == 'CHR') {
					if(($ifdx == '') && ($ifpx == '')) {
						$birt = 'J';
//						echo "Enbart döpt <br/>";
					}	
				}
				elseif($tagl == 'DEAT') {
					$deat = 'J';
				}
				elseif($tagl == 'BURI') {
					if(($iddx == '') && ($idpx == '')) {
						$deat = 'J';
//						echo "Enbart begravd <br/>";
					}	
				}
				elseif($tagl == 'RGDF') {
					$ifnx = $str;
				}
				elseif($tagl == 'RGDE') {
					$ienx = $str;
				}
				elseif(($tagl == 'RGDD') && ($birt == 'J')) {
					$ifdx = $str;
				}
				elseif(($tagl == 'RGDP') && ($birt == 'J')) {
					$ifpx = $str;
				}
				elseif(($tagl == 'RGDD') && ($deat == 'J')) {
					$iddx = $str;
				}
				elseif(($tagl == 'RGDP') && ($deat == 'J')) {
					$idpx = $str;
				}
				elseif($tagl == 'FAMC') {
					$famc = $str;
				}
				elseif($tagl == 'NAME') {
					$nradx = $str;
				}
				elseif(($tagl == 'DATE') && ($birt == 'J')) {
					$tstx = $str; // fimpa ()
					$lend = strlen($str);
					$tstp = substr($str,0,1);
					if($tstp == '(') {
						$tstx = substr($str,1,$lend-2); }
					$dpref = 'f. ';
					$dradx = $dpref.$tstx;
				}
				elseif(($tagl == 'PLAC') && ($birt == 'J')) {
					if($dpref == '') {
						$dpref = 'f. ';
						$pradx = $dpref.$str;
					}
					else {
						$pradx = $str;
					}
				}
				elseif(($tagl == 'DATE') && ($deat == 'J') && ($dpref == '')) {
					$tstx = $str; // fimpa ()
					$lend = strlen($str);
					$tstp = substr($str,0,1);
					if($tstp == '(') {
						$tstx = substr($str,1,$lend-2); }
					$dpref = 'd. ';
					$dradx = $dpref.$tstx;
				}
				elseif(($tagl == 'PLAC') && ($deat == 'J')) {
					if($dpref == '') {
						$pradx = 'd. '.$str;
					}	
					if($dpref == 'd. ') {
						$pradx = $str;
					}	
				}
				else {
//	ointressant rad
				}
			}		
		}
		fclose($handle);
//
//		echo $n1." individer inlästa för bearbetning efter block 2.<br/>";
//		echo "<br/>";
//
	
//////////////////////////////////
		$max = $n1;
		$n1 = $min + 1;
//
		while($n1 <= $max) {
				$kp1[$n1]='';
				$kp2[$n1]='';
				if(($ifd[$n1] != '') && ($ifp[$n1] != '')); {
					$kp1[$n1]=$ifd[$n1].$ifp[$n1];
				}	
				if(($idd[$n1] != '') && ($idp[$n1] != '')); {
					$kp2[$n1]=$idd[$n1].$idp[$n1];
				}	
//						
				$flen = strlen($ifn[$n1]);		
				$ifn1[$n1] = '';
				$ifn2[$n1] = '';
				$ifn3[$n1] = '';
				$ifn4[$n1] = '';
				$ifn5[$n1] = '';
//			
				$fx = 0;
				while(($fx <= $flen) && (substr($ifn[$n1],$fx,1) != ',')) {
					$ifn1[$n1] = $ifn1[$n1].substr($ifn[$n1],$fx,1);
					$fx++;
				}
				$fx++;
				while(($fx <= $flen) && (substr($ifn[$n1],$fx,1) != ',')) {
					$ifn2[$n1] = $ifn2[$n1].substr($ifn[$n1],$fx,1);
					$fx++;
				}
				$fx++;
				while(($fx <= $flen) && (substr($ifn[$n1],$fx,1) != ',')) {
					$ifn3[$n1] = $ifn3[$n1].substr($ifn[$n1],$fx,1);
					$fx++;
				}
				$fx++;
				while(($fx <= $flen) && (substr($ifn[$n1],$fx,1) != ',')) {
					$ifn4[$n1] = $ifn4[$n1].substr($ifn[$n1],$fx,1);
					$fx++;
				}
				$fx++;
				if($fx <= $flen) {
					$ifn5[$n1] = substr($ifn[$n1],$fx,($flen-$fx));
				}
//						
				$elen = strlen($ien[$n1]);		
				$ien1[$n1] = '';
				$ien2[$n1] = '';
				$ien3[$n1] = '';
//
				$ex = 0;
				while(($ex <= $elen) && (substr($ien[$n1],$ex,1) != ',')) {
					$ien1[$n1] = $ien1[$n1].substr($ien[$n1],$ex,1);
					$ex++;
				}
				$ex++;
				while(($ex <= $elen) && (substr($ien[$n1],$ex,1) != ',')) {
					$ien2[$n1] = $ien2[$n1].substr($ien[$n1],$ex,1);
					$ex++;
				}
				$ex++;
				if($ex <= $elen) {
					$ien3[$n1] = substr($ien[$n1],$ex,($elen-$ex));
				}
//						
				$blen = strlen($ifd[$n1]);		
				$ifd1[$n1] = '';
				$ifd2[$n1] = '';
				$ifd3[$n1] = '';
//			
				$bx = 0;
				if(($bx < $blen) && (substr($ifd[$n1],$bx,4) != '')) {
					$ifd1[$n1] = substr($ifd[$n1],$bx,4);
				}
				$bx = 4;
				if(($bx <= $blen) && (substr($ifd[$n1],$bx,2) != '')) {
					$ifd2[$n1] = substr($ifd[$n1],$bx,2);
				}
				$bx = 6;
				if($bx <= $blen) {
					$ifd3[$n1] = substr($ifd[$n1],$bx,2);
				}	
//						
				$dlen = strlen($idd[$n1]);		
				$idd1[$n1] = '';
				$idd2[$n1] = '';
				$idd3[$n1] = '';
//			
				$dx = 0;
				if(($dx < $dlen) && (substr($idd[$n1],$dx,4) != '')) {
					$idd1[$n1] = substr($idd[$n1],$dx,4);
				}
				$dx = 4;
				if(($dx <= $dlen) && (substr($idd[$n1],$dx,2) != '')) {
					$idd2[$n1] = substr($idd[$n1],$dx,2);
				}
				$dx = 6;
				if($dx <= $dlen) {
					$idd3[$n1] = substr($idd[$n1],$dx,2);
				}	
				$kp3[$n1]='';
				$kp4[$n1]='';
				if(($ifd1[$n1] != '') && ($ifp[$n1] != '')); {
					$kp3[$n1]=$ifd1[$n1].$ifp[$n1];
				}	
				if(($idd1[$n1] != '') && ($idp[$n1] != '')); {
					$kp4[$n1]=$idd1[$n1].$idp[$n1];
				}	
				$kp5[$n1]='';
				$kp6[$n1]='';
				if(($ifd2[$n1] != '') && ($ifd3[$n1] != '') && ($ifp[$n1] != '')); {
					$kp5[$n1]=$ifd2[$n1].$ifd3[$n1].$ifp[$n1];
				}	
				if(($idd2[$n1] != '') && ($idd3[$n1] != '') && ($idp[$n1] != '')); {
					$kp6[$n1]=$idd2[$n1].$idd3[$n1].$idp[$n1];
				}
			$n1++;
		}			
//
///////////////////////////
//	Loop 1		
		$n1 = $min + 1;
		$min = $max;
		$nsist = $max - 1;
		while($n1 <= $nsist) {
//	Ladda z
			$numz = $num[$n1];
			$nradz = $nrad[$n1];
			$dradz = $drad[$n1];
			$pradz = $prad[$n1];
//
			$isexz = $isex[$n1];
			$fmcz = $fmc[$n1];
			$ifpz = $ifp[$n1];
			$ifdz = $ifd[$n1];
			$ifd1z = $ifd1[$n1];
			$ifd2z = $ifd2[$n1];
			$ifd3z = $ifd3[$n1];
			$ifnz = $ifn[$n1];
			$ifn1z = $ifn1[$n1];
			$ifn2z = $ifn2[$n1];
			$ifn3z = $ifn3[$n1];
			$ifn4z = $ifn4[$n1];
			$ifn5z = $ifn5[$n1];
			$idpz = $idp[$n1];
			$iddz = $idd[$n1];
			$idd1z = $idd1[$n1];
			$idd2z = $idd2[$n1];
			$idd3z = $idd3[$n1];
			$ienz = $ien[$n1];
			$ien1z = $ien1[$n1];
			$ien2z = $ien2[$n1];
			$ien3z = $ien3[$n1];
			$kp1z = $kp1[$n1];
			$kp2z = $kp2[$n1];
			$kp3z = $kp3[$n1];
			$kp4z = $kp4[$n1];
			$kp5z = $kp5[$n1];
			$kp6z = $kp6[$n1];
//		
//	Loop 2	
			$n2 = $n1 + 1;
			while($n2 <= $max) {
//	
				$bon = 0;
				$pos = 0;
				$neg = 0;
				$ant = 0;
				$halv = 0;
				$plus = ' ';
//	Båda har barnfamilj och dessutom samma familj (syskon)					
				if($fmcz != '') {
					if($fmc[$n2] != '') {
						if($fmcz == $fmc[$n2]) {
							$plus = '-';
							$neg++;
							$neg++;
						}		
					}
				}
//	Boost					
				if(($ifdz != '') && (strlen($ifdz) == 8)) {
					if($ifd[$n2] != '') {
						if($ifdz == $ifd[$n2]) {
							$bon++;
						}
					}
				}
				if($ifd1z != '') {
					if($ifd1[$n2] != '') {
						$ant++;
						if($ifd1z == $ifd1[$n2]) {
							$pos++;
//	Extra bonus
							$bon++;
						}
						else {
							$neg++;
							if($ifd1z < $ifd1[$n2]) {
								if(($ifd1z + 10) < $ifd1[$n2]) {
									$neg++;
									$neg++;
								}
							}	
							else {
								if(($ifd1[$n2] + 10) < $ifd1z) {
									$neg++;
									$neg++;
								}
							}
						}
					}
				}
//	Bryt om ett dödår är mindre än det andra födelseåret
				if($ifd1z != '') {
					if($idd1[$n2] != '') {
						if($idd1[$n2] < $ifd1z) {
							$neg++;
							$neg++;
							$neg++;
						}
					}
				}
				if($ifd1[$n2] != '') {
					if($idd1z != '') {
						if($idd1z < $ifd1[$n2]) {
							$neg++;
							$neg++;
							$neg++;
						}
					}
				}
//			
				if($neg < 3){
//						
//	Boost					
					if(($iddz != '') && (strlen($iddz) == 8)) {
						if($idd[$n2] != '') {
							if($iddz == $idd[$n2]) {
								$bon++;
							}
						}
					}
//						
					if($idd1z != '') {
						if($idd1[$n2] != '') {
							$ant++;
							if($idd1z == $idd1[$n2]) {
								$pos++;
//	Extra bonus
								$bon++;
							}
							else {
								$neg++;
								if($idd1z < $idd1[$n2]) {
									if(($idd1z + 10) < $idd1[$n2]) {
										$neg++;
										$neg++;
									}
								}	
								else {
									if(($idd1[$n2] + 10) < $idd1z) {
										$neg++;
										$neg++;
									}
								}
							}
						}
					}
//			
					if($neg < 3){
//						
//	Boost		
						$ntest = 0;
						if($ifnz != '') {
							if($ifn[$n2] != '') {
								if($ifnz == $ifn[$n2]) {
									$bon++;
									if($ifnz != $ifn1z) {
										$bon++;
									}	
									$ntest++;
								}
							}
						}
//						
						if($ifn1z != '') {
							if($ifn1[$n2] != '') {
								$ant++;
								if(($ifn1z == $ifn1[$n2]) || ($ifn1z == $ifn2[$n2]) || ($ifn1z == $ifn3[$n2])
								 || ($ifn1z == $ifn4[$n2]) || ($ifn1z == $ifn5[$n2]) ) {
									$pos++;
									$ntest++;
								}
							}	
						}
//						
						if($ifn2z != '') {
							if($ifn1[$n2] != '') {
								$ant++;
								if(($ifn2z == $ifn1[$n2]) || ($ifn2z == $ifn2[$n2]) || ($ifn2z == $ifn3[$n2])
								 || ($ifn2z == $ifn4[$n2]) || ($ifn2z == $ifn5[$n2]) ) {
									$pos++;
									$ntest++;
								}
							}
						}
//					
						if($ifn3z != '') {
							if($ifn1[$n2] != '') {
								$ant++;
								if(($ifn3z == $ifn1[$n2]) || ($ifn3z == $ifn2[$n2]) || ($ifn3z == $ifn3[$n2])
								 || ($ifn3z == $ifn4[$n2]) || ($ifn3z == $ifn5[$n2]) ) {
									$pos++;
									$ntest++;
								}
							}
						}
//					
						if($ifn4z != '') {
							if($ifn1[$n2] != '') {
								$ant++;
								if(($ifn4z == $ifn1[$n2]) || ($ifn4z == $ifn2[$n2]) || ($ifn4z == $ifn3[$n2])
								 || ($ifn4z == $ifn4[$n2]) || ($ifn4z == $ifn5[$n2]) ) {
									$pos++;
									$ntest++;
								}
							}
						}
//					
						if($ifn5z != '') {
							if($ifn1[$n2] != '') {
								$ant++;
								if(($ifn5z == $ifn1[$n2]) || ($ifn5z == $ifn2[$n2]) || ($ifn5z == $ifn3[$n2])
								 || ($ifn5z == $ifn4[$n2]) || ($ifn5z == $ifn5[$n2]) ) {
									$pos++;
									$ntest++;
								}
							}
						}
						if($ntest == 0) {
							$neg++;
							$neg++;
						}
//			
						if($neg < 3) {
//						
							if($ifd2z != '') {
								if($ifd2[$n2] != '') {
									$ant++;
									if($ifd2z == $ifd2[$n2]) {
										$pos++;
									}
									else {
										$neg++;
									}
								}
							}
//						
							if($ifd3z != '') {
								if($ifd3[$n2] != '') {
									$ant++;
									if($ifd3z == $ifd3[$n2]) {
										$pos++;
									}
									else {
										$neg++;
									}
								}
							}
//						
							if($ifpz != '') {
								if($ifp[$n2] != '') {
									$ant++;
									if($ifpz == $ifp[$n2]) {
										$pos++;
									}
									else {
										$neg++;
										$neg++;
									}
								}
							}
//			
							if($neg < 3) {
//						
								if($idd2z != '') {
									if($idd2[$n2] != '') {
										$ant++;
										if($idd2z == $idd2[$n2]) {
											$pos++;
										}
										else {
											$neg++;
										}
									}
								}
//						
								if($idd3z != '') {
									if($idd3[$n2] != '') {
										$ant++;
										if($idd3z == $idd3[$n2]) {
											$pos++;
										}
										else {
											$neg++;
										}
									}
								}
//						
								if($idpz != '') {
									if($idp[$n2] != '') {
										$ant++;
										if($idpz == $idp[$n2]) {
											$pos++;
										}
										else {
											$neg++;
											$halv++;
										}
									}
								}
//			
								if($neg < 3) {
//	Boost					
									$ntest = 0;
									if($ienz != '') {
										if($ien[$n2] != '') {
											if($ienz == $ien[$n2]) {
												$bon++;
												if($ienz != $ien1z) {
													$bon++;
												}	
												$ntest++;
											}
										}
									}
//						
									if($ien1z != '') {
										if($ien1[$n2] != '') {
											$ant++;
											if(($ien1z == $ien1[$n2]) || ($ien1z == $ien2[$n2]) || ($ien1z == $ien3[$n2])) {
												$pos++;
											}
											$ntest++;
										}
									}
//						
									if($ien2z != '') {
										if($ien1[$n2] != '') {
											$ant++;
											if(($ien2z == $ien1[$n2]) || ($ien2z == $ien2[$n2]) || ($ien2z == $ien3[$n2])) {
												$pos++;
											}
											$ntest++;
										}
									}
//						
									if($ien3z != '') {
										if($ien1[$n2] != '') {
											$ant++;
											if(($ien3z == $ien1[$n2]) || ($ien3z == $ien2[$n2]) || ($ien3z == $ien3[$n2])) {
												$pos++;
											}
											$ntest++;
										}
									}
									if($ntest == 0) {
										$halv++;
									}
//	Boost					
									if($kp1z != '') {
										if($kp1[$n2] != '') {
											if(($kp1z == $kp1[$n2]) && (strlen($ifdz) == 8)) {
												$bon++;
											}
										}
									}
//	Boost					
									if($kp2z != '') {
										if($kp2[$n2] != '') {
											if(($kp2z == $kp2[$n2]) && (strlen($iddz) == 8)) {
												$bon++;
											}
										}
									}
//	Boost					
									if($kp3z != '') {
										if($kp3[$n2] != '') {
											if($kp3z == $kp3[$n2]) {
												$bon++;
											}
										}
									}
//	Boost					
									if($kp4z != '') {
										if($kp4[$n2] != '') {
											if($kp4z == $kp4[$n2]) {
												$bon++;
											}
										}
									}
//	Boost					
									if($kp5z != '') {
										if($kp5[$n2] != '') {
											if($kp5z == $kp5[$n2]) {
												$bon++;
											}
										}
									}
//	Boost					
									if($kp6z != '') {
										if($kp6[$n2] != '') {
											if($kp6z == $kp6[$n2]) {
												$bon++;
											}
										}
									}
//	Tolka betydelsen av familj FAMC 
//					
//	Båda har barmfamilj men olika familjer
									if($fmcz != '') {
										if($fmc[$n2] != '') {
											if($fmcz != $fmc[$n2]) {
												$plus = '?';
												$halv++;
											}
										}
									}
//	En saknar barnfamilj (mor/dotter - far/som)					
									if($fmcz != '') {
										if($fmc[$n2] == '') {
											$plus = '+';
											$bon++;
										}
									}
//	En saknar barnfamilj (mor/dotter - far/som)					
									if($fmcz == '') {
										if($fmc[$n2] != '') {
											$plus = '+';
											$bon++;
										}
									}
								}
							}
						}
					}	
				}
				if($halv >= 2) {
					$neg++;
				}
//	
				if(($bon > 2) && ($pos > 2) && ($pos > $neg) && ($neg < 3) && ($ant > 2)) {
					$tmp = $pos/$ant;
					if($tmp >= 0.5) {
						$diff = $ant - $pos;
						$totp = $bon + $pos - $diff - $neg;
						if($plus == '+') {
							$totp = $totp + 3;
						}	
						if($plus == ' ') {
							$totp = $totp + 2;
						}	
						if($plus == '?') {
							$totp = $totp + 1;
						}	
						if($tmp >= 0.65) {
							$totp = $totp + 1;
						}	
						if($tmp >= 0.75) {
							$totp = $totp + 1;
						}	
						if($tmp >= 0.8) {
							$totp = $totp + 1;
						}	
						if($tmp >= 0.9) {
							$totp = $totp + 1;
						}	
						if($totp > 7) {
							if($totp <= 9) {
								$txtp = '0'.$totp;
							}
							else {
								$txtp = $totp;
							}
							$fellista[]=$txtp.":"
							.$numz.", ".$nradz.", ".$dradz.", ".$pradz.":"
							.$num[$n2].", ".$nrad[$n2].", ".$drad[$n2].", ".$prad[$n2];
							$kant++;
							$nyckel = $numz.';'.$num[$n2];
							$dublData[$nyckel] = $totp;
						}
						else {
							$nyckel = $numz.';'.$num[$n2];
							$dublData[$nyckel] = $totp;
						}
					}	
				}
//
				$n2++;
			}
			$n1++;
		}
//	
//	steg 2F avslutat	
//
//	Steg 3F startar	
//	
		$handle=fopen($filename,"r");
//	
		$n1 = $min;
		$nsist = 0;
		$znum = '';
		$kand = '';
		$birt = '';
		$deat = '';
		$ifnx = '';
		$ienx = '';
		$ifdx = '';
		$ifpx = '';
		$iddx = '';
		$idpx = '';
		$famc = '';
		$nradx = '';
		$dradx = '';
		$pradx = '';
		$dpref = '';
		$sexx = '';
		$fam = ''; 
		$ind = '';
		$head = 'ON';	
//	Läs in indatafilen				
		$lines = file($filename,FILE_IGNORE_NEW_LINES);
		foreach($lines as $radnummer => $str)
		{
//	Huvud börjar - läs tills första individ/relation
			if($head == 'ON') {
				$ztag = substr($str,0,3);
				if($ztag == '0 @') {
					$zlen = strlen($str);
					$zmax = 3;
					while($zmax <= $zlen) {
						$ztal = substr($str,$zmax,1);
						if($ztal == '@')  {
							if((substr($str,$zmax,5) == '@ IND') || (substr($str,$zmax,5) == '@ FAM')) {
								$head = 'OFF';
//							echo "Första individen/relationen = ".$str." <br/>";
							}
							$zmax = $zlen; 
						}	
						$zmax++;
					}
				}	
			}
//	Första individ/relation börjar	
			if($head == 'OFF')
			{
//	hitta idnummer för individ/relation
				$ztag = substr($str,0,3);
				if($ztag == '0 @') {
//	Ny post, ladda upp tidigare data
					$aar = 0;
					$aar = (int)substr($ifdx,0,4);
					if(($znum != '') && ($ind == 'J') && 
					($aar >= 1850) && ($aar <= 1899) && ($sexx == 'F')) {
						$n1++;
						$num[$n1]=$znum;
						$ifn[$n1]=$ifnx;
						$ien[$n1]=$ienx;
						$ifd[$n1]=$ifdx;
						$ifp[$n1]=$ifpx;
						$idd[$n1]=$iddx;
						$idp[$n1]=$idpx;
						$fmc[$n1]=$famc;
						$nrad[$n1]=$nradx;
						$drad[$n1]=$dradx;
						$prad[$n1]=$pradx;
						$isex[$n1]=$sexx;
					}
					$ifnx = '';
					$ienx = '';
					$ifdx = '';
					$ifpx = '';
					$iddx = '';
					$idpx = '';
					$famc = '';
					$nradx = '';
					$dradx = '';
					$pradx = '';
					$dpref = '';
					$sexx = '';
//	Ny post, sök identitet
					$znum = '';
					$zlen = strlen($str);
					$zmax = 3;
					while($zmax <= $zlen) {
						$ztal = substr($str,$zmax,1);
						if($ztal == '@') {
							if(substr($str,$zmax,5) == '@ IND') {
								$ind = 'J';
								$fam = '';
							}
							elseif(substr($str,$zmax,5) == '@ FAM') {
								$ind = '';
								$fam = 'J';
							}
							else {
								$ind = '';
								$fam = '';
								}
						}	
						if($ztal != '@') {
							$znum = $znum.$ztal;
						}
						else {
							$zmax = $zlen;
							$zmax++;
						}
						$zmax++;
					}
				}
//		
//	Fortsätt samla data
				$tag1 = substr($str,0,1);
				$tagk = substr($str,2,3);
				$tagl = substr($str,2,4);
				$tag7 = substr($str,6,1);
				$tlen = strlen($str);
				$str = substr($str,7,($tlen-7));
//
				if($tag1 == '1') {
					$birt = '';
					$deat = '';
				}
//
				if($tagk == 'SEX') {
					$sexx = $tag7;
				}
				elseif($tagl == 'BIRT') {
					$birt = 'J';
				}
				elseif($tagk == 'CHR') {
					if(($ifdx == '') && ($ifpx == '')) {
						$birt = 'J';
//						echo "Enbart döpt <br/>";
					}	
				}
				elseif($tagl == 'DEAT') {
					$deat = 'J';
				}
				elseif($tagl == 'BURI') {
					if(($iddx == '') && ($idpx == '')) {
						$deat = 'J';
//						echo "Enbart begravd <br/>";
					}	
				}
				elseif($tagl == 'RGDF') {
					$ifnx = $str;
				}
				elseif($tagl == 'RGDE') {
					$ienx = $str;
				}
				elseif(($tagl == 'RGDD') && ($birt == 'J')) {
					$ifdx = $str;
				}
				elseif(($tagl == 'RGDP') && ($birt == 'J')) {
					$ifpx = $str;
				}
				elseif(($tagl == 'RGDD') && ($deat == 'J')) {
					$iddx = $str;
				}
				elseif(($tagl == 'RGDP') && ($deat == 'J')) {
					$idpx = $str;
				}
				elseif($tagl == 'FAMC') {
					$famc = $str;
				}
				elseif($tagl == 'NAME') {
					$nradx = $str;
				}
				elseif(($tagl == 'DATE') && ($birt == 'J')) {
					$tstx = $str; // fimpa ()
					$lend = strlen($str);
					$tstp = substr($str,0,1);
					if($tstp == '(') {
						$tstx = substr($str,1,$lend-2); }
					$dpref = 'f. ';
					$dradx = $dpref.$tstx;
				}
				elseif(($tagl == 'PLAC') && ($birt == 'J')) {
					if($dpref == '') {
						$dpref = 'f. ';
						$pradx = $dpref.$str;
					}
					else {
						$pradx = $str;
					}
				}
				elseif(($tagl == 'DATE') && ($deat == 'J') && ($dpref == '')) {
					$tstx = $str; // fimpa ()
					$lend = strlen($str);
					$tstp = substr($str,0,1);
					if($tstp == '(') {
						$tstx = substr($str,1,$lend-2); }
					$dpref = 'd. ';
					$dradx = $dpref.$tstx;
				}
				elseif(($tagl == 'PLAC') && ($deat == 'J')) {
					if($dpref == '') {
						$pradx = 'd. '.$str;
					}	
					if($dpref == 'd. ') {
						$pradx = $str;
					}	
				}
				else {
//	ointressant rad
				}
			}		
		}
		fclose($handle);
//
//		echo $n1." individer inlästa för bearbetning efter block 3.<br/>";
//		echo "<br/>";
//
	
//////////////////////////////////
		$max = $n1;
		$n1 = $min + 1;
//
		while($n1 <= $max) {
				$kp1[$n1]='';
				$kp2[$n1]='';
				if(($ifd[$n1] != '') && ($ifp[$n1] != '')); {
					$kp1[$n1]=$ifd[$n1].$ifp[$n1];
				}	
				if(($idd[$n1] != '') && ($idp[$n1] != '')); {
					$kp2[$n1]=$idd[$n1].$idp[$n1];
				}	
//						
				$flen = strlen($ifn[$n1]);		
				$ifn1[$n1] = '';
				$ifn2[$n1] = '';
				$ifn3[$n1] = '';
				$ifn4[$n1] = '';
				$ifn5[$n1] = '';
//			
				$fx = 0;
				while(($fx <= $flen) && (substr($ifn[$n1],$fx,1) != ',')) {
					$ifn1[$n1] = $ifn1[$n1].substr($ifn[$n1],$fx,1);
					$fx++;
				}
				$fx++;
				while(($fx <= $flen) && (substr($ifn[$n1],$fx,1) != ',')) {
					$ifn2[$n1] = $ifn2[$n1].substr($ifn[$n1],$fx,1);
					$fx++;
				}
				$fx++;
				while(($fx <= $flen) && (substr($ifn[$n1],$fx,1) != ',')) {
					$ifn3[$n1] = $ifn3[$n1].substr($ifn[$n1],$fx,1);
					$fx++;
				}
				$fx++;
				while(($fx <= $flen) && (substr($ifn[$n1],$fx,1) != ',')) {
					$ifn4[$n1] = $ifn4[$n1].substr($ifn[$n1],$fx,1);
					$fx++;
				}
				$fx++;
				if($fx <= $flen) {
					$ifn5[$n1] = substr($ifn[$n1],$fx,($flen-$fx));
				}
//						
				$elen = strlen($ien[$n1]);		
				$ien1[$n1] = '';
				$ien2[$n1] = '';
				$ien3[$n1] = '';
//
				$ex = 0;
				while(($ex <= $elen) && (substr($ien[$n1],$ex,1) != ',')) {
					$ien1[$n1] = $ien1[$n1].substr($ien[$n1],$ex,1);
					$ex++;
				}
				$ex++;
				while(($ex <= $elen) && (substr($ien[$n1],$ex,1) != ',')) {
					$ien2[$n1] = $ien2[$n1].substr($ien[$n1],$ex,1);
					$ex++;
				}
				$ex++;
				if($ex <= $elen) {
					$ien3[$n1] = substr($ien[$n1],$ex,($elen-$ex));
				}
//						
				$blen = strlen($ifd[$n1]);		
				$ifd1[$n1] = '';
				$ifd2[$n1] = '';
				$ifd3[$n1] = '';
//			
				$bx = 0;
				if(($bx < $blen) && (substr($ifd[$n1],$bx,4) != '')) {
					$ifd1[$n1] = substr($ifd[$n1],$bx,4);
				}
				$bx = 4;
				if(($bx <= $blen) && (substr($ifd[$n1],$bx,2) != '')) {
					$ifd2[$n1] = substr($ifd[$n1],$bx,2);
				}
				$bx = 6;
				if($bx <= $blen) {
					$ifd3[$n1] = substr($ifd[$n1],$bx,2);
				}	
//						
				$dlen = strlen($idd[$n1]);		
				$idd1[$n1] = '';
				$idd2[$n1] = '';
				$idd3[$n1] = '';
//			
				$dx = 0;
				if(($dx < $dlen) && (substr($idd[$n1],$dx,4) != '')) {
					$idd1[$n1] = substr($idd[$n1],$dx,4);
				}
				$dx = 4;
				if(($dx <= $dlen) && (substr($idd[$n1],$dx,2) != '')) {
					$idd2[$n1] = substr($idd[$n1],$dx,2);
				}
				$dx = 6;
				if($dx <= $dlen) {
					$idd3[$n1] = substr($idd[$n1],$dx,2);
				}	
				$kp3[$n1]='';
				$kp4[$n1]='';
				if(($ifd1[$n1] != '') && ($ifp[$n1] != '')); {
					$kp3[$n1]=$ifd1[$n1].$ifp[$n1];
				}	
				if(($idd1[$n1] != '') && ($idp[$n1] != '')); {
					$kp4[$n1]=$idd1[$n1].$idp[$n1];
				}	
				$kp5[$n1]='';
				$kp6[$n1]='';
				if(($ifd2[$n1] != '') && ($ifd3[$n1] != '') && ($ifp[$n1] != '')); {
					$kp5[$n1]=$ifd2[$n1].$ifd3[$n1].$ifp[$n1];
				}	
				if(($idd2[$n1] != '') && ($idd3[$n1] != '') && ($idp[$n1] != '')); {
					$kp6[$n1]=$idd2[$n1].$idd3[$n1].$idp[$n1];
				}
			$n1++;
		}			
//
///////////////////////////
//	Loop 1		
		$n1 = $min + 1;
		$min = $max;
		$nsist = $max - 1;
		while($n1 <= $nsist) {
//	Ladda z
			$numz = $num[$n1];
			$nradz = $nrad[$n1];
			$dradz = $drad[$n1];
			$pradz = $prad[$n1];
//
			$isexz = $isex[$n1];
			$fmcz = $fmc[$n1];
			$ifpz = $ifp[$n1];
			$ifdz = $ifd[$n1];
			$ifd1z = $ifd1[$n1];
			$ifd2z = $ifd2[$n1];
			$ifd3z = $ifd3[$n1];
			$ifnz = $ifn[$n1];
			$ifn1z = $ifn1[$n1];
			$ifn2z = $ifn2[$n1];
			$ifn3z = $ifn3[$n1];
			$ifn4z = $ifn4[$n1];
			$ifn5z = $ifn5[$n1];
			$idpz = $idp[$n1];
			$iddz = $idd[$n1];
			$idd1z = $idd1[$n1];
			$idd2z = $idd2[$n1];
			$idd3z = $idd3[$n1];
			$ienz = $ien[$n1];
			$ien1z = $ien1[$n1];
			$ien2z = $ien2[$n1];
			$ien3z = $ien3[$n1];
			$kp1z = $kp1[$n1];
			$kp2z = $kp2[$n1];
			$kp3z = $kp3[$n1];
			$kp4z = $kp4[$n1];
			$kp5z = $kp5[$n1];
			$kp6z = $kp6[$n1];
//		
//	Loop 2	
			$n2 = $n1 + 1;
			while($n2 <= $max) {
//	
				$bon = 0;
				$pos = 0;
				$neg = 0;
				$ant = 0;
				$halv = 0;
				$plus = ' ';
//	Båda har barnfamilj och dessutom samma familj (syskon)					
				if($fmcz != '') {
					if($fmc[$n2] != '') {
						if($fmcz == $fmc[$n2]) {
							$plus = '-';
							$neg++;
							$neg++;
						}		
					}
				}
//	Boost					
				if(($ifdz != '') && (strlen($ifdz) == 8)) {
					if($ifd[$n2] != '') {
						if($ifdz == $ifd[$n2]) {
							$bon++;
						}
					}
				}
				if($ifd1z != '') {
					if($ifd1[$n2] != '') {
						$ant++;
						if($ifd1z == $ifd1[$n2]) {
							$pos++;
//	Extra bonus
							$bon++;
						}
						else {
							$neg++;
							if($ifd1z < $ifd1[$n2]) {
								if(($ifd1z + 10) < $ifd1[$n2]) {
									$neg++;
									$neg++;
								}
							}	
							else {
								if(($ifd1[$n2] + 10) < $ifd1z) {
									$neg++;
									$neg++;
								}
							}
						}
					}
				}
//	Bryt om ett dödår är mindre än det andra födelseåret
				if($ifd1z != '') {
					if($idd1[$n2] != '') {
						if($idd1[$n2] < $ifd1z) {
							$neg++;
							$neg++;
							$neg++;
						}
					}
				}
				if($ifd1[$n2] != '') {
					if($idd1z != '') {
						if($idd1z < $ifd1[$n2]) {
							$neg++;
							$neg++;
							$neg++;
						}
					}
				}
//			
				if($neg < 3){
//						
//	Boost					
					if(($iddz != '') && (strlen($iddz) == 8)) {
						if($idd[$n2] != '') {
							if($iddz == $idd[$n2]) {
								$bon++;
							}
						}
					}
//						
					if($idd1z != '') {
						if($idd1[$n2] != '') {
							$ant++;
							if($idd1z == $idd1[$n2]) {
								$pos++;
//	Extra bonus
								$bon++;
							}
							else {
								$neg++;
								if($idd1z < $idd1[$n2]) {
									if(($idd1z + 10) < $idd1[$n2]) {
										$neg++;
										$neg++;
									}
								}	
								else {
									if(($idd1[$n2] + 10) < $idd1z) {
										$neg++;
										$neg++;
									}
								}
							}
						}
					}
//			
					if($neg < 3){
//						
//	Boost		
						$ntest = 0;
						if($ifnz != '') {
							if($ifn[$n2] != '') {
								if($ifnz == $ifn[$n2]) {
									$bon++;
									if($ifnz != $ifn1z) {
										$bon++;
									}	
									$ntest++;
								}
							}
						}
//						
						if($ifn1z != '') {
							if($ifn1[$n2] != '') {
								$ant++;
								if(($ifn1z == $ifn1[$n2]) || ($ifn1z == $ifn2[$n2]) || ($ifn1z == $ifn3[$n2])
								 || ($ifn1z == $ifn4[$n2]) || ($ifn1z == $ifn5[$n2]) ) {
									$pos++;
									$ntest++;
								}
							}	
						}
//						
						if($ifn2z != '') {
							if($ifn1[$n2] != '') {
								$ant++;
								if(($ifn2z == $ifn1[$n2]) || ($ifn2z == $ifn2[$n2]) || ($ifn2z == $ifn3[$n2])
								 || ($ifn2z == $ifn4[$n2]) || ($ifn2z == $ifn5[$n2]) ) {
									$pos++;
									$ntest++;
								}
							}
						}
//					
						if($ifn3z != '') {
							if($ifn1[$n2] != '') {
								$ant++;
								if(($ifn3z == $ifn1[$n2]) || ($ifn3z == $ifn2[$n2]) || ($ifn3z == $ifn3[$n2])
								 || ($ifn3z == $ifn4[$n2]) || ($ifn3z == $ifn5[$n2]) ) {
									$pos++;
									$ntest++;
								}
							}
						}
//					
						if($ifn4z != '') {
							if($ifn1[$n2] != '') {
								$ant++;
								if(($ifn4z == $ifn1[$n2]) || ($ifn4z == $ifn2[$n2]) || ($ifn4z == $ifn3[$n2])
								 || ($ifn4z == $ifn4[$n2]) || ($ifn4z == $ifn5[$n2]) ) {
									$pos++;
									$ntest++;
								}
							}
						}
//					
						if($ifn5z != '') {
							if($ifn1[$n2] != '') {
								$ant++;
								if(($ifn5z == $ifn1[$n2]) || ($ifn5z == $ifn2[$n2]) || ($ifn5z == $ifn3[$n2])
								 || ($ifn5z == $ifn4[$n2]) || ($ifn5z == $ifn5[$n2]) ) {
									$pos++;
									$ntest++;
								}
							}
						}
						if($ntest == 0) {
							$neg++;
							$neg++;
						}
//			
						if($neg < 3) {
//						
							if($ifd2z != '') {
								if($ifd2[$n2] != '') {
									$ant++;
									if($ifd2z == $ifd2[$n2]) {
										$pos++;
									}
									else {
										$neg++;
									}
								}
							}
//						
							if($ifd3z != '') {
								if($ifd3[$n2] != '') {
									$ant++;
									if($ifd3z == $ifd3[$n2]) {
										$pos++;
									}
									else {
										$neg++;
									}
								}
							}
//						
							if($ifpz != '') {
								if($ifp[$n2] != '') {
									$ant++;
									if($ifpz == $ifp[$n2]) {
										$pos++;
									}
									else {
										$neg++;
										$neg++;
									}
								}
							}
//			
							if($neg < 3) {
//						
								if($idd2z != '') {
									if($idd2[$n2] != '') {
										$ant++;
										if($idd2z == $idd2[$n2]) {
											$pos++;
										}
										else {
											$neg++;
										}
									}
								}
//						
								if($idd3z != '') {
									if($idd3[$n2] != '') {
										$ant++;
										if($idd3z == $idd3[$n2]) {
											$pos++;
										}
										else {
											$neg++;
										}
									}
								}
//						
								if($idpz != '') {
									if($idp[$n2] != '') {
										$ant++;
										if($idpz == $idp[$n2]) {
											$pos++;
										}
										else {
											$neg++;
											$halv++;
										}
									}
								}
//			
								if($neg < 3) {
//	Boost					
									$ntest = 0;
									if($ienz != '') {
										if($ien[$n2] != '') {
											if($ienz == $ien[$n2]) {
												$bon++;
												if($ienz != $ien1z) {
													$bon++;
												}	
												$ntest++;
											}
										}
									}
//						
									if($ien1z != '') {
										if($ien1[$n2] != '') {
											$ant++;
											if(($ien1z == $ien1[$n2]) || ($ien1z == $ien2[$n2]) || ($ien1z == $ien3[$n2])) {
												$pos++;
											}
											$ntest++;
										}
									}
//						
									if($ien2z != '') {
										if($ien1[$n2] != '') {
											$ant++;
											if(($ien2z == $ien1[$n2]) || ($ien2z == $ien2[$n2]) || ($ien2z == $ien3[$n2])) {
												$pos++;
											}
											$ntest++;
										}
									}
//						
									if($ien3z != '') {
										if($ien1[$n2] != '') {
											$ant++;
											if(($ien3z == $ien1[$n2]) || ($ien3z == $ien2[$n2]) || ($ien3z == $ien3[$n2])) {
												$pos++;
											}
											$ntest++;
										}
									}
									if($ntest == 0) {
										$halv++;
									}
//	Boost					
									if($kp1z != '') {
										if($kp1[$n2] != '') {
											if(($kp1z == $kp1[$n2]) && (strlen($ifdz) == 8)) {
												$bon++;
											}
										}
									}
//	Boost					
									if($kp2z != '') {
										if($kp2[$n2] != '') {
											if(($kp2z == $kp2[$n2]) && (strlen($iddz) == 8)) {
												$bon++;
											}
										}
									}
//	Boost					
									if($kp3z != '') {
										if($kp3[$n2] != '') {
											if($kp3z == $kp3[$n2]) {
												$bon++;
											}
										}
									}
//	Boost					
									if($kp4z != '') {
										if($kp4[$n2] != '') {
											if($kp4z == $kp4[$n2]) {
												$bon++;
											}
										}
									}
//	Boost					
									if($kp5z != '') {
										if($kp5[$n2] != '') {
											if($kp5z == $kp5[$n2]) {
												$bon++;
											}
										}
									}
//	Boost					
									if($kp6z != '') {
										if($kp6[$n2] != '') {
											if($kp6z == $kp6[$n2]) {
												$bon++;
											}
										}
									}
//	Tolka betydelsen av familj FAMC 
//					
//	Båda har barmfamilj men olika familjer
									if($fmcz != '') {
										if($fmc[$n2] != '') {
											if($fmcz != $fmc[$n2]) {
												$plus = '?';
												$halv++;
											}
										}
									}
//	En saknar barnfamilj (mor/dotter - far/som)					
									if($fmcz != '') {
										if($fmc[$n2] == '') {
											$plus = '+';
											$bon++;
										}
									}
//	En saknar barnfamilj (mor/dotter - far/som)					
									if($fmcz == '') {
										if($fmc[$n2] != '') {
											$plus = '+';
											$bon++;
										}
									}
								}
							}
						}
					}	
				}
				if($halv >= 2) {
					$neg++;
				}
//	
				if(($bon > 2) && ($pos > 2) && ($pos > $neg) && ($neg < 3) && ($ant > 2)) {
					$tmp = $pos/$ant;
					if($tmp >= 0.5) {
						$diff = $ant - $pos;
						$totp = $bon + $pos - $diff - $neg;
						if($plus == '+') {
							$totp = $totp + 3;
						}	
						if($plus == ' ') {
							$totp = $totp + 2;
						}	
						if($plus == '?') {
							$totp = $totp + 1;
						}	
						if($tmp >= 0.65) {
							$totp = $totp + 1;
						}	
						if($tmp >= 0.75) {
							$totp = $totp + 1;
						}	
						if($tmp >= 0.8) {
							$totp = $totp + 1;
						}	
						if($tmp >= 0.9) {
							$totp = $totp + 1;
						}	
						if($totp > 7) {
							if($totp <= 9) {
								$txtp = '0'.$totp;
							}
							else {
								$txtp = $totp;
							}
							$fellista[]=$txtp.":"
							.$numz.", ".$nradz.", ".$dradz.", ".$pradz.":"
							.$num[$n2].", ".$nrad[$n2].", ".$drad[$n2].", ".$prad[$n2];
							$kant++;
							$nyckel = $numz.';'.$num[$n2];
							$dublData[$nyckel] = $totp;
						}
						else {
							$nyckel = $numz.';'.$num[$n2];
							$dublData[$nyckel] = $totp;
						}
					}	
				}
//
				$n2++;
			}
			$n1++;
		}
//	
//	steg 3F avslutat	
//
//	Steg 4F startar	
//	
		$handle=fopen($filename,"r");
//	
		$n1 = $min;
		$nsist = 0;
		$znum = '';
		$kand = '';
		$birt = '';
		$deat = '';
		$ifnx = '';
		$ienx = '';
		$ifdx = '';
		$ifpx = '';
		$iddx = '';
		$idpx = '';
		$famc = '';
		$nradx = '';
		$dradx = '';
		$pradx = '';
		$dpref = '';
		$sexx = '';
		$fam = ''; 
		$ind = '';
		$head = 'ON';	
//	Läs in indatafilen				
		$lines = file($filename,FILE_IGNORE_NEW_LINES);
		foreach($lines as $radnummer => $str)
		{
//	Huvud börjar - läs tills första individ/relation
			if($head == 'ON') {
				$ztag = substr($str,0,3);
				if($ztag == '0 @') {
					$zlen = strlen($str);
					$zmax = 3;
					while($zmax <= $zlen) {
						$ztal = substr($str,$zmax,1);
						if($ztal == '@')  {
							if((substr($str,$zmax,5) == '@ IND') || (substr($str,$zmax,5) == '@ FAM')) {
								$head = 'OFF';
//							echo "Första individen/relationen = ".$str." <br/>";
							}
							$zmax = $zlen; 
						}	
						$zmax++;
					}
				}	
			}
//	Första individ/relation börjar	
			if($head == 'OFF')
			{
//	hitta idnummer för individ/relation
				$ztag = substr($str,0,3);
				if($ztag == '0 @') {
//	Ny post, ladda upp tidigare data
					$aar = 0;
					$aar = (int)substr($ifdx,0,4);
					if(($znum != '') && ($ind == 'J') && 
					($aar >= 1900) && ($sexx == 'F')) {
						$n1++;
						$num[$n1]=$znum;
						$ifn[$n1]=$ifnx;
						$ien[$n1]=$ienx;
						$ifd[$n1]=$ifdx;
						$ifp[$n1]=$ifpx;
						$idd[$n1]=$iddx;
						$idp[$n1]=$idpx;
						$fmc[$n1]=$famc;
						$nrad[$n1]=$nradx;
						$drad[$n1]=$dradx;
						$prad[$n1]=$pradx;
						$isex[$n1]=$sexx;
					}
					$ifnx = '';
					$ienx = '';
					$ifdx = '';
					$ifpx = '';
					$iddx = '';
					$idpx = '';
					$famc = '';
					$nradx = '';
					$dradx = '';
					$pradx = '';
					$dpref = '';
					$sexx = '';
//	Ny post, sök identitet
					$znum = '';
					$zlen = strlen($str);
					$zmax = 3;
					while($zmax <= $zlen) {
						$ztal = substr($str,$zmax,1);
						if($ztal == '@') {
							if(substr($str,$zmax,5) == '@ IND') {
								$ind = 'J';
								$fam = '';
							}
							elseif(substr($str,$zmax,5) == '@ FAM') {
								$ind = '';
								$fam = 'J';
							}
							else {
								$ind = '';
								$fam = '';
								}
						}	
						if($ztal != '@') {
							$znum = $znum.$ztal;
						}
						else {
							$zmax = $zlen;
							$zmax++;
						}
						$zmax++;
					}
				}
//		
//	Fortsätt samla data
				$tag1 = substr($str,0,1);
				$tagk = substr($str,2,3);
				$tagl = substr($str,2,4);
				$tag7 = substr($str,6,1);
				$tlen = strlen($str);
				$str = substr($str,7,($tlen-7));
//
				if($tag1 == '1') {
					$birt = '';
					$deat = '';
				}
//
				if($tagk == 'SEX') {
					$sexx = $tag7;
				}
				elseif($tagl == 'BIRT') {
					$birt = 'J';
				}
				elseif($tagk == 'CHR') {
					if(($ifdx == '') && ($ifpx == '')) {
						$birt = 'J';
//						echo "Enbart döpt <br/>";
					}	
				}
				elseif($tagl == 'DEAT') {
					$deat = 'J';
				}
				elseif($tagl == 'BURI') {
					if(($iddx == '') && ($idpx == '')) {
						$deat = 'J';
//						echo "Enbart begravd <br/>";
					}	
				}
				elseif($tagl == 'RGDF') {
					$ifnx = $str;
				}
				elseif($tagl == 'RGDE') {
					$ienx = $str;
				}
				elseif(($tagl == 'RGDD') && ($birt == 'J')) {
					$ifdx = $str;
				}
				elseif(($tagl == 'RGDP') && ($birt == 'J')) {
					$ifpx = $str;
				}
				elseif(($tagl == 'RGDD') && ($deat == 'J')) {
					$iddx = $str;
				}
				elseif(($tagl == 'RGDP') && ($deat == 'J')) {
					$idpx = $str;
				}
				elseif($tagl == 'FAMC') {
					$famc = $str;
				}
				elseif($tagl == 'NAME') {
					$nradx = $str;
				}
				elseif(($tagl == 'DATE') && ($birt == 'J')) {
					$tstx = $str; // fimpa ()
					$lend = strlen($str);
					$tstp = substr($str,0,1);
					if($tstp == '(') {
						$tstx = substr($str,1,$lend-2); }
					$dpref = 'f. ';
					$dradx = $dpref.$tstx;
				}
				elseif(($tagl == 'PLAC') && ($birt == 'J')) {
					if($dpref == '') {
						$dpref = 'f. ';
						$pradx = $dpref.$str;
					}
					else {
						$pradx = $str;
					}
				}
				elseif(($tagl == 'DATE') && ($deat == 'J') && ($dpref == '')) {
					$tstx = $str; // fimpa ()
					$lend = strlen($str);
					$tstp = substr($str,0,1);
					if($tstp == '(') {
						$tstx = substr($str,1,$lend-2); }
					$dpref = 'd. ';
					$dradx = $dpref.$tstx;
				}
				elseif(($tagl == 'PLAC') && ($deat == 'J')) {
					if($dpref == '') {
						$pradx = 'd. '.$str;
					}	
					if($dpref == 'd. ') {
						$pradx = $str;
					}	
				}
				else {
//	ointressant rad
				}
			}		
		}
		fclose($handle);
//
//		echo $n1." individer inlästa för bearbetning efter block 4.<br/>";
//		echo "<br/>";
//
	
//////////////////////////////////
		$max = $n1;
		$n1 = $min + 1;
//
		while($n1 <= $max) {
				$kp1[$n1]='';
				$kp2[$n1]='';
				if(($ifd[$n1] != '') && ($ifp[$n1] != '')); {
					$kp1[$n1]=$ifd[$n1].$ifp[$n1];
				}	
				if(($idd[$n1] != '') && ($idp[$n1] != '')); {
					$kp2[$n1]=$idd[$n1].$idp[$n1];
				}	
//						
				$flen = strlen($ifn[$n1]);		
				$ifn1[$n1] = '';
				$ifn2[$n1] = '';
				$ifn3[$n1] = '';
				$ifn4[$n1] = '';
				$ifn5[$n1] = '';
//			
				$fx = 0;
				while(($fx <= $flen) && (substr($ifn[$n1],$fx,1) != ',')) {
					$ifn1[$n1] = $ifn1[$n1].substr($ifn[$n1],$fx,1);
					$fx++;
				}
				$fx++;
				while(($fx <= $flen) && (substr($ifn[$n1],$fx,1) != ',')) {
					$ifn2[$n1] = $ifn2[$n1].substr($ifn[$n1],$fx,1);
					$fx++;
				}
				$fx++;
				while(($fx <= $flen) && (substr($ifn[$n1],$fx,1) != ',')) {
					$ifn3[$n1] = $ifn3[$n1].substr($ifn[$n1],$fx,1);
					$fx++;
				}
				$fx++;
				while(($fx <= $flen) && (substr($ifn[$n1],$fx,1) != ',')) {
					$ifn4[$n1] = $ifn4[$n1].substr($ifn[$n1],$fx,1);
					$fx++;
				}
				$fx++;
				if($fx <= $flen) {
					$ifn5[$n1] = substr($ifn[$n1],$fx,($flen-$fx));
				}
//						
				$elen = strlen($ien[$n1]);		
				$ien1[$n1] = '';
				$ien2[$n1] = '';
				$ien3[$n1] = '';
//
				$ex = 0;
				while(($ex <= $elen) && (substr($ien[$n1],$ex,1) != ',')) {
					$ien1[$n1] = $ien1[$n1].substr($ien[$n1],$ex,1);
					$ex++;
				}
				$ex++;
				while(($ex <= $elen) && (substr($ien[$n1],$ex,1) != ',')) {
					$ien2[$n1] = $ien2[$n1].substr($ien[$n1],$ex,1);
					$ex++;
				}
				$ex++;
				if($ex <= $elen) {
					$ien3[$n1] = substr($ien[$n1],$ex,($elen-$ex));
				}
//						
				$blen = strlen($ifd[$n1]);		
				$ifd1[$n1] = '';
				$ifd2[$n1] = '';
				$ifd3[$n1] = '';
//			
				$bx = 0;
				if(($bx < $blen) && (substr($ifd[$n1],$bx,4) != '')) {
					$ifd1[$n1] = substr($ifd[$n1],$bx,4);
				}
				$bx = 4;
				if(($bx <= $blen) && (substr($ifd[$n1],$bx,2) != '')) {
					$ifd2[$n1] = substr($ifd[$n1],$bx,2);
				}
				$bx = 6;
				if($bx <= $blen) {
					$ifd3[$n1] = substr($ifd[$n1],$bx,2);
				}	
//						
				$dlen = strlen($idd[$n1]);		
				$idd1[$n1] = '';
				$idd2[$n1] = '';
				$idd3[$n1] = '';
//			
				$dx = 0;
				if(($dx < $dlen) && (substr($idd[$n1],$dx,4) != '')) {
					$idd1[$n1] = substr($idd[$n1],$dx,4);
				}
				$dx = 4;
				if(($dx <= $dlen) && (substr($idd[$n1],$dx,2) != '')) {
					$idd2[$n1] = substr($idd[$n1],$dx,2);
				}
				$dx = 6;
				if($dx <= $dlen) {
					$idd3[$n1] = substr($idd[$n1],$dx,2);
				}	
				$kp3[$n1]='';
				$kp4[$n1]='';
				if(($ifd1[$n1] != '') && ($ifp[$n1] != '')); {
					$kp3[$n1]=$ifd1[$n1].$ifp[$n1];
				}	
				if(($idd1[$n1] != '') && ($idp[$n1] != '')); {
					$kp4[$n1]=$idd1[$n1].$idp[$n1];
				}	
				$kp5[$n1]='';
				$kp6[$n1]='';
				if(($ifd2[$n1] != '') && ($ifd3[$n1] != '') && ($ifp[$n1] != '')); {
					$kp5[$n1]=$ifd2[$n1].$ifd3[$n1].$ifp[$n1];
				}	
				if(($idd2[$n1] != '') && ($idd3[$n1] != '') && ($idp[$n1] != '')); {
					$kp6[$n1]=$idd2[$n1].$idd3[$n1].$idp[$n1];
				}
			$n1++;
		}			
//
///////////////////////////
//	Loop 1		
		$n1 = $min + 1;
		$min = $max;
		$nsist = $max - 1;
		while($n1 <= $nsist) {
//	Ladda z
			$numz = $num[$n1];
			$nradz = $nrad[$n1];
			$dradz = $drad[$n1];
			$pradz = $prad[$n1];
//
			$isexz = $isex[$n1];
			$fmcz = $fmc[$n1];
			$ifpz = $ifp[$n1];
			$ifdz = $ifd[$n1];
			$ifd1z = $ifd1[$n1];
			$ifd2z = $ifd2[$n1];
			$ifd3z = $ifd3[$n1];
			$ifnz = $ifn[$n1];
			$ifn1z = $ifn1[$n1];
			$ifn2z = $ifn2[$n1];
			$ifn3z = $ifn3[$n1];
			$ifn4z = $ifn4[$n1];
			$ifn5z = $ifn5[$n1];
			$idpz = $idp[$n1];
			$iddz = $idd[$n1];
			$idd1z = $idd1[$n1];
			$idd2z = $idd2[$n1];
			$idd3z = $idd3[$n1];
			$ienz = $ien[$n1];
			$ien1z = $ien1[$n1];
			$ien2z = $ien2[$n1];
			$ien3z = $ien3[$n1];
			$kp1z = $kp1[$n1];
			$kp2z = $kp2[$n1];
			$kp3z = $kp3[$n1];
			$kp4z = $kp4[$n1];
			$kp5z = $kp5[$n1];
			$kp6z = $kp6[$n1];
//		
//	Loop 2	
			$n2 = $n1 + 1;
			while($n2 <= $max) {
//	
				$bon = 0;
				$pos = 0;
				$neg = 0;
				$ant = 0;
				$halv = 0;
				$plus = ' ';
//	Båda har barnfamilj och dessutom samma familj (syskon)					
				if($fmcz != '') {
					if($fmc[$n2] != '') {
						if($fmcz == $fmc[$n2]) {
							$plus = '-';
							$neg++;
							$neg++;
						}		
					}
				}
//	Boost					
				if(($ifdz != '') && (strlen($ifdz) == 8)) {
					if($ifd[$n2] != '') {
						if($ifdz == $ifd[$n2]) {
							$bon++;
						}
					}
				}
				if($ifd1z != '') {
					if($ifd1[$n2] != '') {
						$ant++;
						if($ifd1z == $ifd1[$n2]) {
							$pos++;
//	Extra bonus
							$bon++;
						}
						else {
							$neg++;
							if($ifd1z < $ifd1[$n2]) {
								if(($ifd1z + 10) < $ifd1[$n2]) {
									$neg++;
									$neg++;
								}
							}	
							else {
								if(($ifd1[$n2] + 10) < $ifd1z) {
									$neg++;
									$neg++;
								}
							}
						}
					}
				}
//	Bryt om ett dödår är mindre än det andra födelseåret
				if($ifd1z != '') {
					if($idd1[$n2] != '') {
						if($idd1[$n2] < $ifd1z) {
							$neg++;
							$neg++;
							$neg++;
						}
					}
				}
				if($ifd1[$n2] != '') {
					if($idd1z != '') {
						if($idd1z < $ifd1[$n2]) {
							$neg++;
							$neg++;
							$neg++;
						}
					}
				}
//			
				if($neg < 3){
//						
//	Boost					
					if(($iddz != '') && (strlen($iddz) == 8)) {
						if($idd[$n2] != '') {
							if($iddz == $idd[$n2]) {
								$bon++;
							}
						}
					}
//						
					if($idd1z != '') {
						if($idd1[$n2] != '') {
							$ant++;
							if($idd1z == $idd1[$n2]) {
								$pos++;
//	Extra bonus
								$bon++;
							}
							else {
								$neg++;
								if($idd1z < $idd1[$n2]) {
									if(($idd1z + 10) < $idd1[$n2]) {
										$neg++;
										$neg++;
									}
								}	
								else {
									if(($idd1[$n2] + 10) < $idd1z) {
										$neg++;
										$neg++;
									}
								}
							}
						}
					}
//			
					if($neg < 3){
//						
//	Boost		
						$ntest = 0;
						if($ifnz != '') {
							if($ifn[$n2] != '') {
								if($ifnz == $ifn[$n2]) {
									$bon++;
									if($ifnz != $ifn1z) {
										$bon++;
									}	
									$ntest++;
								}
							}
						}
//						
						if($ifn1z != '') {
							if($ifn1[$n2] != '') {
								$ant++;
								if(($ifn1z == $ifn1[$n2]) || ($ifn1z == $ifn2[$n2]) || ($ifn1z == $ifn3[$n2])
								 || ($ifn1z == $ifn4[$n2]) || ($ifn1z == $ifn5[$n2]) ) {
									$pos++;
									$ntest++;
								}
							}	
						}
//						
						if($ifn2z != '') {
							if($ifn1[$n2] != '') {
								$ant++;
								if(($ifn2z == $ifn1[$n2]) || ($ifn2z == $ifn2[$n2]) || ($ifn2z == $ifn3[$n2])
								 || ($ifn2z == $ifn4[$n2]) || ($ifn2z == $ifn5[$n2]) ) {
									$pos++;
									$ntest++;
								}
							}
						}
//					
						if($ifn3z != '') {
							if($ifn1[$n2] != '') {
								$ant++;
								if(($ifn3z == $ifn1[$n2]) || ($ifn3z == $ifn2[$n2]) || ($ifn3z == $ifn3[$n2])
								 || ($ifn3z == $ifn4[$n2]) || ($ifn3z == $ifn5[$n2]) ) {
									$pos++;
									$ntest++;
								}
							}
						}
//					
						if($ifn4z != '') {
							if($ifn1[$n2] != '') {
								$ant++;
								if(($ifn4z == $ifn1[$n2]) || ($ifn4z == $ifn2[$n2]) || ($ifn4z == $ifn3[$n2])
								 || ($ifn4z == $ifn4[$n2]) || ($ifn4z == $ifn5[$n2]) ) {
									$pos++;
									$ntest++;
								}
							}
						}
//					
						if($ifn5z != '') {
							if($ifn1[$n2] != '') {
								$ant++;
								if(($ifn5z == $ifn1[$n2]) || ($ifn5z == $ifn2[$n2]) || ($ifn5z == $ifn3[$n2])
								 || ($ifn5z == $ifn4[$n2]) || ($ifn5z == $ifn5[$n2]) ) {
									$pos++;
									$ntest++;
								}
							}
						}
						if($ntest == 0) {
							$neg++;
							$neg++;
						}
//			
						if($neg < 3) {
//						
							if($ifd2z != '') {
								if($ifd2[$n2] != '') {
									$ant++;
									if($ifd2z == $ifd2[$n2]) {
										$pos++;
									}
									else {
										$neg++;
									}
								}
							}
//						
							if($ifd3z != '') {
								if($ifd3[$n2] != '') {
									$ant++;
									if($ifd3z == $ifd3[$n2]) {
										$pos++;
									}
									else {
										$neg++;
									}
								}
							}
//						
							if($ifpz != '') {
								if($ifp[$n2] != '') {
									$ant++;
									if($ifpz == $ifp[$n2]) {
										$pos++;
									}
									else {
										$neg++;
										$neg++;
									}
								}
							}
//			
							if($neg < 3) {
//						
								if($idd2z != '') {
									if($idd2[$n2] != '') {
										$ant++;
										if($idd2z == $idd2[$n2]) {
											$pos++;
										}
										else {
											$neg++;
										}
									}
								}
//						
								if($idd3z != '') {
									if($idd3[$n2] != '') {
										$ant++;
										if($idd3z == $idd3[$n2]) {
											$pos++;
										}
										else {
											$neg++;
										}
									}
								}
//						
								if($idpz != '') {
									if($idp[$n2] != '') {
										$ant++;
										if($idpz == $idp[$n2]) {
											$pos++;
										}
										else {
											$neg++;
											$halv++;
										}
									}
								}
//			
								if($neg < 3) {
//	Boost				
									$ntest = 0;
									if($ienz != '') {
										if($ien[$n2] != '') {
											if($ienz == $ien[$n2]) {
												$bon++;
												if($ienz != $ien1z) {
													$bon++;
												}	
												$ntest++;
											}
										}
									}
//						
									if($ien1z != '') {
										if($ien1[$n2] != '') {
											$ant++;
											if(($ien1z == $ien1[$n2]) || ($ien1z == $ien2[$n2]) || ($ien1z == $ien3[$n2])) {
												$pos++;
											}
											$ntest++;
										}
									}
//						
									if($ien2z != '') {
										if($ien1[$n2] != '') {
											$ant++;
											if(($ien2z == $ien1[$n2]) || ($ien2z == $ien2[$n2]) || ($ien2z == $ien3[$n2])) {
												$pos++;
											}
											$ntest++;
										}
									}
//						
									if($ien3z != '') {
										if($ien1[$n2] != '') {
											$ant++;
											if(($ien3z == $ien1[$n2]) || ($ien3z == $ien2[$n2]) || ($ien3z == $ien3[$n2])) {
												$pos++;
											}
											$ntest++;
										}
									}
									if($ntest == 0) {
										$halv++;
									}
//	Boost					
									if($kp1z != '') {
										if($kp1[$n2] != '') {
											if(($kp1z == $kp1[$n2]) && (strlen($ifdz) == 8)) {
												$bon++;
											}
										}
									}
//	Boost					
									if($kp2z != '') {
										if($kp2[$n2] != '') {
											if(($kp2z == $kp2[$n2]) && (strlen($iddz) == 8)) {
												$bon++;
											}
										}
									}
//	Boost					
									if($kp3z != '') {
										if($kp3[$n2] != '') {
											if($kp3z == $kp3[$n2]) {
												$bon++;
											}
										}
									}
//	Boost					
									if($kp4z != '') {
										if($kp4[$n2] != '') {
											if($kp4z == $kp4[$n2]) {
												$bon++;
											}
										}
									}
//	Boost					
									if($kp5z != '') {
										if($kp5[$n2] != '') {
											if($kp5z == $kp5[$n2]) {
												$bon++;
											}
										}
									}
//	Boost					
									if($kp6z != '') {
										if($kp6[$n2] != '') {
											if($kp6z == $kp6[$n2]) {
												$bon++;
											}
										}
									}
//	Tolka betydelsen av familj FAMC 
//					
//	Båda har barmfamilj men olika familjer
									if($fmcz != '') {
										if($fmc[$n2] != '') {
											if($fmcz != $fmc[$n2]) {
												$plus = '?';
												$halv++;
											}
										}
									}
//	En saknar barnfamilj (mor/dotter - far/som)					
									if($fmcz != '') {
										if($fmc[$n2] == '') {
											$plus = '+';
											$bon++;
										}
									}
//	En saknar barnfamilj (mor/dotter - far/som)					
									if($fmcz == '') {
										if($fmc[$n2] != '') {
											$plus = '+';
											$bon++;
										}
									}
								}
							}
						}
					}	
				}
				if($halv >= 2) {
					$neg++;
				}
//	
				if(($bon > 2) && ($pos > 2) && ($pos > $neg) && ($neg < 3) && ($ant > 2)) {
					$tmp = $pos/$ant;
					if($tmp >= 0.5) {
						$diff = $ant - $pos;
						$totp = $bon + $pos - $diff - $neg;
						if($plus == '+') {
							$totp = $totp + 3;
						}	
						if($plus == ' ') {
							$totp = $totp + 2;
						}	
						if($plus == '?') {
							$totp = $totp + 1;
						}	
						if($tmp >= 0.65) {
							$totp = $totp + 1;
						}	
						if($tmp >= 0.75) {
							$totp = $totp + 1;
						}	
						if($tmp >= 0.8) {
							$totp = $totp + 1;
						}	
						if($tmp >= 0.9) {
							$totp = $totp + 1;
						}	
						if($totp > 7) {
							if($totp <= 9) {
								$txtp = '0'.$totp;
							}
							else {
								$txtp = $totp;
							}
							$fellista[]=$txtp.":"
							.$numz.", ".$nradz.", ".$dradz.", ".$pradz.":"
							.$num[$n2].", ".$nrad[$n2].", ".$drad[$n2].", ".$prad[$n2];
							$kant++;
							$nyckel = $numz.';'.$num[$n2];
							$dublData[$nyckel] = $totp;
						}
						else {
							$nyckel = $numz.';'.$num[$n2];
							$dublData[$nyckel] = $totp;
						}
					}	
				}
//
				$n2++;
			}
			$n1++;
		}
//	
//	steg 4F avslutat	
//
		$handdub=fopen($filedub,"w");
//	Array start
		fwrite($handdub,json_encode($dublData)."\r\n");
		fclose($handdub);
//	Array slut
//
		if($kant > 0) {
//
//	Disgen Ask
			if($dg == 'JA') {
				$handu2=fopen($fileu2,"w");
				fwrite($handu2,$stig."\r\n");
			}
//	Ask end			
			rsort($fellista);
			fwrite($handut," \r\n");
			foreach($fellista as $felrad) {
				$xlen = 0;
				$xant = 0;
				$xblock = '';
//	Disgen Ask
				$yblk1 = '';
				$yblk2 = '';
				$yblk3 = '';
				$yblock = '';
//	Ask end				
				$xlen = strlen($felrad);
				while($xant <= $xlen) {
					$xtkn = substr($felrad,$xant,1);
					if($xtkn == ':') {
						if($yblk1 == '') {
							$yblk1 = $xblock;
						}
						$xblock = $xblock.$xtkn;
//echo $xblock."<br/>";
						fwrite($handut,$xblock." \r\n");
						$xblock = '';
					}
					else {
//	Disgen Ask
						if($xtkn == ',') {
							if($yblk2 == '') {
								$ylen = strlen($xblock);
								$yblk2 = substr($xblock,1,$ylen);
							}
						}
//	Ask end
						$xblock = $xblock.$xtkn;
					}
					$xant++;
				}
//	Disgen Ask
//echo $xblock."<br/>";
				$xlen = 0;
				$xant = 1;
				$xlen = strlen($xblock);
				while($xant <= $xlen) {
					$xtkn = substr($xblock,$xant,1);
					if($xtkn == ',') {
							$xant = $xlen;
					}
					else {
						$yblk3 = $yblk3.$xtkn;
					}
					$xant++;
				}
//	Disgen Ask
				$toty = 0;
				if($yblk1 >= 28) {
					$toty = 20;
				}
				elseif($yblk1 >= 25) {
					$toty = 19;
				}
				elseif($yblk1 >= 22) {
					$toty = 18;
				}
				elseif($yblk1 >= 19) {
					$toty = 17;
				}
				elseif($yblk1 >= 16) {
					$toty = 16;
				}
				elseif($yblk1 >= 13) {
					$toty = 15;
				}
				else {
					$toty = 14;
				}
//	Ask end			
				$yblock = $toty.','.$yblk2.','.$yblk3;
/*
echo "<br/>";
echo "1 /".$yblk1."/ <br/>";
echo "1 /".$toty."/ <br/>";
echo "2 /".$yblk2."/ <br/>";
echo "3 /".$yblk3."/ <br/>";
echo $yblock."<br/>";
echo "<br/>";
*/
				if($dg == 'JA') {
					fwrite($handu2,$yblock."\r\n");
					$yblock = '';
				}
//	Ask end
				fwrite($handut,$xblock." \r\n");
				fwrite($handut," \r\n");
				$xblock = '';
			}
// Disgen Ask
			if($dg == 'JA') {
				fclose($handu2);
			}
//	Ask end
			echo "<br/>";
			if($kant > 1) {
				echo "Bearbetningen sökte fram ".$kant." dubblettkandidater <br/>";
			}
			else {
				echo "Bearbetningen sökte fram ".$kant." dubblettkandidat <br/>";
			}	
			echo "Ta hand om den skapade filen RGDXL.txt<br/>";
			fwrite($handut," \r\n");
			fwrite($handut,"Bearbetningen sökte fram ".$kant." dubblettkandidater. \r\n");
		}
		else {
			echo "<br/>";
			echo "Inga kandidater hittade, sökningen avslutad. <br/>";
			echo "Filen RGDXL.txt skapad men innehåller bara rubriken.<br/>";
			fwrite($handut," \r\n");
			fwrite($handut,"Inga kandidater hittade, sökningen avslutad. \r\n");
		}
	fclose($handut);
	}
//**** extra fixen
	}
	else
	{
		echo "<br/>";
		echo "Filen ".$filename." saknas, programmet avbryts <br/>";
	}
	echo "<br/>";
	echo "Program klart ".date('Y-m-d')." / ".date('H:i:s')."<br/>";
}
?>
