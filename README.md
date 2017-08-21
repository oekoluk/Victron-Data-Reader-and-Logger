# Victron-Data-Reader-and-Logger

/************************************************************************************************/
/*  Dieses Programm kann frei kopiert und modifiziert werden 							 	  	*/
/*  Das Programm liest über Victron-USB-Kabel Daten von Victron-Produkten im Text-Modus ein 	*/
/*  und protokolliert diese als CSV-Datei													 	*/
/*	Das Programm kann unter Ausschluss jeglicher Haftung auf eigene Verantwortung genutzt werden*/
/*	Danke an Peter Ott fuer die korrekten stty Einstellungen									*/
/*	Die Autoren Franz Spreitz und Lukas Pawek freuen sich ueber einen Hinweis im Programm-Code: */
/*	Code von Franz Spreitz und Lukas Pawek https://energieautark.wordpress.com 					*/
/************************************************************************************************/

Benötigte Ausrüstung: Raspberry Pi Computer inkl. 8GB SD-Karte, ein VE.Direct-USB-Kabel zur Verbindung an ein Victron-Produkt (zB. Laderegler).
Im Beispielprogramm werden drei Victron-Geraete ausgelesen und als CSV-Datei geloggt.
Wir setzen ein funktionsfähig installiertes Linux („Raspbian“) voraus. 
Es gibt im Fachhandel auch bereits korrekt installierte SD-Karten samt Betriebssystem zu kaufen. 
Weiters muss auch die Programmiersprache PHP installiert sein. Im Internet gibt es zahlreiche Anleitungen, wie dies bewerkstelligt wird. 

Nun gibt es zwei Arten der Messwert-Erfassung: Die lokale Speicherung oder die Speicherung auf einem Webserver. Das von uns zur Verfügung gestellte Programm speichert die Daten lokal als .CSV Datei. 
Tipp: Wenn die Daten nahezu live mitprotokolliert werden sollen, empfehlen wir durch das hohe Datenaufkommen die Speicherung auf einem externen Webserver in einer Datenbank, da die zahlreichen Speicherzugriffe auf den Raspberry die Lebensdauer der SD-Karte massiv senken und damit das System leicht zerstört werden kann, was wir bereits (unfreiwillig) mehrmals testen „durften“. 
Sobald das USB-Kabel am Victron-Gerät, in diesem Beispiel ein Laderegler Victron MPPT 100-30, ein Laderegler Victron MPPT 75-15 und ein Batteriemonitor BMV-700 an den Raspberry angeschlossen ist, werden Daten geliefert. 
Voraussetzung dafür ist, dass die Geräte natürlich bereits funktionieren, indem sie korrekt an die Batterie angeschlossen wurden. 

Nun muss nur der eindeutige Name der Victron-USB-Schnittstelle ermittelt werden. 
Diese beginnt (Stand: 2017) immer mit „/dev/serial/by-id/usb-VictronEnergy_BV_VE_Direct_cable_VE“. 
Eine Abfrage ermittelt den korrekten Namen: Mit dem Kommando „ls /dev/serial/by-id/usb-VictronEnergy_BV_VE_Direct_cable_VE*“. 
Dieser Name (also ab dem Verzeichnis by-id/) muss notiert werden. 
Der Name kann beispielsweise so lauten: „usb-VictronEnergy_BV_VE_Direct_cable_VE12345X-if00-port0“. 
Um die Daten korrekt zu empfangen, muss die nun ermittelte Schnittstelle korrekt eingestellt werden. 
Dies erfolgt beispielsweise beim hochbooten, also in der crontab. Wir öffnen in der „Shell“ die Crontab mittels: „crontab –e“.
Hinzugefügt wird die folgende Zeile: 
@reboot stty -F /dev/serial/by-id/usb-VictronEnergy_BV_VE_Direct_cable_NUMMERIHRESPRODUKTS-if00-port0 speed 19200 raw –echo
Weiters fügen wir das gewünschte Intervall hinzu, wie oft die Messwerte protokolliert werden. Wenn wir diese minütlich loggen möchten, was bereits viele Megabyte an Daten pro Monat produziert, so ist folgender Eintrag in einer neuen Zeile notwendig:
* * * * * sudo php /home/pi/lesenschreiben.php
Anmerkung: Das Verzeichnis muss dementsprechend angepasst werden, wenn das Programm zum Lesen und Schreiben der Daten („lesenschreiben.php“) in ein anderes Verzeichnis als das Standard-Home-Directory kopiert wurde.
Speichern und ein Neustart übernimmt die korrekten Parameter.   

Wichtig! Das PHP-Programm muss mit Admin-Rechten ausgefuehrt werden, da vom USB-Port gelesen und danach eine Datei geschrieben wird.
Standardmaessig wird eine Datei ins Webroot geschrieben, damit sie sofort am Smartphone/Computer ausgelesen werden kann.
Dateiname: /var/www/html/messwertesimple.csv
Die Datei wird automatisch von PHP angelegt.