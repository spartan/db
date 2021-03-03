<?php

namespace Spartan\Db\Adapter\Propel\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Spartan\Db\Adapter\Propel\Propel2;

/**
 * PropelConnect Middleware
 *
 * @package Spartan\Db
 * @author  Iulian N. <iulian@spartanphp.com>
 * @license LICENSE MIT
 */
class PropelConnect implements MiddlewareInterface
{
    /**
     * @param ServerRequestInterface  $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     * @throws \Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        Propel2::connect();

        return $handler->handle($request);
    }
}
