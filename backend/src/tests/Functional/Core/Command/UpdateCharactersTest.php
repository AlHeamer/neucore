<?php declare(strict_types=1);

namespace Tests\Functional\Core\Command;

use Brave\Core\Entity\Character;
use Brave\Core\Entity\CharacterRepository;
use Swagger\Client\Eve\Api\CharacterApi;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use Monolog\Logger;
use Monolog\Handler\TestHandler;
use Psr\Log\LoggerInterface;
use Swagger\Client\Eve\Api\CorporationApi;
use Swagger\Client\Eve\Model\GetCharactersCharacterIdOk;
use Swagger\Client\Eve\Model\GetCorporationsCorporationIdOk;
use Tests\Functional\ConsoleTestCase;
use Tests\Helper;

class UpdateCharactersTest extends ConsoleTestCase
{
    private $em;

    private $charApi;

    private $corpApi;

    private $oauth;

    public function setUp()
    {
        $h = new Helper();
        $h->emptyDb();
        $this->em = $h->getEm();

        $this->charApi = $this->createMock(CharacterApi::class);
        $this->corpApi = $this->createMock(CorporationApi::class);
        $this->oauth = $this->createMock(GenericProvider::class);
    }

    public function testExecuteErrorUpdate()
    {
        $c = (new Character())->setId(1)->setName('c1')->setCharacterOwnerHash('coh1')->setAccessToken('at1');
        $this->em->persist($c);
        $this->em->flush();
        $this->charApi->method('getCharactersCharacterId')->willReturn(null);

        $output = $this->runConsoleApp('update-chars', ['--sleep' => 0], [
            CharacterApi::class => $this->charApi
        ]);

        $expectedOutput = [
            '1: error updating.',
            'All done.',
        ];
        $this->assertSame(implode("\n", $expectedOutput)."\n", $output);
    }

    public function testExecuteWithoutToken()
    {
        // setup
        $c1 = (new Character())->setId(1122)->setName('c11')->setCharacterOwnerHash('coh11')->setAccessToken('at11');
        $c2 = (new Character())->setId(2233)->setName('c22')->setCharacterOwnerHash('coh22')->setAccessToken('at22');
        $this->em->persist($c1);
        $this->em->persist($c2);
        $this->em->flush();
        $this->charApi->method('getCharactersCharacterId')->willReturn(new GetCharactersCharacterIdOk([
            'name' => 'char xx', 'corporation_id' => 234
        ]));
        $this->corpApi->method('getCorporationsCorporationId')->willReturn(new GetCorporationsCorporationIdOk([
            'name' => 'The Corp.', 'ticker' => '-T-T-', 'alliance_id' => null
        ]));

        // run
        $output = $this->runConsoleApp('update-chars', ['--sleep' => 0], [
            CharacterApi::class => $this->charApi,
            CorporationApi::class => $this->corpApi,
        ]);

        $this->em->clear();

        $expectedOutput = [
            '1122: update OK, token N/A',
            '2233: update OK, token N/A',
            'All done.',
        ];
        $this->assertSame(implode("\n", $expectedOutput)."\n", $output);

        # read result
        $actual = (new CharacterRepository($this->em))->findAll();
        $this->assertSame(1122, $actual[0]->getId());
        $this->assertSame(2233, $actual[1]->getId());
        $this->assertNotNull($actual[0]->getLastUpdate());
        $this->assertNotNull($actual[1]->getLastUpdate());
        $this->assertSame(234, $actual[0]->getCorporation()->getId());
        $this->assertSame(234, $actual[1]->getCorporation()->getId());
        $this->assertNull($actual[0]->getCorporation()->getAlliance());
        $this->assertNull($actual[1]->getCorporation()->getAlliance());
    }

