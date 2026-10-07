<?php

declare(strict_types=1);

namespace App\Http\Controllers\CentralAdmin;

use App\Actions\CentralCatalog\ActivateCentralCategoryAction;
use App\Actions\CentralCatalog\ArchiveCentralCategoryAction;
use App\Actions\CentralCatalog\RestoreCentralCategoryAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CentralAdmin\CentralCategoryListRequest;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

final class CentralCategoryLifecycleController extends Controller
{
    public function activate(CentralCategoryListRequest $request, CentralCategory $category, ActivateCentralCategoryAction $action): RedirectResponse
    {
        $actor = $request->user();
        assert($actor instanceof User);
        $action->handle($actor, $category);

        return $this->backToList($request, 'Category activated.');
    }

    public function archive(CentralCategoryListRequest $request, CentralCategory $category, ArchiveCentralCategoryAction $action): RedirectResponse
    {
        $actor = $request->user();
        assert($actor instanceof User);
        $action->handle($actor, $category);

        return $this->backToList($request, 'Category archived.');
    }

    public function restore(CentralCategoryListRequest $request, CentralCategory $category, RestoreCentralCategoryAction $action): RedirectResponse
    {
        $actor = $request->user();
        assert($actor instanceof User);
        $action->handle($actor, $category);

        return $this->backToList($request, 'Category restored to Draft.');
    }

    private function backToList(CentralCategoryListRequest $request, string $message): RedirectResponse
    {
        return redirect()->route('central.categories.index', $request->queryParameters())->with('success', $message);
    }
}
