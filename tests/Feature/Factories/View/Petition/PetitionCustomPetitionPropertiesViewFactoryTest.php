<?php

declare(strict_types=1);

namespace Tests\Feature\Factories\View\Petition;

use App\Enums\CustomPetitionPropertyType;
use App\Factories\View\Petition\PetitionCustomPetitionPropertiesViewFactory;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

use function __;

class PetitionCustomPetitionPropertiesViewFactoryTest extends FeatureTestCase
{
    #[Test]
    public function testBuildWithTitleAndOption(): void
    {
        $optionId = $this->faker->uuid();

        $customPetitionProperty1 = (object) [
            'id' => $this->faker->uuid(),
            'name' => $this->faker->word(),
            'type' => CustomPetitionPropertyType::TITLE,
        ];
        $customPetitionProperty2 = (object) [
            'id' => $optionId,
            'name' => $this->faker->word(),
            'type' => CustomPetitionPropertyType::OPTION,
        ];
        $customPetitionProperties = new Collection([$customPetitionProperty1, $customPetitionProperty2]);

        $customPetitionPropertiesViewFactory = $this->getCustomPetitionPropertiesViewFactory();
        $result = $customPetitionPropertiesViewFactory->build($customPetitionProperties, new Collection([$optionId]));

        $expectedResult = new Collection([
            (object) [
                'id' => $customPetitionProperty1->id,
                'name' => $customPetitionProperty1->name,
                'type' => $customPetitionProperty1->type,
            ],
            (object) [
                'id' => $customPetitionProperty2->id,
                'name' => $customPetitionProperty2->name,
                'type' => $customPetitionProperty2->type,
            ],
        ]);

        $this->assertEquals($expectedResult, $result);
    }

    #[Test]
    public function testBuildWithTitleAndOptionButOptionNotSelected(): void
    {
        $customPetitionProperty1 = (object) [
            'id' => $this->faker->uuid(),
            'name' => $this->faker->word(),
            'type' => CustomPetitionPropertyType::TITLE,
        ];
        $customPetitionProperty2 = (object) [
            'id' => $this->faker->uuid(),
            'name' => $this->faker->word(),
            'type' => CustomPetitionPropertyType::OPTION,
        ];
        $customPetitionProperties = new Collection([$customPetitionProperty1, $customPetitionProperty2]);
        $customPetitionPropertiesViewFactory = $this->getCustomPetitionPropertiesViewFactory();
        $result = $customPetitionPropertiesViewFactory->build($customPetitionProperties, new Collection());

        $expectedResult = new Collection([
            (object) [
                'id' => $customPetitionProperty1->id,
                'name' => $customPetitionProperty1->name,
                'type' => $customPetitionProperty1->type,
            ],
            (object) [
                'id' => null,
                'name' => __('petition.custom_petition_properties_no_selected_options'),
                'type' => CustomPetitionPropertyType::NO_SELECTED_OPTIONS,
            ],
        ]);

        $this->assertEquals($expectedResult, $result);
    }

