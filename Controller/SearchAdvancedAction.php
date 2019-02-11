<?php

namespace Pintushi\Bundle\SearchBundle\Controller;

use Pintushi\Bundle\SearchBundle\Event\PrepareResultItemEvent;
use Pintushi\Bundle\SecurityBundle\Annotation\AclAncestor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Pintushi\Bundle\SearchBundle\Engine\Indexer;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/** Get advanced search result.

* Supported Keywords:
*
*   from: List of entity aliases to search from. It can be one alias or group
*
*   where: Auxiliary keyword for visual separation 'from' block from search parameters
*
*   and, or: Used to combine multiple clauses, allowing you to refine your search.
*
* Syntax: and(or) field_type field_name operator value
*
*   aggregate: Allows to builds extra aggregating operations
*
* Syntax: field_type field_name grouping_function grouping_name
*
*   offset: Allow to set offset of first result.
*
*   max_results: Set results count for the query.
*
*   order_by: Allow to set results order. Syntax: order_by field_type field_name direction
*
* Supported keywords:
*
*  select
*
*  text
*
*  integer
*
*  decimal
*
*  datetime
*
* Operators:
*
*  ~, !~ Work only with string fields. Used for set text field value / search strings without value.
*
*  =  Used for search records where field matches the specified value.
*
*  != used for search records where field does not matches the specified value.
*
*  >, <, <=, >= Operators is used to search for the records that have the specified field must be greater, less,
* than, less than equals, or greater than equals of the specified value
*
*  in Used for search records where field in the specified set of data
*
*  !in Used for search records where field not in the specified set of data
*
*  replace spaces with _ underscore for fulltext search
*
* Aggregating functions:
*
*  count
*
*  sum
*
*  avg
*
*  min
*
*  max
*
* Examples:
*
*  select (name, price) from demo_products
*
*  from demo_product where name ~ samsung and double price > 100
*
*  where integer count != 10
*
*  where all_text !~ test_string
*
*  from demo_products where description ~ test order_by name offset 5
*
*  from (demo_products, demo_categories) where description ~ test offset 5 max_results 10
*
*  integer count !in (1, 3, 5)
*
*  from demo_products aggregate integer price sum price_sum
*
*/
class SearchAdvancedAction extends Controller
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
     * @Route("/advanced-search",
     *     name="pintushi_search_advanced",
     *     methods={"GET"},
     *     defaults={
     *         "_api_response"=true,
     *    }
     * )
     * @AclAncestor("pintushi_search")
     * @param Request $request
     * @return Response
     */
    public function __invoke(Request $request)
    {
        $result = $this->index->advancedSearch(
            $request->get('query')
        );

        foreach ($result->getElements() as $item) {
            $this->eventDispatcher->dispatch(PrepareResultItemEvent::EVENT_NAME, new PrepareResultItemEvent($item));
        }

        return $result->toSearchResultData();
    }
}
