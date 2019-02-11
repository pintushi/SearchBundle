<?php

namespace Pintushi\Bundle\SearchBundle\Controller;

use Pintushi\Bundle\SearchBundle\Event\PrepareResultItemEvent;
use Pintushi\Bundle\SecurityBundle\Annotation\AclAncestor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Pintushi\Bundle\SearchBundle\Engine\Indexer;

class SearchAction extends Controller
{
    protected $eventDispatcher;

    protected $index;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        Indexer $index
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->index = $index;
    }

    /**
     * @AclAncestor("pintushi_search")
     *
     * @Route("/search",
     *     name="pintushi_search",
     *     methods={"GET"},
     *     defaults={
     *         "_api_response"=true,
     *    }
     * )
     *
     * @param Request $request
     * @return Response
     */
    public function __invoke(Request $request)
    {
        $searchResults = $this->index->simpleSearch(
            $request->get('search'),
            (int) $request->get('offset'),
            (int) $request->get('max_results'),
            $request->get('from')
        );

        foreach ($searchResults->getElements() as $item) {
            $this->eventDispatcher->dispatch(PrepareResultItemEvent::EVENT_NAME, new PrepareResultItemEvent($item));
        }

        return $searchResults->toSearchResultData();
    }
}
