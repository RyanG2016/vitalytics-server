# Vitalytics Remote Configuration - Client Implementation Guide

## Overview

Vitalytics now supports serving configuration files to client applications. This allows centralized configuration management with version tracking, automatic change detection, and rollback capability.

**Use Case:** Replace local/network config files (e.g., UNC path configs) with cloud-hosted configs that auto-update on heartbeat.

---

## How It Works

1. **Server stores configs** with version numbers and content hashes
2. **Client sends heartbeat** → response includes config metadata (version + hash)
3. **Client compares hash** to cached version → downloads if different
4. **Client caches config** locally for offline use

---

## Heartbeat Response (Enhanced)

When your app sends a heartbeat event, the response now includes a `configs` field:

```json
{
  "success": true,
  "batchId": "batch-123",
  "eventsReceived": 1,
  "configs": {
    "main": {
      "version": 5,
      "hash": "a1b2c3d4e5f6..."
    },
    "local": {
      "version": 2,
      "hash": "x9y8z7w6v5u4..."
    }
  }
}
```

**Fields:**
- `configs` - Object with config keys as properties (only present if configs exist for this app)
- `version` - Integer version number (increments on each save)
- `hash` - SHA-256 hash of the config content (use this for comparison)

---

## API Endpoints

### Get Config Manifest
```
GET /api/v1/config/{app_identifier}
Header: X-API-Key: your-api-key
```

**Response:**
```json
{
  "success": true,
  "appIdentifier": "example-app-helper",
  "configs": {
    "main": {
      "version": 5,
      "hash": "a1b2c3...",
      "filename": "config.ini",
      "contentType": "ini"
    }
  }
}
```

### Download Config Content
```
GET /api/v1/config/{app_identifier}/{config_key}
Header: X-API-Key: your-api-key
```

**Response:**
- Content-Type: `text/plain` (for INI files)
- X-Config-Version: `5`
- X-Config-Hash: `a1b2c3...`
- ETag: `"a1b2c3..."`
- Body: The raw config file content

**Example INI response (with version header):**
```ini
; ===========================================
; Config: config.ini
; Version: 5
; Updated: 2026-01-28 10:30:00
; Hash: a1b2c3d4e5f6...
; Managed by Vitalytics
; ===========================================

[Server]
Address=192.168.1.100
Port=8080

[Settings]
Timeout=30
RetryCount=3
```

---

## Client Implementation (.NET)

### 1. Data Models

```csharp
public class ConfigMetadata
{
    public int Version { get; set; }
    public string Hash { get; set; }
}

public class CachedConfig
{
    public string ConfigKey { get; set; }
    public string Content { get; set; }
    public string Hash { get; set; }
    public DateTime CachedAt { get; set; }
}
```

### 2. Parse Heartbeat Response

```csharp
public class HeartbeatResponse
{
    public bool Success { get; set; }
    public string BatchId { get; set; }
    public int EventsReceived { get; set; }
    public Dictionary<string, ConfigMetadata> Configs { get; set; }
}

// After sending heartbeat
var response = JsonSerializer.Deserialize<HeartbeatResponse>(responseJson);

if (response.Configs != null)
{
    foreach (var (configKey, meta) in response.Configs)
    {
        await CheckAndUpdateConfig(configKey, meta);
    }
}
```

### 3. Check and Update Config

```csharp
private readonly string _configCachePath = Path.Combine(
    Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData),
    "Example AppHelper", "configs"
);

public async Task CheckAndUpdateConfig(string configKey, ConfigMetadata serverMeta)
{
    // Get cached hash
    var cachedHash = GetCachedConfigHash(configKey);

    // Compare hashes
    if (cachedHash == serverMeta.Hash)
    {
        // Config is up to date
        return;
    }

    // Download new config
    _logger.Info($"Config '{configKey}' changed (v{serverMeta.Version}), downloading...");

    try
    {
        var content = await DownloadConfig(configKey);
        SaveConfigToCache(configKey, content, serverMeta.Hash);

        // Notify application of config change
        OnConfigUpdated?.Invoke(configKey, content);
    }
    catch (Exception ex)
    {
        _logger.Error($"Failed to download config '{configKey}': {ex.Message}");
        // Continue using cached version
    }
}

private string GetCachedConfigHash(string configKey)
{
    var hashFile = Path.Combine(_configCachePath, $"{configKey}.hash");
    if (File.Exists(hashFile))
    {
        return File.ReadAllText(hashFile).Trim();
    }
    return null;
}

private void SaveConfigToCache(string configKey, string content, string hash)
{
    Directory.CreateDirectory(_configCachePath);

    var configFile = Path.Combine(_configCachePath, $"{configKey}.ini");
    var hashFile = Path.Combine(_configCachePath, $"{configKey}.hash");

    File.WriteAllText(configFile, content);
    File.WriteAllText(hashFile, hash);
}
```

