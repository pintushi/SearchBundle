<?php

namespace Pintushi\Bundle\SearchBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Decimal entity for search index
 *
 * @ORM\Table(
 *      name="pintushi_search_index_decimal",
 *      indexes={
 *          @ORM\Index(name="pintushi_search_index_decimal_field_idx", columns={"field"}),
 *          @ORM\Index(name="pintushi_search_index_decimal_item_field_idx", columns={"item_id", "field"})
 *      }
 * )
 * @ORM\Entity
 */
class IndexDecimal extends AbstractIndexDecimal
{
    /**
     * @ORM\ManyToOne(targetEntity="Pintushi\Bundle\SearchBundle\Entity\Item", inversedBy="decimalFields")
     * @ORM\JoinColumn(name="item_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $item;
}
