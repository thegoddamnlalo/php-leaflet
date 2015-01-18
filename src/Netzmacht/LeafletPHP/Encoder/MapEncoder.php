<?php

/**
 * @package    dev
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2014 netzmacht creative David Molineus
 * @license    LGPL 3.0
 * @filesource
 *
 */

namespace Netzmacht\LeafletPHP\Encoder;

use Netzmacht\JavascriptBuilder\Encoder;
use Netzmacht\JavascriptBuilder\Symfony\Event\EncodeReferenceEvent;
use Netzmacht\JavascriptBuilder\Symfony\Event\GetObjectStackEvent;
use Netzmacht\LeafletPHP\Definition;
use Netzmacht\LeafletPHP\Definition\Control\Layers;
use Netzmacht\LeafletPHP\Definition\Group\LayerGroup;
use Netzmacht\LeafletPHP\Definition\Map;

/**
 * Class MapEncoder encodes the map.
 *
 * @package Netzmacht\LeafletPHP\Encoder
 */
class MapEncoder extends AbstractEncoder
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        $events = parent::getSubscribedEvents();
        $events[GetObjectStackEvent::NAME] = 'getStack';

        return $events;
    }

    /**
     * Get object stack of the map as far as possible.
     *
     * @param GetObjectStackEvent $event
     */
    public function getStack(GetObjectStackEvent $event)
    {
        $stack = array();
        $value = $event->getValue();

        if ($value instanceof Map) {
            foreach ($value->getControls() as $control) {
                if ($control instanceof Layers) {
                    $this->addLayersToStack($control->getBaseLayers(), $stack);
                    $this->addLayersToStack($control->getOverlays(), $stack);
                }

                $stack[] = $control;
            }

            $this->addLayersToStack($value->getLayers(), $stack);

            $event->setStack($stack);
        }
    }

    /**
     * Compile a map.
     *
     * @param Map     $map     The map.
     * @param Encoder $encoder The builder.
     *
     * @return void
     */
    public function encodeMap(Map $map, Encoder $encoder)
    {
        $output = $encoder->getOutput();

        $output->append(
            sprintf(
                '%s = L.map(%s);',
                $encoder->encodeReference($map),
                $encoder->encodeArguments(array($map->getElementId(), $map->getOptions()))
            )
        );

        foreach ($map->getControls() as $control) {
            $output->append(
                sprintf(
                    '%s.addTo(%s);',
                    $encoder->encodeReference($control),
                    $encoder->encodeReference($map)
                )
            );
        }

        foreach ($map->getLayers() as $layer) {
            $output->append(
                sprintf(
                    '%s.addTo(%s);',
                    $encoder->encodeReference($layer),
                    $encoder->encodeReference($map)
                )
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setReference(Definition $definition, EncodeReferenceEvent $event)
    {
        if ($definition instanceof Map) {
            $event->setReference('map');
        }
    }

    /**
     * Add layers to to the stack.
     *
     * @param array $layers The layers to be added.
     * @param array $stack  The object stack being built.
     *
     * @return void
     */
    private function addLayersToStack($layers, &$stack)
    {
        foreach ($layers as $layer) {
            if ($layer instanceof LayerGroup) {
                $this->addLayersToStack($layer->getLayers(), $stack);
            }

            $stack[] = $layer;
        }
    }
}
