<template>
    <div class="col-md-12">
        <div class="alert alert-info" role="alert" v-if="in_call">
            Внимание. Идет звонок.
        </div>
        <p>
            <button class="btn btn-block btn-outline-dark" type="button" data-toggle="collapse"
                    data-target="#new-proposal"
                    aria-expanded="false" aria-controls="new-proposal">
                Новая заявка
            </button>
        </p>
        <div class="collapse" id="new-proposal">
            <div class="row">
                <form id="proposal_form" class="col-md-12">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Текущий номер</label>
                                <input class="form-control" type="number" id="phone" :value="current_number"
                                       name="phone_number">
                            </div>
                            <div class="form-group">
                                <label>Выберите регион</label>
                                <helper-select-input :data="regions"
                                                     column-name="Выберите регион"
                                                     :place-holder="'Выберите регион'"
                                                     :col-name="'region'"
                                                     :hide-city="1"
                                                     :initial="''"></helper-select-input>
                            </div>
                            <div class="form-group">
                                <label>Выберите город</label>
                                <helper-select-input :data="regions"
                                                     column-name="Выберите город"
                                                     :place-holder="'Выберите город'"
                                                     :col-name="'city_id'"
                                                     :hide-city="1"
                                                     :initial="''"></helper-select-input>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Адрес проведения работ</label>
                                <input class="form-control" type="text" name="address" id="address">
                            </div>
                            <div class="form-group">
                                <label>Бюджет заказа (руб)</label>
                                <input class="form-control" type="number" name="sum" id="sum" min="0" value="1">
                            </div>
                            <div class="form-group">
                                <label>Комментарий</label>
                                <textarea class="form-control" type="text" name="comment"></textarea>
                            </div>
                            <div class="col-md-12 row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Кол-во смен</label>
                                        <input class="form-control" type="number" name="days" min="1" value="1">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group input-date">
                                        <label>Дата начала</label>
                                        <input class="form-control datetimepicker_" type="text" name="date">

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <machine-search
                            :data="categories_vehicle"
                            :equipments-data="categories_equipment"
                            :brand-data="brands"
                            :column-name="$t('transbaza_proposal_search.machine_category_label')"
                            :place-holder="$t('transbaza_proposal_search.machine_category_choose')"
                            col-name="type"
                            :initial="''"
                            :show-column-name="1"
                    ></machine-search>
                    <div class="m-2">
                        <button class="btn btn-block btn-outline-success" @click="pushProposal" type="button"
                                :disabled="disabled">
                            Сформировать заявку
                        </button>

                        <button class="btn btn-block btn-warning" @click="saveOffline" type="button">
                            Сохранить оффлайн <br> (при отсутствии интернета.)
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <ul class="nav nav-pills m-3">
                    <li class="nav-item">
                        <a class="nav-link active" data-toggle="pill" href="#calls_panel">Звонки</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="pill" href="#proposals">Заявки</a>
                    </li>
                    <li class="nav-item" v-if="offlineStorage.length">
                        <a class="nav-link" data-toggle="pill" href="#offlineStorage">Офлайн заявки</a>
                    </li>
                </ul>

                <div class="tab-content" id="pills-tabContent">
                    <div class="tab-pane fade show active" id="calls_panel" role="tabpanel"
                         aria-labelledby="pills-home-tab">
                         <div class="table-responsive">
                        <table class="table">
                            <thead>
                            <th>Телефон</th>
                            <th>Пользователь</th>
                            <th>Статус звонка</th>
                            <th>Статус разговора</th>
                            <th>Тип</th>
                            <th>Дата</th>
                            </thead>
                            <tbody>
                            <tr v-for="call in paginate">

                                <td>{{call.phone}}</td>
                                <td>
                                    <a v-if="call.user_url" :href="call.user_url" target="_blank">Существующий пользователь</a>
                                <p v-else >Незарегистрированный </p>
                                </td>
                                <td v-html="call.html_status"></td>
                                <td v-html="call.html_global_status"></td>
                                <td>{{call.type === 'incoming' ? 'Входящий' : 'Исходящий'}}</td>
                                <td>{{call.created_at}}</td>
                            </tr>
                            </tbody>
                        </table>
                         </div>
                        <div class="blog-pagination" v-if="calls.length > itemsPerPage">
                            <div class="btn-toolbar justify-content-center mb-15">
                                <div class="btn-group">
                                    <template v-for="(pageNumber, i ) in totalPages">
                                        <a href="#" class="btn btn-outline-primary"
                                           @click.prevent="setPage(pageNumber)"
                                           v-if="currentPage !==  pageNumber">{{ i + 1 }}</a>
                                        <span v-else class="btn btn-primary current">{{ i + 1 }}</span>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="proposals" role="tabpanel" aria-labelledby="pills-profile-tab">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                <th>Пользователь</th>
                                <th>Заявка</th>
                                <th>Дата</th>
                                </thead>
                                <tbody>
                                <tr v-for="proposal in created_proposals">
                                    <td>
                                        <a :href="proposal.user_url">Пользователь #{{proposal.user_id}}</a>
                                    </td>
                                    <td>
                                        <a :href="proposal.proposal_url">Заявка #{{proposal.proposal_id}}</a>
                                    </td>

                                    <td>{{proposal.created_at}}</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="offlineStorage" role="tabpanel"
                         aria-labelledby="pills-home-tab">
                        <div class="table-responsive">
                           <table class="table">
                               <thead>
                                   <tr>
                                       <th>Номер телефона</th>
                                       <th><i class="fa fa-cog"></i> </th>
                                   </tr>
                               </thead>
                               <tbody>
                               <tr v-for="item, i in offlineStorage">
                                   <td>{{item.phone_number}}</td>
                                   <td>
                                       <button class="btn btn-info" @click="restoreOffline(item)">Дозаполнить</button>
                                       <button class="btn btn-danger" @click="deleteOffline(i)"><i class="fa fa-trash"></i> </button>
                                   </td>
                               </tr>
                               </tbody>
                           </table>
                        </div>
                    </div>
                </div>

            </div>

        </div>
        <div class="absolute-width" v-show="show_phone" @click="show_phone = false"></div>
        <div id="phone_block" v-show="show_phone"></div>
        <div id="callme"  v-show="!show_phone"  @click="show_phone = true">
            <div id="callmeMain"></div>
        </div>
        <div id="bottom_line" v-show="phoneStatus === 'call_started'">
            <div class="text-center">
                <span>Текущий звонок &nbsp;</span><span v-html="current_call.phone"></span><button class="btn btn-sm btn-danger" @click="hangUp()">Завершить</button>

            </div>
        </div>
    </div>
