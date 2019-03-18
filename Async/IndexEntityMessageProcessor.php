<?php
namespace Pintushi\Bundle\SearchBundle\Async;

use Doctrine\ORM\EntityManager;
use Pintushi\Bundle\SearchBundle\Engine\IndexerInterface;
use Enqueue\Util\JSON;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Interop\Queue\Message;
use Interop\Queue\Context;
use Interop\Queue\Processor;
use Enqueue\Client\TopicSubscriberInterface;

class IndexEntityMessageProcessor implements Processor, TopicSubscriberInterface
{
    /**
     * @var IndexerInterface
     */
    protected $indexer;

    /**
     * @var RegistryInterface
     */
    protected $doctrine;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param IndexerInterface  $indexer
     * @param RegistryInterface $doctrine
     * @param LoggerInterface   $logger
     */
    public function __construct(IndexerInterface $indexer, RegistryInterface $doctrine, LoggerInterface $logger)
    {
        $this->indexer = $indexer;
        $this->doctrine = $doctrine;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Message $message, Context $context)
    {
        $body = JSON::decode($message->getBody());

        if (empty($body['class'])) {
            $this->logger->error('Message is invalid. Class was not found.');

            return self::REJECT;
        }

        if (empty($body['id'])) {
            $this->logger->error('Message is invalid. Id was not found.');

            return self::REJECT;
        }

        /** @var EntityManager $entityManager */
        $entityManager = $this->doctrine->getManagerForClass($body['class']);
        if (null === $entityManager) {
            $this->logger->error(
                sprintf('Entity manager is not defined for class: "%s"', $body['class'])
            );

            return self::REJECT;
        }

        $repository = $entityManager->getRepository($body['class']);

        if ($entity = $repository->find($body['id'])) {
            $this->indexer->save($entity);
        } else {
            $entity = $entityManager->getReference($body['class'], $body['id']);
            $this->indexer->delete($entity);
        }

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::INDEX_ENTITY];
    }
}
