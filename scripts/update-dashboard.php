<?php

declare(strict_types=1);

/**
 * Dice & Dragons - Automated Organization Suite Dashboard Generator
 *
 * Scans all repositories in the Dice-Dragons organization via GitHub REST API (curl),
 * extracts metadata from published releases and addon.json, and regenerates
 * profile/README.md.
 *
 * 100% native PHP (cURL) - Zero shell/exec dependencies.
 * Works both locally and inside GitHub Actions.
 */

$rootDir = dirname(__DIR__);
$readmeFile = $rootDir . DIRECTORY_SEPARATOR . 'profile' . DIRECTORY_SEPARATOR . 'README.md';

$token = getenv('GH_TOKEN') ?: getenv('GITHUB_TOKEN') ?: null;

echo "=====================================================\n";
echo " Dice & Dragons - Organization Dashboard Generator\n";
echo " Target: {$readmeFile}\n";
echo " Auth:   " . ($token ? "Token provided (Authenticated)" : "Anonymous (subject to rate limits)") . "\n";
echo "=====================================================\n\n";

// 1. Fetch repositories from organization Dice-Dragons
echo "🔍 Fetching repositories from Dice-Dragons organization...\n";
$repos = fetchAllOrgRepos('Dice-Dragons', $token);

if (!is_array($repos)) {
    echo "❌ Failed to fetch repositories from GitHub API.\n";
    exit(1);
}

$excludeRepos = [
    '.github',
    'Settings',
    'Vorgify',
    'DevTools',
    'BookList',
    'demo-repository',
];

$rows = [];

echo "Found " . count($repos) . " repositories in organization.\n";
echo "Scanning for published XenForo add-ons...\n\n";

foreach ($repos as $r) {
    $repoName = $r['name'];
    if (in_array($repoName, $excludeRepos, true)) {
        continue;
    }

    $fullName = "Dice-Dragons/{$repoName}";

    // Check if repository has published releases
    $releases = githubApi("/repos/{$fullName}/releases?per_page=5", $token);

    $hasPublishedRelease = false;
    if (is_array($releases)) {
        foreach ($releases as $rel) {
            if (empty($rel['draft'])) {
                $hasPublishedRelease = true;
                break;
            }
        }
    }

    if (!$hasPublishedRelease) {
        continue;
    }

    // Fetch addon.json from repo
    $addonFileResp = githubApi("/repos/{$fullName}/contents/addon.json", $token);
    if (!is_array($addonFileResp) || empty($addonFileResp['content'])) {
        continue;
    }

    $addonJsonRaw = base64_decode(trim((string)$addonFileResp['content']));
    // Strip trailing commas before JSON decode for compatibility
    $addonJsonClean = preg_replace('/,\s*([\}\]])/', '$1', $addonJsonRaw);
    $addonJson = json_decode($addonJsonClean, true);

    if (!is_array($addonJson)) {
        continue;
    }

    $req = $addonJson['require'] ?? [];
    $coreDep = '-';
    $otherDeps = [];

    foreach ($req as $pkg => $spec) {
        if ($pkg === 'cv6/Core') {
            $ver = is_array($spec) ? ($spec[1] ?? '') : $spec;
            if (preg_match('/([0-9]+(?:\.[0-9]+)+)/', (string)$ver, $m)) {
                $coreDep = '^' . $m[1];
            } else {
                $coreDep = (string)$ver;
            }
        } elseif (str_starts_with($pkg, 'cv6/')) {
            $otherDeps[] = substr($pkg, 4);
        } elseif ($pkg === 'XFRM') {
            $otherDeps[] = 'XFRM';
        }
    }

    $depStr = $coreDep;
    if (!empty($otherDeps)) {
        if ($depStr === '-') {
            $depStr = implode(', ', $otherDeps);
        } else {
            $depStr .= ' + ' . implode(', ', $otherDeps);
        }
    }

    $support = $addonJson['support_url'] ?? $addonJson['faq_url'] ?? '';
    $title = $addonJson['title'] ?? $repoName;
    $version = $addonJson['version_string'] ?? '1.0.0';

    echo "  ✓ {$title} ({$version})\n";

    $rows[] = [
        'repo' => $repoName,
        'title' => $title,
        'version' => $version,
        'deps' => $depStr,
        'support' => $support,
    ];
}

