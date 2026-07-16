@php
    /** @var \App\Models\ChangeRequest $changeRequest */
    $changeRequest = $getRecord();
@endphp

<x-admin.diff-viewer
    :field-label="$changeRequest->field_path"
    before-label="Current central value"
    :before-value="$changeRequest->old_value_json"
    after-label="Proposed value"
    :after-value="$changeRequest->proposed_value_json"
    variant="side-by-side"
/>
