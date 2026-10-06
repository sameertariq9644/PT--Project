<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

/**
 * Site settings an admin can change at runtime. Values live in the `settings`
 * table, are cached forever and flushed on write. A setting that has not been
 * saved falls back to `config('site.settings.*')`.
 */
class Settings
{
    public const CACHE_KEY = 'settings';

    /**
     * Keys whose values are encrypted at rest.
     *
     * @var list<string>
     */
    public const ENCRYPTED = ['mail.password'];

    /**
     * @var array<string, mixed>|null
     */
    protected ?array $values = null;

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = app(self::class)->all()[$key] ?? null;

        if ($value === null || $value === '') {
            return $default ?? config('site.settings.'.$key);
        }

        return $value;
    }

    /**
     * The decrypted value of an encrypted setting, or null when it is not set.
     */
    public static function secret(string $key): ?string
    {
        $value = app(self::class)->all()[$key] ?? null;

        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return null;
        }
    }

    /**
     * Store the given settings and flush the cache.
     *
     * @param  array<string, mixed>  $values
     */
    public static function set(array $values): void
    {
        foreach ($values as $key => $value) {
            if (in_array($key, self::ENCRYPTED, true) && is_string($value) && $value !== '') {
                $value = Crypt::encryptString($value);
            }

            Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Cache::forget(self::CACHE_KEY);

        app(self::class)->forgetLoaded();
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->values ??= $this->load();
    }

    /**
     * Drop the values held in memory so the next read goes back to the cache.
     * Long-running queue workers call this before every job.
     */
    public function forgetLoaded(): void
    {
        $this->values = null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function load(): array
    {
        try {
            return Cache::rememberForever(self::CACHE_KEY, function (): array {
                return Setting::query()->get()->mapWithKeys(
                    fn (Setting $setting): array => [$setting->key => $setting->value],
                )->all();
            });
        } catch (QueryException) {
            // The tables do not exist yet (fresh install, before migrating).
            return [];
        }
    }
}
