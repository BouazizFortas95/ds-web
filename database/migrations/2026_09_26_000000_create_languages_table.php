<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The languages the app offers, editable from the panel's Languages screen.
 *
 * `config/localization.php` used to be the only record of this, but a config file
 * cannot be edited from a browser, so adding a language meant a deploy. The
 * config stays as the seed and as the fallback for when this table is empty,
 * which keeps a fresh install and the test suite working untouched.
 *
 * The rows are seeded here rather than by a command so that migrating is the
 * only step needed to get a working language picker.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('languages', function (Blueprint $table) {
            // A language tag such as `en`, `ar` or `zh_CN`. Deliberately short and
            // constrained: this value is interpolated into a `lang_path()` glob by
            // the seeder, so anything with a slash or a dot in it has to be
            // impossible to store, not merely discouraged.
            $table->string('code', 12)->primary();

            // The language's own name, in its own script, because this is what the
            // switcher shows to someone who cannot read the current language.
            $table->string('name', 64);

            $table->string('direction', 3)->default('ltr');

            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);

            // The order the switcher lists languages in. A column rather than
            // sorting by code, because the existing order (English, Arabic,
            // French) is deliberate and alphabetical would reshuffle it, and
            // because a language added later should appear at the end rather
            // than in the middle.
            $table->unsignedSmallInteger('position')->default(0);

            $table->timestamps();
        });

        $this->seedFromConfig();
    }

    public function down(): void
    {
        Schema::dropIfExists('languages');
    }

    /**
     * Copy the configured languages in, so the table is never the reason a page
     * renders with no switcher.
     */
    private function seedFromConfig(): void
    {
        $locales = (array) config('localization.locales', []);
        $names = (array) config('localization.names', []);
        $directions = (array) config('localization.directions', []);
        $default = config('localization.default', 'en');

        $now = now();

        $rows = [];

        foreach (array_values($locales) as $index => $locale) {
            $rows[] = [
                'code' => (string) $locale,
                'name' => (string) ($names[$locale] ?? strtoupper((string) $locale)),
                'direction' => ($directions[$locale] ?? 'ltr') === 'rtl' ? 'rtl' : 'ltr',
                'is_default' => $locale === $default,
                'is_active' => true,
                'position' => $index,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows === []) {
            return;
        }

        DB::table('languages')->insert($rows);
    }
};
