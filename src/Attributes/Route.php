<?php


namespace Meast\Router\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Route
{

    public function __construct (
        private string $path,
        private string $method = 'GET'
    )
    {}

    public function getPath (): string
    {
        return $this->path;
    }

    public function getMethod (): string
    {
        return $this->method;
    }
}