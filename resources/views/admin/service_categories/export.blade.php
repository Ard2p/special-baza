
<button class="btn btn-warning export">Excel</button>
{{--<button class="btn btn-warning import">Импорт</button>--}}
<form class="btn btn-warning" id="import_categories" action="{{route('service_import')}}">
    <label style="    display: block;">
                                                <span class=""
                                                      style="padding: 0 20px;">Импорт из файла</span>
        <input type="file" onchange="updateFile()"
               name="excel"
               id="email_file"
               style="display: none;">
    </label>
</form>

<script>
    document.addEventListener("DOMContentLoaded", function (event) {
        $(document).on('click', '.export', function (e) {
            e.preventDefault();
            var btn = $(this);
            var filters = btn.closest('.tab-content').find('table .display-filters')
            var form = '<form action="{{route('service_export')}}" method="post">';
            var counter = 1;
            /*  $(filters).find('td').each(function () {
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
              })*/
            var order = localStorage.getItem('DataTables_DataTables_Table_0_/machineries');
            if (order) {
                order = JSON.parse(order).order[0]

                form += '<input type="hidden" name="sorting_column" value="' + order[0] + '">'
                form += '<input type="hidden" name="sorting_type" value="' + order[1] + '">'
            }

            form += '{{csrf_field()}}</form>';
            $(form).appendTo('body').submit().remove()
        })
        updateFile = function (type) {
            var input = document.getElementById('email_file');

            for (var i = 0; i < input.files.length; ++i) {
                var _name = input.files.item(i).name;
                var array = _name.split('.');
                var extension = array[array.length - 1];
                var allow_extension = ['xls', 'xlsx', 'csv'];
                console.log(extension);
                if (!allow_extension.includes(extension)) {
                    alert('Внимание! Файл ' + _name + ' не будет обработан т.к. формат не поддерживается.')
                    //Array.prototype.slice.call(files).splice(i, 1);
                    return false;
                }
            }
            let $form = $('#import_categories')
            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: new FormData($form[0]),
                processData: false,
                contentType: false,
                success: function (e) {
                    swal(e.message)
                    document.getElementById("email_file").value = "";
                    $('.datatables').DataTable().ajax.reload()
                },
                error: function (e) {
                    swal(e.responseJSON.errors)
                }

            })
        }
    })
</script>
