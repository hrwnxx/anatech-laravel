@extends('layouts.storefront')

@section('title', 'My Purchases | Anatech')

@section('content')
    <h2>My Purchases</h2>
    <p class="text-muted">Gadgets you bought at the store. You can review anything on this list.</p>

    @if ($purchases->isEmpty())
        <div class="alert alert-secondary">No purchases recorded yet. Purchases are added by our staff when you buy in-store.</div>
    @else
        <div class="card p-3 table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr><th></th><th>Gadget</th><th>Qty</th><th>Price</th><th>Total</th><th>Date</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach ($purchases as $purchase)
                        <tr>
                            <td style="width: 70px">
                                @if ($purchase->gadget->image_url)
                                    <img src="{{ $purchase->gadget->image_url }}" alt="" style="height: 50px">
                                @endif
                            </td>
                            <td>{{ $purchase->gadget->brand }} {{ $purchase->gadget->gadget_name }}</td>
                            <td>{{ $purchase->quantity }}</td>
                            <td>₱{{ number_format((float) $purchase->unit_price, 2) }}</td>
                            <td>₱{{ number_format((float) $purchase->unit_price * $purchase->quantity, 2) }}</td>
                            <td>{{ $purchase->purchase_date->format('M j, Y') }}</td>
                            <td class="text-end text-nowrap">
                                @if (in_array($purchase->gadget_id, $reviewedGadgetIds))
                                    <a href="{{ route('catalog.show', $purchase->gadget_id) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-check2"></i> Reviewed</a>
                                @else
                                    <a href="{{ route('catalog.show', $purchase->gadget_id) }}" class="btn btn-sm btn-dark">Write a Review</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
