<?php

namespace Pintushi\Bundle\SearchBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Pintushi\Bundle\SearchBundle\Query\Query as SearchQuery;

/**
 * Search index items that correspond to specific entity record
 *
 * @ORM\Table(
 *  name="pintushi_search_item",
 *  uniqueConstraints={@ORM\UniqueConstraint(name="IDX_ENTITY", columns={"entity", "record_id"})},
 *  indexes={@ORM\Index(name="IDX_ALIAS", columns={"alias"}), @ORM\Index(name="IDX_ENTITIES", columns={"entity"})}
 * )
 * @ORM\Entity(repositoryClass="Pintushi\Bundle\SearchBundle\Entity\Repository\SearchIndexRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Item extends AbstractItem
{
    /**
     * {@inheritdoc}
     */
    public function saveItemData($objectData)
    {
        $this->saveData($objectData, $this->textFields, new IndexText(), SearchQuery::TYPE_TEXT);
        $this->saveData($objectData, $this->integerFields, new IndexInteger(), SearchQuery::TYPE_INTEGER);
        $this->saveData($objectData, $this->datetimeFields, new IndexDatetime(), SearchQuery::TYPE_DATETIME);
        $this->saveData($objectData, $this->decimalFields, new IndexDecimal(), SearchQuery::TYPE_DECIMAL);

        return $this;
    }
}
