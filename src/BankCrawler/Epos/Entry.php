<?php

namespace Maalls\BankCrawler\Epos;

class Entry {

    public $date;
    public $location;
    public $amount;
    public $due_month;
    public $comment;
    public $category;

    public function __construct($date = null, $location = null, $amount = null, $due_month = null, $comment = null, $category = null) {

        $this->date = $date;
        $this->location = $location;
        $this->amount = $amount;
        $this->due_month = $due_month;
        $this->comment = $comment;
        $this->category = $category;

    }

}