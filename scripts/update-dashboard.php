<?php

declare(strict_types=1);

/**
 * Dice & Dragons - Automated Organization Suite Dashboard Generator
 *
 * Scans all repositories in the Dice-Dragons organization via GitHub REST API (curl),
 * extracts metadata from published releases and addon.json, and regenerates
 * profile/README.md (English default) and profile/README.de.md (German).
 *
 * 100% native PHP (cURL) - Zero shell/exec dependencies.
 * Works both locally and inside GitHub Actions.
 */

$rootDir = dirname(__DIR__);
$readmeFileEn = $rootDir . DIRECTORY_SEPARATOR . 'profile' . DIRECTORY_SEPARATOR . 'README.md';
$readmeFileDe = $rootDir . DIRECTORY_SEPARATOR . 'profile' . DIRECTORY_SEPARATOR . 'README.de.md';

$token = getenv('GH_TOKEN') ?: getenv('GITHUB_TOKEN') ?: null;

echo "=====================================================\n";
echo " Dice & Dragons - Organization Dashboard Generator\n";
echo " Targets: {$readmeFileEn} & {$readmeFileDe}\n";
echo " Auth:    " . ($token ? "Token provided (Authenticated)" : "Anonymous (subject to rate limits)") . "\n";
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
    'zzzInternal',
    'cv6-AI-Skills',
    'XenForo-AI-Skills',
    'BookList',
    'demo-repository',
];

// Always include core RPG Suite pillar in main table even before first tagged release
$alwaysIncludeRepos = [
    'RPG',
];

$rows = [];
$allAddonMeta = [];

echo "Found " . count($repos) . " repositories in organization.\n";
echo "Scanning for XenForo add-ons...\n\n";

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
        } elseif ($pkg === 'XFES') {
            $otherDeps[] = 'XFES';
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

    $meta = [
        'repo' => $repoName,
        'title' => $title,
        'version' => $version,
        'deps' => $depStr,
        'support' => $support,
        'published' => $hasPublishedRelease,
    ];

    $allAddonMeta[$repoName] = $meta;

    if (!$hasPublishedRelease && !in_array($repoName, $alwaysIncludeRepos, true)) {
        continue;
    }

    echo "  ✓ {$title} ({$version})\n";
    $rows[] = $meta;
}

// Sort: Core library first, RPG Suite second, then alphabetically by title
usort($rows, function ($a, $b) {
    if ($a['repo'] === 'Core') return -1;
    if ($b['repo'] === 'Core') return 1;
    if ($a['repo'] === 'RPG') return -1;
    if ($b['repo'] === 'RPG') return 1;
    return strcmp($a['title'], $b['title']);
});

$count = count($rows);
echo "\nTotal dashboard add-ons: {$count}\n";

$tableEn = "| Add-on | Version | Dependencies | Support |\n";
$tableEn .= "| :--- | :--- | :--- | :--- |\n";

$tableDe = "| Add-on | Version | Abhängigkeiten | Support |\n";
$tableDe .= "| :--- | :--- | :--- | :--- |\n";

foreach ($rows as $row) {
    $link = $row['support'] !== '' ? "[Forum]({$row['support']})" : '-';
    $line = "| **{$row['title']}** | `{$row['version']}` | `{$row['deps']}` | {$link} |\n";
    $tableEn .= $line;
    $tableDe .= $line;
}

