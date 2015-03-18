<?php

namespace Tests\Maalls\BankCrawler\Smbc;
include_once __dir__ . "/../../../../vendor/autoload.php";

use Maalls\BankCrawler\Smbc\Crawler as Smbc;
use Symfony\Component\DomCrawler\Crawler;

class CrawlerTest extends \PHPUnit_Framework_TestCase
{
    public function testGetEntries()
    {

        $smbc = new Smbc();

        $html = file_get_contents(__dir__ . "/data/smbc-detail-page.html");
        $crawler = new Crawler($html);

        $entry = $smbc->getEntries($crawler);

        //var_dump($entry);

    }

    public function testIterate() {

        $smbc = new Smbc();
        $loginFile = __dir__ . "/config/smbc.login.txt";
        if(!file_exists($loginFile)) {

            throw new \Exception("You must create a login file in tests/config/smbc.login.txt like: id1-id2:password");

        }
        list($login, $password) = explode(":", file_get_contents($loginFile));
        list($id1, $id2) = explode("-", $login);
        $password = trim($password);
        
        $smbc->login($id1, $id2, $password);
        $entries = $smbc->iterate("2015-03-01");
        $this->assertTrue(count($entries) > 0);



    }


}
?>