<?php

namespace Pintushi\Bundle\SearchBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Integer entity for search index
 *
 * @ORM\Table(
 *      name="pintushi_search_index_integer",
 *      indexes={
 *          @ORM\Index(name="pintushi_search_index_integer_field_idx", columns={"field"}),
 *          @ORM\Index(name="pintushi_search_index_integer_item_field_idx", columns={"item_id", "field"})
 *      }
 * )
 * @ORM\Entity
 */
class IndexInteger extends AbstractIndexInteger
{
    /**
     * @ORM\ManyToOne(targetEntity="Pintushi\Bundle\SearchBundle\Entity\Item", inversedBy="integerFields")
     * @ORM\JoinColumn(name="item_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $item;
}
