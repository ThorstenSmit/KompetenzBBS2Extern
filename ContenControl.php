<?php

/**
 * Description of ContenControl
 * @author T.Smit, 2016
 */
class ContenControl {

    private $path;
    private $mainTableWidth;
    private $mainTableHeight;

    public function __construct($path) {
        global $DBSQL;
        $this->path = $path . '.php';
        if ($resultSettings = $DBSQL->queryAndObjektResult("SELECT * FROM settings;")) {
            $this->$mainTableWidth = $resultSettings->mainTableWidth;
            $this->$mainTableHeight = $resultSettings->mainTableHeight;
        }else{
            $this->$mainTableWidth = '1024px';
            $this->$mainTableHeight = '750px';
        }
    }

    public function getMainTableWidth() {
        return $this->mainTableWidth;
    }
    public function getMainTableHeight() {
        return $this->mainTableHeight;
    }

    public function getHeaderContent() {
        global $DBSQL;
    }

}
