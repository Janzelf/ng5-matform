<?php
/**
 * Created by PhpStorm.
 * User: Jan
 * Date: 12-11-2014
 * Time: 18:01
 */
header("Access-Control-Allow-Origin: *");
//if (isset ($_REQUEST["op"])) {
//    print "{toernooinaam: 'indexN in test!'}";
//}
//print json_encode($_REQUEST);
session_name("BEHEER");
if (isset ($_REQUEST["BEHEER"]))
    session_id($_REQUEST["BEHEER"]);
session_start();
ini_set('display_errors', true);
error_reporting (E_ALL | E_STRICT);
include_once("../class.phpmailer.php");
//print nl2br(print_r($_REQUEST,true));
//print nl2br(print_r($_SESSION,true));
//testMail(print_r($_REQUEST,true));

if (!empty($_REQUEST['stap1'])) {   // stap 1 vanaf TA
    foreach($_REQUEST as $key => $element) {
        if ($key != "stap1") {
            $element = str_ireplace('&', 'en', $element);  // geen & aan mijn lijf!!
            $_SESSION[$key] = iconv('CP1252', 'UTF-8//IGNORE', $element);
        }
    }
    checkTransactions();
    $sid = session_name() . "=" . session_id();
    print  $sid . "\"\n";
    foreach($_SESSION["IBANfouten"] as $ibanfout) {
        print $ibanfout."\n";
    }
    exit();
}

if (isset($_REQUEST["BEHEER"])) {         // stap 2 vanaf TA
    if (empty($_SESSION['aantal'])) {
        header("Location: https://www.toernooiklapper.nl/inc-ng/selectproef");
        exit;
    } else {
        header("Location: https://www.toernooiklapper.nl/inc-ng/selectinc");
        exit;
    }
}

$isProef = false;
if (!empty($_REQUEST['proefincasso'])) $isProef = true;

/* voor stap 4 vanaf app, géén gewone POST variabelen */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($_POST)) {
    $postdata = json_decode(file_get_contents('php://input'), true);
    if (!empty($postdata['isProef']))   $isProef = ($postdata['isProef'] == 1) ? true : false;
}

//testMail(__LINE__ . " ". print_r($postdata,true));
if (isset($postdata["op"])) {
    switch ($postdata["op"]) {
        case "incasso" :
            if ($isProef) {
                maaktest($postdata);
                echo '{"result": "to the end van proef!"}';
            } else {
                maak($postdata);
                echo '{"result": "to the end van echt!"}';
                if (!empty($_SESSION["sepaerrors"]) || !empty($_SESSION["sepaerrors"])) {
                    reporterrors();
                }
            }
            exit;
            break;
    case "getparms" :                       //stap 3 vanaf app
        $parms['fname'] = 'incasso/'.$_SESSION['plaatsbewijs'].date("-YmdHi").".xml";
        $_SESSION['fname'] = $parms['fname'];
        $parms['toernooinaam'] = $_SESSION['toernooinaam'];
        $parms['aantal'] = $_SESSION['aantal'];
        $parms['totaal'] = $_SESSION['totaalbedrag'];
        print json_encode($parms);
        //testMail(__LINE__ . " getparms session: " . nl2br(json_encode($parms,JSON_PRETTY_PRINT)));
        exit;
        break;
    default :
        print json_encode($_REQUEST);
    }
}

/**
 *
 */
function reporterrors() {
    $body = <<<TAG
<body style="background-color: LightSkyBlue">
<p>Er zijn fouten opgetreden!<br>Gegevens bijgevoegd.
TAG;
    $errstr = print_r($_SESSION, true);
    $body .= "<p>Incassorun ToernooiKlapper</p></body>";
    $mail = new PHPMailer();
    $mail->CharSet = "UTF-8";
    $mail->Subject = "Incassobestand: fouten in run!";
    $mail->AddAddress("support@toernooiklapper.nl", "Test");
    $mail->AddStringAttachment($errstr, "errors.txt");
    $mail->Body = $body;
    $mail->IsHTML(true);
    if (!$mail->Send()) {
        $_REQUEST["mailerror2"] = $mail->ErrorInfo; // ???
    };
}

