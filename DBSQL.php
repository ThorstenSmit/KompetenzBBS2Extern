<?php

/**
 * DBSQL: mysql cennection, input, update, etc.
 *
 * @author Thorsten Smit in 2015
 * @version 1.0
 */
class DBSQL {

    private $connection = NULL;
    private $result = NULL;
    private $counter = NULL;

    public function __construct($host = NULL, $database = NULL, $user = NULL, $pass = NULL) {
        $this->connection = mysql_connect($host, $user, $pass, TRUE) or die(mysql_error());
        mysql_select_db($database, $this->connection) or die(mysql_error());
    }

    public function installDB($database = NULL, $user = NULL, $pass = NULL, $rights = Null) {
        $result = mysql_query("CREATE DATABASE IF NOT EXIST " . $database);
        if (!$result) {
            return "Die Datenbank konnte nicht angelegt werden: " . mysql_error();
        } else {
            $result = mysql_query($rights . " ON " . $database . ".* TO " . $user . "@localhost IDENTIFIED BY " . $pass);
            if (!$result) {
                return "Der Benutzer konnte nicht angelegt werden: " . mysql_error();
            } else {
                return "Die Benutzer Anmeldung war erfolgreich";
            }
        }
    }

    public function disconnect() {
        if (is_resource($this->connection))
            mysql_close($this->connection);
    }

    public function queryAndObjektResult($query) {
        $this->result = mysql_query($query, $this->connection);
        $this->counter = NULL;
        if ($this->count() > 0) {
            return mysql_fetch_object($this->result);
        }else{
            return null;
        }
    }

    public function fetchRow() {
        return mysql_fetch_row($this->result);
    }

    public function fetchAssoc() {
        return mysql_fetch_assoc($this->result);
    }

    public function fetchObject() {
        if (is_resource($this->result)) {
            return mysql_fetch_object($this->result);
        } else {
            return null;
        }
    }

    public function count() {
        if ($this->counter == NULL && is_resource($this->result)) {
            $this->counter = mysql_num_rows($this->result);
        }
        return $this->counter;
    }

    public function insert_table($table, array $input) {
        if (is_string($table) && is_array($input)) {
            $cols = array_keys($input);
            $vals = array_values($input);
            $column = sprintf("%s", $cols[0]);
            $values = sprintf("'%s'", $vals[0]);
            for ($int = 1; $int < count($cols); ++$int) {
                if ($vals[$int] != 'Null') {
                    $column .= sprintf(", %s", $cols[$int]);
                    $values .= sprintf(", '%s'", str_replace("'", "\'", $vals[$int]));
                }
            }
            $syntax = sprintf("INSERT INTO %s (%s) VALUES (%s)", $table, $column, $values);
            @$res = mysql_query($syntax);
            if (mysql_errno()) {
                echo mysql_error() . '<br>';
            }
            if (!$res) {
                return false;
            } else {
                return mysql_insert_id();
            }
        } else {
            return false;
        }
    }

    public function saveAttributesFromTable($table) {
        $result = mysql_query("SHOW COLUMNS FROM " . $table . ";");
        $rows = array();
        while ($row = mysql_fetch_assoc($result)) {
            $rows[] = $row['Field'];
        }
        return $rows;
    }

    public function showAttributeDescribtionsFromTable($table) {
        $result = mysql_query("SHOW COLUMNS FROM " . $table . ";");
        $describtion = array();
        while ($row = mysql_fetch_assoc($result)) {
            if ($row['Key'] != 'PRI') {
                $describtion[0][] = $row['Field'];
                $describtion[1][] = $row['Type'];
            }
        }
        return $describtion;
    }

    public function saveGetFromFormular(array $items) {
        $table = $items['table'];
        $result = $this->query("SHOW COLUMNS FROM $table;");
        $PRI = $this->show_primary_key($table);
        $itemsToSave = array();
        while ($row = $this->fetchAssoc($result)) {
            if (array_key_exists($row['Field'], $items) AND $row['Field'] != $PRI) {
                $itemsToSave[$row['Field']] = $items[$row['Field']];
            }
        }
        return $itemsToSave;
    }

