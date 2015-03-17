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

    public function getHistory($year, $month) {

        $crawler = $this->getHistoryPreload();
        $form = $this->extractHistoryForm($crawler);

        $crawler = $this->client->submit($form, array("monthSelectTagsDateYear" => $year, "monthSelectTagsDateMonth" => $month));
        
        $history = $this->extractHistory($crawler);                

        return $history;

    }

    public function extractHistory($crawler) {

        $tables = $this->extractHistoryTables($crawler);

        // 3 -> cashing
        // 5 -> other
        // 1 -> shopping
        
        $shoppingTable = $tables[1];
        $results = $this->extractEntriesFromTable($shoppingTable);

        print_r($results);

    }

    public function extractHistoryTables($crawler) {

        $tables = $crawler->filterXPath('//div[@id="main_contents_data"]//table')->each(function($table, $indice) {

            return $table;

        });

        return $tables;

    }

    public function extractEntriesFromTable($table, $type = "shopping") {

        if(!in_array($type, array("shopping", "cashing", "other"))) throw new \Exception("Invalid table type $type.");

        $results = $table->filterXPath("//tr")->each(function($row, $index) {

            $entry = $row->filterXPath("//td/div")->each(function($cell, $index) {

                $trim = preg_replace("@^[ 　\s]*@isu", "", $cell->html());
                $trim = preg_replace("@[ 　\s]*$@isu","", $trim);
                
                return $trim;

            });

            return $entry;

        });

        unset($results[0]);
        array_pop($results);

        $entries = array();

        foreach($results as $row) {

            $entry = new Entry();
            $entry->date = $row[0];
            $entry->location = $row[1];
            $entry->amount = $row[3];
            $entry->due_date = $row[5];
            $entry->comment = $row[6];

            $entries[] = $entry;

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
    public $due_date;
    public $comment;

}