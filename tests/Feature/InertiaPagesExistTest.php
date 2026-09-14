<?php

/*
 * A controller rendering a page component that does not exist fails only in the
 * browser, at runtime, with a thrown error and a blank screen — no test, type
 * check or build catches it. This closes that gap.
 */

it('has a Vue component for every page the server renders', function () {
    $sources = [base_path('app'), base_path('routes')];
    $rendered = [];

    foreach ($sources as $source) {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source));

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            preg_match_all(
                '/Inertia::render\(\s*[\'"]([^\'"]+)[\'"]/',
                (string) file_get_contents($file->getPathname()),
                $matches,
            );

            foreach ($matches[1] as $component) {
                $rendered[$component] = $file->getFilename();
            }
        }
    }

    expect($rendered)->not->toBeEmpty('No Inertia::render calls found — has the scan broken?');

    $missing = [];

    foreach ($rendered as $component => $definedIn) {
        $path = resource_path("js/pages/{$component}.vue");

        if (! file_exists($path)) {
            $missing[] = "{$component} (rendered by {$definedIn})";
        }
    }

    expect($missing)->toBe([], 'Missing page components: '.implode(', ', $missing));
});
