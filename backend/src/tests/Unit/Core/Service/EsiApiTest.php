<?php declare(strict_types=1);

namespace Tests\Unit\Core\Service;

use Brave\Core\Entity\Character;
use Brave\Core\Factory\EsiApiFactory;
use Brave\Core\Service\EsiApi;
use Brave\Core\Service\OAuthToken;
use Brave\Core\Service\ObjectManager;
use League\OAuth2\Client\Provider\GenericProvider;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Swagger\Client\Eve\Api\AllianceApi;
use Swagger\Client\Eve\Api\CharacterApi;
use Swagger\Client\Eve\Api\CorporationApi;
use Swagger\Client\Eve\Model\GetAlliancesAllianceIdOk;
use Swagger\Client\Eve\Model\GetCharactersCharacterIdOk;
use Swagger\Client\Eve\Model\GetCorporationsCorporationIdOk;
use Tests\Helper;

class EsiApiTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Logger
     */
    private $log;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $alliApi;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $corpApi;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $charApi;

    /**
     * @var EsiApi
     */
    private $esi;

    public function setUp()
    {
        $this->log = new Logger('Test');
        $this->log->pushHandler(new TestHandler());

        $this->alliApi = $this->createMock(AllianceApi::class);
        $this->corpApi = $this->createMock(CorporationApi::class);
        $this->charApi = $this->createMock(CHaracterApi::class);
        $this->esi = new EsiApi($this->log, new EsiApiFactory($this->alliApi, $this->corpApi, $this->charApi));
    }

    public function testGetAllianceException500()
    {
        $this->alliApi->method('getAlliancesAllianceId')->will(
            $this->throwException(new \Exception('Oops.', 500))
        );

        $result = $this->esi->getAlliance(456);

        $this->assertNull($result);
        $this->assertSame(500, $this->esi->getLastErrorCode());
        $this->assertSame('Oops.', $this->esi->getLastErrorMessage());
        $this->assertSame('Oops.', $this->log->getHandlers()[0]->getRecords()[0]['message']);
    }

    public function testGetAlliance()
    {
        $this->alliApi->method('getAlliancesAllianceId')->willReturn(new GetAlliancesAllianceIdOk([
            'name' => 'The Alliance.',
            'ticker' => '-HAT-',
        ]));

        $result = $this->esi->getAlliance(456);
        $this->assertNull($this->esi->getLastErrorCode());
        $this->assertNull($this->esi->getLastErrorMessage());
        $this->assertInstanceOf(GetAlliancesAllianceIdOk::class, $result);
        $this->assertSame('The Alliance.', $result->getName());
        $this->assertSame('-HAT-', $result->getTicker());
    }

    public function testGetCorporation500()
    {
        $this->corpApi->method('getCorporationsCorporationId')->will(
            $this->throwException(new \Exception('Oops.', 500))
        );

        $result = $this->esi->getCorporation(123);

        $this->assertNull($result);
        $this->assertSame(500, $this->esi->getLastErrorCode());
        $this->assertSame('Oops.', $this->esi->getLastErrorMessage());
        $this->assertSame('Oops.', $this->log->getHandlers()[0]->getRecords()[0]['message']);
    }

    public function testGetCorporation()
    {
        $this->corpApi->method('getCorporationsCorporationId')->willReturn(new GetCorporationsCorporationIdOk([
            'name' => 'The Corp.',
            'ticker' => '-HAT-',
            'alliance_id' => 123
        ]));

        $result = $this->esi->getCorporation(123);
        $this->assertNull($this->esi->getLastErrorCode());
        $this->assertNull($this->esi->getLastErrorMessage());
        $this->assertInstanceOf(GetCorporationsCorporationIdOk::class, $result);
        $this->assertSame('The Corp.', $result->getName());
        $this->assertSame('-HAT-', $result->getTicker());
    }

    public function testGetCharacter500()
    {
        $this->charApi->method('getCharactersCharacterId')->will(
            $this->throwException(new \Exception('Oops.', 500))
        );

        $result = $this->esi->getCharacter(123);

        $this->assertNull($result);
        $this->assertSame(500, $this->esi->getLastErrorCode());
        $this->assertSame('Oops.', $this->esi->getLastErrorMessage());
        $this->assertSame('Oops.', $this->log->getHandlers()[0]->getRecords()[0]['message']);
    }

    public function testGetCharacter()
    {
        $this->charApi->method('getCharactersCharacterId')->willReturn(new GetCharactersCharacterIdOk([
            'name' => 'The Char.',
        ]));

        $result = $this->esi->getCharacter(123);
        $this->assertNull($this->esi->getLastErrorCode());
        $this->assertNull($this->esi->getLastErrorMessage());
        $this->assertInstanceOf(GetCharactersCharacterIdOk::class, $result);
        $this->assertSame('The Char.', $result->getName());
    }
}
