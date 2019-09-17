<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use Symfony\Component\Validator\Constraint;

interface MetaTableTypeInterface
{
    /**
     * Returns the name of this entry.
     *
     * @return string|null
     */
    public function getName(): ?string;

    /**
     * Sets the name of this entry.
     *
     * @param string $name
     * @return MetaTableTypeInterface
     */
    public function setName(string $name): MetaTableTypeInterface;

    /**
     * @return mixed|null
     */
    public function getValue();

    /**
     * Value will not be serialized before its stored, so it should be a primitive type.
     *
     * @param mixed|null $value
     * @return MetaTableTypeInterface
     */
    public function setValue($value): MetaTableTypeInterface;

    /**
     * Get the linked entity.
     *
     * @return EntityWithMetaFields|null
     */
    public function getEntity(): ?EntityWithMetaFields;

    /**
     * Set the linked entity of this entry.
     *
     * @param EntityWithMetaFields $entity
     * @return MetaTableTypeInterface
     */
    public function setEntity(EntityWithMetaFields $entity): MetaTableTypeInterface;

    /**
     * This will merge the current object with the values from the given $meta instance.
     * It should NOT update the name or value, but only the form settings.
     *
     * @param MetaTableTypeInterface $meta
     * @return MetaTableTypeInterface
     */
    public function merge(MetaTableTypeInterface $meta): MetaTableTypeInterface;

    /**
     * Whether this field can be displayed in "public" places like API results or export.
     *
     * @param bool $include
     * @return MetaTableTypeInterface
     */
    public function setIsVisible(bool $include): MetaTableTypeInterface;

    /**
     * Whether this field can be displayed in "public" places like API results or export.
     *
     * @return bool
     */
    public function isVisible(): bool;

    /**
     * Whether this field is required to be filled out in the form.
     *
     * @param bool $isRequired
     * @return MetaTableTypeInterface
     */
    public function setIsRequired(bool $isRequired): MetaTableTypeInterface;

    /**
     * Whether the form field is required.
     *
     * @return bool
     */
    public function isRequired(): bool;

    /**
     * The form type for this field.
     * If this method returns null, it will not be shown on the form.
     *
     * @return string|null
     */
    public function getType(): ?string;

    /**
     * Sets the form type.
     *
     * @param string $type
     * @return MetaTableTypeInterface
     */
    public function setType(string $type): MetaTableTypeInterface;

    /**
     * Get all constraints that should be attached to the form type.
     *
     * @return Constraint[]
     */
    public function getConstraints(): array;

    /**
     * Adds a constraint to the form type.
     *
     * @param Constraint $constraint
     * @return MetaTableTypeInterface
     */
    public function addConstraint(Constraint $constraint): MetaTableTypeInterface;

    /**
     * Sets all constraints for the form type, overwriting all previously attached.
     *
     * @param Constraint[] $constraints
     * @return MetaTableTypeInterface
     */
    public function setConstraints(array $constraints): MetaTableTypeInterface;

    /**
     * Returns the label shown to the end-user.
     *
     * @return string
     */
    public function getLabel(): ?string;
    
    /**
     * Sets the label shown to the end-user.
     *
     * @param string $label
     * @return MetaTableTypeInterface
     */
    public function setLabel(string $label): MetaTableTypeInterface;
}
