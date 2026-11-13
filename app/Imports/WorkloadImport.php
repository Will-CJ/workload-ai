<?php

namespace App\Imports;

use App\Enums\Frequency;
use App\Models\Activity;
use App\Models\Department;
use App\Models\Employee;
use App\Models\ImportBatch;
use App\Services\FteCalculator;
use App\Services\TextNormalizer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class WorkloadImport implements ToCollection, WithHeadingRow
{
    public const REQUIRED_COLUMNS = [
        'activity_id', 'employee_id', 'employee_name', 'department',
        'activity_name', 'description', 'frequency', 'volume', 'duration_minutes',
    ];

    public int $totalRows = 0;
    public int $successRows = 0;
    public bool $headerInvalid = false;
    /** @var array<int, array{row: int|null, activity_id: string|null, messages: list<string>}> */
    public array $errors = [];

    public function __construct(
        private ImportBatch $batch,
        private FteCalculator $calculator,
        private TextNormalizer $normalizer,
    ) {}

    public function collection(Collection $rows): void
    {
        if ($rows->isEmpty()) {
            $this->headerInvalid = true;
            $this->errors[] = ['row' => null, 'activity_id' => null, 'messages' => ['File tidak berisi data.']];

            return;
        }

        $missing = array_diff(self::REQUIRED_COLUMNS, array_keys($rows->first()->toArray()));
        if ($missing !== []) {
            $this->headerInvalid = true;
            $this->errors[] = ['row' => 1, 'activity_id' => null, 'messages' => ['Kolom wajib tidak ada: '.implode(', ', $missing)]];

            return;
        }

        $seen = []; // activity_id => nomor baris pertama

        foreach ($rows as $index => $row) {
            $line = $index + 2; // +1 header, +1 karena index mulai 0
            $data = $this->clean($row);

            if (collect($data)->every(fn ($v) => $v === null || $v === '')) {
                continue; // lewati baris kosong
            }

            $this->totalRows++;
            $messages = Validator::make($data, $this->rules(), $this->messages())->errors()->all();

            $activityId = $data['activity_id'];
            if ($activityId !== null && isset($seen[$activityId])) {
                $messages[] = "activity_id {$activityId} duplikat dengan baris {$seen[$activityId]}.";
            } elseif ($activityId !== null) {
                $seen[$activityId] = $line; // catat semua ID, valid maupun tidak
            }

            if ($messages !== []) {
                $this->errors[] = ['row' => $line, 'activity_id' => $activityId, 'messages' => $messages];

                continue;
            }

            $this->persist($data);
            $this->successRows++;
        }
    }

    private function clean(Collection $row): array
    {
        $data = [];
        foreach (self::REQUIRED_COLUMNS as $column) {
            $value = $row->get($column);
            $data[$column] = is_string($value) ? trim($value) : $value;
        }
        if (is_string($data['frequency'])) {
            $data['frequency'] = strtolower($data['frequency']);
        }
        foreach (['activity_id', 'employee_id'] as $column) {
            if ($data[$column] !== null) {
                $data[$column] = (string) $data[$column];
            }
        }

        return $data;
    }

    private function rules(): array
    {
        return [
            'activity_id' => ['required', 'string', 'max:20'],
            'employee_id' => ['required', 'string', 'max:20'],
            'employee_name' => ['required', 'string', 'max:150'],
            'department' => ['required', 'string', 'max:100'],
            'activity_name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'frequency' => ['required', Rule::in(Frequency::values())],
            'volume' => ['required', 'integer', 'min:1'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
        ];
    }

    private function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'integer' => ':attribute harus berupa angka bulat.',
            'min' => ':attribute minimal :min.',
            'max' => ':attribute maksimal :max.',
            'frequency.in' => 'frequency harus salah satu dari: '.implode(', ', Frequency::values()).'.',
        ];
    }

    private function persist(array $data): void
    {
        $department = Department::firstOrCreate(['name' => $data['department']]);

        $employee = Employee::updateOrCreate(
            ['employee_code' => $data['employee_id']],
            ['name' => $data['employee_name'], 'department_id' => $department->id],
        );

        $frequency = Frequency::from($data['frequency']);
        $annualMinutes = $this->calculator->annualMinutes($frequency, (int) $data['volume'], (int) $data['duration_minutes']);

        Activity::updateOrCreate(
            ['activity_code' => $data['activity_id']],
            [
                'employee_id' => $employee->id,
                'import_batch_id' => $this->batch->id,
                'activity_name' => $data['activity_name'],
                'description' => $data['description'],
                'frequency' => $frequency,
                'volume' => (int) $data['volume'],
                'duration_minutes' => (int) $data['duration_minutes'],
                'annual_minutes' => $annualMinutes,
                'fte' => $this->calculator->fteFromAnnual($annualMinutes),
                'normalized_key' => $this->normalizer->key($data['activity_name']),
                'content_hash' => $this->normalizer->contentHash($data['activity_name'], $data['description']),
                // dihitung ulang oleh pipeline (dari cache kalau konten sama)
                'activity_group_id' => null,
                'estimated_fte_saving' => null,
            ],
        );
    }
}