<?php

namespace App\Http\Controllers\CentralAdmin\Media;

use App\Http\Controllers\Controller;
use App\Models\MediaAsset;
use App\Services\Media\MediaUrlGenerator;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class MediaLibraryController extends Controller
{
    public function __invoke(Request $request, MediaUrlGenerator $urls): View
    {
        $this->authorizeMedia($request);

        $query = MediaAsset::query()
            ->with(['variants' => fn ($query) => $query->where('variant_type', 'thumbnail')->where('status', 'ready')])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        if ($request->filled('search')) {
            $search = (string) $request->string('search');
            $query->where(function ($query) use ($search): void {
                $query->where('original_filename', 'like', "%{$search}%")
                    ->orWhere('checksum', 'like', "%{$search}%");
            });
        }

        $assets = $query->paginate(24)->withQueryString();

        return view('central-admin.media.library', [
            'assets' => $assets,
            'urlGenerator' => $urls,
        ]);
    }

    private function authorizeMedia(Request $request): void
    {
        abort_unless($request->user()?->hasCatalogHubPermission('media.manage'), 403);
    }
}
