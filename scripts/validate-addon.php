<?php

declare(strict_types=1);

/**
 * Dice & Dragons - XenForo Add-on Validator
 *
 * Validates:
 * 1. addon.json schema, required fields, and version syntax
 * 2. composer.json syntax and structure (if present)
 * 3. Dependency synchronization between addon.json and composer.json
 * 4. Security & Policy: Strict prohibition of exec(), system(), shell_exec(), etc.
 * 5. FontAwesome standard: Detect forbidden FA6+ icon class names in templates
 *
 * Can be run in GitHub Actions or locally:
 *   php validate-addon.php [path-to-addon-root]
 */

$targetDir = isset($argv[1]) ? rtrim($argv[1], '/\\') : '.';
if (!is_dir($targetDir)) {
    emitError("Directory not found: {$targetDir}");
    exit(1);
}

$isGithubActions = getenv('GITHUB_ACTIONS') === 'true';

$errors = [];
$warnings = [];

echo "=====================================================\n";
echo " Dice & Dragons - Add-on Metadata & Policy Validator\n";
echo " Target: " . realpath($targetDir) . "\n";
echo "=====================================================\n\n";

// --------------------------------------------------------------------------
// 1. addon.json Validation
// --------------------------------------------------------------------------
echo "🔍 Checking addon.json...\n";
$addonJsonFile = $targetDir . DIRECTORY_SEPARATOR . 'addon.json';

if (!file_exists($addonJsonFile)) {
    logError($addonJsonFile, 1, "addon.json is missing in repository root.");
} else {
    $addonJsonRaw = file_get_contents($addonJsonFile);
    $addonJson = json_decode($addonJsonRaw, true);

    if (!is_array($addonJson)) {
        logError($addonJsonFile, 1, "addon.json contains invalid JSON: " . json_last_error_msg());
    } else {
        $requiredKeys = [
            'legacy_addon_id' => 'string',
            'title' => 'string',
            'description' => 'string',
            'version_id' => 'integer',
            'version_string' => 'string',
            'dev' => 'string',
            'require' => 'array',
        ];

        foreach ($requiredKeys as $key => $expectedType) {
            if (!isset($addonJson[$key])) {
                logError($addonJsonFile, 1, "Missing required key '{$key}' in addon.json.");
            } else {
                $actualType = gettype($addonJson[$key]);
                if ($actualType !== $expectedType) {
                    logError($addonJsonFile, 1, "Key '{$key}' in addon.json must be of type {$expectedType}, got {$actualType}.");
                }
            }
        }

        // Check version_id format
        if (isset($addonJson['version_id']) && is_int($addonJson['version_id'])) {
            if ($addonJson['version_id'] <= 0) {
                logError($addonJsonFile, 1, "version_id must be a positive integer.");
            }
        }

        // Check PHP baseline in require
        $requires = $addonJson['require'] ?? [];
        if (!isset($requires['php'])) {
            logWarning($addonJsonFile, 1, "addon.json does not specify an explicit 'php' requirement under 'require' (recommended: [\"8.2.0\", \"PHP 8.2.0+\"]).");
        } else {
            $phpReq = $requires['php'];
            $phpMin = is_array($phpReq) ? ($phpReq[0] ?? '') : $phpReq;
            if (version_compare((string)$phpMin, '8.2.0', '<')) {
                logError($addonJsonFile, 1, "PHP requirement in addon.json must be at least 8.2.0 (got '{$phpMin}').");
            }
        }

        // Check XenForo baseline in require
        if (isset($requires['XF'])) {
            $xfReq = $requires['XF'];
            $xfMin = is_array($xfReq) ? ($xfReq[0] ?? '') : $xfReq;
            // XF 2.3.0 is 2030070 or string '2.3.0' or integer 2030000
            $xfMinInt = (int)$xfMin;
            if ($xfMinInt > 0 && $xfMinInt < 2030000) {
                logWarning($addonJsonFile, 1, "XenForo requirement is below 2.3.0 (version_id: {$xfMinInt}).");
            }
        }

        echo "  ✓ addon.json syntax and required fields valid.\n";
    }
}

// --------------------------------------------------------------------------
// 2. composer.json Validation & Synchronization
// --------------------------------------------------------------------------
echo "\n🔍 Checking composer.json & synchronization...\n";
$composerJsonFile = $targetDir . DIRECTORY_SEPARATOR . 'composer.json';