    /*
     * @param tablename
     * @return string Fieldname: primary key
     */

    public function show_primary_key($table) {
        $result = mysql_query("SHOW COLUMNS FROM $table;");
        if (!$result) {
            return 'Fehler';
        }
        if (mysql_num_rows($result) > 0) {
            while ($row = mysql_fetch_assoc($result)) {
                if ($row['Key'] == 'PRI') {
                    return $row['Field'];
                }
            }
        }
    }

    public function get_Primary_Key_From_Arbitrary_Needle($table, $Needle) {
        $PK = $this->show_primary_key($table);
        $result = mysql_query("SHOW COLUMNS FROM $table;");
        $attributes = array();
        while ($row = mysql_fetch_assoc($result)) {
            $attributes[] = $row['Field'];
        }
        for ($i = 0; $i < count($attributes); $i++) {
            $attributeTemp = $attributes[$i];
            $result = $this->query("SELECT $PK FROM $table WHERE $attributeTemp=$Needle;");
            if ($this->count() > 0) {
                $result = $this->fetchobject();
                $PKBack = $result->$PK;
            }
        }
        return $PKBack;
    }

    public function update_table($table, array $input, $ID, $DifferentKey = false, $String = false) {
        if ($DifferentKey) {
            $ID = explode(';', $ID);
            $whereKlausel = '';
            $keys = explode(';', $DifferentKey);
            for ($m = 0; $m < count($keys); $m++) {
                if ($m == 0) {
                    if (!$String) {
                        $whereKlausel.=$keys[$m] . '=' . $ID[$m] . ' ';
                    } else {
                        $whereKlausel.=$keys[$m] . '="' . $ID[$m] . '" ';
                    }
                } else {
                    $whereKlausel.=' AND ' . $keys[$m] . '=' . $ID[$m] . ' ';
                }
            }
        }
        $PRI = $this->show_primary_key($table);
        if (is_string($table) && is_array($input)) {
            $cols = array_keys($input);
            $vals = array_values($input);
            for ($int = 0; $int < count($cols); ++$int) {
                $val_temp = str_replace("'", "\'", $vals[$int]);
                if (!$DifferentKey) {
                    @mysql_query("UPDATE " . $table . " SET " . $cols[$int] . " = '" . $val_temp . "' WHERE $PRI = '" . $ID . "'");
                } else {
                    @mysql_query("UPDATE " . $table . " SET " . $cols[$int] . " = '" . $val_temp . "' WHERE " . $whereKlausel . ";");
                }
            }
            return true;
        } else {
            return false;
        }
    }

    public function delete_from_table($table, $ID) {
        $PRI = $this->show_primary_key($table);
        mysql_query("DELETE FROM " . $table . " WHERE $PRI = '$ID'");
    }

    public function getProjektIDIfExist($PhNo) {
        $this->query("SELECT ProjektID FROM projekt WHERE ProjektNoTaifun = '$PhNo';");
        if ($this->count() > 0) {
            $result = $this->fetchobject();
            return $result->ProjektID;
        } else {
            return false;
        }
    }

    public function getReklamationIDIfExist($ProjektID, $lfdNr) {
        $this->query("SELECT * FROM reklamation WHERE ProjektID = $ProjektID AND lfdNr=$lfdNr;");
        if ($this->count() > 0) {
            $result = $this->fetchobject();
            return $result->ReklamationID;
        } else {
            return false;
        }
    }

    public function getFolgeauftragIDIfExist($ProjektID, $lfdNr) {
        $this->query("SELECT * FROM folgeauftrag WHERE ProjektID = $ProjektID AND lfdNr=$lfdNr;");
        if ($this->count() > 0) {
            $result = $this->fetchobject();
            return $result->FolgeauftragID;
        } else {
            return false;
        }
    }

