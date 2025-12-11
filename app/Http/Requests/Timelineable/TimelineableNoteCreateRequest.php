<?php

declare(strict_types=1);

namespace App\Http\Requests\Timelineable;

use App\Config\Config;
use App\Enums\RouteName;
use App\Http\Requests\FormRequest;
use App\Models\Contracts\TimelineableInterface;
use App\Models\Department;
use App\Rules\VirusscannerRule;
use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Route;
use Illuminate\Validation\Rules\File;
use Override;
use Symfony\Component\Mime\MimeTypes;
use Webmozart\Assert\Assert;

use function __;
use function array_intersect;
use function in_array;
use function route;

class TimelineableNoteCreateRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(VirusscannerRule $virusscannerRule): array
    {
        $extensions = Config::arrayAllString('filesystems.disks.uploads.allowed_extensions');

        return [
            'comment' => [
                'required',
                'string',
            ],
            'attachments' => [
                'array',
            ],
            'attachments.*' => [
                $virusscannerRule,
                File::types($extensions)
                    ->max(Config::string('filesystems.disks.uploads.max_file_size')),
                $this->secureFileUpload(...),
            ],
        ];
    }

    #[Override]
    protected function getRedirectUrl(): string
    {
        $route = $this->route();
        Assert::isInstanceOf($route, Route::class);

        $department = $route->parameter('department');
        Assert::isInstanceOf($department, Department::class);

        if ($this->has('hx-target')) {
            $parameters = [
                'department' => $department,
                'timelineableType' => $route->parameter('timelineableType'),
                'timelineable' => $route->parameter('timelineable'),
                'url' => $route->parameter('url'),
                'hx-target' => $this->input('hx-target'),
            ];

            return route(RouteName::DEPARTMENTS_TIMELINEABLE_NOTES_CREATE, $parameters);
        }

        $timelineable = $route->parameter('timelineable');
        Assert::isInstanceOf($timelineable, TimelineableInterface::class);

        return route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
            'department' => $department,
            'petition' => $timelineable,
        ]);
    }

    protected function secureFileUpload(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value instanceof UploadedFile) {
            $fail(__("validation.secure_file_upload.uploaded_file"));

            return;
        }

        $mimeType = $value->getMimeType();

        Assert::string($mimeType);
        $fileExtension = $value->getClientOriginalExtension();

        $expectedExtensions = array_intersect(
            (new MimeTypes())->getExtensions($mimeType),
            Config::array('filesystems.disks.uploads.allowed_extensions'),
        );

        if (!in_array($fileExtension, $expectedExtensions, true)) {
            $fail(__("validation.secure_file_upload.invalid_extension"));
        }
    }
}
