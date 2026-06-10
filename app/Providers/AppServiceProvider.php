<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Statamic\Facades\CP\Nav;
use Statamic\Statamic;
use Statamic\Facades\Entry;
use Statamic\Events\EntrySaved;
use Statamic\Events\UserRegistered;
use Illuminate\Support\Facades\Event;

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

        // Event listener to sync parent story parallax status to linked tiles
        Event::listen(EntrySaved::class, function (EntrySaved $event) {
            $entry = $event->entry;
            if ($entry->collectionHandle() === 'stories') {
                $this->updateAllTilesParallaxStatus();
            }
        });

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

    /**
     * Recalculates and updates the parent_parallax field on all Tiles
     * based on whether they are linked in any parallax-enabled Story.
     */
    protected function updateAllTilesParallaxStatus(): void
    {
        // 1. Gather IDs of all tiles that belong to a story with parallax enabled
        $parallaxTileIds = [];
        $stories = Entry::whereCollection('stories');
        foreach ($stories as $story) {
            if ($story->get('parallax') === true) {
                $tiles = $story->get('add_tiles');
                $tilesList = [];
                if ($tiles instanceof \Statamic\Entries\EntryCollection) {
                    $tilesList = $tiles->pluck('id')->all();
                } elseif (is_array($tiles)) {
                    $tilesList = array_map(function($t) {
                        return is_object($t) ? $t->id : (string)$t;
                    }, $tiles);
                }
                foreach ($tilesList as $tId) {
                    $parallaxTileIds[$tId] = true;
                }
            }
        }

        // 2. Update parent_parallax on all tiles
        $tiles = Entry::whereCollection('tiles');
        foreach ($tiles as $tile) {
            $shouldBeParallax = isset($parallaxTileIds[$tile->id()]);
            if ($tile->get('parent_parallax') !== $shouldBeParallax) {
                $tile->set('parent_parallax', $shouldBeParallax);
                $tile->saveQuietly();
            }
        }
    }
}
