<?php

namespace Tests\Maalls\BankCrawler;
include_once __dir__ . "/../../vendor/autoload.php";

use Maalls\BankCrawler\Smbc;
use Symfony\Component\DomCrawler\Crawler;

class SmbcTest extends \PHPUnit_Framework_TestCase
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
        $smbc->login("01595", "20538", "0314");
        $entries = $smbc->iterate();



    }
}
?>