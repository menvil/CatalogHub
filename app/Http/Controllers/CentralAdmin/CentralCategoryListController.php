<?php

declare(strict_types=1);

namespace App\Http\Controllers\CentralAdmin;

use App\Enums\CategorySchemaStatus;
use App\Enums\CentralCategoryStatus;
use App\Enums\Permission;
use App\Filament\Resources\CentralCategoryResource;
use App\Http\Controllers\Controller;
use App\Http\Requests\CentralAdmin\CentralCategoryListRequest;
use App\Queries\CentralCatalog\CategoryListReadModelQuery;
use App\Services\Categories\CategoryAccess;
use Illuminate\Contracts\View\View;

final class CentralCategoryListController extends Controller
{
    public function __invoke(CentralCategoryListRequest $request, CategoryListReadModelQuery $query, CategoryAccess $access): View
    {
        $filters = $request->filters();
        $list = $query->paginate($filters);
        $canMutate = $access->allows(Permission::CatalogCategoriesManage, true);
        $canSchema = $access->allows(Permission::CatalogSchemaManage);
        $actions = [];
        foreach ($list->categories as $row) {
            $category = $row->category;
            $links = [];
            if ($canMutate) {
                $links[] = ['label' => 'Edit', 'url' => CentralCategoryResource::getUrl('edit', ['record' => $category])];
            }
            if ($canSchema) {
                $links[] = ['label' => 'Schema', 'url' => CentralCategoryResource::getUrl('schema', ['record' => $category])];
            }
            $commands = [];
            if ($canMutate) {
                $commands = match ($category->status) {
                    CentralCategoryStatus::Draft => ['activate', 'archive'],
                    CentralCategoryStatus::Active => ['archive'],
                    CentralCategoryStatus::Archived => ['restore'],
                };
            }
            foreach ($commands as $command) {
                $links[] = ['label' => ucfirst($command), 'url' => route('central.categories.'.$command, $category), 'destructive' => $command === 'archive', 'confirmationId' => $command.'-category-'.$category->id.'-modal'];
            }
            $actions[$category->id] = ['links' => $links, 'commands' => $commands];
        }
        $presentation = ['sort' => $filters->sort, 'direction' => $filters->direction, 'per_page' => $filters->perPage];
        $sortUrls = [];
        $sortLabels = ['name' => 'Category', 'products' => 'Products', 'attributes' => 'Attributes', 'facets' => 'Facets', 'sites' => 'Selected by Sites', 'status' => 'Category status', 'schema_status' => 'Schema status', 'updated_at' => 'Updated'];
        foreach ($sortLabels as $key => $label) {
            $sortUrls[$key] = route('central.categories.index', [...array_diff_key($request->queryParameters(), array_flip(['page', 'sort', 'direction'])), 'sort' => $key, 'direction' => $filters->sort === $key && $filters->direction === 'asc' ? 'desc' : 'asc']);
        }

        return view('central-admin.categories.index', [
            'queryParameters' => $request->queryParameters(), 'list' => $list, 'filters' => $filters, 'actions' => $actions,
            'createUrl' => $canMutate ? CentralCategoryResource::getUrl('create') : null,
            'statusOptions' => CentralCategoryStatus::options(), 'schemaOptions' => CategorySchemaStatus::options(),
            'sortLabels' => $sortLabels, 'sortUrls' => $sortUrls,
            'clearFiltersUrl' => route('central.categories.index', $presentation),
            'metrics' => [
                ['key' => 'total', 'label' => 'Total Categories', 'value' => $list->summary->total, 'icon' => 'squares-2x2', 'tone' => 'primary', 'detail' => 'Canonical registry'],
                ['key' => 'active', 'label' => 'Active', 'value' => $list->summary->active, 'icon' => 'check-circle', 'tone' => 'success', 'detail' => $list->summary->activePercentage() === null ? 'Current state' : $list->summary->activePercentage().'% of total'],
                ['key' => 'with-schema', 'label' => 'With Schema', 'value' => $list->summary->withSchema, 'icon' => 'squares-2x2', 'tone' => 'info', 'detail' => 'Has assignments'],
                ['key' => 'missing-translations', 'label' => 'Missing Translations', 'value' => $list->summary->missingTranslations, 'icon' => 'language', 'tone' => 'danger', 'detail' => 'Active Locales'],
                ['key' => 'needs-review', 'label' => 'Needs Review', 'value' => $list->summary->needsReview, 'icon' => 'exclamation-triangle', 'tone' => 'warning', 'detail' => 'Schema is Draft'],
            ],
        ]);
    }
}
