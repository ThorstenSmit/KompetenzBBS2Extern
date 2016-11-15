<?php

/**
 * Description of system
 * @author T.Smit
 */
class System {

    protected function getLogin() {
        if (isset($_SESSION['tanNo'])) {
            return true;
        } else {
            return false;
        }
    }

    protected function setLogin(array $tanNo) {
        global $DBSQL;
        if ($resultSettings = $DBSQL->queryAndObjektResult("SELECT * FROM anwender WHERE $tan;")) {
            $_SESSION['tanNo'] = $personalData['tanNo'];
            $_SESSION['AnwenderID'] = $personalData['AnwenderID'];
            $_SESSION['AnwenderName'] = $personalData['AnwenderName'];
            $_SESSION['AnwenderVorname'] = $personalData['AnwenderVorname'];
            $_SESSION['AnwenderEMail'] = $personalData['AnwenderEMail'];
        }
    }

    protected function setLogout() {
        session_destroy();
    }

}
