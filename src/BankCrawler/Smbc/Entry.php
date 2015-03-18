<?php

namespace Maalls\BankCrawler\Smbc;

class Entry {

    public $year;
    public $month;
    public $day;

    public $amount;
    public $description;
    public $balance;


    public function __construct($year = null, $month = null, $day = null, $amount = null, $description = null, $balance = null) {

        $this->year = $year;
        $this->month = $month;
        $this->day = $day;
        $this->amount = $amount;
        $this->description = $description;
        $this->balance = $balance;

    }

}