</template>

<script>

    import Echo from 'laravel-echo'

    export default {
        name: "Telephony",
        data() {
            return {
                all_calls: [],
                brands: [],
                regions: [],
                created_proposals: [],
                categories_vehicle: [],
                categories_equipment: [],
                current_call: [],
                in_call: false,
                current_number: '',
                currentPage: 1,
                resultCount: 0,
                disabled: false,
                itemsPerPage: 10,
                show_phone: false,
                phoneStatus: '',
                reloadStorage: true,
                get offlineStorage() {

                    return JSON.parse(localStorage.getItem('proposals') || '[]');
                },
                set offlineStorage(value) {
                    localStorage.setItem('proposals', JSON.stringify(value))
                },
            }
        },
        props: ['callsUrl', 'depUrl', 'allBrands', 'allRegions', 'allCategories', 'proposalUrl', 'createdProposals'],
        created() {
            this.brands = JSON.parse(this.allBrands)
            this.regions = JSON.parse(this.allRegions)
            let cats = JSON.parse(this.allCategories)
            this.created_proposals = JSON.parse(this.createdProposals)
            this.categories_vehicle = cats.filter(function (el) {
                return el.type === 'machine'
            });
            this.categories_equipment = cats.filter(function (el) {
                return el.type === 'equipment'
            });
        },
        computed: {
            calls() {
                let app = this;
                let item;
                app.in_call = false;
                for (item in app.all_calls) {

                    if (app.all_calls[item]['global_status'] === 'start') {
                        app.setCurrentCall(app.all_calls[item])
                    }
                }
                return app.all_calls
            },
            callChannel() {

                return this.echo.join('calls-listen')
            },
            onPage: function () {
                return this.itemsPerPage
            },
            totalPages: function () {
                if (!this.calls ||
                    Number.isNaN(parseInt(this.resultCount)) ||
                    Number.isNaN(parseInt(this.onPage)) ||
                    this.onPage <= 0
                ) {
                    return 0;
                }

                const result = Math.ceil(this.resultCount / this.onPage)
                return (result < 1) ? 1 : result
            },

            paginate: function () {

                this.resultCount = this.calls.length
                if (this.currentPage >= this.totalPages) {
                    this.currentPage = this.totalPages
                }
                var index = this.currentPage * this.onPage - this.onPage
                return this.calls.slice(index, index + this.onPage)
            },
        },
        mounted() {

            let app = this;

            app.getCalls();

            app.echo = new Echo({
                broadcaster: 'socket.io',
                host: window.location.hostname + ':6001',
                encrypted: true
            });

            this.callChannel.listen('.CallsListen', ({data}) => {

                console.log(data);
                app.pushCall(data)
            });
            const children = [...document.getElementsByTagName('input')];
            children.forEach((child) => {
                if(child.type == 'radio'){
                    return;
                }
                child.classList.add('form-control');
            });
            var mcConfig = {login: "86b8fa62-f0e4-4607-9da8-9a30661c138b", password: "14b6637fa216"};

            MightyCallWebPhone.ApplyConfig(mcConfig);
            MightyCallWebPhone.Phone.Init("phone_block"); //id контейнера для встраивания WebPhone.
            MightyCallWebPhone.Phone.OnStatusChange.subscribe(app.watchStatus);


        },
        methods: {
            saveOffline () {
                let form_data = new FormData(document.getElementById('proposal_form'));
                let phone = this.current_number;
                let proposals =   this.offlineStorage;

                var object = {};
                form_data.forEach(function(value, key){
                    object[key] = value;
                });

                proposals.push(object);

                this.offlineStorage = proposals;
                swal.fire('Заявка сохранена в браузере.')
                this.reloadStorage = !! this.reloadStorage;
            },
            restoreOffline(data)
            {
                let key, app = this;
                app.current_number = data.phone_number
                Events.$emit('select-region', data.region);
                Events.$emit('select-city', data.city_id);
                Events.$emit('push-stock', JSON.parse(data.big_container));

                for (key in data) {

                   let element  =  document.getElementsByName(key);
                   if(element) {
                       element[0].value  = data[key]
                   }

                }
                $('#new-proposal').collapse('show')

            },
            deleteOffline(i)
            {
                let proposals =   this.offlineStorage;

                if(proposals.length){
                    proposals.splice(i, 1)
                }
                this.offlineStorage = (proposals)

            },
            hangUp() {
                MightyCallWebPhone.Phone.HangUp();
            },
            watchStatus(status) {
                console.log(status);
                this.phoneStatus = status;
            },
            setPage: function (pageNumber) {
                this.currentPage = pageNumber
            },
            getCalls() {
                let data = axios.get(this.callsUrl)
                    .then(({data}) => {
                        this.all_calls = data;

                    }).catch((error) => {

                    });
            },
            pushProposal() {
                clearBootstrapErrors();
                let app = this;
                app.disabled = true;
                let form_data = new FormData(document.getElementById('proposal_form'));
                axios.post(app.proposalUrl, form_data).then(function (resp) {
                    app.disabled = false;
                    let address = document.getElementById('address');
                    let sum = document.getElementById('sum');

                    address.value = '';
                    sum.value = '';
                    swal.fire('Заявка успешно создана.')
                }).catch(error => {

                    app.disabled = false;
                    showBootstrapErrors(error.response.data);

                });

            },
            pushCall(call) {
                let app = this;
                let item;

                for (item in app.all_calls) {

                    let current = app.all_calls[item]

                    if (call.id === current.id) {

                        app.$set(app.all_calls, item, call)

                        return;
                    }
                }
                app.setCurrentCall(call)
                app.all_calls.unshift(call)
            },
            setCurrentCall(call) {
                let app = this;
                $('#new-proposal').collapse('show')
                app.current_call = call
                app.in_call = true

                if (!app.current_number.length) {
                    app.current_number = call.phone
                }
            }
        }


    }
</script>

<style scoped>

</style>