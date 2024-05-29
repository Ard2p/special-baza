@if(Session::has('success_delete'))
<div class="alert alert-success alert-message">
    <button type="button" data-dismiss="alert" aria-label="Close" class="close"><span aria-hidden="true">×</span>
    </button>
    <i class="fa fa-check fa-lg"></i> {{Session::get('success_delete')}}
</div>
@elseif(Session::has('fail_delete'))
    <div class="alert alert-danger alert-message">
        <button type="button" data-dismiss="alert" aria-label="Close" class="close"><span aria-hidden="true">×</span>
        </button>
        <i class="fa fa-warning fa-lg"></i> {{Session::get('fail_delete')}}
    </div>
@endif
<div class="alert bg-info"><button class="btn btn-warning export" data-table="0">Excel</button></div>

    <script>
        $(document).on('click', '.export', function (e) {
            e.preventDefault();
            var btn = $(this);
            var filters = btn.closest('.tab-content').find('table .display-filters')
            var form = '<form action="{{route('user_export')}}" method="post">';
            var i = 0;
            $(filters).find('td').each(function () {
                ++i;
                if(i < 2){
                    return;
                }
                var _in = $(this);
                form += '<input type="hidden" name="' + _in.children().data('name') + '" value="' + _in.children().val() + '" />'
            })
            var order = localStorage.getItem('DataTables_DataTables_Table_0_/users');
            if(order){
                order = JSON.parse(order).order[0]

                form += '<input type="hidden" name="sorting_column" value="'+ order[0] + '">'
                form += '<input type="hidden" name="sorting_type" value="'+ order[1] + '">'
            }

            form += '{{csrf_field()}}</form>';
            $(form).appendTo('body').submit().remove()
        })
    </script>