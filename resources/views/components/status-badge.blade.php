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
        'failed' => ['label' => 'Failed', 'class' => 'status-badge--danger'],
        'preparing' => ['label' => 'Preparing', 'class' => 'status-badge--warning'],
        'rider_assigned' => ['label' => 'Rider Assigned', 'class' => 'status-badge--warning'],
        'out_for_delivery' => ['label' => 'Out for Delivery', 'class' => 'status-badge--warning'],
        'assigned' => ['label' => 'Assigned', 'class' => 'status-badge--warning'],
        'picked_up' => ['label' => 'Picked Up', 'class' => 'status-badge--warning'],
        'available' => ['label' => 'Available', 'class' => 'status-badge--success'],
        'unavailable' => ['label' => 'Unavailable', 'class' => 'status-badge--neutral'],
    ];

    $variant = $variants[$status] ?? ['label' => ucfirst($status), 'class' => 'status-badge--neutral'];
@endphp

<span {{ $attributes->merge(['class' => 'status-badge ' . $variant['class']]) }}>{{ $variant['label'] }}</span>