// Sort: Core library first, then alphabetically by title
usort($rows, function ($a, $b) {
    if ($a['repo'] === 'Core') return -1;
    if ($b['repo'] === 'Core') return 1;
    return strcmp($a['title'], $b['title']);
});

$count = count($rows);
echo "\nTotal published add-ons found: {$count}\n";

$table = "| Add-on | Version | Abhängigkeiten | Support |\n";
$table .= "| :--- | :--- | :--- | :--- |\n";
foreach ($rows as $row) {
    $link = $row['support'] !== '' ? "[Forum]({$row['support']})" : '-';
    $table .= "| **{$row['title']}** | `{$row['version']}` | `{$row['deps']}` | {$link} |\n";
}

$readme = <<<MD
# Dice & Dragons — XenForo 2.3+ Add-on Suite

Herzlich willkommen im GitHub-Organisationsprofil von **Dice & Dragons**!

Hier werden die offiziellen XenForo 2.3+ Add-ons von [Hoffi](https://forum.dice-dragons.de/mitglieder/hoffi.1/) verwaltet, gepflegt und über CI/CD-Pipelines bereitgestellt.

---

## 📊 Live Suite-Dashboard

Übersicht aller {$count} veröffentlichten Add-ons der Suite:

{$table}

---

## ⚙️ Technische Richtlinien

* **Framework**: XenForo 2.3.0+
* **PHP Baseline**: PHP 8.2.0+ (optimiert für PHP 8.4)
* **Frontend**: Vanilla JS (ES2020), LESS, FontAwesome 5.x Baseline
* **Lizenz**: Proprietary (Nutzung für Inhaber einer gültigen XenForo-Lizenz)

---

## 💬 Community & Support

* 🌐 **Hauptforum**: [https://forum.dice-dragons.de](https://forum.dice-dragons.de)
* 📥 **Downloads & Ressourcen**: [https://forum.dice-dragons.de/downloads/](https://forum.dice-dragons.de/downloads/)
* 👤 **Entwickler-Profil**: [Hoffi auf Dice & Dragons](https://forum.dice-dragons.de/mitglieder/hoffi.1/) | [Hoffi auf XenForo.com](https://xenforo.com/community/members/hoffi.214/)
MD;

if (!is_dir(dirname($readmeFile))) {
    mkdir(dirname($readmeFile), 0777, true);
}

file_put_contents($readmeFile, $readme);
echo "✅ Successfully written {$count} add-ons to {$readmeFile}!\n";

// ==========================================================================
// Native PHP cURL Functions
// ==========================================================================

function fetchAllOrgRepos(string $org, ?string $token): array
{
    $all = [];
    $page = 1;
    while (true) {
        $endpoint = "/orgs/{$org}/repos?per_page=100&page={$page}";
        $data = githubApi($endpoint, $token);
        if (!is_array($data) || empty($data)) {
            break;
        }
        foreach ($data as $item) {
            $all[] = $item;
        }
        if (count($data) < 100) {
            break;
        }
        $page++;
    }
    return $all;
}

function githubApi(string $endpoint, ?string $token): ?array
{
    $url = 'https://api.github.com/' . ltrim($endpoint, '/');
    $ch = curl_init($url);

    $headers = [
        'User-Agent: Dice-Dragons-Suite-Dashboard/1.0',
        'Accept: application/vnd.github.v3+json',
    ];

    if ($token !== null && $token !== '') {
        $headers[] = 'Authorization: Bearer ' . $token;
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_FOLLOWLOCATION => true,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 200 && $httpCode < 300 && is_string($response)) {
        return json_decode($response, true);
    }

    return null;
}
