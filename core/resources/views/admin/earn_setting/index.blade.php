@php
    use App\Models\CryptoCurrency;
    use Illuminate\Support\Str;
    $cryptos = CryptoCurrency::get();
@endphp

@extends('admin.layouts.app')
@section('panel')
<div class="row">
    <div class="col-lg-12">
        <div class="card b-radius--10 ">
            <div class="card-body p-0">
                <div class="table-responsive--md  table-responsive">
                    <table class="table table--light style--two">
                        <thead>
                            <tr>
                                <th>@lang('S.N.')</th>
                                <th>@lang('Time')</th>
                                <th>@lang('Unit')</th>
                                <th>@lang('Profit') (%)</th>
                                <th>@lang('Minimum')</th>
                                <th>@lang('Action')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($games as $game)
                            <tr>
                                <td>{{$loop->index+$games->firstItem()}}</td>
                                <td>{{$game->time}}</td>
                                <td>{{ucfirst($game->unit)}}</td>
                                <td>{{str_replace('}', '', str_replace('{', '', str_replace('"', '', $game->profit)))}}</td>
                                <td>{{str_replace('}', '', str_replace('{', '', str_replace('"', '', $game->minimum)))}}</td>
                                <td>
                                    <button type="button"  class="btn btn-sm btn-outline--primary editBtn" data-game='@json($game)' data-minimum='@json(json_decode($game->minimum,true))' data-profit='@json(json_decode($game->profit,true))'>
                                        <i class="la la-pencil"></i>@lang('Edit')
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline--danger ms-1 confirmationBtn"
                                            data-question="@lang('Are you sure to delete this earn setting')?"
                                            data-action="{{route('admin.earn.setting.delete',$game->id) }}">
                                        <i class="las la-trash"></i>@lang('Delete')
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td class="text-muted text-center" colspan="100%">{{ __($emptyMessage) }}</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($games->hasPages())
            <div class="card-footer py-4">
                {{ paginateLinks($games)}}
            </div>
            @endif
        </div>
    </div>
</div>

<div id="cryptoModal" class="modal fade">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">@lang('Add Game Setting')</h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="las la-times"></i>
                </button>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>@lang('Time')</label>
                        <input type="number" step="any" class="form-control" name="time" required>
                    </div>
                    <div class="form-group">
                        <label>@lang('Unit')</label>
                        <select class="form-control" name="unit" required>
                            <option selected disabled>@lang('Select One')</option>
                            <option value="days">@lang('Days')</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>@lang('Minimum USDT Deposit')</label>
                        <input type="number" step="any" class="form-control" name="minimum_usdt" required>
                    </div>
                    <div class="form-group">
                        <label>@lang('Minimum BTC Deposit')</label>
                        <input type="number" step="any" class="form-control" name="minimum_btc" required>
                    </div>
                    <div class="form-group">
                        <label>@lang('Minimum ETH Deposit')</label>
                        <input type="number" step="any" class="form-control" name="minimum_eth" required>
                    </div>
                    @foreach($cryptos as $crypto)
                        <div class="form-group">
                            <label>{{$crypto->symbol}} @lang('Profit') %</label>
                            <input type="number" step="any" class="form-control" name="profit_{{Str::lower($crypto->symbol)}}" required>
                        </div>
                    @endforeach
                    <button type="submit" class="btn btn--primary w-100 h-45">@lang('Submit')</button>
                </div>
            </form>
        </div>
    </div>
</div>
<x-confirmation-modal/>
@endsection

@push('breadcrumb-plugins')
<button type="button" class="btn btn-sm btn-outline--primary addBtn">
    <i class="las la-plus"></i>@lang('Add New')
</button>
@endpush

@push('script')
<script>
    "use strict";
    (function ($) {

        let modal = $('#cryptoModal');

        $('.addBtn').on('click', function (e) {
            let action = `{{ route('admin.earn.setting.save') }}`;
            modal.find('form').trigger('reset');
            modal.find('.modal-title').text("@lang('Add Earn Setting')")
            modal.find('form').prop('action', action);
            $(modal).modal('show');
        });

        $('.editBtn').on('click', function (e) {
            let action = `{{ route('admin.earn.setting.save',':id') }}`;
            let data   = $(this).data('game');
            let minimum   = $(this).data('minimum');
            let profit   = $(this).data('profit');
            modal.find('form').prop('action', action.replace(':id', data.id))
            modal.find("input[name=time]").val(data.time);
            modal.find("input[name=profit]").val(data.profit);
            modal.find("input[name=minimum_usdt]").val(minimum.USDT);
            modal.find("input[name=minimum_btc]").val(minimum.BTC);
            modal.find("input[name=minimum_eth]").val(minimum.ETH);
            @foreach($cryptos as $crypto)
                try{
                    modal.find("input[name=profit_{{Str::lower($crypto->symbol)}}]").val(profit.{{$crypto->symbol}});
                }catch(e) {
                    //
                }
            @endforeach
            modal.find("select[name=unit]").val(data.unit);
            modal.find('.modal-title').text(`@lang('Update Earn Setting')`);
            $(modal).modal('show');
        });
    })(jQuery);
</script>
@endpush
