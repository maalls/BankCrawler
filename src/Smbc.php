<?php

namespace Maalls\BankCrawler;

class Smbc extends BankCrawler {

    const HEISEI_OFFSET = 1988;

    
    protected $form;
    
    protected $loginUrl = "https://direct.smbc.co.jp/aib/aibgsjsw5001.jsp";
    protected $commandUrl = "https://direct3.smbc.co.jp/servlet/com.smbc.SUPPostServlet";
    protected $queryUrl = "https://direct3.smbc.co.jp/servlet/com.smbc.SUPGetServlet";
    protected $displayUrl = "https://direct3.smbc.co.jp/servlet/com.smbc.SUPRedirectServlet";

    

    public function init() {


        parent::init();
        $client = $this->client;

        $client->getClient()->setDefaultOption('config/curl/'.CURLOPT_SSL_VERIFYPEER, true);
        $client->getClient()->setDefaultOption('config/curl/'.CURLOPT_SSLVERSION, 3);
        $client->getClient()->setDefaultOption('config/curl/'.CURLOPT_FOLLOWLOCATION, true);


    }

    public function login($id1, $id2, $password) {

        $crawler = $this->client->request('GET', $this->loginUrl);

        $form = $crawler->selectButton('ログイン')->form();
        $id1 = "" . $id1;
        $id2 = "" . $id2;
        $pwd = "" . $password;
        $values = $form->getValues();
        $values["USRID"] = $id1 . $id2;
        $values["LOGIN_TYPE"] = "0";
        $values["USRID1"] = $id1;
        $values["USRID2"] = $id2;
        $values["PASSWORD"] = $pwd;

        $crawler = $this->post($values);

        /*$button = $crawler->selectButton("確認して次へ");
        var_dump($button);
        exit;
        if($button) {

            $form = $button->form();
            $values = $form->getValues();
            $crawler = $this->post($values);

        }*/

        $link = $crawler->selectLink("明細照会")->link();
        
        $crawler = $this->client->click($link);
        $this->extractForm($crawler);

        return $crawler;
        
    }


    public function iterate($fromDate = null, $toDate = null) {

        if($fromDate && $toDate) {

            if($from > $until) throw new \Exception("from must be before until: $from, $until.");

        }

        if(!$toDate) {

            $to = time() + 9*3600;
            $toDate = date("Y-m-d", $to);

        }
        
        if(!$fromDate) {

            $to = strtotime($toDate);
            $from = $to - 86400 * 364;
            $fromDate = date("Y-m-d", $from);


        }

        list($toYear, $toMonth, $toDay) = explode("-", $toDate);
        list($fromYear, $fromMonth, $fromDay) = explode("-", $fromDate);

        $M_START_YMD = $fromYear . $fromMonth . $fromDay;
        $M_END_YMD = $toYear . $toMonth . $toDay;

        $fromYear = $this->toJapaneseYear($fromYear);
        $toYear = $this->toJapaneseYear($toYear);

        //var_dump($M_START_YMD, $fromYear, $fromMonth, $fromDay);
        //var_dump($M_END_YMD, $toYear, $toMonth, $toDay);
        
        $crawler = $this->submit(array(
            "M_START_YMD" => $M_START_YMD, 
            "M_END_YMD" => $M_END_YMD,
            "FromYear" => $fromYear,
            "FromMonth" => $fromMonth,
            "FromDate" => $fromDay,
            "ToYear" => $toYear,
            "ToMonth" => $toMonth,
            "ToDate" => $toDay));

        $entries = $this->getEntries($crawler);
        $i = 1;
        do {

            $links = $crawler->filterXPath('//div[@class="pageNum"]//a[' . $i . ']')->each(function($link, $i) {

                return $link->attr("href");


            });

            if($links) {
                $link = $links[0];

                $this->client->request("GET", $link);
                $crawler = $this->client->request("GET", $this->displayUrl);
                $this->extractForm($crawler);
                $html = $crawler->html();
                //file_put_contents(__dir__ . "/contents/" . urlencode($link->attr("href")) . ".html", $html);
                $entries = array_merge($entries, $this->getEntries($crawler));

            }
            else {

                break;

            }
            if($i == 1) $i++;
            $i++;


        }
        while(true && $i < 20);

        return $entries;

    }

    public function click($link) {

        $this->client->click($link);
        $crawler = $this->client->request("GET", $this->displayUrl);
        $this->extractForm($crawler);

        return $crawler;

    }



    public function query($parameters = array()) {

        $crawler = $this->submit($parameters);

        return $this->getEntries($crawler);

    }

    public function submit($parameters = array()) {

        $parameters = array_merge($this->form->getValues(), $parameters);
        $this->form->setValues($parameters);

        $this->client->submit($this->form);
        $crawler = $this->client->request("GET", $this->displayUrl);
        $this->extractForm($crawler);

        return $crawler;

    } 


    public function extractForm($crawler) {

        $this->form = $crawler->selectButton('照会')->form();

    }

    public function getEntries($crawler) {

        $entries = $crawler->filterXPath('//div[@class="section"]//tr')->each(
        function($data) {
                
                $entry = $data->filterXPath('//td')->each(function($data, $i) {

                    return $data->html();
                    
                });

                return $entry;

                

        });
        
        return $this->formatEntries($entries);

    }

    public function formatEntries($entries) {

        $formatedEntries = array();

        foreach($entries as $entry) {

            if(!$entry) continue;

            $e = new Entry();
            

            list($date, $expense, $income, $e->description, $balance) = $entry;
            //var_dump($e);
            $expense = trim($expense, " 　");
            $income = trim($income, " 　");
            $e->balance = $this->formatNumber($balance);
            
            $e->amount = $expense ? - $this->formatNumber($expense) : $this->formatNumber($income);
            
            if($date == '合計金額') continue;

            list($e->year, $e->month, $e->day) = explode(".", str_replace(array(" ", "　"), "", $date));
            //var_dump($e);

            $e->year = $this->toGregorianYear(trim($e->year, "H"));
            $formatedEntries[] = $e;

        }

        return $formatedEntries;

    }

    public function formatNumber($number) {

        return str_replace(array("円", ","), "", $number);

    }

    public function post($values) {

        $this->client->request("POST", $this->commandUrl, $values);
        
        return $this->client->request("GET", $this->displayUrl);

    }

    public function toJapaneseYear($year) {

        return $year - self::HEISEI_OFFSET;

    }

    public function toGregorianYear($year) {

        return $year + self::HEISEI_OFFSET;

    }

}

class Entry {

    public $year;
    public $month;
    public $day;

    public $amount;
    public $description;
    public $balance;

}