    public function getProjektartIDIfExist($ArtDerLeistung) {
        $ArtDerLeistung = str_replace('ö', 'oe', utf8_decode($ArtDerLeistung));
        $this->query("SELECT * FROM auftragsart WHERE AuftragsartBezeichnung = '$ArtDerLeistung';");
        if ($this->count() > 0) {
            $result = $this->fetchobject();
            return $result->AuftragsartID;
        } else {
            return 1;
        }
    }

    public function saveChangesInTable($ID, $table, $items) {
        $stringOfChanges = '';
        $PK = $this->show_primary_key($table);
        $this->query("SELECT * FROM " . $table . " WHERE $PK=$ID;");
        $resultArray = $this->fetchAssoc();
        $allAttribues = $this->saveAttributesFromTable($table);
        unset($allAttribues[$PK]);
        unset($resultArray[$PK]);
        for ($i = 0; $i < count($allAttribues); $i++) {
            //ist der Wert übergeben, also in Taifun nicht NULL
            if (array_key_exists($allAttribues[$i], $items)) {
                if ($resultArray[$allAttribues[$i]] != $items[$allAttribues[$i]]) {
                    $stringOfChanges.=$allAttribues[$i] . ':von:' . $resultArray[$allAttribues[$i]] . ':auf:' . $items[$allAttribues[$i]] . '<br>';
                }
            } else if ($allAttribues[$i] != $PK) {
                if ($resultArray[$allAttribues[$i]]) {
                    $stringOfChanges.=$allAttribues[$i] . ':von:' . $resultArray[$allAttribues[$i]] . ':auf:NULL<br>';
                }
            }
        }
        if ($stringOfChanges != '') {
            $items = array('ProjektID' => $ID, 'ProjektupdatesString' => $stringOfChanges);
            $this->insert_table('projektupdate', $items);
            return $stringOfChanges;
        } else {
            return false;
        }
    }

    public function saveChangesInReklamationTable($ID, $table, $items) {
        $stringOfChanges = '';
        $PK = $this->show_primary_key($table);
        $this->query("SELECT * FROM " . $table . " WHERE $PK=" . $ID . ";");
        $resultArray = $this->fetchAssoc();
        $allAttribues = $this->saveAttributesFromTable($table);
        unset($allAttribues[$PK]);
        unset($resultArray[$PK]);
        for ($i = 0; $i < count($allAttribues); $i++) {
            //ist der Wert übergeben, also in Taifun nicht NULL
            if (array_key_exists($allAttribues[$i], $items)) {
                if ($resultArray[$allAttribues[$i]] != $items[$allAttribues[$i]]) {
                    $stringOfChanges.=$allAttribues[$i] . ':von:' . $resultArray[$allAttribues[$i]] . ':auf:' . $items[$allAttribues[$i]] . '<br>';
                }
            } else if ($allAttribues[$i] != $PK) {
                if ($resultArray[$allAttribues[$i]]) {
                    $stringOfChanges.=$allAttribues[$i] . ':von:' . $resultArray[$allAttribues[$i]] . ':auf:NULL<br>';
                }
            }
        }
        if ($stringOfChanges != '') {
            $items = array('ReklamationID' => $ID, 'ReklamationupdatesString' => $stringOfChanges);
            $this->insert_table('reklamationupdate', $items);
            return $stringOfChanges;
        } else {
            return false;
        }
    }

