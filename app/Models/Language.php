<?php

namespace App\Models;

use App\Support\Locales;
use Illuminate\Database\Eloquent\Model;

/**
 * A language the app offers, editable from the panel.
 *
 * @property string $code
 * @property string $name
 * @property string $direction
 * @property bool $is_default
 * @property bool $is_active
 */
class Language extends Model
{
    /**
     * `code` is the primary key rather than an auto-incrementing id, so a row is
     * addressed by the thing that makes it unique and no lookup has to go by id.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'direction',
        'is_default',
        'is_active',
        'position',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'position' => 'integer',
    ];

    /**
     * `code` is the key, so it can never be an auto-incrementing integer, and
     * saving must not try to renumber anything.
     */
    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * Without this Eloquent looks for an `id` column that the table does not
     * have, so every `find()`, every relation and the panel's own record
     * resolution would fail against a column that was never created.
     */
    protected $primaryKey = 'code';

    /**
     * Languages written right to left.
     *
     * Only used to pre-select the direction when a language is added, so the list
     * does not need to be exhaustive -- the form lets the answer be corrected,
     * which matters because a language can change script by region: `az` is Latin
     * in Azerbaijan and Arabic in Azerbaijan, and `uz` is Cyrillic in
     * Uzbekistan and Latin in Afghanistan.
     *
     * @var list<string>
     */
    public const RTL_LANGUAGES = [
        'ar', 'arc', 'ckb', 'dv', 'fa', 'ha', 'he', 'khw', 'ks', 'ku', 'ps',
        'sd', 'ur', 'uz_AF', 'yi',
    ];

    protected static function booted(): void
    {
        // The default is a single row, not a per-request comparison, so that
        // "which language do we fall back to" has one answer at any moment.
        static::saved(function (self $language) {
            if ($language->wasChanged('is_default') && $language->is_default) {
                static::query()
                    ->where('code', '!=', $language->code)
                    ->update(['is_default' => false]);
            }

            Locales::flush();
        });

        static::deleted(fn () => Locales::flush());
    }

    /**
     * The direction to offer when this language is first added.
     */
    public static function guessDirection(string $code): string
    {
        return in_array($code, self::RTL_LANGUAGES, true) ? 'rtl' : 'ltr';
    }

    /**
     * What the switcher shows, and what the language is called in the panel.
     */
    public function getLabelAttribute(): string
    {
        return $this->name;
    }

    public function isRtl(): bool
    {
        return $this->direction === 'rtl';
    }

    /**
     * Whether removing this language would leave the app with nothing to serve.
     *
     * A visitor who arrives without a stored preference is given the default
     * language, so an app whose last available language had been deleted would
     * have nothing to fall back to. The delete action in the panel is withheld
     * when this is true, rather than accepting the click and then refusing, so
     * the reason is visible before the attempt instead of after it.
     */
    public function isLastActiveLanguage(): bool
    {
        return static::query()
            ->where('is_active', true)
            ->where('code', '!=', $this->code)
            ->doesntExist();
    }

    /**
     * How much of the catalogue has been translated into this language, as
     * `[$translated, $total]`.
     *
     * A language added through the panel has no `lang/` files and no database
     * cells, so it starts at zero and the editor fills it in. Without this the
     * table would list four healthy-looking languages with no way to tell which
     * one is still empty.
     *
     * @return array{0: int, 1: int}
     */
    public function progress(): array
    {
        $total = Locales::translatableKeyCount();

        if ($total === 0) {
            return [0, 0];
        }

        $translated = Locales::translationRows()
            ->filter(fn ($text) => trim((string) ($text[$this->code] ?? '')) !== '')
            ->count();

        return [$translated, $total];
    }
}
