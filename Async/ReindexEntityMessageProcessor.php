<?php
namespace Pintushi\Bundle\SearchBundle\Async;

use Pintushi\Bundle\SearchBundle\Engine\IndexerInterface;
use Interop\Queue\Message;
use Interop\Queue\Context;
use Interop\Queue\Processor;
use Enqueue\Client\TopicSubscriberInterface;
use Enqueue\JobQueue\JobRunner;
use Enqueue\Util\JSON;
use Enqueue\Client\ProducerInterface;
use Enqueue\JobQueue\Job;

class ReindexEntityMessageProcessor implements Processor, TopicSubscriberInterface
{
    /**
     * @var IndexerInterface
     */
    private $indexer;

    /**
     * @var JobRunner
     */
    private $jobRunner;

    /**
     * @var ProducerInterface
     */
    private $producer;

    /**
     * @param IndexerInterface $indexer
     * @param JobRunner $jobRunner
     * @param ProducerInterface $producer
     */
    public function __construct(IndexerInterface $indexer, JobRunner $jobRunner, ProducerInterface $producer)
    {
        $this->indexer = $indexer;
        $this->jobRunner = $jobRunner;
        $this->producer = $producer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Message $message, Context $session)
    {
        $classes = JSON::decode($message->getBody());

        $result = $this->jobRunner->runUnique(
            $message->getMessageId(),
            Topics::REINDEX,
            function (JobRunner $jobRunner) use ($classes) {
                $entityClasses = $this->getClassesForReindex($classes);

                foreach ($entityClasses as $entityClass) {
                    $jobRunner->createDelayed(
                        sprintf('%s:%s', Topics::INDEX_ENTITY_TYPE, $entityClass),
                        function (JobRunner $jobRunner, Job $child) use ($entityClass) {
                            $this->producer->send(Topics::INDEX_ENTITY_TYPE, [
                                'entityClass' => $entityClass,
                                'jobId' => $child->getId(),
                            ]);
                        }
                    );
                }

                return true;
            }
        );

        return $result ? self::ACK : self::REJECT;
    }

    /**
     * @param null|string|string[] $classes
     *
     * @return string[]
     */
    public function getClassesForReindex($classes)
    {
        if (! $classes) {
            $this->indexer->resetIndex();
            return $this->indexer->getClassesForReindex();
        }

        $classes = is_array($classes) ? $classes : [$classes];

        $entityClasses = [];
        foreach ($classes as $class) {
            $entityClasses = array_merge($entityClasses, $this->indexer->getClassesForReindex($class));
        }

        $entityClasses = array_unique($entityClasses);

        foreach ($entityClasses as $entityClass) {
            $this->indexer->resetIndex($entityClass);
        }

        return $entityClasses;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::REINDEX];
    }
}
