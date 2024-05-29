<button class="btn btn-warning export">Excel</button>
@push('footer-scripts')
    <script>
        $(document).on('click', '.export', function (e) {
            e.preventDefault();
            var btn = $(this);
            var filters = btn.closest('.tab-content').find('table .display-filters')
            var form = '<form action="/machine-export" method="post">';
            var counter = 1;
            $(filters).find('td').each(function () {
                var _in = $(this);
                if(_in.children().data('name') === undefined){

                    _in.children().find('input').each(function () {
                        var name;
                        switch (counter){
                            case 1:
                                name = 'sum_hour_from';
                                break;
                            case 2:
                                name = 'sum_hour_to';
                                break;
                            case 3:
                                name = 'sum_day_from';
                                break;
                            case 4:
                                name = 'sum_day_to';
                                break;
                        }
                        form += '<input type="hidden" name="' + name + '" value="' + $(this).val() + '" />'
                        ++counter;
                    });
                    return;
                }

                form += '<input type="hidden" name="' + _in.children().data('name') + '" value="' + _in.children().val() + '" />'
            })
            var order = localStorage.getItem('DataTables_DataTables_Table_0_/machineries');
            if(order){
                order = JSON.parse(order).order[0]

                form += '<input type="hidden" name="sorting_column" value="'+ order[0] + '">'
                form += '<input type="hidden" name="sorting_type" value="'+ order[1] + '">'
            }

            form += '{{csrf_field()}}</form>';
            $(form).appendTo('body').submit().remove()
        })
    </script>
@endpush