if (file_exists($composerJsonFile)) {
    $composerJsonRaw = file_get_contents($composerJsonFile);
    $composerJson = json_decode($composerJsonRaw, true);

    if (!is_array($composerJson)) {
        logError($composerJsonFile, 1, "composer.json contains invalid JSON: " . json_last_error_msg());
    } else {
        // Name check
        if (empty($composerJson['name'])) {
            logError($composerJsonFile, 1, "composer.json must have a 'name' field.");
        } elseif (!str_starts_with($composerJson['name'], 'dice-dragons/')) {
            logError($composerJsonFile, 1, "composer.json 'name' must use vendor 'dice-dragons/' (got '{$composerJson['name']}').");
        }

        // License check
        if (($composerJson['license'] ?? '') !== 'proprietary') {
            logWarning($composerJsonFile, 1, "composer.json license should be 'proprietary' (got '" . ($composerJson['license'] ?? '') . "').");
        }

        // PHP requirement check
        $composerRequires = $composerJson['require'] ?? [];
        if (!isset($composerRequires['php'])) {
            logError($composerJsonFile, 1, "composer.json must specify 'php' under 'require'.");
        }

        // Synchronization with addon.json
        if (isset($addonJson) && is_array($addonJson)) {
            $slugMap = [
                'MMF' => 'mmf',
                'RPG' => 'rpg',
            ];

            $addonRequires = $addonJson['require'] ?? [];
            $expectedPackages = [];

            // Check every cv6 dependency in addon.json is present in composer.json
            foreach ($addonRequires as $reqKey => $spec) {
                if (str_starts_with($reqKey, 'cv6/')) {
                    $depFolder = substr($reqKey, 4);
                    $expectedPkg = null;

                    // If running in multi-addon workspace, read neighbor composer.json directly
                    $neighborComposer = dirname((string)realpath($targetDir)) . DIRECTORY_SEPARATOR . $depFolder . DIRECTORY_SEPARATOR . 'composer.json';
                    if (file_exists($neighborComposer)) {
                        $neighborData = json_decode((string)file_get_contents($neighborComposer), true);
                        if (is_array($neighborData) && !empty($neighborData['name']) && is_string($neighborData['name'])) {
                            $expectedPkg = $neighborData['name'];
                        }
                    }

                    if ($expectedPkg === null) {
                        $depSlug = $slugMap[$depFolder]
                            ?? strtolower((string)preg_replace('/(?<!^)(?:[A-Z][a-z]|(?<=[a-z])[A-Z])/', '-$0', $depFolder));
                        $expectedPkg = "dice-dragons/{$depSlug}";
                    }

                    $expectedPackages[$expectedPkg] = $reqKey;

                    if (!isset($composerRequires[$expectedPkg])) {
                        logError($composerJsonFile, 1, "composer.json is out of sync with addon.json: missing dependency '{$expectedPkg}' (required by '{$reqKey}' in addon.json).");
                    }
                }
            }

            // Check if composer.json has extra dice-dragons dependencies not in addon.json
            foreach ($composerRequires as $pkg => $constraint) {
                if (str_starts_with($pkg, 'dice-dragons/')) {
                    if (!isset($expectedPackages[$pkg])) {
                        logError($composerJsonFile, 1, "composer.json contains orphaned dependency '{$pkg}' which is not defined in addon.json.");
                    }
                }
            }
        }

        echo "  ✓ composer.json validated and synchronized with addon.json.\n";
    }
} else {
    logWarning($targetDir, 1, "composer.json is not present in repository root.");
}

// --------------------------------------------------------------------------
// 3. Security & Policy: Forbidden Shell Execution Functions
// --------------------------------------------------------------------------
echo "\n🔍 Scanning PHP files for forbidden shell execution functions...\n";
$phpFiles = findFiles($targetDir, '/\.php$/', ['vendor', '_build', '.git']);
$forbiddenFuncs = ['exec', 'shell_exec', 'system', 'passthru', 'popen', 'proc_open'];

$scannedCount = 0;
foreach ($phpFiles as $file) {
    $scannedCount++;
    $violations = scanPhpFileForForbiddenFunctions($file, $forbiddenFuncs);
    foreach ($violations as $v) {
        logError($file, $v['line'], $v['message']);
    }
}
echo "  ✓ Scanned {$scannedCount} PHP files for shell functions.\n";

// --------------------------------------------------------------------------
// 4. FontAwesome 5.x Standard Check in Templates
// --------------------------------------------------------------------------
echo "\n🔍 Scanning templates for FontAwesome 6+ icon names...\n";
$templateFiles = findFiles($targetDir . DIRECTORY_SEPARATOR . '_output' . DIRECTORY_SEPARATOR . 'templates', '/\.(html|less)$/', []);

// Map of common FA6 names to FA5 equivalents
$fa6Map = [
    'fa-gear' => 'fa-cog',
    'fa-gears' => 'fa-cogs',
    'fa-circle-question' => 'fa-question-circle',
    'fa-circle-info' => 'fa-info-circle',
    'fa-circle-exclamation' => 'fa-exclamation-circle',
    'fa-triangle-exclamation' => 'fa-exclamation-triangle',
    'fa-shield-halved' => 'fa-shield-alt',
    'fa-trash-can' => 'fa-trash-alt',
    'fa-magnifying-glass' => 'fa-search',
    'fa-pen-to-square' => 'fa-edit',
    'fa-floppy-disk' => 'fa-save',
    'fa-arrow-right-from-bracket' => 'fa-sign-out-alt',
    'fa-arrow-right-to-bracket' => 'fa-sign-in-alt',
    'fa-circle-xmark' => 'fa-times-circle',
    'fa-circle-check' => 'fa-check-circle',
];