function checkTransactions()
{
    if (empty($_SESSION['transacties'])) {
        $_SESSION['aantal'] = 0;
        $_SESSION['totaalbedrag'] = 0;
        $_SESSION['IBANfouten'] = [];
    return;
    }
    $IBANfouten = [];
    $aantal = 0;
    $totaalbedrag = 0;
    $tar = explode("\n", $_SESSION["transacties"]);
    foreach ($tar as $tx) {
        $txarr = str_getcsv($tx, ";");
        if (empty($txarr[1])) continue;
        $iban = strtoupper($txarr[1]);
        if (!checkIBAN($iban)) {
            $IBANfouten[] =  $iban." ".$txarr[0];  // naam en IBAN
            continue;
        }
        $aantal += 1;
        $totaalbedrag += $txarr[4];
    }
    $_SESSION["IBANfouten"] = $IBANfouten;
    $_SESSION["aantal"] = $aantal;
    $_SESSION["totaalbedrag"] = money_format('%.2n',$totaalbedrag);
}

/**
 * @throws Exception
 * @throws phpmailerException
 * maak de sepa xml-file en mail het resultaat
 */
function maak($postdata)
{
    include_once("../class.phpmailer.php");
    ob_start();
    $tar = explode("\n", $_SESSION["transacties"]);
    require 'incasso/vendor/autoload.php';

// create new instance
    $gegevens = json_decode($postdata["gegevens"],true);
    testMail(__LINE__ . nl2br(print_r($gegevens,true)));
    $creator = new \SepaXmlCreator\SepaXmlCreator();
    $rekeningnr = strtoupper($gegevens["rekeningnr"]);
    $incassant = $gegevens['incassant'];
    $incid = strtoupper($gegevens['incid']);
    $incassant = str_ireplace('&','en', $incassant);

    $creator->setAccountValues($incassant, $rekeningnr, toBic($rekeningnr));
    $toernooinaam = $_SESSION['toernooinaam'];


    if (!empty($gegevens["herhaalbaar"])) $creator->setIsFolgelastschrift();

//Add creditor identifier you get from
    $creator->setCreditorIdentifier($incid);
    /*
    Optional parameter. If not set, execution will be done as soon as possible
    1 for tomorrow, 2 for day after tomorrow and so on
     */
    $creator->setExecutionOffset(3);
    $fouten = array();
    $aantal = 0;
    $totaalbedrag = 0;
    foreach ($tar as $tx) {
        $txarr = str_getcsv($tx, ";");
        if (empty($txarr[1])) continue;
        $iban = strtoupper($txarr[1]);
        if (!checkIBAN($iban)) {
            $fouten[] = "IBAN klopt niet van " . $txarr[0] . " " . $iban;
            //echo "IBAN klopt niet van ".$txarr[0]." ".$iban;
            continue;
        }
        $aantal += 1;
        $totaalbedrag += $txarr[4];
// Create new transfer
        $transaction = new \SepaXmlCreator\SepaTransaction();
// Amount
        $transaction->setAmount($txarr[4]);
// end2end reference (OPTIONAL)
        $transaction->setEnd2End('Inschrijving'); // max 35 lang
// recipient BIC
        $transaction->setBic($txarr[2]);
// recipient name
        $transaction->setRecipient($txarr[0]);
// recipient IBAN
        $transaction->setIban($iban);
// reference (OPTIONAL)
        $transaction->setReference($toernooinaam . " " . $txarr[10]);
// add mandate
        $transaction->setMandate($txarr[11], $txarr[12], true);  // niet meer nodig?????
// add transaction
        $creator->addTransaction($transaction);
    }

// generate the transfer file
    $sepaxml = $creator->generateSepaDirectDebitXml();

//print $sepaxml;

    $fname = $_SESSION['fname'];
    $body = <<<TAG
<body style="background-color: LightSkyBlue">
<h4>$toernooinaam</h4>
<p>Het gegenereerde bestand is hier te downloaden: <a href='https://www.toernooiklapper.nl/inc-ng/$fname' download>incassobestand</a>
<br><small>Klik met de rechtermuisknop op  de link!</small></p>
<p>Sla dit bestand op in uw PC en voer het binnen drie dagen in bij uw online
bankieren!</p>De volgende gegevens zijn gebruikt (check deze gegevens met uw contract!):
<ul>
<li>Tenaamstelling rekening: $incassant</li>
<li>Rekening nummer: $rekeningnr</li>
<li>Incassanten Id: $incid</li>
</ul>
<p>Het aantal transacties = $aantal, het totale bedrag € $totaalbedrag.<br>
TAG;
    if (!empty($fouten[0])) {
        $body .= "De volgende transacties worden <strong>niet</strong> uitgevoerd:<ul>";
        foreach ($fouten as $fout) {
            $body .= "<li>" . $fout . "</li>";
        }
        $body .= "</ul>";
        $body .= "Je kunt dit incassobestand gewoon invoeren en eventueel laten volgen door een additionele incassorun ";
        $body .= "- na verbetering van de incassonummers - maar zorg er voor dat er geen doublures in voorkomen!";
    }
    $body .= "<p>Gegenereerd door: ToernooiKlapper</p></body>";
    $mail = new PHPMailer();
    $mail->CharSet = "UTF-8";
    $mail->Subject = "Incassobestand";
    $mail->AddAddress($gegevens["pemail"], "Penningmeester");
    $mail->AddBCC("support@toernooiklapper.nl", "Test");
    //$mail->AddStringAttachment($sepaxml, "separun".$gegevens["runid"].".xml");
    $mail->Body = $body;
    $mail->IsHTML(true);
    if (!$mail->Send()) {
        echo "<br>stuur mail error: " . $mail->ErrorInfo;
    };
    ob_start();
    file_put_contents($fname, $sepaxml);
    $creator->validateSepaDirectDebitXml($fname);
    $creator->printXmlErrors();
    $_SESSION["sepaerrors"] = ob_get_contents();
    ob_end_clean();
    $_SESSION["phpwarnings"] = ob_get_contents();
    ob_end_clean();
}
/**
 * @param $nr
 * @return bool
 */
