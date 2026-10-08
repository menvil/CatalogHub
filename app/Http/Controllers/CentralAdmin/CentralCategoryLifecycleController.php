<?php

declare(strict_types=1);

namespace App\Http\Controllers\CentralAdmin;

use App\Actions\CentralCatalog\ActivateCentralCategoryAction;
use App\Actions\CentralCatalog\ArchiveCentralCategoryAction;
use App\Actions\CentralCatalog\RestoreCentralCategoryAction;
use App\Enums\CentralCategoryStatus;
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
        $action->handle($actor, $category, $request->validated('expected_status') === null ? null : CentralCategoryStatus::from($request->validated('expected_status')));

        return $this->redirectAfterMutation($request, $category, 'Category activated.');
    }

    public function archive(CentralCategoryListRequest $request, CentralCategory $category, ArchiveCentralCategoryAction $action): RedirectResponse
    {
        $actor = $request->user();
        assert($actor instanceof User);
        $action->handle($actor, $category, $request->validated('expected_status') === null ? null : CentralCategoryStatus::from($request->validated('expected_status')));

        return $this->redirectAfterMutation($request, $category, 'Category archived.');
    }

    public function restore(CentralCategoryListRequest $request, CentralCategory $category, RestoreCentralCategoryAction $action): RedirectResponse
    {
        $actor = $request->user();
        assert($actor instanceof User);
        $action->handle($actor, $category, $request->validated('expected_status') === null ? null : CentralCategoryStatus::from($request->validated('expected_status')));

        return $this->redirectAfterMutation($request, $category, 'Category restored to Draft.');
    }

    private function redirectAfterMutation(CentralCategoryListRequest $request, CentralCategory $category, string $message): RedirectResponse
    {
        if ($request->validated('context') === 'detail') {
            return redirect()->route('central.categories.show', ['category' => $category, 'locale' => $request->validated('locale')])->with('success', $message);
        }

        return redirect()->route('central.categories.index', $request->queryParameters())->with('success', $message);
    }
}
