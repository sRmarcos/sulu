<?php

namespace DTL\Bundle\ContentBundle\Form\EventListener;

use Symfony\Component\Form\Extension\Core\EventListener\ResizeFormListener;
use Symfony\Component\Form\FormEvent;

class ResizeableListener extends ResizeFormListener
{
    public function __construct($type, array $options = array())
    {
        parent::__construct($type, $options, true, true, true);
    }

    public function preSubmit(FormEvent $event)
    {
        $data = $event->getData();
        parent::preSubmit($event);
    }
}