function checkIBAN($nr) {
    if (strlen($nr) !== 18) return false;
    $bank = substr($nr,4,4);      // bank
    $rnr = substr($nr,8);         // reknr
    $lc = substr($nr,0,2);        // lc
    $bankarray = str_split($bank);
    $out = '';
    foreach ($bankarray as $bchar) {
        $orde = ord($bchar) - ord("A") + 10;
        $out .= (string)$orde;
    }
    $out .= $rnr;
    $lcarray = str_split($lc);
    foreach ($lcarray as $lchar) {
        $out .= ord($lchar) - ord("A") + 10;
    }
    $out .= "00";
    $controlegetal = 98 - bcmod($out, 97);
    if (substr($nr,2,2) != $controlegetal) return false;
    return true;
}
/**
 * @throws Exception
 * @throws phpmailerException
 * maak de sepa xml-file en mail het resultaat
 */
function maaktest($postdata)
{
    include_once("../class.phpmailer.php");
    ob_start();
    require 'incasso/vendor/autoload.php';

// create new instance
    $gegevens = json_decode($postdata["gegevens"],true);
//    testMail(__LINE__ . nl2br(print_r($gegevens,true)));
    $creator = new \SepaXmlCreator\SepaXmlCreator();
    $rekeningnr = strtoupper($gegevens["rekeningnr"]);
    $incassant = $gegevens['incassant'];
    $incid = strtoupper($gegevens['incid']);
    $proefgegevens = json_decode($postdata["proefgegevens"],true);
    $proefaccount = $proefgegevens['proefaccount'];
    $rekeningnrt = strtoupper($proefgegevens['rekeningnrt']);
    $creator->setAccountValues($incassant, $rekeningnr, toBic($rekeningnr));
    if (!empty($_SESSION['toernooinaam'])) $toernooinaam = $_SESSION['toernooinaam'];


//Add creditor identifier you get from
    $creator->setCreditorIdentifier($incid);


    /*
    Optional parameter. If not set, execution will be done as soon as possible
    1 for tomorrow, 2 for day after tomorrow and so on
     */
    $creator->setExecutionOffset(3);
    $aantal = 1;
    $totaalbedrag = 1;
// Create new transfer
    $transaction = new \SepaXmlCreator\SepaTransaction();
// Amount
    $transaction->setAmount(1);
// end2end reference (OPTIONAL)
    $transaction->setEnd2End('TestInschrijving'); // max 35 lang
// recipient BIC
    $transaction->setBic(toBic($rekeningnrt));
// recipient name
    $transaction->setRecipient($proefaccount);
// recipient IBAN
    $transaction->setIban($rekeningnrt);
// reference (OPTIONAL)
    $transaction->setReference($toernooinaam);
// add mandate
    $transaction->setMandate("proef", "2015-09-01", true);
// add transaction
    $creator->addTransaction($transaction);
// generate the transfer file
$sepaxml = $creator->generateSepaDirectDebitXml();

//print $sepaxml;

    $fname = $_SESSION['fname'];
    $body = <<<TAG
<body style="background-color: LightSkyBlue">
<h4>$toernooinaam</h4>
<p>Het gemaakte bestand is hier te downloaden: <a href='https://www.toernooiklapper.nl/inc-ng/$fname' download>incassobestand</a></p>
<p>Sla dit bestand op in uw PC (<small>klik met de  rechtermuisknop!</small>) en voer het binnen drie dagen in bij uw online
bankieren!</p>
De volgende gegevens zijn gebruikt (check deze gegevens met uw contract!):
<ul>
<li>Tenaamstelling rekening: $incassant</li>
<li>Rekening nummer: $rekeningnr</li>
<li>Incassanten Id: $incid</li>
</ul>
Deze proef incasseert $totaalbedrag € van de rekening van de proefpersoon. Als deze transactie goed werkt zal
de echte incasso ook vlot verlopen omdat dezelfde instellingen gebruikt worden.
TAG;
    $body .= "<p>Gegenereerd door: ToernooiKlapper</p></body>";
    $mail = new PHPMailer();
    $mail->CharSet = "UTF-8";
    $mail->Subject = "Incassobestand";
    $mail->AddAddress($postdata["gegevens"]["pemail"], "Penningmeester");
    $mail->AddBCC("support@toernooiklapper.nl", "Test");
//    $mail->AddStringAttachment($sepaxml, "separun".$postdata["runid"].".xml");
    $mail->Body = $body;
    $mail->IsHTML(true);
    if (!$mail->Send()) {
        echo "<br>stuur mail error: " . $mail->ErrorInfo;
    };
    ob_start();
    file_put_contents($fname, $sepaxml);
    $creator->validateSepaDirectDebitXml($fname);
    $creator->printXmlErrors();
    $_SESSION["sepaerrors"] = ob_get_contents();
    ob_end_clean();
    $_SESSION["phpwarnings"] = ob_get_contents();
    ob_end_clean();
}

