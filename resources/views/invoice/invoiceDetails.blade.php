<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice Details</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }
        th {
            background: #f5f5f5;
        }
        .right {
            text-align: right;
        }
    </style>
</head>
<body onload="">

<h2 style="text-align: center;">Invoice Details</h2>
@if($data->count())
    <div style="display: flex; justify-content: space-between;">
        <div>
            <p><strong>Invoice No:</strong> {{ $invoice->invoice_no }}</p>
        </div>
        <div style="align-items: center;">
            <p><strong>Invoice Date:</strong> {{ $invoice->invoice_date }}</p>
        </div>
    </div>

    <table>
        <thead>
        <tr>
            <th>#</th>
            <th>Product Name</th>
            <th>Product Code</th>
            <th>Serials</th>
            <th class="right">Quantity</th>
            <th class="right">Unit Price</th>
            <th class="right">Total Price</th>
        </tr>
        </thead>
        <tbody>
        @php
            $grandTotal = 0;
            $unitTotal = 0;
            $quantityTotal = 0;
        @endphp

        @foreach($data as $item)
            @php
                $grandTotal += $item->total_price;
                $unitTotal += $item->unit_price;
                $quantityTotal += $item->quantity;
            @endphp
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $item->product_name }}</td>
                <td>{{ $item->product_code }}</td>
                <td>
                    @if(isset($item->serials) && $item->serials->count())
                        @foreach($item->serials as $serial)
                            {{ $serial }}<br>
                        @endforeach
                    @else
                        <span style="color: red;"> This Invoice No serial assigned / Not delivered</span>
                    @endif
                </td>
                <td class="right">{{ $item->quantity }}</td>
                <td class="right">{{ number_format($item->unit_price, 2) }}</td>
                <td class="right">{{ number_format($item->total_price, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
        <tr>
            <th colspan="4" class="right">Grand Total</th>
            <th class="right">{{ number_format($quantityTotal, 2) }}</th>
            <th class="right">{{ number_format($unitTotal, 2) }}</th>
            <th class="right">{{ number_format($grandTotal, 2) }}</th>
        </tr>
        </tfoot>
    </table>
@else
    <p>No items found for this invoice.</p>
@endif

</body>
</html>
