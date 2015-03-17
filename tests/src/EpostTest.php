<?php

namespace Tests\Maalls\BankCrawler;
include_once __dir__ . "/../../vendor/autoload.php";

use Maalls\BankCrawler\Epos;
use Symfony\Component\DomCrawler\Crawler;

class EposTest extends \PHPUnit_Framework_TestCase
{

    public function testExtractName() {

        $html = file_get_contents(__dir__ . "/data/epos-login-response.html");
        $crawler = new Crawler($html);
        $epos = new Epos();

        $name = $epos->extractName($crawler);

        $this->assertEquals("山門　麿実樹", $name);

    }

    public function testExtractHistoryForm() {

        $html = file_get_contents(__dir__ . "/data/epos-history-preload.html");
        $crawler = new Crawler($html, "http://dummy.com");
        $epos = new Epos();

        $form = $epos->extractHistoryForm($crawler);

        $this->assertEquals(count($form->getValues()), 2);


    }

    public function testExtractHistory() {

        $html = file_get_contents(__dir__ . "/data/epos-history-2014-12.html");
        $crawler = new Crawler($html);
        $epos = new Epos();

        $tables = $epos->extractHistoryTables($crawler);

        $this->assertEquals(16, count($tables));

        $history = $epos->extractHistory($crawler);

    }

    /*public function testGetHistory() {

        $epos = new Epos();

        list($loginId, $password) = $this->getLoginConf();

        $crawler = $epos->login($loginId, $password);

        $epos->getHistory("2014", "12");


    }*/

    /*public function testLogin() {

        $epos = new Epos();

        list($loginId, $password) = $this->getLoginConf();

        $crawler = $epos->login($loginId, $password);
        
        $this->assertEquals("山門　麿実樹", $epos->extractName($crawler));


    }*/

    /*public function testHistory() {

        $epos = new Epos();
        list($loginId, $password) = $this->getLoginConf();
        $crawler = $epos->login($loginId, $password);
        $epos->getHistoryPreload();
        //$history = $epos->history("2014", "12");

    }*/

    protected function getLoginConf() {

        $loginFile = __dir__ . "/config/login.epos.txt";

        if(!file_exists($loginFile)) {
        
            throw new \Exception("login file required in tests/config/epos.login.text containing: loginid:password");

        }
        return explode(":", file_get_contents($loginFile));

    }

}