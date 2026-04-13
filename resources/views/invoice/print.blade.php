
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Invoice</title>

    <style>
        body {
            font-family: "Times New Roman", serif;
            font-size: 13px;
            margin: 30px;
            color: #000;
        }

        .container {
            width: 100%;
        }

        .header {
            display: flex;
            justify-content: flex-start;
            align-items: flex-start;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }

        .logo {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .logo img {
            height: 60px;
        }

        .company-info {
            font-size: 12px;
        }

        .company-info strong {
            font-size: 16px;
        }

        .title {
            text-align: center;
            margin: 10px 0;
            font-weight: bold;
            font-size: 18px;
        }

        .invoice-info {
            display: flex;
            gap: 40px;
            margin-bottom: 10px;
        }

        .invoice-info > div {
            width: 300px;
        }

        .invoice-info p {
            margin: 3px 0;
            display: flex;
        }

        .label {
            width: 110px;
            font-weight: bold;
        }

        .colon {
            width: 10px;
            text-align: center;
        }

        .value {
            flex: 1;
        }



        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table, th, td {
            border: 1px solid #000;
        }

        th {
            text-align: center;
            padding: 6px;
            font-weight: bold;
        }

        td {
            padding: 6px;
            vertical-align: top;
        }

        td {
            vertical-align: middle;
        }


        .text-center { text-align: center; }
        .text-right { text-align: right; }

        .serial-list {
            line-height: 1.3;
            font-size: 12px;
        }

        .total-row td {
            font-weight: bold;
        }

        .footer {
            margin-top: 15px;
        }

        .footer p {
            margin: 4px 0;
        }

        .warranty {
            margin-top: 10px;
            font-size: 12px;
        }

        .warranty ul {
            margin: 5px 0 0 15px;
            padding: 0;
        }

        .signature {
            margin-top: 40px;
        }

        .billed-to-section h2 {
            font-size: 14px;
            margin-bottom: 5px;
            text-decoration: underline;
        }

        .invoice-info .value {
            flex: 1;
        }

        .serial-ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .serial-ul li {
            padding: 2px 0;
            white-space: nowrap;
        }


        @media print {
            body {
                margin: 0;
            }
        }
    </style>
</head>
<body onload="window.print()">

<div class="container">
    <div class="header">
        <div class="logo">
            @php $app_logo = configs(['app_logo']); @endphp
            <div class="text-center mb-4">
                <img src="{{ $app_logo }}" alt="App Logo" style="max-height: 70px;">
            </div>
            <div class="company-info me-5" style="margin-left: 10px">
                <strong>“Registered & Reliable Lithium Battery and Equipment Solutions”</strong><br>
                <span style="margin-left: 10px;">{{ $settings['company_address'] ?? 'Not Set' }}</span><br>
                <strong>Email:</strong> {{ $settings['company_email'] ?? '' }}, Mob.: {{ $settings['company_phone'] ?? '' }}
            </div>
        </div>

    </div>
    <div class="title">SALES INVOICE</div>
    @php
        $party = $invoice->dealer_id && $invoice->dealer ? $invoice->dealer : $invoice->customer;
       if($invoice->dealer_id && $invoice->dealer && $invoice->dealer->address) {
        $party_address = $invoice->dealer->address;
         } elseif($invoice->customer && $invoice->customer->address) {
        $party_address = $invoice->customer->p_area;
         } else {
        $party_address = $address;
    }
    @endphp
    <div class="invoice-info">
        <div>
            <p>
                <span class="label">Invoice No</span>
                <span class="colon">:</span>
                <span class="value">{{ $invoice->invoice_no }}</span>
            </p>
            <p>
                <span class="label">Name</span>
                <span class="colon">:</span>
                <span class="value">{{ $party->name ?? '' }}</span>
            </p>
            <p>
                <span class="label">Contact No</span>
                <span class="colon">:</span>
                <span class="value">{{ $party->phone ?? '' }}</span>
            </p>
        </div>

        <div>
            <p>
                <span class="label">Invoice Date</span>
                <span class="colon">:</span>
                <span class="value">{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d/m/Y') }}</span>
            </p>
            <p>
                <span class="label">Address</span>
                <span class="colon">:</span>
                <span class="value">
                {{ $address->p_area ?? $address->s_area ?? 'Not Available' }}
            </span>
            </p>
        </div>
    </div>

    <div style="font-size:16px; font-weight:bold; margin:10px 0 4px; text-decoration: underline;">
        INVOICE DETAILS :
    </div>

    <table>
        <thead>
        <tr>
            <th style="width:5%">SL</th>
            <th style="width:25%">Product Description</th>
            <th style="width:35%">Serial No.</th>
            <th style="width:5%">Qty.</th>
            <th style="width:15%">U/P (BDT)</th>
            <th style="width:15%">Amount (BDT)</th>
        </tr>
        </thead>
        <tbody>
        @foreach($invoice->items as $item)
            <tr>
                <td class="text-center">{{ $loop->iteration }}</td>
                <td>{{ $item->product->product_name }}</td>
                <td class="serial-list">
                    @php
                        $serials = $invoice->productSerials
                            ->where('product_id', $item->product_id)
                            ->where('serial_group_id', $item->serial_group_id)
                            ->pluck('serial');
                    @endphp

                    @if($serials->count())
                        <ul class="serial-ul">
                            @foreach($serials as $serial)
                                <li>{{ $serial }}</li>
                            @endforeach
                        </ul>
                    @else
                        -
                    @endif
                </td>


                <td class="text-center">{{ number_format($item->quantity)}}</td>
                <td class="text-right">{{ number_format($item->unit_price, 0) }}</td>
                <td class="text-right">{{ number_format($item->total_price, 0) }}</td>
            </tr>
        @endforeach
        @php
            $hasDiscount = ($discount ?? 0) > 0;
        @endphp

        @if($hasDiscount)

            <tr>
                <td colspan="5" class="text-right"><strong>Sub Total</strong></td>
                <td class="text-right">{{ number_format($subTotal, 0) }}</td>
            </tr>
            <tr>
                <td colspan="5" class="text-right"><strong>Discount (-)</strong></td>
                <td class="text-right">{{ number_format($discount, 0) }}</td>
            </tr>
            <tr class="total-row">
                <td colspan="5" class="text-right"><strong>Grand Total</strong></td>
                <td class="text-right"><strong>{{ number_format($grandTotal, 0) }}</strong></td>
            </tr>

        @else

            {{-- Normal Total (No Discount) --}}
            <tr class="total-row">
                <td colspan="2" class="text-center">Total</td>
                <td></td>
                <td class="text-center">{{ $invoice->items->sum('quantity') }}</td>
                <td></td>
                <td class="text-right">{{ number_format($subTotal, 0) }}</td>
            </tr>

        @endif

        </tbody>
    </table>
    <div class="footer">
        <p><strong>In words:</strong> {{ $amountInWords ?? 'Amount in words here' }} Taka Only</p>

        <div class="warranty">
            <strong>WARRANTY & GUARANTEE POLICY:</strong>
            <ul>
                <li>Product: Cell: Gotin (A+) | BMS: JiaBaida</li>
                <li>Replacement Guarantee: 2 (Two) Years replacement guarantee against manufacturing defects only.</li>
                <li>Service Warranty: 3 (Three) Years service warranty after replacement period. (Total Coverage: 5 Years)</li>
                <li>Spears Warranty: 1-Year Warranty for Battery Chargers and Displays.</li>
                <li>Battery Life Cycle: Up to 1845 charge cycles under normal operating conditions.</li>
                <li>Exclusions: Physical damage, water or moisture damage, short circuit, misuse, accident, fire, earthquake or any abnormal/natural disaster, and unauthorized repair or modification are not covered.</li>
                <li>Normal Wear: Capacity reduction due to normal usage over time is not considered a defect.</li>
                <li>Condition: Warranty and guarantee services are applicable only upon presentation of this original invoice & enclosed Battery QR Code.</li>
                <li>Authority: The company authority reserves the right to make the final decision regarding all warranty or guarantee claims.</li>
                <li>Under no circumstances shall our chargers be used to charge any lead-acid battery.</li>
            </ul>
            <div style="text-align:center; margin-top:10px; font-size:12px; border-top:1px solid rgba(0,0,0,0.3); padding-top:8px;"></div>
        </div>

        <div style="display:flex; justify-content:space-between; margin-top:30px; align-items:flex-start;">
            <div style="width:65%;">
                <p style="border-bottom:1px solid rgba(0,0,0,0.3); display:inline-block; padding-bottom:5px; font-weight:bold;">
                    <strong>Authorized by :</strong>
                </p>

                <p>Battergo (Shanghai) Tech. Co., Ltd.</p>

                <div style="text-align:center; margin-top:10px; font-size:12px; border-top:1px solid rgba(0,0,0,0.3); padding-top:8px;"></div>


                {{--                @if(isset($salesman) && $salesman->signature)--}}
{{--                    <img src="{{ storageImage($salesman->signature) }}" style="height:60px; margin:10px 0;">--}}
{{--                @endif--}}

{{--                <p>--}}
{{--                    <strong>Name & Designation :</strong>--}}
{{--                    {{ $salesman->name ?? 'Not Available' }}--}}
{{--                    /--}}
{{--                    {{ $salesman->designation->designation_name ?? '' }}--}}
{{--                </p>--}}
            </div>

            <div style="width:30%; text-align:center;">

                <div style="border:2px solid #000; padding:5px; display:inline-block;">
{{--                    {!! QrCode::size(90)->generate(route('invoice.public.print', $invoice->invoice_no)) !!}--}}
                    {!! QrCode::size(90)->generate('https://battergo.tmssict.com/invoice_public/'.$invoice->invoice_no) !!}
                </div>

                <p style="font-size:12px; margin-top:5px;">
                    Scan to verify invoice
                </p>

            </div>
        </div>

        <p style="font-size:12px; margin-top:15px; text-align: center">
            This is a BatterGo ERP System generated invoice and is valid without a physical signature or company stamp.
        </p>

        <div style="text-align:center; margin-top:10px; font-size:12px; border-top:1px solid rgba(0,0,0,0.3); padding-top:8px;">
            <strong>Web:</strong> www.battergo.tech &nbsp;&nbsp; | &nbsp;&nbsp;
            <strong>ERP:</strong> battergo.tmssict.com
        </div>

    </div>
</div>

</body>
</html>