    #[Test]
    public function testBuildWithMultipeTitlesAndOptions(): void
    {
        $customPetitionPropertyTitle = (object) [
            'id' => $this->faker->uuid(),
            'name' => $this->faker->word(),
            'type' => CustomPetitionPropertyType::TITLE,
        ];
        $customPetitionPropertySubTitleA = (object) [
            'id' => $this->faker->uuid(),
            'name' => $this->faker->word(),
            'type' => CustomPetitionPropertyType::SUBTITLE,
        ];
        $customPetitionPropertyOptionA1NotSelected = (object) [
            'id' => $this->faker->uuid(),
            'name' => $this->faker->word(),
            'type' => CustomPetitionPropertyType::OPTION,
        ];
        $customPetitionPropertyOptionA2Selected = (object) [
            'id' => $this->faker->uuid(),
            'name' => $this->faker->word(),
            'type' => CustomPetitionPropertyType::OPTION,
        ];
        $customPetitionPropertySubtitleB = (object) [
            'id' => $this->faker->uuid(),
            'name' => $this->faker->word(),
            'type' => CustomPetitionPropertyType::SUBTITLE,
        ];
        $customPetitionPropertyOptionB1NotSelected = (object) [
            'id' => $this->faker->uuid(),
            'name' => $this->faker->word(),
            'type' => CustomPetitionPropertyType::OPTION,
        ];
        $customPetitionPropertyOptionB2NotSelected = (object) [
            'id' => $this->faker->uuid(),
            'name' => $this->faker->word(),
            'type' => CustomPetitionPropertyType::OPTION,
        ];
        $customPetitionPropertySubtitleC = (object) [
            'id' => $this->faker->uuid(),
            'name' => $this->faker->word(),
            'type' => CustomPetitionPropertyType::SUBTITLE,
        ];
        $customPetitionPropertyOptionC1NotSelected = (object) [
            'id' => $this->faker->uuid(),
            'name' => $this->faker->word(),
            'type' => CustomPetitionPropertyType::OPTION,
        ];
        $customPetitionPropertyOptionC2Selected = (object) [
            'id' => $this->faker->uuid(),
            'name' => $this->faker->word(),
            'type' => CustomPetitionPropertyType::OPTION,
        ];
        $customPetitionProperties = new Collection([
            $customPetitionPropertyTitle,
            $customPetitionPropertySubTitleA,
            $customPetitionPropertyOptionA1NotSelected,
            $customPetitionPropertyOptionA2Selected,
            $customPetitionPropertySubtitleB,
            $customPetitionPropertyOptionB1NotSelected,
            $customPetitionPropertyOptionB2NotSelected,
            $customPetitionPropertySubtitleC,
            $customPetitionPropertyOptionC1NotSelected,
            $customPetitionPropertyOptionC2Selected,
        ]);

        $customPetitionPropertiesViewFactory = $this->getCustomPetitionPropertiesViewFactory();
        $result = $customPetitionPropertiesViewFactory->build($customPetitionProperties, new Collection([
            $customPetitionPropertyOptionA2Selected->id,
            $customPetitionPropertyOptionC2Selected->id,
        ]));

        $expectedResult = new Collection([
            (object) [
                'id' => $customPetitionPropertyTitle->id,
                'name' => $customPetitionPropertyTitle->name,
                'type' => $customPetitionPropertyTitle->type,
            ],
            (object) [
                'id' => $customPetitionPropertySubTitleA->id,
                'name' => $customPetitionPropertySubTitleA->name,
                'type' => $customPetitionPropertySubTitleA->type,
            ],
            (object) [
                'id' => $customPetitionPropertyOptionA2Selected->id,
                'name' => $customPetitionPropertyOptionA2Selected->name,
                'type' => $customPetitionPropertyOptionA2Selected->type,
            ],
            (object) [
                'id' => $customPetitionPropertySubtitleB->id,
                'name' => $customPetitionPropertySubtitleB->name,
                'type' => $customPetitionPropertySubtitleB->type,
            ],
            (object) [
                'id' => null,
                'name' => __('petition.custom_petition_properties_no_selected_options'),
                'type' => CustomPetitionPropertyType::NO_SELECTED_OPTIONS,
            ],
            (object) [
                'id' => $customPetitionPropertySubtitleC->id,
                'name' => $customPetitionPropertySubtitleC->name,
                'type' => $customPetitionPropertySubtitleC->type,
            ],
            (object) [
                'id' => $customPetitionPropertyOptionC2Selected->id,
                'name' => $customPetitionPropertyOptionC2Selected->name,
                'type' => $customPetitionPropertyOptionC2Selected->type,
            ],
        ]);

        $this->assertEquals($expectedResult, $result);
    }

    private function getCustomPetitionPropertiesViewFactory(): PetitionCustomPetitionPropertiesViewFactory
    {
        return $this->app->get(PetitionCustomPetitionPropertiesViewFactory::class);
    }
}
