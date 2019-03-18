<?php

namespace Pintushi\Bundle\SearchBundle\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Pintushi\Bundle\SearchBundle\Engine\IndexerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Pintushi\Bundle\SearchBundle\Async\Indexer;

class IndexCommand extends Command
{
    const NAME = 'pintushi:search:index';

    private $registry;

    private $indexer;

    public function __construct(Indexer $indexer, ManagerRegistry $registry)
    {
        parent::__construct();

        $this->registry = $registry;
        $this->indexer = $indexer;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName(self::NAME)
            ->setDescription('Update search index for specified entities with the same type')
            ->addArgument(
                'class',
                InputArgument::REQUIRED,
                'Full or compact class name of indexed entities ' .
                '(f.e. Pintushi\Bundle\UserBundle\Entity\User or PintushiUserBundle:User)'
            )
            ->addArgument(
                'identifiers',
                InputArgument::REQUIRED|InputArgument::IS_ARRAY,
                'Identifiers of indexed entities (f.e. 42)'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $class = $input->getArgument('class');
        $identifiers = $input->getArgument('identifiers');

        /** @var EntityManager $em */
        $em = $this->registry->getManagerForClass($class);
        if (null === $em) {
            throw new \LogicException(sprintf('Entity manager was not found for class: "%s"', $class));
        }

        $entities = [];
        foreach ($identifiers as $id) {
            $entities[] = $em->getReference($class, $id);
        }

        $this->indexer->save($entities);

        $output->writeln('Started index update for entities.');
    }
}
