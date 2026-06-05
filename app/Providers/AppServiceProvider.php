<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Statamic\Facades\CP\Nav;
use Statamic\Statamic;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

        $cpRoute = config('statamic.cp.route', 'cp');

        Nav::extend(function ($nav) use ($cpRoute) {
            $nav->content('SSG Exporter')
                ->icon('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 16V4m0 12l-4-4m4 4l4-4M4 20h16"/></svg>')
                ->url("/$cpRoute/ssg-exporter");
        });

        Statamic::externalScript('/js/cp.js?v=' . @filemtime(public_path('js/cp.js')));

        // API route to check if a Tile belongs to a Story with Parallax enabled
        \Illuminate\Support\Facades\Route::get('/_scrollytelling/api/story-parallax', function (\Illuminate\Http\Request $request) {
            $tileId = $request->query('tile');
            $storySlug = $request->query('story');

            if ($storySlug) {
                $story = \Statamic\Facades\Entry::findBySlug($storySlug, 'stories');
                if ($story && $story->get('parallax') === true) {
                    return response()->json(['parallax' => true]);
                }
            }

            if ($tileId) {
                $stories = \Statamic\Facades\Entry::whereCollection('stories');
                foreach ($stories as $story) {
                    $tiles = $story->get('add_tiles');
                    $tileIds = [];
                    if ($tiles instanceof \Statamic\Entries\EntryCollection) {
                        $tileIds = $tiles->pluck('id')->all();
                    } elseif (is_array($tiles)) {
                        $tileIds = array_map(function($t) {
                            return is_object($t) ? $t->id : (string)$t;
                        }, $tiles);
                    }
                    if (in_array($tileId, $tileIds)) {
                        if ($story->get('parallax') === true) {
                            return response()->json(['parallax' => true]);
                        }
                    }
                }
            }

            return response()->json(['parallax' => false]);
        })->middleware('web');

        // Add a direct API route for visual editing within Story previews
        \Illuminate\Support\Facades\Route::post('/_scrollytelling/api/save-layer', function (\Illuminate\Http\Request $request) {
            $tileId = $request->input('tile_id');
            $layerIndex = $request->input('layer_index');
            $updates = $request->input('updates');

            $tile = \Statamic\Facades\Entry::find($tileId);
            if ($tile) {
                $layers = $tile->get('add_layers', []);
                if (isset($layers[$layerIndex])) {
                    if (isset($updates['x'])) $layers[$layerIndex]['layer_x'] = $updates['x'];
                    if (isset($updates['y'])) $layers[$layerIndex]['layer_y'] = $updates['y'];
                    if (isset($updates['size'])) $layers[$layerIndex]['layer_size'] = $updates['size'];
                    if (isset($updates['text'])) $layers[$layerIndex]['layer_text'] = $updates['text'];
                    $tile->set('add_layers', $layers);
                    $tile->save();
                }
            }
            return response()->json(['success' => true]);
        })->middleware('web'); // Use web for session/auth if needed, or bypass if safe
    }
}
