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

//mi collego al database
$link = mysql_connect("$host", "$username")or die("connessione alla banca dati fallita. Riprovare tra 1 minuto.".mysql_error());

mysql_select_db("$db_name")or die("Connessione al database fallita. Riprovare tra 1 minuto.".mysql_error());

// seleziono dal CDR l'ultima chiamata che coinvolge il callerID
$sql="select * from $tbl_name where (src LIKE '%3348319995' OR dst LIKE '%3348319995') AND calldate  > (DATE_SUB(CURRENT_TIMESTAMP,INTERVAL 1 DAY)) ORDER BY calldate DESC LIMIT 1 ;";
$result = mysql_query($sql) or die(mysql_error());

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
$num_rows = mysql_num_rows($result);
echo $num_rows;
if ($num_rows == '0'):
        exit();
endif;



//vediamo se la chiamata trovata è stata in entrata o in uscita
//se si tratta di una chiamata in entrata, EXIT, non ci interessa
if (strpos($dst, '3348319995') !== false):
        $direzione = 'uscita';
     echo 'uscita';

elseif (strpos($src,'3348319995') !== false):
        $direzione = 'entrata';
     echo 'entrata';
        exit();

endif;

//cambio DB
mysql_select_db("$db_name2")or die("Connessione al database fallita. Riprovare tra 1 minuto.".mysql_error());

//controlliamo se il CALLERID fa parte dei numeri autorizzati
$sql3="SELECT * from $tbl_name3 WHERE numero LIKE '%3348319995';";
$result3 = mysql_query($sql3) or die(mysql_error());

//valuto il risultato3 (se ci sono corrispondenze, il ho una riga, se no zero)
$num_rows3 = mysql_num_rows($result3);

if ($num_rows3 == '1'):
    $autorizzato = 'si';
elseif ($num_rows3 == '0'):
        $autorizzato = 'no';
endif;

echo "\r\n";
echo "autorizzato - ".$autorizzato;
echo "\r\n";

// seleziono dalla tabella le fasce attive in questo momente
$sql2="SELECT * from $tbl_name2 WHERE interno = $interno;";
$result2 = mysql_query($sql2) or die(mysql_error());



while ($row = mysql_fetch_assoc($result2)) {
        echo $row['reparto'];
        $reparto = $row['reparto'];
        echo ' ';
    }

mysql_close($link);


?>