    public function saveChangesInFolgeauftragTable($ID, $table, $items) {
        $stringOfChanges = '';
        $PK = $this->show_primary_key($table);
        $this->query("SELECT * FROM " . $table . " WHERE $PK=" . $ID . ";");
        $resultArray = $this->fetchAssoc();
        $allAttribues = $this->saveAttributesFromTable($table);
        unset($allAttribues[$PK]);
        unset($resultArray[$PK]);
        for ($i = 0; $i < count($allAttribues); $i++) {
            //ist der Wert übergeben, also in Taifun nicht NULL
            if (array_key_exists($allAttribues[$i], $items)) {
                if ($resultArray[$allAttribues[$i]] != $items[$allAttribues[$i]]) {
                    $stringOfChanges.=$allAttribues[$i] . ':von:' . $resultArray[$allAttribues[$i]] . ':auf:' . $items[$allAttribues[$i]] . '<br>';
                }
            } else if ($allAttribues[$i] != $PK) {
                if ($resultArray[$allAttribues[$i]]) {
                    $stringOfChanges.=$allAttribues[$i] . ':von:' . $resultArray[$allAttribues[$i]] . ':auf:NULL<br>';
                }
            }
        }
        if ($stringOfChanges != '') {
            $items = array('FolgeauftragID' => $ID, 'FolgeauftragupdatesString' => $stringOfChanges);
            $this->insert_table('folgeauftragupdate', $items);
            return $stringOfChanges;
        } else {
            return false;
        }
    }

    //Dispositionsanfragen
    public function getGesamtKapazitaetStd($abteilung, $firma) {
        $this->query("SELECT SUM(StundenKapazitaet) AS Kapazitaet, SUM(StundenUeberKapazitaet) AS UeberKapazitaet from mitarbeiter WHERE AbteilungID=$abteilung AND FirmaID=$firma AND MitarbeiterSichtbar=1;");
        if ($this->count() > 0) {
            $result = $this->fetchObject();
            return array("Kapazitaet" => $result->Kapazitaet, "StundenUeberKapazitaet" => $result->UeberKapazitaet);
        } else {
            return false;
        }
    }

    public function getGesamtStdDisponiertNachAbt($abteilung, $firma, $week, $KWJahr) {
        $stundenDisponiertArray = array();
        $this->query("SELECT SUM(((UNIX_TIMESTAMP(TerminEndeDateTime) - UNIX_TIMESTAMP(TerminStartDateTime))/3600)-(((WEEKDAY(TerminEndeDateTime)+1) - (WEEKDAY(TerminStartDateTime)+1)) * 12) ) AS GesamtstundenErsterMonteur FROM termin t LEFT JOIN mitarbeiter m ON t.MitarbeiterID=m.MitarbeiterID WHERE WEEK(t.TerminStartDateTime,1)=$week AND YEAR(t.TerminStartDateTime)=$KWJahr AND m.AbteilungID=$abteilung AND m.FirmaID=$firma;");
        if ($this->count() > 0) {
            $result = $this->fetchObject();
            $stundenDisponiertArray['GesamtstundenErsterMonteur'] = $result->GesamtstundenErsterMonteur;
        } else {
            $stundenDisponiertArray['GesamtstundenErsterMonteur'] = 0;
        }
        $this->query("SELECT SUM(((UNIX_TIMESTAMP(TerminEndeDateTime) - UNIX_TIMESTAMP(TerminStartDateTime))/3600)-(((WEEKDAY(TerminEndeDateTime)+1) - (WEEKDAY(TerminStartDateTime)+1)) * 12) ) AS GesamtstundenZweiterMonteur FROM termin t LEFT JOIN mitarbeiter m ON t.ZweiterMonteurID=m.MitarbeiterID WHERE WEEK(t.TerminStartDateTime,1)=$week AND YEAR(t.TerminStartDateTime)=$KWJahr AND m.AbteilungID=$abteilung AND m.FirmaID=$firma;");
        if ($this->count() > 0) {
            $result = $this->fetchObject();
            $stundenDisponiertArray['GesamtstundenZweiterMonteur'] = $result->GesamtstundenZweiterMonteur;
        } else {
            $stundenDisponiertArray['GesamtstundenZweiterMonteur'] = 0;
        }
        return $stundenDisponiertArray;
    }

