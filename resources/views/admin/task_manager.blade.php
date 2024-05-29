<link href="/css/jquery.datetimepicker.min.css" rel="stylesheet">

<div class="content body">
    <task-template
            :tasks="{{json_encode($all_tasks)}}"
            :planned-tasks="{{json_encode($planned_tasks)}}"
            :work-tasks="{{json_encode($work_tasks)}}"
            :complete-tasks="{{json_encode($complete_tasks)}}"
            :cancel-tasks="{{json_encode($cancel_tasks)}}"
            :bg="{{json_encode('lead')}}"
            :work-url="{{json_encode(route('set_status'))}}"
            :hour-types="{{json_encode(\App\System\WorkSlip::$types)}}"
            :hour-url="{{json_encode(route('add_hours'))}}"
            :stats-url="{{json_encode(route('show_stats'))}}"
            :roles="{{json_encode(\App\Support\TaskManager::$roles)}}"
            :system-modules="{{json_encode(\App\System\SystemModule::all())}}"

            :url="{{json_encode(route('save_task'))}}"></task-template>
</div>
<script type="text/x-template" id="task-template">
    <div class="nav-tabs-custom ">
        <ul role="tablist" class="nav nav-tabs">
            <li role="presentation" class="active"><a href="#planned" aria-controls="planned" role="tab"
                                                      data-toggle="tab">

                    Запланировано
                </a></li>
            <li role="presentation"><a href="#in_work" aria-controls="in_work" role="tab" data-toggle="tab">

                    В работе
                </a></li>
            <li role="presentation"><a href="#complete" aria-controls="complete" role="tab" data-toggle="tab">

                    Завершено
                </a></li>

            <li role="presentation"><a href="#cancel" aria-controls="cancel" role="tab" data-toggle="tab">

                    Отказ
                </a></li>
            <li role="presentation"><a href="#all" aria-controls="all" role="tab" data-toggle="tab">

                    Учет рабочего времени
                </a></li>
            <li role="presentation"><a href="#analyse" aria-controls="all" role="tab" data-toggle="tab">

                    Анализ
                </a></li>


        </ul>
        <div class="tab-content">
            <div role="tabpanel" id="all" class="tab-pane">
                <button class="btn btn-primary" @click="isHidden = !isHidden">Добавить часы</button>

                <form class="row" id="addHour" v-show="isHidden">
                    @csrf
                    <div class="col-md-12">
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="radio">
                                    <label><input type="radio" :value="0" v-model="picked"
                                                  name="task_type">Новая</label>
                                </div>
                                <div class="radio">
                                    <label><input type="radio" :value="1" v-model="picked" name="task_type">Существующая</label>
                                </div>
                            </div>
                            <div class="form-group" v-if="picked === 1">
                                <label class="control-label">
                                    Автор

                                    <span class="form-element-required">*</span>
                                </label>
                                <select class="form-control" name="user">
                                    <option value="0">Выберите пользователя</option>
                                    @foreach($users as $user)
                                        <option value="{{$user->id}}">{{$user->id_with_email}}</option>
                                    @endforeach

                                </select>
                            </div>
                            <div class="form-group" v-show="picked === 1">
                                <label class="control-label">
                                    Выберите задачу

                                    <span class="form-element-required">*</span>
                                </label>
                                <select class="form-control input-select column-filter" name="task_id">
                                    <option value="0">Выберите задачу</option>
                                    <option v-for="task in all_tasks" :value="task.id"
                                            v-html="task.title_task"></option>

                                </select>
                            </div>
                            <div class="form-group">
                                <label>Тип работы</label>
                                <select class="form-control" name="hour_type">
                                    <option v-for="(type, i) in hourTypes" v-html="type" :value="i"></option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Кол-во часов</label>
                                <input type="number" v-model="hours" name="hours"
                                       class="form-control"/>
                            </div>

                        </div>
                        <div class="col-md-6" v-if="picked === 0">
                            <div class="form-group">
                                <label>Добавить описание</label>


                                <textarea type="text" name="content"
                                          class="form-control"></textarea>
                            </div>

                            <div class="form-group">
                                <label>Приоритет</label>


                                <input type="number" min="1" name="priority"
                                       class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Для кого функционал</label>
                                <select class="form-control" name="role">
                                    <option v-for="(name, i) in roles" v-html="name" :value="i"></option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="control-label">
                                    Модуль системы

                                    <span class="form-element-required">*</span>
                                </label>
                                <select class="form-control" name="system_module" v-model="system_module"
                                        @change="onChangeModule">
                                    <option value="0">Выберите модуль</option>

                                    <option v-for="module in systemModules" :value="module.id"
                                            v-html="module.name"></option>

                                </select>

                            </div>
                            <div class="form-group">
                                <label class="control-label">
                                    Функционал модуля

                                    <span class="form-element-required">*</span>
                                </label>
                                <select class="form-control" name="system_function" v-model="system_function">
                                    <option value="0">Выберите Функционал</option>
                                    <option v-for="func in system_functions" :value="func.id"
                                            v-html="func.name"></option>

                                </select>
                            </div>
                            <div class="form-group">
                                <label>Статус</label>
                                <select class="form-control" name="status">
                                    @foreach(\App\Support\TaskManager::$types_lng as $key => $type)
                                        <option value="{{$key}}">{{$type}}</option>
                                    @endforeach

                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <button type="button" class="btn btn-success" @click="taskWithHour">Сохранить</button>
                    </div>
                </form>
                <div class="table-responsive">
                    <form id="filter_form">
                        @csrf
                        <table class="table table-bordered">

                            <thead>
                            <th class="text-center">ID</th>
                            <th class="text-center">Дата создания</th>
                            <th class="text-center">Автор</th>
                            <th class="text-center">Описание</th>
                            <th class="text-center">Статус</th>
                            <th class="text-center">Приоритет</th>
                            <th class="text-center">Модуль</th>
                            <th class="text-center">Функционал</th>
                            <th class="text-center"><i class="fa fa-cog"></i></th>
                            </thead>
                            <thead>
                            <tr>
                                <td></td>
                                <td data-index="1">
                                    <div data-type="range" class="column-filter">
                                        <div class="input-date form-group input-group" style="width: 150px;">
                                            <input v-datetimepicker name="from" data-date-format="DD.MM.YYYY"
                                                   data-date-useseconds="false" type="text"
                                                   placeholder="Начиная с" class="form-control column-filter">
                                            <span class="input-group-addon"><span class="fa fa-calendar">

                                            </span></span></div>
                                        <div style="margin-top: 5px;"></div>
                                        <div class="input-date form-group input-group" style="width: 150px;">
                                            <input v-datetimepicker name="to" data-date-format="DD.MM.YYYY"
                                                   data-date-useseconds="false" type="text"
                                                   placeholder="Заканчивая" class="form-control column-filter">
                                            <span class="input-group-addon"><span class="fa fa-calendar"></span></span>
                                        </div>
                                    </div>
                                </td>
                                <td data-index="1">
                                    <select class="form-control input-select column-filter" name="user_id">
                                        <option value="0">Все</option>
                                        @foreach($users as $user)
                                            <option value="{{$user->id}}">{{$user->id_with_email}}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td data-index="1">
                                    <input type="text" v-model="search_desc" data-type="text" placeholder="#"
                                           class="form-control column-filter">
                                </td>

                                <td data-index="1">
                                    <input type="text" v-model="search_stat" data-type="text" placeholder="#"
                                           class="form-control column-filter">
                                </td>
                                <td data-index="1">
                                    <input type="text" v-model="search_prior" data-type="text" placeholder="#"
                                           class="form-control column-filter">
                                </td>
                                <td data-index="1">
                                    <select class="form-control" name="system_module"
                                            @change="onChangeModule">
                                        <option value="0">Выберите модуль</option>

                                        <option v-for="module in systemModules" :value="module.id"
                                                v-html="module.name"></option>

                                    </select>
                                </td>
                                <td data-index="1">
                                    <select class="form-control" name="system_function" v-model="system_function">
                                        <option value="0">Выберите Функционал</option>
                                        <option v-for="func in system_functions" :value="func.id"
                                                v-html="func.name"></option>

                                    </select>
                                </td>


                                <td data-index="1">
                                    <button class="btn btn-info" type="button" @click="filterForm">Фильтр</button>
                                    <br>
                                    <button class="btn btn-danger" type="button" @click.prevnt="getTasks">Сбросить</button>
                                </td>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="task in filterTask">
                                <td v-html="task.id"></td>
                                <td v-html="task.created_at"></td>
                                <td v-html="task.user.email"></td>
                                <td v-html="task.task"></td>
                                <td v-html="task.status"></td>
                                <td v-html="task.priority"></td>
                                <td v-html="task.module_name"></td>
                                <td v-html="task.function_name"></td>
                                <td>
                                    <button class="btn btn-info" data-toggle="modal" type="button"
                                            data-target="#info-modal" @click.prevent="getInfo(task.id)"><i
                                                class="fa fa-info"></i></button>
                                    <button class="btn btn-info" data-toggle="modal" type="button"
                                            data-target="#edit-modal" @click.prevent="getInfo(task.id)"><i
                                                class="fa fa-pencil"></i></button>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </form>
                </div>


            </div>
            <div role="tabpanel" id="in_work" class="tab-pane">
                <div class="panel panel-default">
                    <div class="table-responsive">
                        <table class="table">
                            <colgroup>
                                <col width="30">
                                <col width="150">
                                <col width="150">
                                <col width="400">
                                <col width="">
                                <col width="">
                                <col width="">
                                <col width="300">
                            </colgroup>
                            <thead>
                            <th>ID</th>
                            <th>Дата создания</th>
                            <th>Автор</th>
                            <th>Описание</th>
                            <th>Статус</th>
                            <th>Приоритет</th>
                            <th>Затрачено часов</th>
                            <th class="text-center"><i class="fa fa-cog"></i></th>
                            </thead>
                            <tbody>
                            <tr v-for="task in work_tasks">
                                <td v-html="task.id"></td>
                                <td v-html="task.created_at"></td>
                                <td v-html="task.user.email"></td>
                                <td v-html="task.task"></td>
                                <td>
                                    <button
                                            data-toggle="modal"
                                            data-target="#history-modal"
                                            class="btn btn-warning btn-sm"
                                            type="button"
                                            @click="getInfo(task.id)"
                                            v-html="task.status"></button>
                                </td>
                                <td v-html="task.priority"></td>
                                <td v-html="task.sum_hours"></td>

                                <td>
                                    <div class="col-md-12">
                                        <button class="btn btn-success" @click="work(task.id, 'complete')"><i
                                                    class="fa fa-check"></i></button>
                                        &nbsp;
                                        <button data-toggle="modal" @click="setCurrent(task.id)"
                                                data-target="#small-modal2"
                                                class="btn btn-primary"><i class="fa fa-plus"></i></button>
                                        &nbsp;
                                        <button class="btn btn-info" data-toggle="modal"
                                                data-target="#info-modal" @click="getInfo(task.id)"><i
                                                    class="fa fa-info"></i></button>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div role="tabpanel" id="planned" class="tab-pane  in active">
                <div class="panel">
                    <div class="panel-body">

                        <button class="btn btn-primary" @click="isHidden = !isHidden">Добавить задачу</button>

                        <form class="row" v-if="isHidden">
                            <div class="col-md-12">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Добавить описание</label>


                                        <textarea type="text" name="content" v-model="newTask"
                                                  class="form-control"></textarea>
                                    </div>

                                    <div class="form-group">
                                        <label>Приоритет</label>


                                        <input type="number" min="1" v-model="priority"
                                               class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label>Для кого функционал</label>
                                        <select class="form-control" v-model="role">
                                            <option v-for="(name, i) in roles" v-html="name" :value="i"></option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label">
                                            Модуль системы

                                            <span class="form-element-required">*</span>
                                        </label>
                                        <select class="form-control" v-model="system_module" @change="onChangeModule">
                                            <option value="0">Выберите модуль</option>

                                            <option v-for="module in systemModules" :value="module.id"
                                                    v-html="module.name"></option>

                                        </select>

                                    </div>
                                    <div class="form-group">
                                        <label class="control-label">
                                            Функционал модуля

                                            <span class="form-element-required">*</span>
                                        </label>
                                        <select class="form-control" v-model="system_function">
                                            <option value="0">Выберите Функционал</option>
                                            <option v-for="func in system_functions" :value="func.id"
                                                    v-html="func.name"></option>

                                        </select>

                                    </div>
                                    <div class="col-md-12">
                                        <button type="button" class="btn btn-success" @click="save">Сохранить</button>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <hr>

                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <colgroup>
                            <col width="30">
                            <col width="150">
                            <col width="150">
                            <col width="400">
                            <col width="">
                            <col width="">
                            <col width="200">
                        </colgroup>
                        <thead>
                        <th>ID</th>
                        <th>Дата создания</th>
                        <th>Автор</th>
                        <th>Описание</th>
                        <th>Статус</th>
                        <th>Приоритет</th>
                        <th class="text-center"><i class="fa fa-cog"></i></th>
                        </thead>
                        <tbody>
                        <tr v-for="task in planned_tasks">
                            <td v-html="task.id"></td>
                            <td v-html="task.created_at"></td>
                            <td v-html="task.user.email"></td>
                            <td v-html="task.task"></td>
                            <td v-html="task.status">
                            </td>
                            <td v-html="task.priority"></td>
                            <td>
                                <button class="btn btn-primary" @click="work(task.id, 'in_work')">В работу!</button>&nbsp;
                                <button class="btn btn-danger" @click="work(task.id, 'cancel')">Отменить!</button>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div role="tabpanel" id="complete" class="tab-pane">
                <div class="table-responsive">
                    <table class="table">
                        <colgroup>
                            <col width="30">
                            <col width="150">
                            <col width="150">
                            <col width="400">
                            <col width="">
                            <col width="">
                            <col width="200">
                        </colgroup>
                        <thead>
                        <th>ID</th>
                        <th>Дата создания</th>
                        <th>Автор</th>
                        <th>Описание</th>
                        <th>Статус</th>
                        <th>Затрачено часов</th>
                        <th class="text-center"><i class="fa fa-cog"></i></th>
                        </thead>
                        <tbody>
                        <tr v-for="task in complete_tasks">
                            <td v-html="task.id"></td>
                            <td v-html="task.created_at"></td>
                            <td v-html="task.user.email"></td>
                            <td v-html="task.task"></td>
                            <td>
                                <button
                                        data-toggle="modal"
                                        data-target="#history-modal"
                                        class="btn btn-warning btn-sm"
                                        type="button"
                                        @click="getInfo(task.id)"
                                        v-html="task.status"></button>
                            </td>
                            <td v-html="task.sum_hours"></td>
                            <td>
                                <button class="btn btn-info" data-toggle="modal"
                                        data-target="#info-modal" @click="getInfo(task.id)"><i
                                            class="fa fa-info"></i></button>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div role="tabpanel" id="analyse" class="tab-pane">
                <div class="panel panel-default">
                    <div class="form-elements">
                        <div class="row">

                            <div class="col-md-12">
                                <div class="col-xs-12">
                                    <b>Статистика за период</b>
                                </div>


                                <div class="col-xs-10">
                                    <div class="col-xs-8">
                                        <div class="form-inline">
                                            <div class="form-group col-xs-6" style="display: inline-block;">
                                                <label>c</label>
                                                <input style="width: 7em; display: inline-block;"
                                                       v-datetimepicker
                                                       class="form-control"
                                                       v-model="from"/>
                                            </div>
                                            <div class="form-group col-xs-6" style="display: inline-block;">
                                                <label>по</label>
                                                <input style="width: 7em; display: inline-block;"
                                                       v-datetimepicker
                                                       class="form-control"
                                                       v-model="to"/>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xs-12" style="padding: 10px">
                                    <button class="btn btn-info" @click="showStats">Показать</button>
                                </div>
                                <div class="col-xs-12" style="padding: 10px">
                                    <button class="btn btn-danger" @click="getTasks">Сбросить</button>
                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="row">
                        <div id="stats" class="col-md-12" v-if="HiddenStats" style="padding: 30px">
                            <div v-for="task in stats">
                                <b>Задача</b>
                                <p v-html="task.preview_task" @click="showMore(task.id)"></p>
                                <p v-html="task.task" v-if="show_analyse.includes(task.id)"></p>
                                <p>Затрачено часов: <span v-html="task.sumHours"></span></p>
                                <hr>
                            </div>
                            <b>Всего часов: <span v-html="total_hours"></span> </b>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <colgroup>
                            <col width="30">
                            <col width="150">
                            <col width="150">
                            <col width="400">
                            <col width="">
                            <col width="">
                            <col width="200">
                        </colgroup>
                        <thead>
                        <th>ID</th>
                        <th>Дата создания</th>
                        <th>Автор</th>
                        <th>Описание</th>
                        <th>Статус</th>
                        <th>Приоритет</th>
                        </thead>
                        <tbody>
                        <tr v-for="task in stats">
                            <td v-html="task.id"></td>
                            <td v-html="task.created_at"></td>
                            <td v-html="task.user.email"></td>
                            <td v-html="task.task"></td>
                            <td v-html="task.status">
                            </td>
                            <td v-html="task.priority"></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div role="tabpanel" id="cancel" class="tab-pane">
                <div class="table-responsive">
                    <table class="table">
                        <colgroup>
                            <col width="30">
                            <col width="150">
                            <col width="150">
                            <col width="400">
                            <col width="">
                            <col width="">
                            <col width="200">
                        </colgroup>
                        <thead>
                        <th>ID</th>
                        <th>Дата создания</th>
                        <th>Автор</th>
                        <th>Описание</th>
                        <th>Статус</th>
                        <th>Приоритет</th>
                        <th class="text-center"><i class="fa fa-cog"></i></th>
                        </thead>
                        <tbody>
                        <tr v-for="task in cancel_tasks">
                            <td v-html="task.id"></td>
                            <td v-html="task.created_at"></td>
                            <td v-html="task.user.email"></td>
                            <td v-html="task.task"></td>
                            <td>
                                <button
                                        data-toggle="modal"
                                        data-target="#history-modal"
                                        class="btn btn-warning btn-sm"
                                        type="button"
                                        @click="getInfo(task.id)"
                                        v-html="task.status"></button>
                            </td>
                            <td v-html="task.priority"></td>
                            <td>
                                <button class="btn btn-info"><i class="fa fa-info"></i></button>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="modal fade" id="small-modal2" tabindex="-1" role="dialog"
             aria-labelledby="myLargeModalLabel"
             aria-hidden="true">
            <div class="modal-dialog modal-sm modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Добавить часы</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Тип работы</label>
                            <select class="form-control" v-model="hour_type">
                                <option v-for="(type, i) in hourTypes" v-html="type" :value="i"></option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Кол-во часов</label>
                            <input type="number" v-model="hours" name="hours"
                                   class="form-control"/>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Закрыть</button>
                        <button type="button" @click="addHours" data-dismiss="modal" class="btn btn-primary">Добавить
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="history-modal" tabindex="-1" role="dialog"
             aria-labelledby="myLargeModalLabel"
             aria-hidden="true">
            <div class="modal-dialog modal-sm modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">История статусов</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                    </div>
                    <div class="modal-body">
                        <p>Автор: <span v-html="current_task.user.email"></span></p>
                        <p>Приоритет: <span v-html="current_task.priority"></span></p>
                        <p>Для кого функционал: <span v-html="current_task.role_name"></span></p>
                        <b>История статусов:</b>
                        <div v-for="status in current_task.status_history">
                            <p v-html="status.status"></p>
                            <p> Дата: <span v-html="status.created_at"></span></p>
                            <hr>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Закрыть</button>
                        <button type="button" @click="addHours" data-dismiss="modal" class="btn btn-primary">Добавить
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="info-modal" tabindex="-1" role="dialog"
             aria-labelledby="myLargeModalLabel1"
             aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Информация о задаче</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                    </div>
                    <div class="modal-body">
                        <p>Автор: <span v-html="current_task.user.email"></span></p>
                        <p>Приоритет: <span v-html="current_task.priority"></span></p>
                        <p>Для кого функционал: <span v-html="current_task.role_name"></span></p>
                        <b>Затраченое время:</b>
                        <div v-for="hour in current_task.work_slips">
                            <p> Тип времени: <span v-html="hour.type"></span></p>
                            <p> Кол-во часов: <span v-html="hour.hours"></span></p>
                            <p> Добавил: <span v-html="hour.user.email"></span></p>
                            <p> Дата: <span v-html="hour.created_at"></span></p>
                            <hr>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Закрыть</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="edit-modal" tabindex="-1" role="dialog"
             aria-labelledby="myLargeModalLabel1"
             aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Редактировать задачу</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                    </div>
                    <div class="modal-body">
                        <form id="edit_task" class="row">
                            @csrf
                            <input type="hidden" name="id" v-model="current_task.id">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Описание</label>


                                    <textarea type="text" name="text" v-model="current_task.task"
                                              class="form-control"></textarea>
                                </div>

                                <div class="form-group">
                                    <label>Приоритет</label>


                                    <input type="number" min="1" name="priority" v-model="current_task.priority"
                                           class="form-control">
                                </div>
                                <div class="form-group">
                                    <label>Для кого функционал</label>
                                    <select class="form-control" name="role" v-model="current_task.role">
                                        <option v-for="(name, i) in roles" v-html="name" :value="i"></option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="control-label">
                                        Модуль системы

                                        <span class="form-element-required">*</span>
                                    </label>
                                    <select class="form-control" name="system_module" v-model="current_task.system_module_id"
                                            @change="onChangeModule">
                                        <option value="0">Выберите модуль</option>

                                        <option v-for="module in systemModules" :value="module.id"
                                                v-html="module.name"></option>

                                    </select>

                                </div>
                                <div class="form-group">
                                    <label class="control-label">
                                        Функционал модуля

                                        <span class="form-element-required">*</span>
                                    </label>
                                    <select class="form-control" name="system_function" v-model="current_task.system_function_id">
                                        <option value="0">Выберите Функционал</option>
                                        <option v-for="func in system_functions" :value="func.id"
                                                v-html="func.name"></option>

                                    </select>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" @click="editTask">Сохранить</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Закрыть</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</script>