#!/bin/php
<?php

if (php_sapi_name() !== 'cli') {
    exit(0);
}

$buildCommands = [];

if (file_exists('composer.json')) {
    if (is_array($argv) && !in_array('--no-composer', $argv, true)) {
        $buildCommands[] = 'composer install --prefer-dist --no-progress --no-dev';
    }

    $buildCommands[] = 'composer dump-autoload';
}

if (file_exists('package.json') && file_exists('package-lock.json')) {
    $buildCommands[] = 'npm ci --no-progress --no-audit';
} elseif (file_exists('package.json')) {
    $buildCommands[] = 'npm install --no-progress --no-audit';
}

if (file_exists('package.json')) {
    $buildCommands[] = 'npx --yes browserslist@latest --update-db';
    $buildCommands[] = 'npm run build';
}

$removables = [
    '.git',
    '.gitignore',
    '.github',
    '.gitattributes',
    'build.php',
    '.npmrc',
    'composer.json',
    'composer.lock',
    'package-lock.json',
    'package.json',
    'README.md',
    'node_modules',
    'source/sass',
    'source/js'
];

$dirName = basename(dirname(__FILE__));
foreach ($buildCommands as $buildCommand) {
    print "---- Running build command '$buildCommand' for $dirName. ----\n";
    $timeStart = microtime(true);
    $exitCode = executeCommand($buildCommand);
    $buildTime = round(microtime(true) - $timeStart);
    print "---- Done build command '$buildCommand' for $dirName. Build time: $buildTime seconds. ----\n";

    if ($exitCode > 0) {
        exit($exitCode);
    }
}

if (is_array($argv) && in_array('--cleanup', $argv, true)) {
    foreach ($removables as $removable) {
        if (file_exists($removable)) {
            print "Removing $removable from $dirName\n";
            shell_exec("rm -rf $removable");
        }
    }
}

function executeCommand(string $command): int
{
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $fullCommand = "cmd /v:on /c \"$command 2>&1 & echo Exit status : !ErrorLevel!\"";
    } else {
        $fullCommand = "$command 2>&1 ; echo Exit status : $?";
    }

    $proc = popen($fullCommand, 'r');
    if ($proc === false) {
        return 1;
    }

    $completeOutput = '';
    while (!feof($proc)) {
        $liveOutput = fread($proc, 4096);
        if ($liveOutput === false) {
            break;
        }

        $completeOutput .= $liveOutput;
        print $liveOutput;
        @flush();
    }

    pclose($proc);

    preg_match('/[0-9]+$/', $completeOutput, $matches);

    return intval($matches[0] ?? 1);
}
