<?php

/**
 */

namespace responsible\core\endpoints;

interface RouteMiddlewareInterface
{
    public function setVerb(string $verb): self;
    public function setRoute(string $route, string $controller): self;
    public function setScope(string $scope): self;
    public function setController(string $controller): self;
    public function setActionMethod(string $actionMethod): self;
    public function setHandler(\Closure $handler): self;

    public function getVerb(): string;
    public function getRoute(): string;
    public function getScope(): string;
    public function getController(): string;
    public function getActionMethod(): string;
    public function getHandler(): \Closure|null;
    public function runHandler(\Closure $handler, $request): mixed;
}
