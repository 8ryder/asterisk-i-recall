#!/usr/bin/php -q
<?php

require 'phpagi.php';
$agi = new AGI();

// le credenziali ed i dati della BD
$host="localhost"; // nome host della banca dati
$username="root"; // nome utente per la connessione al database
//$password="changemepassword"; // password dell'utente per la connessione al database
$db_name="asteriskcdrdb"; // nome database
$tbl_name="cdr"; // nome tabella
//tabella associazione interni-reparto
$db_name2 = "asteriskrecall";
$tbl_name2 = "interni";
//tabella numeri autorizzati
$tbl_name3 = "numeriautorizzati";

//chi ha chiamato
$callerid = $agi->request['agi_callerid'];
//$callerid = substr($callerid,2);
//interno
$exten = '00000';
//direzione chiamata
$direzione = '000000';
//reparto
$reparto = '000000';
//ora e minuti chiamata
$call_hour = '000000';
$call_minutes = '00000';
$disposition = '00000';

//mi collego al database
$link = mysql_connect("$host", "$username")or die("connessione alla banca dati fallita. Riprovare tra 1 minuto.".mysql_error());

mysql_select_db("$db_name")or die("Connessione al database fallita. Riprovare tra 1 minuto.".mysql_error());

//================================================
// seleziono dal CDR l'ultima chiamata che coinvolge il callerID
//================================================
$sql="select * from $tbl_name where (src LIKE '%$callerid' OR dst LIKE '%$callerid') AND calldate  > (DATE_SUB(CURRENT_TIMESTAMP,INTERVAL 1 DAY)) ORDER BY calldate DESC LIMIT 1 ;";
$result = mysql_query($sql) or die(mysql_error());
mysql_close($link);

//estrapolo il risultato
while ($row = mysql_fetch_assoc($result)) {
        $src = $row['src'];
        $dst = $row['dst'];
        $calldate = $row['calldate'];
	$disposition = $row['disposition'];
}


//se non ci sono chiamate, EXIT
$calls_found = mysql_num_rows($result);

if ($calls_found == '0'):
        echo 'nessuna chiamata trovata';
		exit();
endif;


//================================================
//vediamo se la chiamata trovata è stata in entrata o in uscita
//se si tratta di una chiamata in entrata, EXIT, non ci interessa
//================================================
if (strpos($dst, $callerid) !== false):
        $direzione = 'uscita';
		//trattandosi di una chiamata in uscita, src equivale al nostro interno che cerchiamo
		$exten = $src;
elseif (strpos($src,$callerid) !== false):
        $direzione = 'entrata';
	$agi->set_variable("direction", $direzione);
        exit();

endif;

//================================================
//vediamo se la chiamata trovata Ã¨ stata con risposta
//se Ã¨ ANSWERED, non ci interessa, EXIT
//================================================
if ($disposition == 'ANSWERED'):
                //trattandosi di una chiamata risposta, non ci interessa, usciamo
                exit();
endif;

//mi collego al database
$link = mysql_connect("$host", "$username")or die("connessione alla banca dati fallita. Riprovare tra 1 minuto.".mysql_error());
//cambio DB
mysql_select_db("$db_name2")or die("Connessione al database fallita. Riprovare tra 1 minuto.".mysql_error());

//================================================
//controlliamo se il CALLERID fa parte dei numeri autorizzati
//================================================
$sql3="SELECT * from $tbl_name3 WHERE numero LIKE '%$callerid';";
$result3 = mysql_query($sql3) or die(mysql_error());
mysql_close($link);

//valuto il risultato3 (se ci sono corrispondenze, ho una riga, se no zero)
$num_rows3 = mysql_num_rows($result3);

if ($num_rows3 == '1'):
    $autorizzato = 'si';
elseif ($num_rows3 == '0'):
        $autorizzato = 'no';
endif;


//se il numero non ha lautorizzazione, recuperiamo il reparto
if ($autorizzato == 'no'):
	$reparto = getReparto($exten,$tbl_name2,$db_name2,$host,$username);
endif;

//recuperiamo ora e minuti chiamata
$call_hour = getHour($calldate);
$call_minutes = getMinutes($calldate);

//================================================
// stampa finale risultati
//================================================
$agi->set_variable("direction", $direzione);
$agi->set_variable("callsFound", $calls_found);
$agi->set_variable("authorizedNumber", $autorizzato);
$agi->set_variable("caller", $callerid);
$agi->set_variable("exten", $exten);
$agi->set_variable("department", $reparto);
$agi->set_variable("callHour", $call_hour);
$agi->set_variable("callMinutes", $call_minutes);
$agi->set_variable("disposition", $disposition);




//================================================
// funzione recupero reparto associato ad interno
//================================================
function getReparto($exten,$tbl_name2,$db_name2,$host,$username) {
//mi collego al database
$link = mysql_connect("$host", "$username")or die("connessione alla banca dati fallita. Riprovare tra 1 minuto.".mysql_error());
//cambio DB
mysql_select_db("$db_name2")or die("Connessione al database fallita. Riprovare tra 1 minuto.".mysql_error());
	
$sql2="SELECT * from $tbl_name2 WHERE interno = '$exten';";
$result2 = mysql_query($sql2) or die(mysql_error());
mysql_close($link);


while ($row = mysql_fetch_assoc($result2)) {
        $reparto = $row['reparto'];
    }
return $reparto;
}

//================================================
// funzione recupero ora chiamata
//================================================
function getHour($calldate) {
	$call_hour = substr($calldate,11,2);
	
return $call_hour;
}

//================================================
// funzione recupero minuto chiamata
//================================================
function getMinutes($calldate) {
	$call_minutes = substr($calldate,14,2);
	
return $call_minutes;
}

?>

