<?php
namespace ICARD;

class ICardDataGridCol {

    private $caption;
    private $name;
    private $callback;
 
    public function __construct($name, $caption, $callback) {
        $this->name = $name;
        $this->caption = $caption;
        $this->callback = $callback;
        return $this;
    }

    public function getCaption() {
        return $this->caption;
    }

    public function val($row) {
        $v = NULL;
        if (isset($row[$this->name])) {
            $v = $row[$this->name];
        }
        if (!is_null($this->callback)) {
            $cb = $this->callback;
            $v = $cb($v,$row);
        }
        return $v;
    }

}