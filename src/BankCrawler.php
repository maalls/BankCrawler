<?php

namespace Maalls\BankCrawler;

class BankCrawler {

    protected $userAgent = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.115 Safari/537.36";

    protected $cookieFilename = null;
    protected $client;


    public function __construct(\Goutte\Client $client = null, $cookieFilename = "/tmp/cookie.jar") {

        $this->cookieFilename = $cookieFilename;
        $this->setClient($client);
        

    }

    public function setClient(\Goutte\Client $client = null) {

        $this->client = $client;
        if(!$this->client) $this->client = new \Goutte\Client();

        if($this->client) $this->init();

    }

    public function init() {

        $client = $this->client;
        $client->setServerParameter('HTTP_USER_AGENT', $this->userAgent);
        //$client->getClient()->setDefaultOption('config/curl/'.CURLINFO_HEADER_OUT, true);
        $client->getClient()->setDefaultOption('config/curl/'.CURLOPT_COOKIESESSION, true);
        $client->getClient()->setDefaultOption('config/curl/'.CURLOPT_COOKIEJAR, $this->cookieFilename);
        $client->getClient()->setDefaultOption('config/curl/'.CURLOPT_COOKIEFILE, $this->cookieFilename . ".cookie");
        

    }

}