<?php

namespace App\Http\Controllers\CP;

use Statamic\Http\Controllers\CP\CpController;
use Illuminate\Http\Request;
use Statamic\Facades\Entry;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;
use ZipArchive;

class SSGUtilityController extends CpController
{
    /**
     * Display the exporter utility page.
     */
    public function index()
    {
        $stories = Entry::query()
            ->where('collection', 'stories')
            ->get()
            ->map(function ($entry) {
                return [
                    'id' => $entry->id(),
                    'title' => $entry->get('title'),
                    'url' => $entry->url(),
                ];
            });

        $zips = [];
        $storagePath = storage_path('app/public/exports');
        if (File::exists($storagePath)) {
            $files = File::files($storagePath);
            foreach ($files as $file) {
                if ($file->getExtension() === 'zip') {
                    $zips[] = [
                        'name' => $file->getFilename(),
                        'size' => number_format($file->getSize() / 1024 / 1024, 2) . ' MB',
                        'date' => date('Y-m-d H:i:s', $file->getMTime()),
                        'download_url' => route('statamic.cp.ssg-exporter.download', ['file' => $file->getFilename()]),
                        'delete_url' => route('statamic.cp.ssg-exporter.delete', ['file' => $file->getFilename()]),
                    ];
                }
            }
        }

        // Sort by date descending
        usort($zips, function ($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        return view('ssg-exporter', [
            'stories' => $stories,
            'zips' => $zips,
            'title' => 'SSG Export Utility',
            'export_url' => route('statamic.cp.ssg-exporter.export'),
        ]);
    }

    /**
     * Trigger the export process.
     */
    public function export(Request $request)
    {
        $storyIds = $request->input('stories', []);
        
        if (empty($storyIds)) {
            return back()->with('error', 'Please select at least one story.');
        }

        $selectedStories = Entry::query()
            ->whereIn('id', $storyIds)
            // Ensure we maintain the order selected if possible, 
            // but query doesn't guarantee it. Let's reorder based on input.
            ->get()
            ->sortBy(function($model) use ($storyIds) {
                return array_search($model->id(), $storyIds);
            })
            ->values();

        $firstStory = $selectedStories->first();
        $urls = $selectedStories->map(fn($s) => $s->url())->toArray();

        // 1. Configure SSG URLs
        // We include '/' to ensure index is generated, but we'll override it later
        Config::set('statamic.ssg.urls', array_unique(array_merge(['/'], $urls)));

        // 2. Prepare/Clean static directory
        $staticPath = base_path('static');
        if (File::exists($staticPath)) {
            File::deleteDirectory($staticPath);
        }
        File::makeDirectory($staticPath, 0755, true);

        try {
            // 3. Run SSG
            if (config('statamic.ssg.enforce_trailing_slashes')) {
                \Statamic\Facades\URL::enforceTrailingSlashes();
            }
            app(\Statamic\StaticSite\Generator::class)->generate('*');

            // 4. Post-processing & Root Override
            $this->postProcess($staticPath, $firstStory);

            // 5. Zip the directory
            $slug = ltrim($firstStory->url(), '/');
            $slug = trim(str_replace('/', '-', $slug), '-');
            if (empty($slug)) {
                $slug = 'export';
            }
            $zipName = $slug . '_' . date('Y-m-d_H-i-s') . '.zip';
            $storageDir = storage_path('app/public/exports');
            if (!File::exists($storageDir)) {
                File::makeDirectory($storageDir, 0755, true);
            }
            $zipPath = $storageDir . '/' . $zipName;

            $this->zipDirectory($staticPath, $zipPath);

            return back()
                ->with('success', "Site exported successfully as: $zipName")
                ->with('success_download', route('statamic.cp.ssg-exporter.download', ['file' => $zipName]));

        } catch (\Exception $e) {
            \Log::error($e);
            return back()->with('error', 'Export failed: ' . $e->getMessage());
        }
    }

    /**
     * Ported logic from fix_static.py and added Root Override.
     */
    protected function postProcess($staticPath, $firstStory)
    {
        $publicPath = public_path();
        
        // 1. Copy missing assets (Assets, Build, Favicon)
        $assetsSrc = $publicPath . '/assets';
        if (File::exists($assetsSrc)) {
            File::copyDirectory($assetsSrc, $staticPath . '/assets');
        }
        
        $buildSrc = $publicPath . '/build';
        if (File::exists($buildSrc)) {
            File::copyDirectory($buildSrc, $staticPath . '/build');
        }

        $faviconSrc = $publicPath . '/favicon.ico';
        if (File::exists($faviconSrc)) {
            File::copy($faviconSrc, $staticPath . '/favicon.ico');
        }

        // 2. Handle Root Override: The first selected story becomes index.html
        $firstStoryUrl = ltrim($firstStory->url(), '/');
        
        // Find the generated file for the first story
        $firstStoryFile = $staticPath . '/' . $firstStoryUrl . '/index.html';
        if (!File::exists($firstStoryFile)) {
            $firstStoryFile = $staticPath . '/' . $firstStoryUrl . '.html';
        }

        if (File::exists($firstStoryFile)) {
            File::copy($firstStoryFile, $staticPath . '/index.html');
        }

        // 3. Prepare manifest injection tags
        $manifestPath = $publicPath . '/build/manifest.json';
        $injectionCode = "";
        if (File::exists($manifestPath)) {
            $manifest = json_decode(File::get($manifestPath), true);
            $tags = [];
            foreach ($manifest as $key => $value) {
                if (isset($value['isEntry']) && $value['isEntry']) {
                    $filePath = "build/" . $value['file']; // Relative form
                    if (str_ends_with($filePath, '.css')) {
                        $tags[] = '<link rel="stylesheet" href="{PREFIX}' . $filePath . '">';
                    } elseif (str_ends_with($filePath, '.js')) {
                        $tags[] = '<script type="module" src="{PREFIX}' . $filePath . '"></script>';
                    }
                }
            }
            $injectionCode = "\n\t" . implode("\n\t", $tags);
        }

        // 4. Process all HTML files
        $files = File::allFiles($staticPath);
        foreach ($files as $file) {
            if ($file->getExtension() === 'html') {
                $content = File::get($file->getPathname());

                // Calculate relative prefix
                $relativePath = substr($file->getPathname(), strlen($staticPath) + 1);
                $depth = substr_count($relativePath, '/');
                $prefix = $depth === 0 ? '' : rtrim(str_repeat('../', $depth), '/');
                $prefixWithSlash = $prefix ? $prefix . '/' : '';

                // Remove Vite dev server scripts if they slipped in
                $content = preg_replace('/<script type="module" src="http(?:s)?:\/\/(?:\[::1\]|localhost|127\.0\.0\.1):5173[^>]+><\/script>/i', '', $content);
                $content = preg_replace('/<link rel="stylesheet" href="http(?:s)?:\/\/(?:\[::1\]|localhost|127\.0\.0\.1):5173[^>]*"\s*\/?>/i', '', $content);

                // Strip the entire {{ vite }} helper output block (absolute-domain preload/stylesheet/script tags)
                // These are replaced by injecting the manifest tags below, so we remove them to avoid duplicates.
                $appUrl = rtrim(config('app.url'), '/');
                if ($appUrl) {
                    $cleanAppUrl = preg_replace('/^https?:\/\//i', '', $appUrl);
                    $escapedAppUrl = preg_quote($cleanAppUrl, '/');
                    // Remove <link rel="preload"> / <link rel="modulepreload"> / <link rel="stylesheet"> / <script> pointing to our domain
                    $content = preg_replace('/<link[^>]+href="https?:\/\/' . $escapedAppUrl . '[^"]*"[^>]*\/?>\s*/i', '', $content);
                    $content = preg_replace('/<script[^>]+src="https?:\/\/' . $escapedAppUrl . '[^"]*"[^>]*><\/script>\s*/i', '', $content);
                }

                // Inject manifest tags
                if ($injectionCode && str_contains($content, '</head>')) {
                    $injected = str_replace('{PREFIX}', $prefixWithSlash, $injectionCode);
                    $content = str_replace('</head>', $injected . "\n</head>", $content);
                }

                // Inline Lottie animations as Base64 data URIs to bypass strict file:// CORS constraints
                $content = preg_replace_callback('/src="([^"]*\.(json|lottie))"/i', function($matches) use ($publicPath) {
                    $pathParam = $matches[1];
                    if (str_starts_with($pathParam, '/')) {
                        $filePath = rtrim($publicPath, '/') . '/' . ltrim($pathParam, '/');
                        if (File::exists($filePath)) {
                            $mime = str_ends_with($filePath, '.lottie') ? 'application/zip' : 'application/json';
                            $base64 = base64_encode(File::get($filePath));
                            return 'src="data:' . $mime . ';base64,' . $base64 . '"';
                        }
                    }
                    return $matches[0];
                }, $content);

                // Rewrite absolute local URLs (/something) to be cleanly relative
                $content = preg_replace_callback('/(href|src)="\/([^"]*)"/i', function($matches) use ($prefixWithSlash) {
                    $attr = $matches[1];
                    $pathParam = $matches[2];
                    
                    // Skip protocol-relative links (e.g. //domain.com)
                    if (str_starts_with($pathParam, '/')) {
                        return $matches[0];
                    }

                    // If it has an extension (like .css, .js, .lottie, .png), just prefix it
                    if (preg_match('/\.[a-zA-Z0-9]+(?:\?.*)?$/', $pathParam)) {
                        return $attr . '="' . $prefixWithSlash . $pathParam . '"';
                    }
                    
                    // Otherwise it is a structural route link. Append index.html for direct filesystem hosting.
                    if (empty($pathParam)) {
                        return $attr . '="' . $prefixWithSlash . 'index.html"';
                    }
                    
                    return $attr . '="' . $prefixWithSlash . $pathParam . '/index.html"';
                }, $content);

                // Rewrite any remaining absolute domain URLs (href/src) to be relative
                // (catches anything not already stripped above, e.g. in inline styles or data attrs)
                if (!empty($appUrl)) {
                    $content = preg_replace_callback('/(href|src)="https?:\/\/' . $escapedAppUrl . '\/([^"]*)"/i', function($matches) use ($prefixWithSlash) {
                        $attr = $matches[1];
                        $pathParam = $matches[2];
                        
                        if (preg_match('/\.[a-zA-Z0-9]+(?:\?.*)?$/', $pathParam)) {
                            return $attr . '="' . $prefixWithSlash . $pathParam . '"';
                        }
                        
                        if (empty($pathParam)) {
                            return $attr . '="' . $prefixWithSlash . 'index.html"';
                        }
                        
                        return $attr . '="' . $prefixWithSlash . $pathParam . '/index.html"';
                    }, $content);
                }

                File::put($file->getPathname(), $content);
            }
        }
    }

    /**
     * Download a ZIP file.
     */
    public function download($file)
    {
        $path = storage_path('app/public/exports/' . $file);
        if (File::exists($path)) {
            return response()->download($path);
        }
        return back()->with('error', 'File not found.');
    }

    /**
     * Delete a ZIP file.
     */
    public function delete($file)
    {
        $path = storage_path('app/public/exports/' . $file);
        if (File::exists($path)) {
            File::delete($path);
            return back()->with('success', 'File deleted.');
        }
        return back()->with('error', 'File not found.');
    }

    /**
     * Zip a directory recursively.
     */
    protected function zipDirectory($directory, $zipFile)
    {
        $zip = new ZipArchive();
        if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \Exception("Could not create ZIP file at $zipFile");
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($directory) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }

        $zip->close();
    }
}
