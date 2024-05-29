<table class="table table-striped">
    <thead>
    <th>Email</th>
    <th>Должность</th>
    @if($company->canEdit())
        <th>Действия</th>
    @endif
    </thead>
    <tbody>
    @foreach($company->employees as $employee)
        <tr>
            <td>{{$employee->email}}</td>
            <td>{{$employee->pivot->position}}</td>
            @if($company->canEdit())
                <td style="    display: inline-flex; width: 100%;">
                    <a class="btn-machinaries"
                       data-toggle="tooltip"
                       title="Просмотр"
                       href="#"><i
                                class="fas fa-eye"></i></a>
                    <a class="btn-machinaries"
                       data-toggle="tooltip"
                       title="Изменить"
                       href="#"><i
                                class="fas fa-file-signature"></i></a>
                    <form method="POST" action="#">
                        @csrf
                        @method('DELETE')
                        <a class="btn-machinaries" data-toggle="tooltip"
                           title="Удалить сотрудника"
                        ><i
                                    class="fa fa-trash"></i></a>
                    </form>
                </td>

            @endif
        </tr>

    @endforeach
    </tbody>
</table>
