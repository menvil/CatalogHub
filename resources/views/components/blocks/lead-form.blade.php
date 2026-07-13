@props(['config' => []])

<section data-theme-block="lead_form">
    <h2>{{ $config['title'] ?? 'Need help choosing?' }}</h2>
    <button type="button" disabled>{{ $config['button_label'] ?? 'Send a request' }}</button>
    <p>Lead processing will be available in a later phase.</p>
</section>
