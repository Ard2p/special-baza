@extends('layouts.main')
@section('header')
    <title>TRANSBAZA - Мои подписки</title>
@endsection
@section('content')
    <div class="container bootstrap snippet">

        <div class="row">
            <div class="col-sm-10"><h1>@lang('transbaza_subscribes.title')</h1></div>
        </div>
        <div class="row">
            <div class="col-md-3 col-xs-12"><!--left col-->

                @include('sections.info')

            </div><!--/col-3-->
            <div class="col-md-9 col-xs-12 user-profile-wrap box-shadow-wrap">
                @if(Session::has('email_verify'))
                    <div class="alert alert-danger">
                        {{implode(' ', Session::get('email_verify'))}}
                    </div>
                @endif
                @if(Session::has('email_confirm'))
                    <div class="alert alert-success">
                        {{Session::get('email_confirm')}}
                    </div>
                @endif

                <table id="subscribes_table" class="table table-striped table-bordered"
                       style="width:100%">
                    <thead>
                    <tr>
                        <th>@lang('transbaza_subscribes.table_name')</th>
                        <th>@lang('transbaza_subscribes.table_status')</th>
                        <th>@lang('transbaza_subscribes.table_action')</th>

                    </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
@endsection

@push('after-scripts')
    <script src="/js/tables/dataTables.js"></script>
    <script src="/js/tables/tableBs.js"></script>
    <script>
        var load_url = "{{route('subscribes.index', ['get_subscribes' => 1])}}"

        var subscribes_table = $('#subscribes_table').DataTable({
            "ajax": load_url,
            "autoWidth": false,
            "ordering": false,
            "searching": false,
            "paging": false,
            "columns": [
                {
                    "data": "name",

                },
                {
                    "data": "status",
                    "class": 'text-center'
                },
                {
                    sDefaultContent: "",
                    "render": function (data, type, full, meta) {
                        if (type === 'display') {
                            if(full['can_unsubscribe'] == '0'){
                                return '@lang('transbaza_subscribes.no_action')';
                            }
                            if(full['is_subscribe'] == '1'){
                                data = '<a  class="btn-machinaries subscribe" data-toggle="tooltip" title="@lang('transbaza_subscribes.unsubscribe')" data-url="' + full['update_link'] + '"><i class="fas fa-times"></i></a>&nbsp;';
                            }else {
                                data = '<a  class="btn-machinaries subscribe" data-toggle="tooltip" title="@lang('transbaza_subscribes.subscribe')" data-url="' + full['update_link'] + '"><i class="fas fa-plus"></i></a>&nbsp;';

                            }

                        }
                        return data;
                    },
                    "class": 'text-center'
                },

            ],
            "initComplete": function( settings, json ) {
                $('[data-toggle=tooltip]').tooltip();
            }
        })

        $(document).on('click', '.subscribe', function () {
            var url = $(this).data('url');

            $.ajax({
                url: url,
                type: 'PATCH',
                success:function (e) {
                    showMessage(e.message);
                    subscribes_table.ajax.reload();
                },
                error: function (e) {
                    showErrors(e)
                }
            })
        })
    </script>
@endpush