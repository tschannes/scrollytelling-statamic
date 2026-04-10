@extends('statamic::layout')
@section('title', $title)

@section('content')
    <div class="flex flex-col h-full overflow-auto">
        <div class="p-6 overflow-auto grow">

    <header class="mb-6">
        <div class="flex items-center">
            <h1 class="flex-1">{{ $title }}</h1>
        </div>
    </header>

    @if (session('success'))
        <div class="p-4 mb-6 text-white bg-green-600 rounded shadow-sm">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="p-4 mb-6 text-white bg-red-600 rounded shadow-sm">
            {{ session('error') }}
        </div>
    @endif

    <div class="card p-0 mb-6">
        <form action="{{ $export_url }}" method="POST" onsubmit="document.getElementById('generate-btn').disabled = true; document.getElementById('generate-btn').innerText = 'Generating Bundle (This may take a while)...';">
            @csrf
            <div class="p-4 border-b dark:border-dark-900">
                <h2 class="text-lg font-bold">Select Stories to Export</h2>
                <p class="text-grey-70 dark:text-dark-175 text-sm mt-1">The first selected story will become the homepage
                    (index.html).</p>
            </div>

            <div class="p-0">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="w-4"></th>
                            <th>Title</th>
                            <th>Path</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($stories as $story)
                            <tr>
                                <td class="w-4">
                                    <input type="checkbox" name="stories[]" value="{{ $story['id'] }}"
                                        id="story-{{ $story['id'] }}">
                                </td>
                                <td>
                                    <label for="story-{{ $story['id'] }}"
                                        class="cursor-pointer font-medium">{{ $story['title'] }}</label>
                                </td>
                                <td class="text-grey-70 dark:text-dark-175">{{ $story['url'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t dark:border-dark-900 flex items-center justify-between">
                <p class="text-sm text-grey-70 dark:text-dark-175">Selected stories will be generated and zipped with all
                    assets.</p>
                <button class="bg-blue-500 hover:bg-blue-z00 text-white font-bold py-2 px-4 rounded" type="submit" id="generate-btn" class="btn-primary">Generate &amp; Bundle ZIP</button>
            </div>
        </form>
    </div>

    <div class="card p-0">
        <div class="p-4 border-b dark:border-dark-900">
            <h2 class="text-lg font-bold">Available ZIP Bundles</h2>
        </div>

        @if (count($zips) > 0)
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Filename</th>
                        <th>Size</th>
                        <th>Created</th>
                        <th class="w-32"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($zips as $zip)
                        <tr>
                            <td>
                                <a href="{{ $zip['download_url'] }}" class="text-blue hover:text-blue-800">{{ $zip['name'] }}</a>
                            </td>
                            <td class="text-grey-70 dark:text-dark-175 text-sm">{{ $zip['size'] }}</td>
                            <td class="text-grey-70 dark:text-dark-175 text-sm">{{ $zip['date'] }}</td>
                            <td class="text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    <a href="{{ $zip['download_url'] }}" class="btn btn-sm">Download</a>
                                    <form action="{{ $zip['delete_url'] }}" method="POST" class="inline"
                                        onsubmit="return confirm('Delete this bundle?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm text-red-500 hover:text-red-700">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="p-8 text-center text-grey-70 dark:text-dark-175">
                <p>No ZIP bundles yet. Select some stories above and generate one!</p>
            </div>
        @endif
    </div>

    </div>
    </div>

    @if (session('success_download'))
        <script>
            setTimeout(function() {
                window.location.href = "{!! session('success_download') !!}";
            }, 500);
        </script>
    @endif
@endsection