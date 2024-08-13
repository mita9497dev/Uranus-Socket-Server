<?php 
namespace Mita\UranusSocketServer\Controllers;

use DI\ContainerBuilder;

interface ControllerInterface
{
    public function __construct(ContainerBuilder $containerBuilder);
}