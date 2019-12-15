<?php

namespace Knp\Menu\Loader;

use Knp\Menu\FactoryInterface;
use Knp\Menu\NodeInterface;

class NodeLoader implements LoaderInterface
{
    private $factory;

    public function __construct(FactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    public function load($data)
    {
        if (!$data instanceof NodeInterface) {
            throw new \InvalidArgumentException(sprintf('Unsupported data. Expected Knp\Menu\NodeInterface but got ', is_object($data) ? get_class($data) : gettype($data)));
        }

        $item = $this->factory->createItem($data->getName(), $data->getOptions());

        foreach ($data->getChildren() as $childNode) {
            $item->addChild($this->load($childNode));
        }

        return $item;
    }

    public function supports($data)
    {
        return $data instanceof NodeInterface;
    }
}
