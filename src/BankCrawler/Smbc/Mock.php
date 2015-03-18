<?php

namespace Maalls\BankCrawler\Smbc;

class Mock {

    public $id1;
    public $id2;
    public $password;
    public $from;
    public $until;

    public $results;

    public function login($id1, $id2, $password) {

        $this->id1 = $id1;
        $this->id2 = $id2;
        $this->password = $password;

    }
    public function iterate($from, $until) {

        $this->from = $from;
        $this->until = $until;

        return $this->results;

    }

}