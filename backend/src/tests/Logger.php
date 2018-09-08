<?php declare(strict_types=1);

namespace Tests;

use Monolog\Handler\TestHandler;

class Logger extends \Monolog\Logger
{
    public function __construct(string $name, $handlers = array(), $processors = array())
    {
        parent::__construct($name, $handlers, $processors);

        $this->pushHandler(new TestHandler());
    }

    /**
     * @return \Monolog\Handler\TestHandler[]|\Monolog\Handler\HandlerInterface[]
     */
    public function getHandlers()
    {
        return parent::getHandlers();
    }
}