// Build RPG Suite Ecosystem Tables (EN & DE)
$rpgCatalog = [
    [
        'repo' => 'Core',
        'default_title' => '[DD] - Core Library',
        'addon_id' => 'cv6/Core',
        'role_en' => 'Foundation',
        'role_de' => 'Basis & Infrastruktur',
        'desc_en' => 'Shared UI components, icon chooser, operators, and base helpers',
        'desc_de' => 'Gemeinsame UI-Komponenten, Icon-Picker, Vergleichsoperatoren & Basis-Helper',
    ],
    [
        'repo' => 'RPG',
        'default_title' => '[DD] RPG Suite',
        'addon_id' => 'cv6/RPG',
        'role_en' => 'Foundation',
        'role_de' => 'Basis & Infrastruktur',
        'desc_en' => 'Central genres, publishers, campaign content bridge, and RPG dashboard',
        'desc_de' => 'Zentrale Genres, Verlage (Publishers), Kampagnen-Content-Bridge & RPG-Dashboard',
    ],
    [
        'repo' => 'CustomFields',
        'default_title' => '[DD] Custom Fields Extension',
        'addon_id' => 'cv6/CustomFields',
        'role_en' => 'Foundation',
        'role_de' => 'Basis & Infrastruktur',
        'desc_en' => 'Extended custom field types, calculations, dependencies, and EntityChooser',
        'desc_de' => 'Erweiterte Zusatzfelder, Formel-Berechnungen, Feld-Abhängigkeiten & EntityChooser',
    ],
    [
        'repo' => 'CharEdit',
        'default_title' => '[DD] Character Database',
        'addon_id' => 'cv6/CharEdit',
        'role_en' => 'Core Module',
        'role_de' => 'Kern-Modul',
        'desc_en' => 'Character sheets, settings, archetypes, abilities, spells, and campaigns',
        'desc_de' => 'Charakterbögen, Settings, Archetypen, Fähigkeiten, Zauber & Kampagnen',
    ],
    [
        'repo' => 'DiceRoller',
        'default_title' => '[DD] Dice Roller',
        'addon_id' => 'cv6/DiceRoller',
        'role_en' => 'Core Module',
        'role_de' => 'Kern-Modul',
        'desc_en' => 'Dice rolling engine, rule pipelines, wiresets, visual dice sets, and character sheet roll triggers',
        'desc_de' => 'Würfel-Engine, Regel-Pipelines, Wiresets, visuelle Würfel-Sets & Charakterbogen-Würfeltrigger',
    ],
    [
        'repo' => 'BattleMaps',
        'default_title' => '[DD] BattleMap Generator',
        'addon_id' => 'cv6/BattleMaps',
        'role_en' => 'Core Module',
        'role_de' => 'Kern-Modul',
        'desc_en' => 'Procedural dungeon & building generator, interactive viewer, and map gallery',
        'desc_de' => 'Prozeduraler Dungeon- & Gebäude-Generator, interaktiver Viewer & Karten-Galerie',
    ],
    [
        'repo' => 'Handout',
        'default_title' => '[DD] Handout Generator',
        'addon_id' => 'cv6/Handout',
        'role_en' => 'Core Module',
        'role_de' => 'Kern-Modul',
        'desc_en' => 'In-game letters, parchments, and shared campaign handouts',
        'desc_de' => 'In-Game-Briefe, Pergamente & geteilte Kampagnen-Handouts',
    ],
    [
        'repo' => 'FontManager',
        'default_title' => '[DD] Font Manager',
        'addon_id' => 'cv6/FontManager',
        'role_en' => 'Recommended Add-on',
        'role_de' => 'Empfohlene Erweiterung',
        'desc_en' => 'Custom web & RPG fonts for handout layouts, character sheets, and dice sets',
        'desc_de' => 'Web- & RPG-Schriftarten für Handout-Layouts, Charakterbögen & Würfel-Sets',
    ],
    [
        'repo' => 'TimeLine',
        'alt_repo' => 'Timeline',
        'default_title' => '[DD] Timeline',
        'addon_id' => 'cv6/Timeline',
        'role_en' => 'Recommended Add-on',
        'role_de' => 'Empfohlene Erweiterung',
        'desc_en' => 'Interactive timelines and chronicles for RPG campaigns and worlds',
        'desc_de' => 'Interaktive Zeitleisten & Chroniken für RPG-Kampagnen und Spielwelten',
    ],
    [
        'repo' => 'Prefix',
        'default_title' => '[DD] Prefix Tools',
        'addon_id' => 'cv6/Prefix',
        'role_en' => 'Recommended Add-on',
        'role_de' => 'Empfohlene Erweiterung',
        'desc_en' => 'Styled badges and prefix management for RPG setting editions',
        'desc_de' => 'Gestylte Badges & Präfix-Verwaltung für RPG-Setting-Editionen',
    ],
    [
        'repo' => 'IconsGame',
        'default_title' => '[DD] Icon Interface: Game-Icons.net',
        'addon_id' => 'cv6/IconsGame',
        'role_en' => 'Recommended Add-on',
        'role_de' => 'Empfohlene Erweiterung',
        'desc_en' => 'Over 4,000 tabletop RPG icons from Game-Icons.net for genres, skills, and dice',
        'desc_de' => 'Über 4.000 Tabletop-RPG-Icons von Game-Icons.net für Genres, Fertigkeiten & Würfel',
    ],
];