    public function testExecuteInvalidToken()
    {
        $c = (new Character())->setId(3)->setName('char1')->setCharacterOwnerHash('coh3')
        ->setAccessToken('at3')->setRefreshToken('at3')->setValidToken(false);
        $this->em->persist($c);
        $this->em->flush();

        $this->charApi->method('getCharactersCharacterId')->willReturn(new GetCharactersCharacterIdOk([
            'name' => 'char1', 'corporation_id' => 1
        ]));
        $this->corpApi->method('getCorporationsCorporationId')->willReturn(new GetCorporationsCorporationIdOk([
            'name' => 'corp1', 'ticker' => 't'
        ]));
        $this->oauth->method('getAccessToken')->willReturn(null);
        $this->oauth->method('getResourceOwner')->willReturn(null);

        $output = $this->runConsoleApp('update-chars', ['--sleep' => 0], [
            CharacterApi::class => $this->charApi,
            CorporationApi::class => $this->corpApi,
            GenericProvider::class => $this->oauth,
        ]);

        $expectedOutput = [
            '3: update OK, token NOK',
            'All done.',
        ];
        $this->assertSame(implode("\n", $expectedOutput)."\n", $output);
    }

    public function testExecuteValidToken()
    {
        $c = (new Character())->setId(3)->setName('char1')->setCharacterOwnerHash('coh3')
            ->setAccessToken('at3')->setRefreshToken('at3')->setValidToken(false);
        $this->em->persist($c);
        $this->em->flush();

        $this->charApi->method('getCharactersCharacterId')->willReturn(new GetCharactersCharacterIdOk([
            'name' => 'char1', 'corporation_id' => 1
        ]));
        $this->corpApi->method('getCorporationsCorporationId')->willReturn(new GetCorporationsCorporationIdOk([
            'name' => 'corp1', 'ticker' => 't'
        ]));
        $this->oauth->method('getAccessToken')->willReturn(null);
        $ro = $this->createMock(ResourceOwnerInterface::class);
        $ro->method('toArray')->willReturn([
            'CharacterOwnerHash' => 'coh3',
        ]);
        $this->oauth->method('getResourceOwner')->willReturn($ro);

        $output = $this->runConsoleApp('update-chars', ['--sleep' => 0], [
            CharacterApi::class => $this->charApi,
            CorporationApi::class => $this->corpApi,
            GenericProvider::class => $this->oauth,
        ]);

        $expectedOutput = [
            '3: update OK, token OK',
            'All done.',
        ];
        $this->assertSame(implode("\n", $expectedOutput)."\n", $output);
    }

    public function testExecuteValidTokenUnexpectedData()
    {
        $c = (new Character())->setId(3)->setName('char1')->setCharacterOwnerHash('coh3')
            ->setAccessToken('at3')->setRefreshToken('at3')->setValidToken(false);
        $this->em->persist($c);
        $this->em->flush();

        $this->charApi->method('getCharactersCharacterId')->willReturn(new GetCharactersCharacterIdOk([
            'name' => 'char1', 'corporation_id' => 1
        ]));
        $this->corpApi->method('getCorporationsCorporationId')->willReturn(new GetCorporationsCorporationIdOk([
            'name' => 'corp1', 'ticker' => 't'
        ]));
        $this->oauth->method('getAccessToken')->willReturn(null);
        $ro = $this->createMock(ResourceOwnerInterface::class);
        $ro->method('toArray')->willReturn([
            'UNKNOWN' => 'DATA',
        ]);
        $this->oauth->method('getResourceOwner')->willReturn($ro);

        $log = new Logger('Test');
        $log->pushHandler(new TestHandler());

        $output = $this->runConsoleApp('update-chars', ['--sleep' => 0], [
            CharacterApi::class => $this->charApi,
            CorporationApi::class => $this->corpApi,
            GenericProvider::class => $this->oauth,
            LoggerInterface::class => $log,
        ]);

        $expectedOutput = [
            '3: update OK, token OK',
            'All done.',
        ];
        $this->assertSame(implode("\n", $expectedOutput)."\n", $output);
        $this->assertSame(
            'Unexpected result from OAuth verify.',
            $log->getHandlers()[0]->getRecords()[0]['message']
        );
        $this->assertSame(
            ['data' => ['UNKNOWN' => 'DATA']],
            $log->getHandlers()[0]->getRecords()[0]['context']
        );
    }
}