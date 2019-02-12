<?php

namespace Pintushi\Bundle\SearchBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\Common\Util\Inflector;

/**
 * Generic resolver for entity names(titles):
 * - Short format returns name based on guesses using first existing field from the list $fieldGuesses or false
 * if no appropriate field is found
 * - Full format returns concatenation of all string fields or false if no string fields are found.
 * When existing fields have empty values, identifier (if exists and is single) will be used as title.
 */
class EntityNameProvider implements EntityNameProviderInterface
{
    /** @var string[] */
    protected $fieldGuesses = ['firstName', 'name', 'title', 'subject'];

    /** @var ManagerRegistry */
    protected $doctrine;

    /**
     * @param ManagerRegistry $doctrine
     */
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritdoc}
     */
    public function getName($format, $locale, $entity)
    {
        if (!in_array($format, [self::SHORT, self::FULL])) {
            // unsupported format
            return false;
        }

        $className = ClassUtils::getClass($entity);

        $fieldNames = self::FULL === $format
            ? $this->getFieldNames($className)
            : (array) $this->guessFieldName($className);

        if (empty($fieldNames)) {
            // no suitable fields
            return false;
        }

        if ($name = $this->getConstructedName($entity, $fieldNames)) {
            return $name;
        }

        // field value is empty, try with id
        if ($idFiledName = $this->getSingleIdFieldName($className)) {
            return $this->getFieldValue($entity, $idFiledName);
        }

        // no identifier column
        return false;
    }

    /**
     * Return single class Identifier Field Name or null if there a multiple or none
     *
     * @param $className
     *
     * @return string|null
     */
    protected function getSingleIdFieldName($className)
    {
        $metadata = $this->getClassMetadata($className);
        if (!$metadata) {
            return null;
        }

        $identifierFieldNames = $metadata->getIdentifierFieldNames();

        if (count($identifierFieldNames) !== 1) {
            return null;
        }

        return reset($identifierFieldNames);
    }

    /**
     * Return metadata of className
     *
     * @param string $className
     *
     * @return ClassMetadata|null
     */
    protected function getClassMetadata($className)
    {
        $manager = $this->doctrine->getManagerForClass($className);
        if (null === $manager) {
            return null;
        }

        return $manager->getClassMetadata($className);
    }

    /**
     * Return first string field from the fieldGuesses or null
     *
     * @param string $className
     *
     * @return string|null
     */
    protected function guessFieldName($className)
    {
        $metadata = $this->getClassMetadata($className);

        if (!$metadata) {
            return null;
        }

        foreach ($this->fieldGuesses as $fieldName) {
            if ($this->isFieldSupported($metadata, $fieldName)) {
                return $fieldName;
            }
        }

        return null;
    }

    /**
     * @param object $entity
     * @param string $fieldName
     *
     * @return mixed
     */
    protected function getFieldValue($entity, $fieldName)
    {
        $getterName = 'get' . Inflector::classify($fieldName);

        if (method_exists($entity, $getterName)) {
            return $entity->{$getterName}();
        }

        if (isset($entity->{$fieldName})) {
            return $entity->{$fieldName};
        }

        return null;
    }

    /**
     * Return string field names of className
     * Return first string field match from fieldGuesses or all string fields
     *
     * @param  string $className
     *
     * @return array
     */
    protected function getFieldNames($className)
    {
        $metadata = $this->getClassMetadata($className);

        if (!$metadata) {
            return [];
        }

        $fieldNames = array_filter(
            (array) $metadata->getFieldNames(),
            function ($fieldName) use ($metadata) {
                return $this->isFieldSupported($metadata, $fieldName);
            }
        );

        return $metadata->getFieldNames();
    }

    /**
     * Constructs and returns a name from the values of the fieldNames
     *
     * @param $entity
     * @param $fieldNames
     *
     * @return string|bool Constructed Name or FALSE if fails
     */
    protected function getConstructedName($entity, $fieldNames)
    {
        $values = [];
        foreach ($fieldNames as $field) {
            $values[] = $this->getFieldValue($entity, $field);
        }

        $values = array_filter($values);

        return empty($values) ? false : implode(' ', $values);
    }

    /**
     * Returns whether field is available to be used as entity name
     *
     * @param ClassMetadata $metadata
     * @param string $fieldName
     *
     * @return bool
     */
    protected function isFieldSupported(ClassMetadata $metadata, $fieldName)
    {
        $isFieldSupported = $metadata->hasField($fieldName) && $metadata->getTypeOfField($fieldName) === 'string';

        return $isFieldSupported;
    }
}
