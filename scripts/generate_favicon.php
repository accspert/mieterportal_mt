<?php

// Generates multi-resolution PNGs and an ICO from public/branding/favicon.svg
// Usage: php scripts/generate_favicon.php

$baseDir = __DIR__.'/../public/branding';
$svgPath = $baseDir.'/favicon.svg';
$outIco = $baseDir.'/favicon.ico';
$sizes = [16, 32, 48, 64, 128];

if (! file_exists($svgPath)) {
    fwrite(STDERR, "SVG source not found at {$svgPath}\n");
    exit(1);
}

function use_imagick(array $sizes, string $svgPath, string $baseDir, string $outIco): bool
{
    if (! class_exists('Imagick')) {
        return false;
    }

    try {
        $ico = new Imagick;
        $ico->setFormat('ico');

        foreach ($sizes as $size) {
            $img = new Imagick;
            $svg = file_get_contents($svgPath);
            $img->readImageBlob($svg);
            $img->setImageFormat('png32');
            $img->resizeImage($size, $size, Imagick::FILTER_LANCZOS, 1);
            $pngPath = "{$baseDir}/favicon-{$size}.png";
            $img->writeImage($pngPath);

            // Add to ICO
            $ico->addImage($img);
            $ico->setImageFormat('ico');
        }

        $ico->writeImages($outIco, true);
        echo "Generated PNGs and ICO using Imagick.\n";

        return true;
    } catch (Exception $e) {
        fwrite(STDERR, 'Imagick error: '.$e->getMessage()."\n");

        return false;
    }
}

function use_convert(array $sizes, string $svgPath, string $baseDir, string $outIco): bool
{
    $convert = null;
    if (stripos(PHP_OS, 'WIN') === 0) {
        // On Windows, user must have ImageMagick in PATH as 'magick'
        $convert = shell_exec('where magick 2>nul');
        if ($convert) {
            $command = 'magick';
        } else {
            $convert = shell_exec('where convert 2>nul');
            $command = $convert ? 'convert' : null;
        }
    } else {
        $command = trim(shell_exec('which convert 2>/dev/null')) ?: trim(shell_exec('which magick 2>/dev/null'));
        if ($command && basename($command) === 'magick') {
            $command = 'magick';
        } elseif ($command) {
            $command = 'convert';
        } else {
            $command = null;
        }
    }

    if (! $command) {
        return false;
    }

    $pngs = [];
    foreach ($sizes as $size) {
        $pngPath = "{$baseDir}/favicon-{$size}.png";
        $cmd = escapeshellcmd("{$command} -background none -resize {$size}x{$size} {$svgPath} {$pngPath}");
        // On Windows with magick, the correct invocation is 'magick convert ...' but many installs accept 'magick' alone.
        exec($cmd, $out, $rc);
        if ($rc !== 0) {
            fwrite(STDERR, "convert command failed for size {$size}\n");

            return false;
        }
        $pngs[] = $pngPath;
    }

    // Create ICO from PNGs
    $pngList = implode(' ', array_map('escapeshellarg', $pngs));
    $cmdIco = escapeshellcmd("{$command} {$pngList} {$outIco}");
    exec($cmdIco, $out, $rc);
    if ($rc !== 0) {
        fwrite(STDERR, "ICO creation failed.\n");

        return false;
    }

    echo "Generated PNGs and ICO using ImageMagick convert.\n";

    return true;
}

// Try Imagick first
if (use_imagick($sizes, $svgPath, $baseDir, $outIco)) {
    exit(0);
}

// Fallback to ImageMagick 'convert' or 'magick'
if (use_convert($sizes, $svgPath, $baseDir, $outIco)) {
    exit(0);
}

fwrite(STDERR, "Could not generate favicon: install PHP Imagick extension or ImageMagick (convert/magick) binary.\n");
exit(1);
