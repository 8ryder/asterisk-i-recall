<?php


// le credenziali ed i dati della BD
$host="localhost"; // nome host della banca dati
$username="root"; // nome utente per la connessione al database
//$password="sigmabox2013"; // password dell'utente per la connessione al database
$db_name="asteriskcdrdb"; // nome database
$tbl_name="cdr"; // nome tabella
//tabella associazione interni-reparto
$db_name2 = "asteriskrecall";
$tbl_name2 = "interni";
//tabella numeri autorizzati
$tbl_name3 = "numeriautorizzati";

//chi ha chiamato
$callerid = '3348319995';
//interno
$exten = '00000';
//direzione chiamata
$direzione = '000000';
//reparto
$reparto = '000000';
//ora e minuti chiamata
$call_hour = '000000';
$call_minutes = '00000';

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
        echo 'src - ';
        echo $row['src'];
        $src = $row['src'];
        echo 'dst -  ';
        echo $row['dst'];
        $dst = $row['dst'];
        echo 'calldate - ';
        echo $row['calldate'];
        $calldate = $row['calldate'];
}


//se non ci sono chiamate, EXIT
$calls_found = mysql_num_rows($result);
echo $calls_found;
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
		echo 'uscita';
		//trattandosi di una chiamata in uscita, src equivale al nostro interno che cerchiamo
		$exten = $src;
elseif (strpos($src,$callerid) !== false):
        $direzione = 'entrata';
		echo 'entrata';
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

echo "\r\n";
echo "autorizzato - ".$autorizzato;
echo "\r\n";

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
echo '========================================';
echo "chiamate trovate: ".$calls_found."\r\n";
echo "direzione: ".$direzione."\r\n";
echo "numero autorizzato: ".$autorizzato."\r\n";
echo "chiamante: ".$callerid."\r\n";
echo "interno: ".$exten."\r\n";
echo "reparto: ".$reparto."\r\n";
echo "ora chiamata: ".$call_hour."\r\n";
echo "minuti chiamata: ".$call_minutes."\r\n";
echo '========================================';


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
        echo "reparto: ".$row['reparto']."\r\n";
        $reparto = $row['reparto'];
        echo ' ';
    }
return $reparto;
}

//================================================
// funzione recupero ora chiamata
//================================================
function getHour($calldate) {
	$call_hour = substr($calldate,11,2);
	echo $call_hour;
	
return $call_hour;
}

//================================================
// funzione recupero minuto chiamata
//================================================
function getMinutes($calldate) {
	$call_minutes = substr($calldate,14,2);
	echo $call_minutes;
	
return $call_minutes;
}

?>
