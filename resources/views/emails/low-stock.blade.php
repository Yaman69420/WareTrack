<x-mail::message>

# ⚠️ Low Stock Alert

Hi **{{ $notifiable->name }}**,

The following product has dropped below its minimum stock level and requires your attention.

---

<x-mail::panel>

**{{ $product->name }}**
SKU: `{{ $product->sku }}` · Category: {{ $product->category?->name ?? '—' }}

| | |
|:--|--:|
| Current stock | **{{ $current }} units** |
| Minimum required | {{ $product->min_stock }} units |
| Shortage | **{{ $shortage }} units** |

</x-mail::panel>

@if($product->stock->isNotEmpty())
**Stock breakdown by location:**

@foreach($product->stock as $line)
- **{{ $line->location->code }}**{{ $line->location->name ? ' — ' . $line->location->name : '' }}: {{ $line->quantity }} units
@endforeach

@endif

<x-mail::button url="{{ url('/stock/movements/create') }}" color="primary">
Register Incoming Stock
</x-mail::button>

---

You received this alert because you are an administrator on **WareTrack**.
Alerts are throttled to once per 24 hours per product.

</x-mail::message>
