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
// 5. XFCP Class Extension Validation
// --------------------------------------------------------------------------
echo "\n🔍 Checking XFCP class extensions...\n";
$extDir = $targetDir . DIRECTORY_SEPARATOR . '_output' . DIRECTORY_SEPARATOR . 'class_extensions';
$extCount = 0;
if (is_dir($extDir)) {
    $extFiles = glob($extDir . DIRECTORY_SEPARATOR . '*.json') ?: [];
    // Known XF 2.3 core overridable methods that do NOT declare a return type in XF core
    $untypedXfCoreMethods = [
        '_preSave', '_postSave', '_preDelete', '_postDelete', 'getStructure',
        'isValid', 'validateSpecialCriteriaValue', 'setupThreadCreate',
        'finalizeThreadCreate', 'fnAvatar', 'fnPrefix', 'fnUserBanners',
    ];

    foreach ($extFiles as $jsonFile) {
        if (basename($jsonFile) === '_metadata.json') {
            continue;
        }
        $extCount++;
        $data = json_decode((string)file_get_contents($jsonFile), true);
        if (!is_array($data) || empty($data['from_class']) || empty($data['to_class'])) {
            logError($jsonFile, 1, "Invalid class_extension JSON definition.");
            continue;
        }

        $fromClass = ltrim((string)$data['from_class'], '\\');
        $toClass = ltrim((string)$data['to_class'], '\\');
        $parts = explode('\\', $toClass);
        if (count($parts) < 3) {
            continue;
        }

        $relPath = implode(DIRECTORY_SEPARATOR, array_slice($parts, 2)) . '.php';
        $phpFile = $targetDir . DIRECTORY_SEPARATOR . $relPath;

        if (!file_exists($phpFile)) {
            logError($jsonFile, 1, "Class extension target PHP file missing: {$relPath} (for {$toClass}).");
            continue;
        }

        $shortName = end($parts);
        $expectedXfcp = 'XFCP_' . $shortName;
        $code = (string)file_get_contents($phpFile);

        if (!preg_match('/class\s+' . preg_quote($shortName, '/') . '\s+extends\s+' . preg_quote($expectedXfcp, '/') . '\b/i', $code)) {
            logError($phpFile, 1, "Class '{$shortName}' must extend '{$expectedXfcp}'.");
        }

        if (str_starts_with($fromClass, 'XF\\')) {
            $extMethods = parsePhpClassMethods($code);
            foreach ($extMethods as $mName => $mInfo) {
                $isActionMethod = str_starts_with($mName, 'action') && str_contains($fromClass, '\\Controller\\');
                if (($isActionMethod || in_array($mName, $untypedXfCoreMethods, true)) && $mInfo['return_type'] !== null) {
                    logError(
                        $phpFile,
                        $mInfo['line'],
                        "XFCP co-install trap: {$toClass}::{$mName}() adds return type ': {$mInfo['return_type']}', but parent {$fromClass}::{$mName}() has NO return type in XenForo Core."
                    );
                }
            }
        }
    }
}
echo "  ✓ Checked {$extCount} class extension(s).\n";

// --------------------------------------------------------------------------
// 6. Setup.php Install / Uninstall Schema Symmetry
// --------------------------------------------------------------------------
echo "\n🔍 Checking Setup.php install/uninstall symmetry...\n";
$setupFile = $targetDir . DIRECTORY_SEPARATOR . 'Setup.php';
if (file_exists($setupFile)) {
    validateSetupSymmetry($setupFile);
    echo "  ✓ Setup.php checked.\n";
} else {
    echo "  ✓ No Setup.php present.\n";
}

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