$faScanned = 0;
foreach ($templateFiles as $file) {
    $faScanned++;
    $content = file_get_contents($file);
    $lines = explode("\n", $content);

    foreach ($lines as $lineNum => $lineText) {
        foreach ($fa6Map as $fa6 => $fa5) {
            if (str_contains($lineText, $fa6)) {
                logError(
                    $file,
                    $lineNum + 1,
                    "Strict FA 5.x policy violation: FontAwesome 6 icon '{$fa6}' used. Replace with native FA5 icon '{$fa5}'."
                );
            }
        }
    }
}
echo "  ✓ Scanned {$faScanned} templates for icon policy compliance.\n";

// --------------------------------------------------------------------------
// Summary & Exit
// --------------------------------------------------------------------------
echo "\n=====================================================\n";
echo " Summary:\n";
echo " Errors:   " . count($errors) . "\n";
echo " Warnings: " . count($warnings) . "\n";
echo "=====================================================\n";

if (count($errors) > 0) {
    echo "\n❌ Validation FAILED with " . count($errors) . " error(s).\n";
    exit(1);
}

echo "\n✅ All checks passed successfully!\n";
exit(0);

// ==========================================================================
// Helper Functions
// ==========================================================================

function logError(string $file, int $line, string $message): void
{
    global $errors, $isGithubActions;
    $errors[] = ['file' => $file, 'line' => $line, 'message' => $message];

    if ($isGithubActions) {
        $cleanPath = str_replace(DIRECTORY_SEPARATOR, '/', $file);
        echo "::error file={$cleanPath},line={$line}::{$message}\n";
    }
    echo "  [ERROR] {$file}:{$line} - {$message}\n";
}

function logWarning(string $file, int $line, string $message): void
{
    global $warnings, $isGithubActions;
    $warnings[] = ['file' => $file, 'line' => $line, 'message' => $message];

    if ($isGithubActions) {
        $cleanPath = str_replace(DIRECTORY_SEPARATOR, '/', $file);
        echo "::warning file={$cleanPath},line={$line}::{$message}\n";
    }
    echo "  [WARN]  {$file}:{$line} - {$message}\n";
}

function emitError(string $message): void
{
    global $isGithubActions;
    if ($isGithubActions) {
        echo "::error::{$message}\n";
    }
    echo "[ERROR] {$message}\n";
}

function findFiles(string $dir, string $pattern, array $skipDirs = []): array
{
    if (!is_dir($dir)) {
        return [];
    }

    $results = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        if ($item->isDir()) {
            $dirName = $item->getFilename();
            if (in_array($dirName, $skipDirs, true)) {
                // Skip descending into excluded dirs
                continue;
            }
        } elseif ($item->isFile()) {
            $path = $item->getPathname();
            // Check if path contains skipped dirs
            foreach ($skipDirs as $skip) {
                if (str_contains($path, DIRECTORY_SEPARATOR . $skip . DIRECTORY_SEPARATOR)) {
                    continue 2;
                }
            }

            if (preg_match($pattern, $item->getFilename())) {
                $results[] = $path;
            }
        }
    }

    return $results;
}

function scanPhpFileForForbiddenFunctions(string $filePath, array $forbidden): array
{
    $code = file_get_contents($filePath);
    $tokens = @token_get_all($code);
    $count = count($tokens);
    $violations = [];

    for ($i = 0; $i < $count; $i++) {
        $token = $tokens[$i];

        // Detect backtick operator
        if ($token === '`' || (is_array($token) && defined('T_BACKTICK') && $token[0] === constant('T_BACKTICK'))) {
            $line = is_array($token) ? $token[2] : 1;
            $violations[] = [
                'line' => $line,
                'message' => 'Strict policy violation: Backtick execution operator (`) is strictly forbidden.',
            ];
            continue;
        }

        if (is_array($token) && $token[0] === T_STRING) {
            $name = strtolower($token[1]);
            if (in_array($name, $forbidden, true)) {
                // Find previous non-whitespace token
                $prev = null;
                for ($j = $i - 1; $j >= 0; $j--) {
                    if (is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
                        continue;
                    }
                    $prev = $tokens[$j];
                    break;
                }

                // If preceded by -> or ?-> or :: or function, it is NOT a standalone function call
                if ($prev !== null && is_array($prev)) {
                    if ($prev[0] === T_OBJECT_OPERATOR // ->
                        || (defined('T_NULLSAFE_OBJECT_OPERATOR') && $prev[0] === T_NULLSAFE_OBJECT_OPERATOR) // ?->
                        || $prev[0] === T_PAAMAYIM_NEKUDOTAYIM // ::
                        || $prev[0] === T_FUNCTION // function declaration
                    ) {
                        continue;
                    }
                }

                // Find next non-whitespace token - must be '(' to be a function call
                $next = null;
                for ($j = $i + 1; $j < $count; $j++) {
                    if (is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
                        continue;
                    }
                    $next = $tokens[$j];
                    break;
                }

                if ($next === '(') {
                    $line = $token[2];
                    $violations[] = [
                        'line' => $line,
                        'message' => "Strict policy violation: Forbidden shell execution function '{$token[1]}()' detected. All OS execution functions in PHP are 100% strictly forbidden.",
                    ];
                }
            }
        }
    }

    return $violations;
}
