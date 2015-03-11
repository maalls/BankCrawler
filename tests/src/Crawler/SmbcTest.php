<?php

namespace Tests\Maalls\Crawler;
include_once __dir__ . "/../../../vendor/autoload.php";

use Maalls\Crawler\Smbc;
use Symfony\Component\DomCrawler\Crawler;

class SmbcTest extends \PHPUnit_Framework_TestCase
{
    public function testGetEntries()
    {

        $smbc = new Smbc();

        $html = file_get_contents(__dir__ . "/data/smbc-detail-page.html");
        $crawler = new Crawler($html);

        $entry = $smbc->getEntries($crawler);

        var_dump($entry);

    }
}
?>