<?php

namespace Maalls\BankCrawler;

class Smbc {

    protected $userAgent = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.115 Safari/537.36";

    protected $cookieFilename = null;
    protected $client;

    protected $form;
    
    protected $loginUrl = "https://direct.smbc.co.jp/aib/aibgsjsw5001.jsp";
    protected $commandUrl = "https://direct3.smbc.co.jp/servlet/com.smbc.SUPPostServlet";
    protected $queryUrl = "https://direct3.smbc.co.jp/servlet/com.smbc.SUPGetServlet";
    protected $displayUrl = "https://direct3.smbc.co.jp/servlet/com.smbc.SUPRedirectServlet";

    public function __construct(\Goutte\Client $client = null, $cookieFilename = "/tmp/cookie.jar") {

        $this->cookieFilename = $cookieFilename;
        $this->setClient($client);
        

    }

    public function setClient(\Goutte\Client $client = null) {

        $this->client = $client;

        if($this->client) $this->init();

    }

    public function init() {

        $client = $this->client;
        $client->setServerParameter('HTTP_USER_AGENT', $this->userAgent);
        $client->getClient()->setDefaultOption('config/curl/'.CURLOPT_COOKIESESSION, true);
        $client->getClient()->setDefaultOption('config/curl/'.CURLOPT_COOKIEJAR, $this->cookieFilename);
        $client->getClient()->setDefaultOption('config/curl/'.CURLOPT_COOKIEFILE, $this->cookieFilename);
        $client->getClient()->setDefaultOption('config/curl/'.CURLINFO_HEADER_OUT, true);
        $client->getClient()->setDefaultOption('config/curl/'.CURLOPT_SSL_VERIFYPEER, true);
        $client->getClient()->setDefaultOption('config/curl/'.CURLOPT_SSLVERSION, 3);
        $client->getClient()->setDefaultOption('config/curl/'.CURLOPT_FOLLOWLOCATION, true);

    }

    public function login($id1, $id2, $password) {

        echo "Start login $id1 $id2 $password..." . PHP_EOL;

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
        var_dump($values);

        $crawler = $this->post($values);

        $form = $crawler->selectButton("確認して次へ")->form();
        $values = $form->getValues();

        $crawler = $this->post($values);

        $link = $crawler->selectLink("明細照会")->link();
        
        $crawler = $this->client->click($link);
        $this->extractForm($crawler);

        return $crawler;
        
    }


    public function iterate() {

        $crawler = $this->submit(array(
            "M_START_YMD" => "20140101", 
            "M_END_YMD" => "20150307",
            "FromYear" => "26",
            "FromMonth" => "01",
            "FromDate" => "01",
            "ToYear" => "27",
            "ToMonth" => "03",
            "ToDate" => "07"));

        $firstEntries = $this->getEntries($crawler);
        $entries = $crawler->filterXPath('//div[@class="pageNum"]//a')->each(function($link, $i) {

            //echo $link->attr("href") . PHP_EOL;
            
            //return $link->attr("href");
            echo "Processing page " . ($i + 1) . PHP_EOL;
            //$crawler = $this->client->click($link);

            $this->client->request("GET", $link->attr("href"));
            $crawler = $this->client->request("GET", $this->displayUrl);
            $this->extractForm($crawler);
            $html = $crawler->html();
            $entries = $this->getEntries($crawler);
            echo count($entries) . " entries found." . PHP_EOL;
            //sleep(1);

            return $entries;


        });

        array_unshift($entries, $firstEntries);
        var_dump($entries);

        echo "DONE" . PHP_EOL;

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

        echo http_build_query($this->form->getValues()) . PHP_EOL;
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

                    return trim($data->html(), " 　");
                    
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
            $e->balance = $this->formatNumber($balance);
            var_dump($expense, $income);
            $e->amount = $expense ? - $this->formatNumber($expense) : $this->formatNumber($income);
            
            if($date == '合計金額') continue;

            list($e->year, $e->month, $e->day) = explode(".", $date);
            //var_dump($e);

            $e->year = 1988 + trim($e->year, "H");
            $formatedEntries[] = $e;

        }

        return $formatedEntries;

    }

    public function formatNumber($number) {

        return str_replace(array("円", ","), "", $number);

    }

    public function post($values) {

        echo "Submitting Login to Server: " . $this->commandUrl . PHP_EOL;
        $this->client->request("POST", $this->commandUrl, $values);
        echo "Requesting page : " . $this->displayUrl . PHP_EOL;
        return $this->client->request("GET", $this->displayUrl);

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