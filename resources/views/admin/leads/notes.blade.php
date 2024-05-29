<note-template

        init-notes="{{$lead->notes->toJson()}}"
        types="{{$statuses->toJson()}}"

        url="{{route('notes.index', ['lead_id' => $lead->id])}}"
></note-template>
<script type="text/x-template" id="note-template">
    <div class="panel">
        <div class="panel-body">

            <button class="btn btn-primary" @click="isHidden = !isHidden">Добавить заметку</button>

            <form class="row" v-if="isHidden" id="new_note">
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
                                        <input type="file" name="files[]" id="file" style="opacity: 0;" multiple
                                               @change="changeFiles">
                                    </label>
                                </div>

                                <div id="fileList">
                                </div>
                            </div>

                        </div>

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
                    <th>Заметка</th>
                    <th>Тип</th>
                    <th>Вложения</th>
                    <th>Дата</th>
                    <th>Менеджер</th>
                    </thead>
                    <tbody>
                    <tr v-for="note in notes">
                        <td v-html="note.note"></td>
                        <td v-html="note.type"></td>
                        <td v-html="note.html_attachment"></td>
                        <td v-html="note.created_at"></td>
                        <td v-html="note.user_name"></td>
                    </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

</script>