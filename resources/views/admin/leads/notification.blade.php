<alarm-template

        init-notes="{{$lead->notifications->toJson()}}"
        types="{{$statuses->toJson()}}"
        all-managers="{{$managers->toJson()}}"
        delay-types="{{json_encode(\App\Modules\Crm\LeadNotification::$delay_types)}}"

        url="{{route('note_noty.index', ['lead_id' => $lead->id])}}"
        update-url="{{route('note_noty.index')}}"
></alarm-template>
<script type="text/x-template" id="alarm-template">
    <div class="panel">
        <div class="panel-body">

            <button class="btn btn-primary" @click="isHidden = !isHidden">Добавить напоминание</button>

            <form class="row" v-if="isHidden" id="new_notification">
                <div class="col-md-12">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Заметка</label>


                            <textarea type="text" name="content"
                                      class="form-control"></textarea>
                        </div>

                        <div class="form-group">
                            <label>Тип заметки</label>
                            <select class="form-control" name="note_type">
                                <option v-for="(type, i) in types" v-html="type.name" :value="type.id"></option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Ответственный менеджер</label>
                            <select class="form-control" name="manager_id">
                                <option v-for="(manager, i) in managers" v-html="manager.id_with_email" :value="manager.id"></option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">

                        <div class="form-group">
                            <label class="control-label">
                                Вложения

                                <span class="form-element-required">*</span>
                            </label>
                            <div class="image-load">
                                <div class="button">
                                    <label>
                                        <span class="btn btn-primary" style="padding: 0 20px;">Добавить вложение</span>
                                        <input type="file" name="files[]" id="_file" style="opacity: 0;" multiple
                                               @change="changeFiles">
                                    </label>
                                </div>

                                <div id="_fileList">
                                </div>
                            </div>

                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Дата напоминания</label>
                                <input
                                        v-datetimepicker
                                        name="start_date"
                                        class="form-control"/>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Когда напомнить?</label>
                                <select class="form-control" name="delay_type">
                                    <option v-for="(name, i) in delay_types" v-html="name" :value="i"></option>
                                </select>
                            </div>
                        </div>
                        <div class="clearfix"></div>
                    </div>
                    <div class="col-md-12">
                        <button type="button" class="btn btn-success" @click="save">Сохранить</button>
                    </div>
                </div>
            </form>

            <hr>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                    <th class="text-center">Напоминание</th>
                    <th class="text-center">Тип</th>
                    <th class="text-center">Вложения</th>
                    <th class="text-center">Дата</th>
                    <th class="text-center">Менеджер</th>
                    <th class="text-center"><i class="fa fa-cog"></i></th>
                    </thead>
                    <tbody>
                    <tr v-for="note in notes">
                        <td v-html="note.note"></td>
                        <td v-html="note.type"></td>
                        <td v-html="note.html_attachment"></td>
                        <td v-html="note.created_at"></td>
                        <td v-html="note.user_name"></td>
                        <td>
                            <button class="btn btn-primary" @click="done(note.id, 'done')" v-if="!note.refuse">Сделано!</button>
                            <button class="btn btn-warning" @click="done(note.id, 'refuse')"  v-if="!note.refuse">Отмена!</button>
                            <button class="btn btn-danger" @click="done(note.id, 'delete')"><i class="fa fa-trash"></i> </button>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

</script>