/**
 * @param $rekeningnr
 * @return string bic code
 */
function toBic($rekeningnr)
{
    $bic = strtoupper(substr($rekeningnr, 4, 4));
    $bankcode = array(
        "RBRB" => "RBRBNL21",
        "INGB" => "INGBNL2A",
        "RABO" => "RABONL2U",
        "ABNA" => "ABNANL2A",
        "TRIO" => "TRIONL2U",
        "SNSB" => "SNSBNL2A",
        "FVLB" => "FVLBNL22",
        "ASNB" => "ASNBNL21",
        "KNAB" => "KNABNL2H",
        "BUNQ" => "BUNQNL2A",
        "INSI" => "INSINL2A"
        );
    if (isset($bankcode[$bic])) {
        return $bankcode[$bic];
    }
    print "??";
    return "??";
}


function testMail($msg) {
    //echo "testMail:<p>";
    $body = "<p>".$msg."</p>";
    $mail = new PHPMailer();
    $mail->CharSet = "UTF-8";
    $mail->Subject = "Incassobestand";
    $mail->AddAddress("support@toernooiklapper.nl", "Nieuwe Incasso Test");
    $mail->SetFrom('info@toernooiklapper.nl','ToernooiKlapper',0);
    $mail->Body = $body;
    $mail->IsHTML(true);
    if (!$mail->Send()) {
        echo "<br>stuur mail error: " . $mail->ErrorInfo;
    };
}

