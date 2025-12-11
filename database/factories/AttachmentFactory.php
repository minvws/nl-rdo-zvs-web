<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Attachment;

use function sprintf;

/**
 * @extends Factory<Attachment>
 */
class AttachmentFactory extends Factory
{
    /** @var class-string<Attachment> $model */
    protected $model = Attachment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [

            'disk' => 'uploads',
            'path' => sprintf('/attachments/%s.txt', $this->faker->slug),
            'name' => sprintf('%s.%s', $this->faker->word(), $this->faker->fileExtension()),
        ];
    }
}
