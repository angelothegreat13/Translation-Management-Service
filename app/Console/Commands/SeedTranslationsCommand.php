<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tag;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SeedTranslationsCommand extends Command
{
    protected $signature = 'translations:seed {--count=100000 : Number of translations to seed}';

    protected $description = 'Seed translations with fake data for scalability testing';

    private const BATCH_SIZE = 1000;

    private const LOCALES = ['en', 'fr', 'es', 'de', 'it', 'pt'];

    private const GROUPS = [
        'auth', 'dashboard', 'navigation', 'buttons',
        'messages', 'errors', 'notifications', 'forms',
        'labels', 'placeholders', 'validation', 'pagination',
    ];

    private const TAG_NAMES = ['web', 'mobile', 'desktop', 'api', 'frontend', 'backend'];

    public function handle(): int
    {
        $count = (int) $this->option('count');

        $this->info("Seeding {$count} translations...");

        $tagIds = $this->seedTags();

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $now = now()->toDateTimeString();

        for ($offset = 0; $offset < $count; $offset += self::BATCH_SIZE) {
            $batchSize = min(self::BATCH_SIZE, $count - $offset);

            $rows = $this->buildTranslationRows($batchSize, $offset, $now);

            DB::table('translations')->insert($rows);

            $firstId = (int) DB::getPdo()->lastInsertId();
            $lastId  = $firstId + $batchSize - 1;

            $this->insertPivotRecords($firstId, $lastId, $tagIds);

            $bar->advance($batchSize);
        }

        $bar->finish();

        $this->newLine();
        $this->info("Done! {$count} translations seeded successfully.");

        return self::SUCCESS;
    }

    /** @return int[] */
    private function seedTags(): array
    {
        return collect(self::TAG_NAMES)
            ->map(fn(string $name) => Tag::firstOrCreate(['name' => $name])->id)
            ->toArray();
    }

    private function buildTranslationRows(int $size, int $offset, string $now): array
    {
        $rows = [];

        for ($i = 0; $i < $size; $i++) {
            $group  = self::GROUPS[array_rand(self::GROUPS)];
            $locale = self::LOCALES[array_rand(self::LOCALES)];

            $rows[] = [
                'key'        => $group . '.' . Str::random(8) . '_' . ($offset + $i),
                'locale'     => $locale,
                'content'    => $this->fakeSentence(),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return $rows;
    }

    /** @param int[] $tagIds */
    private function insertPivotRecords(int $firstId, int $lastId, array $tagIds): void
    {
        $pivot = [];

        for ($id = $firstId; $id <= $lastId; $id++) {
            $assigned = (array) array_rand(
                array_flip($tagIds),
                random_int(1, min(2, count($tagIds)))
            );

            foreach ($assigned as $tagId) {
                $pivot[] = ['translation_id' => $id, 'tag_id' => $tagId];
            }
        }

        DB::table('translation_tag')->insertOrIgnore($pivot);
    }

    private function fakeSentence(): string
    {
        $words = [
            'the', 'quick', 'brown', 'fox', 'jumps', 'over', 'lazy', 'dog',
            'click', 'here', 'save', 'cancel', 'submit', 'delete', 'update',
            'welcome', 'error', 'success', 'warning', 'loading', 'please',
            'enter', 'your', 'email', 'password', 'name', 'confirm', 'back',
        ];

        $length   = random_int(4, 10);
        $sentence = [];

        for ($i = 0; $i < $length; $i++) {
            $sentence[] = $words[array_rand($words)];
        }

        return ucfirst(implode(' ', $sentence)) . '.';
    }
}
