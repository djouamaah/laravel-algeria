<?php

namespace Djouamaah\Algeria;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * In-memory access to the bundled dataset. Works without a database.
 *
 * Every row is a plain array:
 *  wilaya  : id, code, name, name_ar, parent_id
 *  daira   : id, wilaya_id, name, name_ar
 *  commune : id, daira_id, wilaya_id, legacy_wilaya_id, name, name_ar
 */
class Algeria
{
    public const DIVISIONS = [58, 69];

    /** @var array{wilayas: array<int, array>, dairas: array<int, array>, communes: array<int, array>}|null */
    protected ?array $data = null;

    public function __construct(
        protected int $division = 69,
        protected ?string $dataPath = null,
    ) {
        if (! in_array($division, self::DIVISIONS, true)) {
            throw new InvalidArgumentException("Unsupported division [{$division}], use 58 or 69.");
        }

        $this->dataPath ??= dirname(__DIR__).'/data';
    }

    public function division(): int
    {
        return $this->division;
    }

    /**
     * A copy of this service using another division (58 or 69).
     */
    public function withDivision(int $division): static
    {
        return new static($division, $this->dataPath);
    }

    public function wilayas(): Collection
    {
        return collect($this->data()['wilayas'])->values();
    }

    /**
     * Find a wilaya by id or code: 16, "16" and "016" all return Algiers.
     */
    public function wilaya(int|string $wilaya): ?array
    {
        return is_numeric($wilaya) ? ($this->data()['wilayas'][(int) $wilaya] ?? null) : null;
    }

    public function dairas(int|string|null $wilaya = null): Collection
    {
        $dairas = collect($this->data()['dairas']);

        if ($wilaya !== null) {
            $id = $this->wilaya($wilaya)['id'] ?? null;
            $dairas = $dairas->where('wilaya_id', $id);
        }

        return $dairas->values();
    }

    public function daira(int|string $daira): ?array
    {
        return is_numeric($daira) ? ($this->data()['dairas'][(int) $daira] ?? null) : null;
    }

    public function communes(int|string|null $wilaya = null): Collection
    {
        $communes = collect($this->data()['communes']);

        if ($wilaya !== null) {
            $id = $this->wilaya($wilaya)['id'] ?? null;
            $communes = $communes->where('wilaya_id', $id);
        }

        return $communes->values();
    }

    public function communesOfDaira(int|string $daira): Collection
    {
        $id = $this->daira($daira)['id'] ?? null;

        return collect($this->data()['communes'])->where('daira_id', $id)->values();
    }

    public function commune(int|string $commune): ?array
    {
        return is_numeric($commune) ? ($this->data()['communes'][(int) $commune] ?? null) : null;
    }

    /**
     * Search communes by Latin or Arabic name, ignoring case, accents and hamza/ta marbuta variants.
     */
    public function search(string $term, int|string|null $wilaya = null): Collection
    {
        $needle = static::normalize($term);

        if ($needle === '') {
            return collect();
        }

        return $this->communes($wilaya)
            ->filter(fn (array $c) => str_contains(static::normalize($c['name']), $needle)
                || str_contains(static::normalize($c['name_ar']), $needle))
            ->values();
    }

    public static function normalize(string $value): string
    {
        $value = str_replace(['أ', 'إ', 'آ', 'ة', 'ى', 'ـ'], ['ا', 'ا', 'ا', 'ه', 'ي', ''], $value);

        if (! preg_match('/\p{Arabic}/u', $value)) {
            $value = Str::ascii($value);
        }

        return trim(preg_replace('/[\s\'\-]+/u', ' ', mb_strtolower($value)));
    }

    /**
     * @return array{wilayas: array<int, array>, dairas: array<int, array>, communes: array<int, array>}
     */
    public function data(): array
    {
        if ($this->data !== null) {
            return $this->data;
        }

        $legacy = $this->division === 58;

        $wilayas = [];
        foreach ($this->read('wilayas') as $w) {
            if ($legacy && $w['id'] > 58) {
                continue;
            }
            $w['parent_id'] = $legacy ? null : $w['parent_id'];
            $wilayas[$w['id']] = $w;
        }

        $dairas = [];
        foreach ($this->read('dairas') as $d) {
            if ($legacy) {
                if ($d['legacy_wilaya_id'] === null) {
                    continue;
                }
                $d['wilaya_id'] = $d['legacy_wilaya_id'];
            }
            unset($d['legacy_wilaya_id']);
            $dairas[$d['id']] = $d;
        }

        $communes = [];
        foreach ($this->read('communes') as $c) {
            if ($legacy) {
                $c['daira_id'] = $c['legacy_daira_id'];
                $c['wilaya_id'] = $c['legacy_wilaya_id'];
            }
            unset($c['legacy_daira_id']);
            $communes[$c['id']] = [
                'id' => $c['id'],
                'daira_id' => $c['daira_id'],
                'wilaya_id' => $c['wilaya_id'],
                'legacy_wilaya_id' => $c['legacy_wilaya_id'],
                'name' => $c['name'],
                'name_ar' => $c['name_ar'],
            ];
        }

        return $this->data = compact('wilayas', 'dairas', 'communes');
    }

    protected function read(string $file): array
    {
        return json_decode(file_get_contents("{$this->dataPath}/{$file}.json"), true, flags: JSON_THROW_ON_ERROR);
    }
}
