<?php
namespace Pintushi\Bundle\SearchBundle\Async;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Interop\Queue\Message;
use Interop\Queue\Context;
use Interop\Queue\Processor;
use Enqueue\Client\TopicSubscriberInterface;
use Enqueue\JobQueue\JobRunner;
use Enqueue\Util\JSON;
use Enqueue\Client\ProducerInterface;
use Enqueue\JobQueue\Job;

class IndexEntitiesByTypeMessageProcessor implements Processor, TopicSubscriberInterface
{
    const BATCH_SIZE = 1000;

    /**
     * @var RegistryInterface
     */
    private $doctrine;

    /**
     * @var JobRunner
     */
    private $jobRunner;

    /**
     * @var ProducerInterface
     */
    private $producer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param RegistryInterface $doctrine
     * @param JobRunner $jobRunner
     * @param ProducerInterface $producer
     * @param LoggerInterface $logger
     */
    public function __construct(
        RegistryInterface $doctrine,
        JobRunner $jobRunner,
        ProducerInterface $producer,
        LoggerInterface $logger
    ) {
        $this->doctrine = $doctrine;
        $this->jobRunner = $jobRunner;
        $this->producer = $producer;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Message $message, Context $context)
    {
        $payload = JSON::decode($message->getBody());

        $result = $this->jobRunner->runDelayed($payload['jobId'], function (JobRunner $jobRunner) use ($payload) {
            /** @var EntityManager $em */
            if (! $em = $this->doctrine->getManagerForClass($payload['entityClass'])) {
                $this->logger->error(
                    sprintf('Entity manager is not defined for class: "%s"', $payload['entityClass'])
                );

                return false;
            }

            $entityCount = $em->getRepository($payload['entityClass'])
                ->createQueryBuilder('entity')
                ->select('COUNT(entity)')
                ->getQuery()
                ->getSingleScalarResult()
            ;

            $batches = (int) ceil($entityCount / self::BATCH_SIZE);
            for ($i = 0; $i < $batches; $i++) {
                $jobRunner->createDelayed(
                    sprintf('%s:%s:%s', Topics::INDEX_ENTITY_BY_RANGE, $payload['entityClass'], $i),
                    function (JobRunner $jobRunner, Job $child) use ($i, $payload) {
                        $this->producer->send(Topics::INDEX_ENTITY_BY_RANGE, [
                            'entityClass' => $payload['entityClass'],
                            'offset' => $i * self::BATCH_SIZE,
                            'limit' => self::BATCH_SIZE,
                            'jobId' => $child->getId(),
                        ]);
                    }
                );
            }

            return true;
        });

        return $result ? self::ACK : self::REJECT;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::INDEX_ENTITY_TYPE];
    }
}
