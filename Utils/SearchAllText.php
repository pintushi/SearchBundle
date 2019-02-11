<?php

namespace Pintushi\Bundle\SearchBundle\Utils;

use Symfony\Component\Translation\TranslatorInterface;

class SearchAllText
{
    const TYPE_CONTAINS     = 1;
    const TYPE_NOT_CONTAINS = 2;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @return array
     */
    public function getOperatorChoices()
    {
        return [
            $this->translator->trans('pintushi.search.form.label_type_contains') => self::TYPE_CONTAINS ,
            $this->translator->trans('pintushi.search.form.label_type_not_contains') => self::TYPE_NOT_CONTAINS,
        ];
    }
}