    public function isProjektIDInWeek($ProjektID, $week, $KWJahr) {
        $this->query("SELECT t.TerminID FROM termin t WHERE WEEK(t.TerminStartDateTime,1)=$week AND YEAR(t.TerminStartDateTime)=$KWJahr AND t.ProjektID=$ProjektID;");
        if ($this->count() > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function getKapazitaetMonteur($MitarbeiterID, $week, $TerminID = 0, $KWJahr) {
        $KapazitaetMitarbeiterArray = array();
        $this->query("SELECT (StundenKapazitaet+StundenUeberKapazitaet) AS StundenKapazitaetMonteur FROM mitarbeiter Where MitarbeiterID=$MitarbeiterID;");
        if ($this->count() > 0) {
            $result = $this->fetchObject();
            $KapazitaetMitarbeiterArray['StundenKapazitaetMonteur'] = $result->StundenKapazitaetMonteur;
        }
        //14 Stunden Pause zwischen zwei Tagen (17 bis 7 Uhr) angenommen:
        $this->query("SELECT SUM(((UNIX_TIMESTAMP(TerminEndeDateTime) - UNIX_TIMESTAMP(TerminStartDateTime))/3600)-(((WEEKDAY(TerminEndeDateTime)+1) - (WEEKDAY(TerminStartDateTime)+1)) * 14) ) AS StundenDisponiertMonteur FROM termin WHERE (MitarbeiterID=$MitarbeiterID OR ZweiterMonteurID=$MitarbeiterID) AND WEEK(TerminStartDateTime,1)=$week AND YEAR(TerminStartDateTime)=$KWJahr AND TerminID<>$TerminID;");
        if ($this->count() > 0) {
            $result = $this->fetchObject();
            $KapazitaetMitarbeiterArray['StundenDisponiertMonteur'] = $result->StundenDisponiertMonteur;
        } else {
            $KapazitaetMitarbeiterArray['StundenDisponiertMonteur'] = 0;
        }
        return $KapazitaetMitarbeiterArray;
    }

    public function getEinsaetzeMonteurInKW($MitarbeiterID) {
        global $DBSQLGEODB;
        $Timestamp = time();
        $KW = date('W', $Timestamp);
        $YEAR = date('Y', $Timestamp);
        $KW1 = date('W', $Timestamp + (7 * 24 * 60 * 60));
        $YEAR1 = date('Y', $Timestamp + (7 * 24 * 60 * 60));
        $this->query("SELECT projekt.*,termin.* FROM termin LEFT JOIN projekt ON termin.ProjektID=projekt.ProjektID WHERE (MitarbeiterID=$MitarbeiterID OR ZweiterMonteurID=$MitarbeiterID) AND (WEEK(TerminStartDateTime,1)=($KW) OR WEEK(TerminStartDateTime,1)=($KW1)) AND (YEAR(TerminStartDateTime)=$YEAR OR YEAR(TerminStartDateTime)=$YEAR1)  ORDER BY TerminStartDateTime,TerminStartZeitenID;");
        if ($this->count()) {
            $PNo = array();
            $PLZ = array();
            $Ort = array();
            $Strasse = array();
            $Text = array();
            $Contact = array();
            $Startzeit = array();
            while ($resultEinsaetze = $this->fetchobject()) {
                $PNummerEintragen = false;
                if (!empty($resultEinsaetze->MtOrt) AND ! empty($resultEinsaetze->MtStr)) {
                    if ($DBSQLGEODB->getPLZExist(substr($resultEinsaetze->MtOrt, 0, 5))) {
                        $Einsatzort = $resultEinsaetze->MtOrt;
                        $Einsatzstrasse = $resultEinsaetze->MtStr;
                        $PNummerEintragen = true;
                    } else {
                        $Ortarray = $DBSQLGEODB->getPLZVonOrtsbezeichnung(substr($resultEinsaetze->MtOrt, 6));
                        if ($Ortarray) {
                            $Einsatzort = $Ortarray[0] . ' ' . substr($resultEinsaetze->MtOrt, 6);
                            $Einsatzstrasse = $resultEinsaetze->MtStr;
                            $PNummerEintragen = true;
                        } else {
                            if ($DBSQLGEODB->getPLZExist(substr($resultEinsaetze->KdOrt, 0, 5))) {
                                $Einsatzort = $resultEinsaetze->KdOrt;
                                $Einsatzstrasse = $resultEinsaetze->KdStr;
                                $PNummerEintragen = true;
                            } else {
                                $Ortarray = $DBSQLGEODB->getPLZVonOrtsbezeichnung(substr($resultEinsaetze->KdOrt, 6));
                                if ($Ortarray) {
                                    $Einsatzort = $Ortarray[0] . ' ' . substr($resultEinsaetze->KdOrt, 6);
                                    $Einsatzstrasse = $resultEinsaetze->KdStr;
                                    $PNummerEintragen = true;
                                }
                            }
                        }
                    }
                } else {
                    if ($DBSQLGEODB->getPLZExist(substr($resultEinsaetze->KdOrt, 0, 5))) {
                        $Einsatzort = $resultEinsaetze->KdOrt;
                        $Einsatzstrasse = $resultEinsaetze->KdStr;
                        $PNummerEintragen = true;
                    } else {
                        $Ortarray = $DBSQLGEODB->getPLZVonOrtsbezeichnung(substr($resultEinsaetze->KdOrt, 6));
                        if ($Ortarray) {
                            $Einsatzort = $Ortarray[0] . ' ' . substr($resultEinsaetze->KdOrt, 6);
                            $Einsatzstrasse = $resultEinsaetze->KdStr;
                            $PNummerEintragen = true;
                        }
                    }
                }
                if ($PNummerEintragen) {
                    $PNo[] = $resultEinsaetze->ProjektNoTaifun;
                    $Strasse[] = $Einsatzstrasse;
                    $PLZ[] = substr($Einsatzort, 0, 5);
                    $Ort[] = substr($Einsatzort, 6);
                    $Text[] = $resultEinsaetze->TerminBemerkung;
                    if ($resultEinsaetze->KdAnrede != 'Sehr geehrte Damen und Herren') {
                        $Contact[] = $resultEinsaetze->KdAnrede;
                    } else {
                        $Contact[] = 'Kein';
                    }
                    if (!empty($resultEinsaetze->MtTelefon)) {
                        $Tel[] = $resultEinsaetze->MtTelefon;
                    } else {
                        $Tel[] = $resultEinsaetze->KdTelefon;
                    }
                    $Startzeit[] = date('d.m.y H:i', strtotime($resultEinsaetze->TerminStartDateTime));
                }
            }
        }
        if (!empty($PNo)) {
            return array($PNo, $Strasse, $PLZ, $Ort, $Startzeit, $Text, $Contact, $Tel);
        } else {
            return false;
        }
    }

    public function getAlleLaufzettelVorgaben($ArtderLeistung) {
        $ArtderLeistung = str_replace('ö', 'oe', utf8_decode($ArtderLeistung)); // Umlaute beachten
        $this->query("SELECT * FROM laufzettelvorgaben l LEFT JOIN auftragsart a ON l.AuftragsartID=a.AuftragsartID LEFT JOIN pmvorgabezeiten p ON l.SchrittID=p.SchrittID Where a.AuftragsartBezeichnung='$ArtderLeistung';");
        if ($this->count() > 0) {
            $VorgabeNachAuftragIDs = array();
            $StartInProzent = array();
            $EndeInProzent = array();
            $NameWieLaufzettel = array();
            $MitarbeiterID = array();
            while ($result = $this->fetchObject()) {
                $VorgabeNachAuftragIDs[] = $result->VorgabeNachAuftragID;
                $StartInProzent[] = $result->StartInProzent;
                $EndeInProzent[] = $result->EndeInProzent;
                $AuftragsartID = $result->AuftragsartID;
                $MitarbeiterID[] = $result->MitarbeiterID;
                $NameWieLaufzettel[] = $result->NameWieLaufzettel;
            }
            return array($VorgabeNachAuftragIDs, $StartInProzent, $EndeInProzent, $AuftragsartID, $NameWieLaufzettel, $MitarbeiterID);
        } else {
            return false;
        }
    }

    public function updateLaufzettelBearbeitung($laufzettelVorgabenArray, $ProjektID, $arrayLaufzettel) {
        $error = false;
        for ($n = 0; $n < count($laufzettelVorgabenArray[4]); $n++) {
            $item = array();
            if (isset($arrayLaufzettel[$laufzettelVorgabenArray[4][$n]])) {
                $item = array('Erledigt' => $arrayLaufzettel[$laufzettelVorgabenArray[4][$n]]);
            } else {
                $item = array('Erledigt' => '0000-00-00');
            }
            if (!$this->update_table('taskplaner', $item, $laufzettelVorgabenArray[0][$n] . ';' . $ProjektID, 'VorgabeNachAuftragID;ProjektID')) {
                $error = true;
            }
        }
        if ($error) {
            return false;
        } else {
            return true;
        }
    }

    public function updateReklamationlaufzettelBearbeitung($laufzettelVorgabenArray, $ProjektID, $ReklamationID, $arrayLaufzettel) {
        $error = false;
        for ($n = 0; $n < count($laufzettelVorgabenArray[4]); $n++) {
            $item = array();
            if (isset($arrayLaufzettel[$laufzettelVorgabenArray[4][$n]])) {
                $item = array('Erledigt' => $arrayLaufzettel[$laufzettelVorgabenArray[4][$n]]);
            } else {
                $item = array('Erledigt' => '0000-00-00');
            }
            if (!$this->update_table('taskplaner', $item, $laufzettelVorgabenArray[0][$n] . ';' . $ProjektID . ';' . $ReklamationID, 'VorgabeNachAuftragID;ProjektID;ReklamationID')) {
                $error = true;
            }
        }
        if ($error) {
            return false;
        } else {
            return true;
        }
    }

    //Erreichbar mit GEO-DB Anbindung (externe DB)
    public function getPLZVonOrtsbezeichnung($Ortsbezeichnung) {
        $this->query("SELECT zc.zipcode,ct.lat,ct.lng FROM zipcode zc RIGHT JOIN city ct ON zc.city_id=ct.id WHERE name like '%" . trim($Ortsbezeichnung) . "%';");
        if ($this->count() > 0) {

            $result = $this->fetchobject();
            return array($result->zipcode, $result->lat, $result->lng);
        } else {
            return false;
        }
    }

    public function getOrtsbezeichnungVonPLZ($PLZ) {
        $this->query("SELECT ct.name FROM zipcode zc RIGHT JOIN city ct ON zc.city_id=ct.id WHERE zipcode ='" . $PLZ . "';");
        if ($this->count() > 0) {

            $result = $this->fetchobject();
            return $result->name;
        } else {
            return false;
        }
    }

    public function getLatIngVonPLZ($PLZ) {
        $this->query("SELECT zc.zipcode,ct.name,ct.lng,ct.lat FROM zipcode zc RIGHT JOIN city ct ON zc.city_id=ct.id WHERE zc.zipcode ='$PLZ';");
        if ($this->count() > 0) {
            $result = $this->fetchobject();
            return array($result->zipcode, $result->lat, $result->lng);
        } else {
            return false;
        }
    }

    public function getPLZExist($PLZ) {
        $this->query("SELECT * FROM zipcode WHERE zipcode='$PLZ';");
        if ($this->count() > 0) {
            return true;
        } else {
            return false;
        }
    }

}
