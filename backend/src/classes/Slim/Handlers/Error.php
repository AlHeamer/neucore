<?php declare(strict_types=1);

namespace Neucore\Slim\Handlers;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;
use Slim\Handlers\ErrorHandler;
use Slim\Interfaces\CallableResolverInterface;

/**
 * Extends Slim's error handler to add a logger.
 */
class Error extends ErrorHandler
{
    protected $logger;

    public function __construct(
        CallableResolverInterface $callableResolver, 
        ResponseFactoryInterface $responseFactory,
        LoggerInterface $logger
    ) {
        $this->logger = $logger;

        parent::__construct($callableResolver, $responseFactory);
    }

    protected function writeToErrorLog(): void
    {
        $context = [];
        if ($this->logErrorDetails) {
            $context['exception'] = $this->exception;
        }
        $this->logger->critical($this->exception->getMessage(), $context);
    }
}
