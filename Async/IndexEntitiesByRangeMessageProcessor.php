<?php
namespace Pintushi\Bundle\SearchBundle\Async;

use Doctrine\ORM\EntityManager;
use Pintushi\Bundle\SearchBundle\Engine\IndexerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Interop\Queue\Message;
use Interop\Queue\Context;
use Interop\Queue\Processor;
use Enqueue\Client\TopicSubscriberInterface;
use Enqueue\JobQueue\JobRunner;
use Enqueue\Util\JSON;

class IndexEntitiesByRangeMessageProcessor implements Processor, TopicSubscriberInterface
{
    /**
     * @var RegistryInterface
     */
    private $doctrine;

    /**
     * @var IndexerInterface
     */
    private $indexer;

    /**
     * @var JobRunner
     */
    private $jobRunner;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param RegistryInterface $doctrine
     * @param IndexerInterface $indexer
     * @param JobRunner $jobRunner
     * @param LoggerInterface $logger
     */
    public function __construct(
        RegistryInterface $doctrine,
        IndexerInterface $indexer,
        JobRunner $jobRunner,
        LoggerInterface $logger
    ) {
        $this->doctrine = $doctrine;
        $this->indexer = $indexer;
        $this->jobRunner = $jobRunner;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Message $message, Context $context)
    {
        $payload = JSON::decode($message->getBody());

        $result = $this->jobRunner->runDelayed($payload['jobId'], function () use ($message, $payload) {
            if (! isset($payload['entityClass'], $payload['offset'], $payload['limit'])) {
                $this->logger->error('Message is not valid.');

                return false;
            }

            /** @var EntityManager $em */
            if (! $em = $this->doctrine->getManagerForClass($payload['entityClass'])) {
                $this->logger->error(
                    sprintf('Entity manager is not defined for class: "%s"', $payload['entityClass'])
                );

                return false;
            }

            $identifierFieldName = $em->getClassMetadata($payload['entityClass'])->getSingleIdentifierFieldName();
            $repository = $em->getRepository($payload['entityClass']);

            $ids = $repository->createQueryBuilder('ids')
                ->select('ids.'.$identifierFieldName)
                ->setFirstResult($payload['offset'])
                ->setMaxResults($payload['limit'])
                ->orderBy('ids.'.$identifierFieldName, 'ASC')
                ->getQuery()->getArrayResult()
            ;
            $ids = array_map('current', $ids);

            if (false == $ids) {
                return true;
            }

            $entities = $repository->createQueryBuilder('entity')
                ->where('entity IN(:ids)')
                ->setParameter('ids', $ids)
                ->getQuery()->getResult()
            ;

            $this->indexer->save($entities);

            return true;
        });

        return $result ? self::ACK : self::REJECT;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::INDEX_ENTITY_BY_RANGE];
    }
}
