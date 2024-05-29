<div class="panel">
    <div class="panel-body">
        @foreach($logs as $log)
            <div class="container-fluid">
                 <pre>
                     {{$log->tables_data}}
                 </pre>
            </div>
        @endforeach
    </div>
</div>