function parsePhpClassMethods(string $code): array
{
    $tokens = @token_get_all($code);
    $count = count($tokens);
    $methods = [];

    $braceDepth = 0;
    $inClass = false;
    $classBraceDepth = 0;

    for ($i = 0; $i < $count; $i++) {
        $token = $tokens[$i];

        if ($token === '{') {
            $braceDepth++;
            continue;
        }
        if ($token === '}') {
            $braceDepth--;
            if ($inClass && $braceDepth < $classBraceDepth) {
                $inClass = false;
            }
            continue;
        }

        if (is_array($token) && $token[0] === T_CLASS) {
            for ($j = $i + 1; $j < $count; $j++) {
                if ($tokens[$j] === '{') {
                    $braceDepth++;
                    $inClass = true;
                    $classBraceDepth = $braceDepth;
                    $i = $j;
                    break;
                }
            }
            continue;
        }

        if ($inClass && $braceDepth === $classBraceDepth && is_array($token) && $token[0] === T_FUNCTION) {
            $line = $token[2];
            $methodName = null;
            $openParenIdx = null;
            for ($j = $i + 1; $j < $count; $j++) {
                $t = $tokens[$j];
                if (is_array($t) && $t[0] === T_STRING && $methodName === null) {
                    $methodName = $t[1];
                } elseif ($t === '(') {
                    $openParenIdx = $j;
                    break;
                }
            }

            if ($methodName === null || $openParenIdx === null) {
                continue;
            }

            $parenDepth = 1;
            $closeParenIdx = null;
            for ($j = $openParenIdx + 1; $j < $count; $j++) {
                $t = $tokens[$j];
                if ($t === '(') {
                    $parenDepth++;
                } elseif ($t === ')') {
                    $parenDepth--;
                    if ($parenDepth === 0) {
                        $closeParenIdx = $j;
                        break;
                    }
                }
            }

            if ($closeParenIdx === null) {
                continue;
            }

            $returnType = null;
            $colonFound = false;
            $retParts = [];
            for ($j = $closeParenIdx + 1; $j < $count; $j++) {
                $t = $tokens[$j];
                if ($t === '{' || $t === ';') {
                    break;
                }
                if ($t === ':') {
                    $colonFound = true;
                    continue;
                }
                if ($colonFound) {
                    if (is_array($t)) {
                        if (in_array($t[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                            continue;
                        }
                        $retParts[] = $t[1];
                    } else {
                        $retParts[] = $t;
                    }
                }
            }
            if (!empty($retParts)) {
                $returnType = implode('', $retParts);
            }

            $methods[$methodName] = [
                'line' => $line,
                'return_type' => $returnType,
            ];
        }
    }

    return $methods;
}

function validateSetupSymmetry(string $setupFile): void
{
    $code = (string)file_get_contents($setupFile);
    $methodBodies = extractPhpMethodBodies($code);

    $installCode = '';
    $uninstallCode = '';
    foreach ($methodBodies as $mName => $body) {
        if (preg_match('/^installStep\d+$/i', $mName)) {
            $installCode .= "\n" . $body;
        } elseif (preg_match('/^uninstallStep\d+$/i', $mName)) {
            $uninstallCode .= "\n" . $body;
        }
    }

    $callsCreateTables = (bool)preg_match('/\$this->createTables\s*\(/', $installCode);
    $callsDropTables = (bool)preg_match('/\$this->dropTables\s*\(/', $uninstallCode);

    if ($callsCreateTables && !$callsDropTables) {
        logWarning($setupFile, 1, "Setup.php calls \$this->createTables() in installStep*, but never calls \$this->dropTables() in uninstallStep*.");
    }

    $getTablesBody = $methodBodies['getTables'] ?? '';
    $traitTables = [];
    if (preg_match_all('/\$tables\[\s*[\'"]([a-zA-Z0-9_]+)[\'"]\s*\]/', $getTablesBody, $m)) {
        $traitTables = $m[1];
    }

    $createdTables = [];
    if (preg_match_all('/createTable\s*\(\s*[\'"]([a-zA-Z0-9_]+)[\'"]/', $installCode, $m)) {
        $createdTables = array_unique($m[1]);
    }

    $droppedTables = [];
    if (preg_match_all('/dropTable\s*\(\s*[\'"]([a-zA-Z0-9_]+)[\'"]/', $uninstallCode, $m)) {
        $droppedTables = array_unique($m[1]);
    }
    if ($callsDropTables) {
        $droppedTables = array_unique(array_merge($droppedTables, $traitTables));
    }

    foreach ($createdTables as $tbl) {
        if (!in_array($tbl, $droppedTables, true)) {
            logWarning($setupFile, 1, "Setup.php creates table '{$tbl}' in installStep*, but does not drop it in uninstallStep*.");
        }
    }

    $ownTables = array_unique(array_merge($traitTables, $createdTables));
    $alteredForeignTables = [];

    if (preg_match_all('/alterTable\s*\(\s*[\'"]([a-zA-Z0-9_]+)[\'"]/', $installCode, $m)) {
        foreach (array_unique($m[1]) as $tbl) {
            if (!in_array($tbl, $ownTables, true)) {
                $alteredForeignTables[] = $tbl;
            }
        }
    }
    if (preg_match_all('/addColumns?IfMissing\s*\(\s*[\'"]([a-zA-Z0-9_]+)[\'"]/', $installCode, $m)) {
        foreach (array_unique($m[1]) as $tbl) {
            if (!in_array($tbl, $ownTables, true)) {
                $alteredForeignTables[] = $tbl;
            }
        }
    }

    $alteredForeignTables = array_unique($alteredForeignTables);

    $uninstalledForeignTables = [];
    if (preg_match_all('/(?:alterTable|dropColumnIfExists|dropColumns?IfExists)\s*\(\s*[\'"]([a-zA-Z0-9_]+)[\'"]/', $uninstallCode, $m)) {
        $uninstalledForeignTables = array_unique($m[1]);
    }

    foreach ($alteredForeignTables as $tbl) {
        if (!in_array($tbl, $uninstalledForeignTables, true)) {
            logWarning($setupFile, 1, "Setup.php alters table '{$tbl}' in installStep*, but does not revert/drop columns on '{$tbl}' in uninstallStep*.");
        }
    }
}

function extractPhpMethodBodies(string $code): array
{
    $tokens = @token_get_all($code);
    $count = count($tokens);
    $bodies = [];

    for ($i = 0; $i < $count; $i++) {
        $token = $tokens[$i];
        if (is_array($token) && $token[0] === T_FUNCTION) {
            $methodName = null;
            $openBraceIdx = null;
            for ($j = $i + 1; $j < $count; $j++) {
                $t = $tokens[$j];
                if (is_array($t) && $t[0] === T_STRING && $methodName === null) {
                    $methodName = $t[1];
                } elseif ($t === '{') {
                    $openBraceIdx = $j;
                    break;
                } elseif ($t === ';') {
                    break;
                }
            }

            if ($methodName === null || $openBraceIdx === null) {
                continue;
            }

            $depth = 1;
            $bodyParts = [];
            for ($j = $openBraceIdx + 1; $j < $count; $j++) {
                $t = $tokens[$j];
                if ($t === '{') {
                    $depth++;
                } elseif ($t === '}') {
                    $depth--;
                    if ($depth === 0) {
                        $i = $j;
                        break;
                    }
                }
                $bodyParts[] = is_array($t) ? $t[1] : $t;
            }

            $bodies[$methodName] = implode('', $bodyParts);
        }
    }

    return $bodies;
}