### 4. Download Config from API

```csharp
public async Task<string> DownloadConfig(string configKey)
{
    using var client = new HttpClient();
    client.DefaultRequestHeaders.Add("X-API-Key", _apiKey);

    var url = $"{_baseUrl}/api/v1/config/{_appIdentifier}/{configKey}";
    var response = await client.GetAsync(url);

    response.EnsureSuccessStatusCode();

    return await response.Content.ReadAsStringAsync();
}
```

### 5. Load Config on Startup

```csharp
public string LoadConfig(string configKey)
{
    var configFile = Path.Combine(_configCachePath, $"{configKey}.ini");

    if (File.Exists(configFile))
    {
        return File.ReadAllText(configFile);
    }

    // No cached config - try to download
    try
    {
        var content = DownloadConfig(configKey).GetAwaiter().GetResult();
        // Compute hash ourselves since we don't have server hash yet
        var hash = ComputeSha256(content);
        SaveConfigToCache(configKey, content, hash);
        return content;
    }
    catch
    {
        // No config available - return null or throw
        return null;
    }
}

private string ComputeSha256(string content)
{
    using var sha256 = SHA256.Create();
    var bytes = Encoding.UTF8.GetBytes(content);
    var hash = sha256.ComputeHash(bytes);
    return BitConverter.ToString(hash).Replace("-", "").ToLowerInvariant();
}
```

### 6. Event Handler for Config Changes

```csharp
public event Action<string, string> OnConfigUpdated;

// In your application startup
configManager.OnConfigUpdated += (configKey, content) =>
{
    _logger.Info($"Config '{configKey}' updated, reloading...");

    if (configKey == "main")
    {
        // Reload main configuration
        ReloadMainConfig(content);
    }
};
```

---

## Recommended Flow

```
┌─────────────────────────────────────────────────────────────┐
│                      APP STARTUP                            │
├─────────────────────────────────────────────────────────────┤
│  1. Check if cached config exists                           │
│     ├─ YES: Load from cache, start app                      │
│     └─ NO: Download from server, cache it, start app        │
│                                                             │
│  2. Start heartbeat timer (e.g., every 5 minutes)           │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                    HEARTBEAT CYCLE                          │
├─────────────────────────────────────────────────────────────┤
│  1. Send heartbeat event to Vitalytics                      │
│  2. Parse response, check for 'configs' field               │
│  3. For each config:                                        │
│     ├─ Compare hash to cached hash                          │
│     ├─ If different: download new config                    │
│     ├─ Save to cache                                        │
│     └─ Trigger OnConfigUpdated event                        │
│  4. App reloads config without restart                      │
└─────────────────────────────────────────────────────────────┘
```

---

## Fallback Behavior

The client should handle these scenarios gracefully:

| Scenario | Behavior |
|----------|----------|
| No internet | Use cached config |
| API error | Use cached config, log warning |
| No cached config + no internet | Use bundled default or fail gracefully |
| Config download fails | Keep using current cached version |

---

## Testing

1. **Initial setup:** Create a config in Vitalytics admin (`/admin/configs`) for your app
2. **First run:** App should download and cache the config
3. **Subsequent runs:** App should load from cache
4. **Config change:** Update config in admin, wait for heartbeat, verify app receives update
5. **Offline test:** Disconnect network, restart app, verify cached config loads

---

## Admin UI

Configs are managed at: `https://your-vitalytics-server.com/admin/configs`

- Create configs per app with unique keys (e.g., "main", "local")
- Edit content with automatic versioning
- View version history and rollback if needed
- Toggle version header embedding (prepends version info to config)

---

## Questions?

Contact the Vitalytics team or check the API documentation.
