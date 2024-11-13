<?php

namespace Glpi\Controller;

use Glpi\Controller\GenericFormController;
use Glpi\Event;
use Html;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class VisibilityController extends GenericFormController
{
    public function __invoke(Request $request): Response
    {
        if ($request->request->has('addvisibility')) {
            return $this->addVisibility($request);
        }

        return parent::__invoke($request);
    }

    public function addVisibility(Request $request): RedirectResponse
    {
        $class = $request->attributes->get('class');
        $item = new $class();
        $item->addVisibility($request->request->all());
        return new RedirectResponse(Html::getBackUrl());
    }
}
