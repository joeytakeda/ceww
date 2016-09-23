<?php

namespace AppBundle\Tests\Services;

use Liip\FunctionalTestBundle\Test\WebTestCase as BaseTestCase;

class ImporterTest extends BaseTestCase {
    
    protected $importer;
    
    public function setUp() {
        parent::setUp();
        $this->importer = $this->getContainer()->get('ceww.importer');
    }
    
    public function testSetup() {
        $this->assertInstanceOf('AppBundle\Services\Importer', $this->importer);
    }
    
    /**
     * @dataProvider processDateData
     */
    public function testProcessDate($str, $year) {
        $this->assertEquals($year, $this->importer->processDate($str));
    }
    
    public function processDateData() {
        return [
            ['b 1929', 1929],
            ['2012-2014', [2012,2014]],
            ['02-May-19', 1919],
            ['MAY-19', 1919],
        ];
    }
    
    /**
     * @dataProvider splitData
     */
    public function testSplit($str, $delim, $alt, $array) {
        $this->assertEquals($array, $this->importer->split($str, $delim, $alt));
    }
    
    public function splitData() {
        return [
            [null, ';', '', ['']],
            ['', ';', '', ['']],
            ['Foo bar', ';', '', ['Foo bar']],
            ['Foo; bar', ';', '', ['Foo', 'bar']],
            ['P, M; B, W; A, B', ';', '', ['P, M', 'B, W', 'A, B']],            
            ['P, M; A B; C D', ';', ',', ['P, M', 'A B', 'C D']],
            ['a; b, c', ';', ',', ['a', 'b, c']],
            ['a; b, c, d', ';', ',', ['a; b', 'c', 'd']],
        ];
    }
    
    /**
     * @dataProvider cleanPlaceNameData
     */
    public function testCleanPlaceName($name, $clean) {
        $this->assertEquals($clean, $this->importer->cleanPlaceName($name));
    }
    
    public function cleanPlaceNameData() {
        return [
            ['Toronto, Ontario', 'Toronto, Ontario'],
            ['"Sommersville," Toronto', 'Toronto'],
            ['Toronto "Sommersville" Ontario', 'Toronto "Sommersville" Ontario'],
            ['Toronto (Ontario)', 'Toronto'],
            ['Toronto (1981)', 'Toronto'],
            ['Toronto (1981-1029)', 'Toronto'],
            ['near Yaletown Ontario', 'Yaletown Ontario'],
            [' near Yaletown Ontario', 'Yaletown Ontario'],
            ['Near Yaletown Ontario', 'Yaletown Ontario'],
            ['Nearing, BC', 'Nearing, BC'],
        ];
    }
    
    /**
     * @dataProvider cleanTitleData
     */
    public function testCleanTitle($title, $clean) {
        $this->assertEquals($clean, $this->importer->cleanTitle($title));
    }
    
    public function cleanTitleData() {
        return [
            # title casing.
            ['ALL ABOUT CHEESE', 'All About Cheese'],
            ['ABOUT A BOY', 'About A Boy'],
            ['A STRANGE DAY', 'A Strange Day'],
            
//            # dates
            ['Title (1991) ', 'Title'],
            ['Title (c1991)', 'Title'],
            ['Title (1991-2019)', 'Title'],
            ['Title (c1991-2918)', 'Title'],
            
            # quotation marks
            ['"CHEESE IT"', 'Cheese It'],
            ['"About the Cat" by Lillian', '"About The Cat" By Lillian'],
        ];
    }
    
    /**
     * @dataProvider sortableTitleData
     */
    public function testSortableTitle($title, $sortable) {
        $this->assertEquals($sortable, $this->importer->sortableTitle($title));
    }
    
    public function sortableTitleData() {
        return [
            ['A Dog', 'dog, a'],
            ['The Chicken', 'chicken, the'],
            ['Then and now', 'then and now'],
            ['Abernathy, a story', 'abernathy, a story'],
            ['"Boo" said the girl', 'boo" said the girl'],
            ['Accént eh?', 'accént eh?']
        ];
    }
    
}
