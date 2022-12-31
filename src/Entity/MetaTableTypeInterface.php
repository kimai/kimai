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
     */
    public function getName(): ?string;

    /**
     * Sets the name of this entry.
     */
    public function setName(string $name): MetaTableTypeInterface;

    public function getValue(): mixed;

    /**
     * Value will not be serialized before its stored, so it should be a primitive type.
     */
    public function setValue(mixed $value): MetaTableTypeInterface;

    /**
     * Get the linked entity.
     */
    public function getEntity(): ?EntityWithMetaFields;

    /**
     * Set the linked entity of this entry.
     */
    public function setEntity(EntityWithMetaFields $entity): MetaTableTypeInterface;

    /**
     * This will merge the current object with the values from the given $meta instance.
     * It should NOT update the name or value, but only the form settings.
     */
    public function merge(MetaTableTypeInterface $meta): MetaTableTypeInterface;

    /**
     * Whether this field can be displayed in "public" places like API results or export.
     */
    public function setIsVisible(bool $include): MetaTableTypeInterface;

    /**
     * Whether this field can be displayed in "public" places like API results or export.
     */
    public function isVisible(): bool;

    /**
     * Whether this field is required to be filled out in the form.
     */
    public function setIsRequired(bool $isRequired): MetaTableTypeInterface;

    /**
     * Whether the form field is required.
     */
    public function isRequired(): bool;

    /**
     * The form type for this field.
     * If this method returns null, it will not be shown on the form.
     */
    public function getType(): ?string;

    /**
     * Sets the form type.
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
     */
    public function getLabel(): ?string;

    /**
     * Sets or removes the label shown to the end-user.
     */
    public function setLabel(?string $label): MetaTableTypeInterface;

    /**
     * Set an array of options for the FormType.
     *
     * @param array<string, mixed> $options
     * @return MetaTableTypeInterface
     */
    public function setOptions(array $options): MetaTableTypeInterface;

    /**
     * Returns an array with options for the FormType.
     *
     * @return array<string, mixed>
     */
    public function getOptions(): array;

    /**
     * Sets the order of this meta-field.
     */
    public function setOrder(int $order): MetaTableTypeInterface;

    /**
     * Returns the order (default: 0).
     */
    public function getOrder(): int;
}
