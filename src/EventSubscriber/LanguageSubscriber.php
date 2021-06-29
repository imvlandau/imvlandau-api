<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LanguageSubscriber implements EventSubscriberInterface
{

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['setPreferredLanguage', 100],
        ];
    }

    public function setPreferredLanguage(RequestEvent $event)
    {
        // A single page can make several requests: one master request, and then
        // multiple sub-requests - f. e. when embedding controllers in templates
        if (!$event->isMasterRequest()) {
            // don't do anything if it's not the master request
            return;
        }

        $request = $event->getRequest();
        $preferredLanguage = $request->getPreferredLanguage();
        $request->setLocale($preferredLanguage);
    }
}
