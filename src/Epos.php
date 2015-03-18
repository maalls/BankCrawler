<?php

namespace Maalls\BankCrawler;

class Epos extends BankCrawler {

    public function init() {

        parent::init();
        $client = $this->client;

        

    }

    public function login($username, $password) {



        $crawler = $this->client->request('GET', "https://www.eposcard.co.jp/include/login/login_static.html");

        $form = $crawler->filterXPath("//form")->form();

        $this->client->getClient()->setDefaultOption('config/curl/'.CURLOPT_REFERER, "https://www.eposcard.co.jp/member/index.html");
        $crawler = $this->client->submit($form, array('loginId' => $username, 'passWord' => $password));

        return $crawler;

    }

    public function iterate($from, $until) {

        list($fromYear, $fromMonth, $fromDay) = explode("-", $from);
        list($toYear, $toMonth, $toDay) = explode("-", $until);

        $results = array();

        for($year = $fromYear; $year <= $toYear; $year++) {

            $minMonth = $year == $fromYear ? $fromMonth : 1;
            $maxMonth = $year == $toYear ? $toMonth : 12;

            for($month = $minMonth; $month <= $maxMonth; $month++) {
            
                $history = $this->getHistory($year, str_pad($month, 2, "0", STR_PAD_LEFT));

                if($month == $fromMonth && $year == $fromYear) {

                    foreach($history as $key => $entry) {

                        if($entry->date < $from) unset($history[$key]);

                    }

                }

                if($month == $toMonth && $year == $toYear) {

                    foreach($history as $key => $entry) {

                        if($entry->date > $until) unset($history[$key]);

                    }

                }
            

                $results = array_merge($results, $history);    

            }

        }

        return $results;

    }

    public function getHistory($year, $month) {

        $crawler = $this->getHistoryPreload();
        $form = $this->extractHistoryForm($crawler);

        $crawler = $this->client->submit($form, array("monthSelectTagsDateYear" => $year, "monthSelectTagsDateMonth" => $month));
        
        $history = $this->extractHistory($crawler);                

        return $history;

    }

    public function extractHistory($crawler) {

        $tables = $this->extractHistoryTables($crawler);

        // 1 -> shopping
        // 3 -> cashing
        // 5 -> other
        
        
        $shoppingTable = $tables[1];
        $shoppings = $this->extractEntriesFromTable($shoppingTable);
        $cashingTable = $tables[3]; 
        $cashings = $this->extractEntriesFromTable($cashingTable, "cashing");

        $otherTable = $tables[5]; 
        $others = $this->extractEntriesFromTable($otherTable, "other");

        $results = array_merge($shoppings, $cashings);
        $results = array_merge($results, $others);

        usort($results, function($a, $b) {

            return $a->date > $b->date ? 1: -1;

        });

        return $results;

    }

    public function extractHistoryTables($crawler) {

        $tables = $crawler->filterXPath('//div[@id="main_contents_data"]//table')->each(function($table, $indice) {

            return $table;

        });

        return $tables;

    }

    public function extractEntriesFromTable($table, $category = "shopping") {

        if(!in_array($category, array("shopping", "cashing", "other"))) throw new \Exception("Invalid table type $type.");


        $results = $table->filterXPath("//tr")->each(function($row, $index) {

            $entry = $row->filterXPath("//td/div")->each(function($cell, $index) {

                $trim = preg_replace("@^[ 　\s]*@isu", "", $cell->html());
                $trim = preg_replace("@[ 　\s]*$@isu","", $trim);
                
                return $trim;

            });

            return $entry;

        });

        $entries = array();

        if(count($results) >= 2) {

            unset($results[0]);
           
            foreach($results as $row) {

                if(count($row) < 6) continue;

                $entry = new Entry();
                $entry->category = $category;
                $entry->date = date("Y-m-d", strtotime($row[0]));
                $entry->location = $row[1];

                if($category == "shopping") {
                
                    $amountOffset = 3;    
                    $dueDateOffset = 5;
                    $commentOffset = 6;

                }
                else if($category == "cashing") {

                    $amountOffset = 2;    
                    $dueDateOffset = 4;
                    $commentOffset = 5;

                }
                else {

                    $amountOffset = 3;    
                    $dueDateOffset = 4;
                    $commentOffset = 5;

                }

                $entry->amount = preg_replace("@[円,]@isu", "", $row[$amountOffset]);
                $entry->due_month = date("Y-m-d", strtotime($row[$dueDateOffset] . "/1"));
                $entry->comment = $row[$commentOffset];

                $entries[] = $entry;

            }

        }

        return $entries;

    }

    public function getHistoryPreload() {

        $crawler = $this->client->request(
            "GET", 
            "https://www.eposcard.co.jp/memberservice/pc/usehistoryreference/use_history_preload.do");

        //file_put_contents(__dir__ . "/../web/epos-history-preload.html", $crawler->html());
        
        return $crawler;

    }

    public function extractHistoryForm($crawler) {

        $form = $crawler->filterXPath('//form[@name="useHistoryPForm"]')->form();

        return $form;

    }

    

    public function extractName($crawler) {

        $name = $crawler->filterXPath("//h2[@class='name']/span")->each(function($data) { return $data->html(); });
        
        if(isset($name[0])) return $name[0];
        else return null;

    }

    
}

class Entry {

    public $date;
    public $location;
    public $amount;
    public $due_month;
    public $comment;
    public $category;

}