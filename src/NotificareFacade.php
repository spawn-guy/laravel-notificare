<?php

namespace Notificare\Notificare;

use Illuminate\Support\Facades\Facade;

class NotificareFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'notificare';
    }
}