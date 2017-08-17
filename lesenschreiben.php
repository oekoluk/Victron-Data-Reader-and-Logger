<?php
error_reporting(E_ERROR | E_WARNING | E_PARSE);

/************************************************************************************************/
/*  Dieses Programm kann frei kopiert und modifiziert werden 							 	  	*/
/*	Das Programm kann unter Ausschluss jeglicher Haftung auf eigene Verantwortung genutzt werden*/
/*	Die Autoren Franz Spreitz und Lukas Pawek freuen sich ueber einen Hinweis im Programm-Code: */
/*	Code von Franz Spreitz und Lukas Pawek https://energieautark.wordpress.com 					*/
/************************************************************************************************/



/*****************************************************************************************/
/*********** Funktion sauber dient zum saeubern der Variablen von Sonderzeichen *********/
/*****************************************************************************************/
function sauber($uebergabe) {
        $uebergabe=str_replace("\n", "", $uebergabe);
        $uebergabe=str_replace("\r", "", $uebergabe);
        $uebergabe=str_replace("   ", "", $uebergabe);
        return $uebergabe;
}

/*****************************************************************************************/
/*********** Funktion readdata dient zum extrahieren der Variablen aus dem Datensatz   ***/
/*****************************************************************************************/
function readdata($befehl) {
	$str="";
	$einzel="";
	$varname="";

	$str = shell_exec ($befehl);
	//echo $str;
	$arr = explode ("\n", $str);
	for($x=0;$x<count($arr);$x++) { 
                $einzel=explode("\t", $arr[$x]);
                $varname=sauber($einzel[0]);
                $arrneuret[$varname]=sauber($einzel[1]);
	}
	return $arrneuret;
}

/******************************************************************************************/
/* Mittels des Grep-Befehls wird ein gesamter Datensatz eines Geraets 					  */
/* (zb. vom Laderegler) vom USB-Port abgegriffen. Dieser Befehl muss spaeter nur noch	  */
/* um die eindeutige ID des USB-Kabels ergaenzt werden. Die eindeutigen IDs können mit	  */
/* diesem Befehl eruiert werden: 														  */
/* ls /dev/serial/by-id/usb-VictronEnergy_BV_VE_Direct_cable_VE*						  */
/* Notieren Sie den Namen des Geraets ohne die Verzeichnisse, also zB. 					  */
/* usb-VictronEnergy_BV_VE_Direct_cable_VE12345X-if00-port0								  */
/******************************************************************************************/
$grepbefehl="timeout 5 grep -a -m 1 PID -A 50 /dev/serial/by-id/";


$csvstring=date("d.m.Y H:i").";"; // Variable, die einen neuen Eintrag (also einen neue Zeile) in der .CSV Datei - mittels Semikolon (;) getrennt - schreibt. Der erste Eintrag ist immer das aktuelle Datum samt Uhrzeit 

/******************************************************************************************/
/* Wichtige Variablen, die von den Victron-Geraeten zurueckgeliefert werden:			  
/* CS... Charger State - Status der Laderegler											  */
/* H20.. aufsummierte taegliche Produktion des Ladereglers								  */
/* P.... Leistung am Batteriemonitor. Vorsicht: Direkt "verbrauchte" Leistung 			  */
/*		 muss vom Laderegler addiert werden
/* PPV.. Modulleistung																	  */
/* SOC.. State of Charge - Batterie-Ladezustand	in Prozent mit einer Nachkommastelle	  */
/* V.... Batteriespannung (geliefert vom Batteriemonitor BMV)							  */
/* VPV.. Modulspannung in Millivolt														  */
/******************************************************************************************/

/****************************/
/* BMV-Daten einlesen ******/
/****************************/
$usbname="usb-VictronEnergy_BV_VE_Direct_cable_KORREKTE-SERIENNUMMER-HIER-EINTRAGEN-if00-port0";
$befehl=$grepbefehl.$usbname;
//Beim Booten muss in der crontab noch die Baudrate auf 19200 eingestellt und das Echo abgeschalten werden - und zwar fuer alle Victron-USB-Anschluesse:
//@reboot stty -F /dev/serial/by-id/usb-VictronEnergy_BV_VE_Direct_cable_KORREKTE-SERIENNUMMER-HIER-EINTRAGEN-if00-port0 speed 19200 raw -echo
$arrneu=readdata($befehl);
$num=$arrneu['V']/1000;
$csvstring.=$num.";".$arrneu['P'].";".$arrneu['SOC'].";";

/*******************************************************/
/* MPPT-Daten 100/30 westseitige Module einlesen *******/
/*******************************************************/
$usbnamemppt10030="usb-VictronEnergy_BV_VE_Direct_cable_KORREKTE-SERIENNUMMER-HIER-EINTRAGEN-if00-port0";
$befehlmppt=$grepbefehl.$usbnamemppt10030;
$arrneumppt=readdata($befehlmppt);
$VPV=$arrneumppt['VPV']/1000; // Modulspannung = VPV - da diese in Millivolt angegeben ist, durch 1.000 dividieren
$csvstring.=$VPV.";".$arrneumppt['PPV'].";".$arrneumppt['CS'].";".$arrneumppt['H20'].";";

/*******************************************************/
/* MPPT-Daten 75/15 suedseitiges Modul einlesen ********/
/*******************************************************/
$usbnamemppt7515="usb-VictronEnergy_BV_VE_Direct_cable_KORREKTE-SERIENNUMMER-HIER-EINTRAGEN-if00-port0";
$befehlmppt7515=$grepbefehl.$usbnamemppt7515;
$arrneumppt7515=readdata($befehlmppt7515);
$VPV7515=$arrneumppt7515['VPV']/1000; // Modulspannung = VPV - da diese in Millivolt angegeben ist, durch 1.000 dividieren
$csvstring.=$VPV7515.";".$arrneumppt7515['PPV'].";".$arrneumppt7515['CS'].";".$arrneumppt7515['H20'].";";

/*******************************************************/
/* Daten als lokale .csv Datei speichern    			*/
/*******************************************************/
if(is_numeric($num)) { // Abfrage, ob die Spannung ein numerischer Wert ist. Wenn nicht, handelt es sich um fehlerhafte Daten und dann sollten keine Daten geschrieben werden...
	//echo $csvstring;
	$csvstring.="\n";
	$dir="/var/www/html/"; // Soll die Datei gleich im lokalen Netzwerk angezeigt werden, sollte hier das Web-Root eingetragen werden, also zB. /var/www/html/ Dann koennen die Messdaten direkt ueber das Smartphone angezeigt werden, indem die lokale IP-Adresse des Raspberry mit folgendem Zusatz aufgerufen werden: messwertesimple.csv - also beispielsweise http://192.168.1.103/messwertesimple.csv, wobei hier klarerweise die korrekte IP-Adresse eingetragen werden muss.
	$out = fopen($dir."messwertesimple.csv", "a+");
	fwrite($out, $csvstring);
	fclose($out);	
}
?>
