<?php

namespace App\Http\Controllers\CentralAdmin\Media;

use App\Http\Controllers\Controller;
use App\Http\Requests\CentralAdmin\Media\ListMediaAssetsRequest;
use App\Models\MediaAsset;
use App\Queries\Media\MediaLibraryQuery;
use App\Services\Media\MediaUrlGenerator;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class MediaLibraryController extends Controller
{
    public function __invoke(
        ListMediaAssetsRequest $request,
        MediaLibraryQuery $query,
        MediaUrlGenerator $urls,
    ): View {
        Gate::authorize('viewAny', MediaAsset::class);

        return view('central-admin.media.library', [
            'assets' => $query->paginate($request->filters())->withQueryString(),
            'urlGenerator' => $urls,
        ]);
    }
}
