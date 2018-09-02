<?php declare(strict_types=1);

namespace Tests\Unit\Core\Repository;

use Brave\Core\Factory\RepositoryFactory;
use Tests\Helper;
use Brave\Core\Entity\Character;

class CharacterRepositoryTest extends \PHPUnit\Framework\TestCase
{
    public function testFindByNamePartialMatch()
    {
        // setup

        $h = new Helper();
        $h->emptyDb();
        $em = $h->getEm();

        $char1 = (new Character())->setId(10)->setName('CHAR Two');
        $char2 = (new Character())->setId(20)->setName('char one');
        $char3 = (new Character())->setId(30)->setName('three');

        $em->persist($char1);
        $em->persist($char2);
        $em->persist($char3);

        $em->flush();

        // test

        $r = (new RepositoryFactory($em))->getCharacterRepository();

        $actual = $r->findByNamePartialMatch('har');
        $this->assertSame(2, count($actual));
        $this->assertSame('char one', $actual[0]->getName());
        $this->assertSame('CHAR Two', $actual[1]->getName());
        $this->assertSame(20, $actual[0]->getID());
        $this->assertSame(10, $actual[1]->getID());
    }
}
