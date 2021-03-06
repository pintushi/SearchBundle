<?php

namespace Pintushi\Bundle\SearchBundle\Async;

use Pintushi\Bundle\EntityBundle\ORM\DoctrineHelper;
use Pintushi\Bundle\SearchBundle\Engine\IndexerInterface;
use Pintushi\Bundle\SearchBundle\Transformer\MessageTransformerInterface;
use Enqueue\Client\ProducerInterface;

class Indexer implements IndexerInterface
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var MessageTransformerInterface
     */
    private $transformer;

    /**
     * @var ProducerInterface
     */
    protected $producer;

    /**
     * @param ProducerInterface $producer
     * @param DoctrineHelper $doctrineHelper
     * @param MessageTransformerInterface $transformer
     */
    public function __construct(
        ProducerInterface $producer,
        DoctrineHelper $doctrineHelper,
        MessageTransformerInterface $transformer
    ) {
        $this->producer = $producer;
        $this->doctrineHelper = $doctrineHelper;
        $this->transformer = $transformer;
    }

    /**
     * {@inheritdoc}
     */
    public function save($entity, array $context = [])
    {
        return $this->doIndex($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($entity, array $context = [])
    {
        return $this->doIndex($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function resetIndex($class = null, array $context = [])
    {
        throw new \LogicException('Method is not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function getClassesForReindex($class = null, array $context = [])
    {
        throw new \LogicException('Method is not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function reindex($class = null, array $context = [])
    {
        if (is_array($class)) {
            $classes = $class;
        } else {
            $classes = $class ? [$class] : [];
        }

        //Ensure specified class exists, if not - exception will be thrown
        foreach ($classes as $class) {
            $this->doctrineHelper->getEntityManagerForClass($class);
        }

        $this->producer->sendEvent(Topics::REINDEX, $classes);
    }

    /**
     * @param string|array $entity
     *
     * @return bool
     */
    protected function doIndex($entity)
    {
        if (!$entity) {
            return false;
        }

        $entities = is_array($entity) ? $entity : [$entity];

        $messages = $this->transformer->transform($entities);
        foreach ($messages as $message) {
            $this->producer->sendEvent(Topics::INDEX_ENTITIES, $message);
        }

        return true;
    }
}
