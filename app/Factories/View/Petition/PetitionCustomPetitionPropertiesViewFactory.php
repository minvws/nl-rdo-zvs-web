<?php

declare(strict_types=1);

namespace App\Factories\View\Petition;

use App\Enums\CustomPetitionPropertyType;
use Illuminate\Support\Collection;
use Ramsey\Uuid\UuidInterface;
use Webmozart\Assert\Assert;

use function __;
use function array_merge;
use function in_array;

/**
 * @phpstan-type CustomPetitionPropertyObjectShape object{
 *     id: ?UuidInterface,
 *     name: string,
 *     type: CustomPetitionPropertyType,
 * }
 */
class PetitionCustomPetitionPropertiesViewFactory
{
    /**
     * @param Collection<int, covariant CustomPetitionPropertyObjectShape> $customPetitionProperties
     * @param Collection<int, UuidInterface> $petitionCustomPetitionPropertyIds
     *
     * @return Collection<int, covariant object{
     *     id: UuidInterface,
     *     name: string,
     *     type: CustomPetitionPropertyType,
     * }>
     */
    public function build(Collection $customPetitionProperties, Collection $petitionCustomPetitionPropertyIds): Collection
    {
        $petitionCustomPetitionPropertiesView = new Collection();
        $petitionCustomPetitionPropertyIds = $petitionCustomPetitionPropertyIds
            ->map(static function (UuidInterface $id): string {
                return $id->toString();
            })
            ->all();

        // append null-item to make sure all items exist when using the sliding()-method
        // (using add() on the current collection gives phpstan error)
        $customPetitionProperties = new Collection(
            array_merge($customPetitionProperties->all(), [$this->buildNullCustomPetitionProperty()]),
        );

        // sliding the collection creates chunks representing a "sliding window" view of the items, this way we can handle items AND look
        // ahead to the next item to decide if we want to add an NoSelectedOptions-item.
        // See https://laravel.com/docs/11.x/collections#method-sliding for info about these methods
        // and the PetitionCustomPetitionPropertiesViewFactoryTest for the use-cases.
        $addNoSelectedOptionsType = null;
        $customPetitionProperties
            ->sliding()
            ->eachSpread(
                function (object $current, object $next) use ($petitionCustomPetitionPropertiesView, $petitionCustomPetitionPropertyIds, &$addNoSelectedOptionsType): void {
                    /** @var CustomPetitionPropertyObjectShape $current */
                    /** @var CustomPetitionPropertyObjectShape $next */

                    if (!$this->customPetitionPropertyIsOption($current)) {
                        $petitionCustomPetitionPropertiesView->add($current);
                        $addNoSelectedOptionsType = null;

                        return;
                    }

                    // since the current item is NOT an option, enable the addOption for this "group" of options
                    if ($addNoSelectedOptionsType === null) {
                        $addNoSelectedOptionsType = true;
                    }

                    // if one of the items is "checked", disable the addOption for this "group" op options
                    if ($current->id !== null && in_array($current->id->toString(), $petitionCustomPetitionPropertyIds, true)) {
                        $petitionCustomPetitionPropertiesView->add($current);
                        $addNoSelectedOptionsType = false;

                        return;
                    }

                    // if the next item is an option, or if the addOptions should not be displayed: move on
                    if ($this->customPetitionPropertyIsOption($next) || $addNoSelectedOptionsType === false) {
                        return;
                    }

                    // add the option and reset the addOption reference
                    $petitionCustomPetitionPropertiesView->add($this->buildNoCustomPetitionPropertyOptionType());
                    $addNoSelectedOptionsType = null;
                },
            );

        return $petitionCustomPetitionPropertiesView;
    }

    /**
     * @param object{type: CustomPetitionPropertyType} $customPetitionProperty
     */
    private function customPetitionPropertyIsOption(object $customPetitionProperty): bool
    {
        return $customPetitionProperty->type === CustomPetitionPropertyType::OPTION;
    }

    /**
     * @return object{
     *     id: null,
     *     name: string,
     *     type: CustomPetitionPropertyType,
     * }
     */
    private function buildNoCustomPetitionPropertyOptionType(): object
    {
        $name = __('petition.custom_petition_properties_no_selected_options');
        Assert::string($name);

        return (object) [
            'id' => null,
            'name' => $name,
            'type' => CustomPetitionPropertyType::NO_SELECTED_OPTIONS,
        ];
    }

    /**
     * @return object{
     *     id: null,
     *     name: string,
     *     type: CustomPetitionPropertyType,
     * }
     */
    private function buildNullCustomPetitionProperty(): object
    {
        return (object) [
            'id' => null,
            'name' => CustomPetitionPropertyType::NULL->value,
            'type' => CustomPetitionPropertyType::NULL,
        ];
    }
}
