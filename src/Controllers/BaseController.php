<?php 

namespace Mita\UranusSocketServer\Controllers;

use Mita\UranusSocketServer\Managers\ConnectionManagerInterface;

abstract class BaseController implements ControllerInterface
{
    protected ConnectionManagerInterface $connectionManager;

    public function __construct(ConnectionManagerInterface $connectionManager)
    {
        $this->connectionManager = $connectionManager;
    }
}