$rpgTableEn = "| Tier | Add-on | ID | Version | Role in the RPG Suite |\n";
$rpgTableEn .= "| :--- | :--- | :--- | :--- | :--- |\n";

$rpgTableDe = "| Bereich | Add-on | ID | Version | Funktion innerhalb der RPG Suite |\n";
$rpgTableDe .= "| :--- | :--- | :--- | :--- | :--- |\n";

foreach ($rpgCatalog as $item) {
    $meta = $allAddonMeta[$item['repo']] ?? (isset($item['alt_repo']) ? ($allAddonMeta[$item['alt_repo']] ?? null) : null);
    $title = $item['default_title'];
    $ver = $meta['version'] ?? '1.0.0 Beta 1';
    $rpgTableEn .= "| **{$item['role_en']}** | **{$title}** | `{$item['addon_id']}` | `{$ver}` | {$item['desc_en']} |\n";
    $rpgTableDe .= "| **{$item['role_de']}** | **{$title}** | `{$item['addon_id']}` | `{$ver}` | {$item['desc_de']} |\n";
}

$readmeEn = <<<MD
# Dice & Dragons — XenForo 2.3+ Add-on Suite
*This overview is also available in: [Deutsch](https://github.com/Dice-Dragons/.github/blob/main/profile/README.de.md)*

Welcome to the official GitHub organization profile of **Dice & Dragons**!

This organization hosts and maintains the official XenForo 2.3+ add-on suite developed by [Hoffi](https://forum.dice-dragons.de/mitglieder/hoffi.1/), built and deployed via automated CI/CD pipelines.

---

## 🎲 [DD] RPG Suite — Ecosystem & Modules

**[DD] RPG Suite (`cv6/RPG`)** is the central domain platform for tabletop & pen-and-paper roleplaying games in XenForo 2.3 (genres, publishers, campaign content bridge, and RPG dashboard), connecting all specialized RPG modules across the suite:

{$rpgTableEn}

---

## 📊 Live Suite Dashboard

Overview of all {$count} active add-ons in the suite:

{$tableEn}

---

## ⚙️ Technical Standards

* **Framework**: XenForo 2.3.0+
* **PHP Baseline**: PHP 8.2.0+ (optimized for PHP 8.4)
* **Frontend**: Vanilla JS (ES2020), LESS, FontAwesome 5.x Baseline
* **License**: Proprietary (for holders of a valid XenForo license)

---

## 💬 Community & Support

* 🌐 **Main Forum**: [https://forum.dice-dragons.de](https://forum.dice-dragons.de)
* 📥 **Downloads & Resources**: [https://forum.dice-dragons.de/downloads/](https://forum.dice-dragons.de/downloads/)
* 👤 **Developer Profile**: [Hoffi on Dice & Dragons](https://forum.dice-dragons.de/mitglieder/hoffi.1/) | [Hoffi on XenForo.com](https://xenforo.com/community/members/hoffi.214/)
MD;

$readmeDe = <<<MD
# Dice & Dragons — XenForo 2.3+ Add-on Suite
*Diese Übersicht ist auch verfügbar auf: [English](https://github.com/Dice-Dragons)*

Herzlich willkommen im GitHub-Organisationsprofil von **Dice & Dragons**!

Hier werden die offiziellen XenForo 2.3+ Add-ons von [Hoffi](https://forum.dice-dragons.de/mitglieder/hoffi.1/) verwaltet, gepflegt und über CI/CD-Pipelines bereitgestellt.

---

## 🎲 [DD] RPG Suite — Ökosystem & Module

Die **[DD] RPG Suite (`cv6/RPG`)** bildet die zentrale Plattform für Tabletop- & Pen-&-Paper-Rollenspiele in XenForo 2.3 (Genres, Verlage, Kampagnen-Content-Bridge und RPG-Dashboard) und verbindet alle spezialisierten Rollenspiel-Module der Suite:

{$rpgTableDe}

---

## 📊 Live Suite-Dashboard

Übersicht aller {$count} aktiven Add-ons der Suite:

{$tableDe}

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

if (!is_dir(dirname($readmeFileEn))) {
    mkdir(dirname($readmeFileEn), 0777, true);
}

file_put_contents($readmeFileEn, $readmeEn);
file_put_contents($readmeFileDe, $readmeDe);
echo "✅ Successfully written {$count} add-ons to {$readmeFileEn} and {$readmeFileDe}!\n";

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
