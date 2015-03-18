<?php

namespace Tests\Maalls\BankCrawler\Epos;
include_once __dir__ . "/../../../../vendor/autoload.php";

use Maalls\BankCrawler\Epos\Crawler as Epos;
use Symfony\Component\DomCrawler\Crawler;

class CrawlerTest extends \PHPUnit_Framework_TestCase
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

        $this->assertEquals(count($history), 30);

    }

    public function testIterate() {

        $epos = new Epos();
        $this->login($epos);

        $results = $epos->iterate("2014-12-14", "2015-02-10");

        $first = $results[0];
        $last = array_pop($results);
        $this->assertEquals($first->date, "2014-12-14");
        $this->assertEquals($last->date, "2015-02-09");

    }

    public function testGetHistory() {

        $epos = new Epos();
        $this->login($epos);

        $results = $epos->getHistory("2014", "12");

        $this->assertTrue(count($results) > 10);

    }

    public function testLogin() {

        $epos = new Epos();
        $crawler = $this->login($epos);
        
        $this->assertEquals("山門　麿実樹", $epos->extractName($crawler));


    }

    protected function login($epos) {

        list($loginId, $password) = $this->getLoginConf();
        $crawler = $epos->login($loginId, $password);

        return $crawler;


    }

    protected function getLoginConf() {

        $loginFile = __dir__ . "/config/epos.login.txt";

        if(!file_exists($loginFile)) {
        
            throw new \Exception("login file required in $loginFile containing: loginid:password");

        }
        return explode(":", file_get_contents($loginFile));

    }

}