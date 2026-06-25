<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        .clearfix:after {
          content: "";
          display: table;
          clear: both;
        }

        body {
            color: #001028;
            background: #FFFFFF; 
            font-family: Helvetica, sans-serif !important;
            font-size: 12px; 
            color: #2A2B2B;
            width: 21cm;  
            height: 29.7cm; 
            margin: 0 auto;
            width: 100%;
        }

        header {
            text-align: left;
            margin-top: .3cm;
            margin-bottom: 1cm;
            width: 100%;
            display: block;
        }

        p {
            font-size: 12px;
            line-height: 18px;
            margin: 0;
        }

        .bold {
            font-weight: bold !important;
        }

        .text-start {
          text-align: left;
        }

        .text-end {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .from {
            margin-top: .9cm;
        }

        .to {
            margin-bottom: .9cm;
        }

        .small {
            font-size: 9px;
        }

        .subtitle {
            text-decoration: underline;
        }

        .footer {
            margin-top: 2cm;
        }

        table {
          width: 100%;
          border-collapse: collapse;
          border-spacing: 0;
          margin: 20px 0 20px 0;
          border: 1px solid #c0c1c2;
        }

        table th {
          padding: 10px;
          white-space: nowrap;
          background: #eeeeee;
        }

        table td {
          padding: 10px;
          text-align: right;
        }

        table td.grand {
          border-top: 1px solid #c0c1c2;;
        }

        .content {
            margin-top: 1cm;
        }

        .h1 {
            font-size: 20px;
        }

    </style>
</head>

<body>
    <header>
        <img class="logo" width="200" src="{{ public_path($company->company_image) }}">
    </header>

    <main>
        <div class="to">
            <p> 
                @if ($model->client)
                    {{ $model->client->name }}
                    <br>{{ $model->client->address }}
                    <br>{{ $model->client->cif_nif }}
                    <br>{{ $model->client->zip_code }}, {{ $model->client->city }}
                    <br>{{ $model->client->country->name }}
                @else
                    {{ $model->client_name }}
                @endif
            </p>
        </div>

        <div class="clearfix">
            <div class="content">
                {!! $model->content !!}
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th class="text-start">{{ __('budget.concept') }}</th>
                        <th class="text-start">{{ __('budget.quantity') }}</th>
                        <th class="text-center">{{ __('budget.price') }}</th>
                        <th class="text-center">{{ __('budget.discount') }}</th>
                        <th class="text-center">{{ __('budget.tax_type') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($model->lines as $line)
                    <tr>
                        <td class="text-start">{{ $line->concept }}</td>
                        <td class="text-center">{{ $line->quantity }}</td>
                        <td class="text-center">{{ $line->price }} €</td>
                        <td class="text-center">
                            @if($line->discount > 0) 
                                {{ $line->discount }} € 
                            @endif
                        </td>
                        <td class="text-center">{{ $line->tax_type }}%</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" class="text-end grand bold">{{ __('budget.subtotal') }}:</td>
                        <td class="text-center grand bold">{{ $model->subtotal }} €</td>
                    </tr>
                    <tr>
                        <td colspan="4" class="text-end grand bold">{{ __('budget.discount') }}:</td>
                        <td class="text-center grand bold">{{ $model->discount }} €</td>
                    </tr>
                    <tr>
                        <td colspan="4" class="text-end grand bold">{{ __('budget.tax') }}:</td>
                        <td class="text-center grand bold">{{ $model->tax }} €</td>
                    </tr>
                    <tr>
                        <td colspan="4" class="text-end grand bold">{{ __('budget.total') }}:</td>
                        <td class="text-center grand bold">{{ $model->total }} €</td>
                    </tr>
                </tfoot>
            </table>
            <div class="footer">
                @if ($model->client && !$model->client->tax_included)
                    <p class="text-start">
                        {{ __('The service is exempt from VAT according to Law 31/1990 of the VAT Art.20.8b.') }}:
                    </p>
                @endif
                <p class="text-end">
                    {{ $company->company_city }}, {{ $model->created_at->format('d M Y') }}
                </p>
                <p class="text-center">
                    <span class="from small">{{ $company->company_name }} | {{ $company->company_address }}, {{ $company->company_zip_code }} - {{ $company->company_city }} | {{ $company->company_email }}<br/>
                        {{ __('setup.config.company_phone') }}: {{ $company->company_phone }}
                    </span>
                </p>
            </div>
        </div>
    </main>
</body>
</html>