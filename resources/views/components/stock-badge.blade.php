@php($stock = (int) $stock)

@if ($stock === 0)
    <span class="badge bg-danger">Out of Stock</span>
@elseif ($stock <= 5)
    <span class="badge bg-warning text-dark">Low Stock: {{ $stock }}</span>
@else
    <span class="badge bg-success">In Stock: {{ $stock }}</span>
@endif
