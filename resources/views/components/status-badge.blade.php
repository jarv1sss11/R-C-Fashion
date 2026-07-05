@props(['status'])

@php
    $variants = [
        'published' => ['label' => 'Published', 'class' => 'status-badge--success'],
        'draft' => ['label' => 'Draft', 'class' => 'status-badge--neutral'],
        'archived' => ['label' => 'Archived', 'class' => 'status-badge--muted'],
        'active' => ['label' => 'Active', 'class' => 'status-badge--success'],
        'inactive' => ['label' => 'Inactive', 'class' => 'status-badge--neutral'],
        'suspended' => ['label' => 'Suspended', 'class' => 'status-badge--danger'],
        'pending' => ['label' => 'Pending', 'class' => 'status-badge--warning'],
        'approved' => ['label' => 'Approved', 'class' => 'status-badge--success'],
        'rejected' => ['label' => 'Rejected', 'class' => 'status-badge--danger'],
        'processing' => ['label' => 'Processing', 'class' => 'status-badge--warning'],
        'completed' => ['label' => 'Completed', 'class' => 'status-badge--success'],
        'cancelled' => ['label' => 'Cancelled', 'class' => 'status-badge--danger'],
        'paid' => ['label' => 'Paid', 'class' => 'status-badge--success'],
        'shipped' => ['label' => 'Shipped', 'class' => 'status-badge--warning'],
        'delivered' => ['label' => 'Delivered', 'class' => 'status-badge--success'],
    ];

    $variant = $variants[$status] ?? ['label' => ucfirst($status), 'class' => 'status-badge--neutral'];
@endphp

<span {{ $attributes->merge(['class' => 'status-badge ' . $variant['class']]) }}>{{ $variant['label'] }}</span>
