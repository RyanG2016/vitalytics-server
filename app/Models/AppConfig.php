<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Carbon\Carbon;

class AppConfig extends Model
{
    protected $fillable = [
        'app_identifier',
        'config_key',
        'filename',
        'description',
        'content_type',
        'embed_version_header',
        'version_header_template',
        'current_version',
        'is_active',
    ];

    protected $casts = [
        'embed_version_header' => 'boolean',
        'is_active' => 'boolean',
        'current_version' => 'integer',
    ];

    /**
     * Default version header template for INI files
     */
    const DEFAULT_HEADER_TEMPLATE = "; ===========================================\n; Config: {filename}\n; Version: {version}\n; Updated: {updated_at}\n; Hash: {hash}\n; Managed by Vitalytics\n; ===========================================\n\n";

    /**
     * All versions of this config
     */
    public function versions(): HasMany
    {
        return $this->hasMany(AppConfigVersion::class)->orderByDesc('version');
    }

    /**
     * The current active version
     */
    public function currentVersionRecord(): HasOne
    {
        return $this->hasOne(AppConfigVersion::class)->where('version', $this->current_version);
    }

    /**
     * Scope: Only active configs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Configs for a specific app
     */
    public function scopeForApp($query, string $appIdentifier)
    {
        return $query->where('app_identifier', $appIdentifier);
    }

    /**
     * Get the current version's content hash
     */
    public function getCurrentHash(): ?string
    {
        $version = $this->versions()->where('version', $this->current_version)->first();
        return $version?->content_hash;
    }

    /**
     * Get the current version's content
     */
    public function getCurrentContent(): ?string
    {
        $version = $this->versions()->where('version', $this->current_version)->first();
        return $version?->content;
    }

    /**
     * Get content with version header prepended (if enabled)
     */
    public function getContentWithHeader(): ?string
    {
        $content = $this->getCurrentContent();

        if (!$content || !$this->embed_version_header) {
            return $content;
        }

        $header = $this->buildVersionHeader();
        return $header . $content;
    }

    /**
     * Build the version header from template
     */
    protected function buildVersionHeader(): string
    {
        $template = $this->version_header_template ?: self::DEFAULT_HEADER_TEMPLATE;
        $version = $this->versions()->where('version', $this->current_version)->first();

        $replacements = [
            '{filename}' => $this->filename,
            '{version}' => $this->current_version,
            '{updated_at}' => $version?->created_at?->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s') ?? now()->format('Y-m-d H:i:s'),
            '{hash}' => $version?->content_hash ?? '',
            '{config_key}' => $this->config_key,
            '{app_identifier}' => $this->app_identifier,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    /**
     * Create a new version of this config
     */
    public function createVersion(string $content, ?string $notes = null, ?int $userId = null): AppConfigVersion
    {
        $newVersion = $this->current_version + 1;

        $version = $this->versions()->create([
            'version' => $newVersion,
            'content' => $content,
            'change_notes' => $notes,
            'created_by' => $userId,
        ]);

        $this->update(['current_version' => $newVersion]);

        return $version;
    }

    /**
     * Rollback to a specific version
     */
    public function rollbackTo(int $versionNumber): bool
    {
        $version = $this->versions()->where('version', $versionNumber)->first();

        if (!$version) {
            return false;
        }

        // Create a new version with the old content
        $this->createVersion(
            $version->content,
            "Rollback to version {$versionNumber}",
            auth()->id()
        );

        return true;
    }

    /**
     * Get content type for HTTP response
     */
    public function getHttpContentType(): string
    {
        return match ($this->content_type) {
            'json' => 'application/json',
            'xml' => 'application/xml',
            'ini' => 'text/plain',
            default => 'text/plain',
        };
    }
}
