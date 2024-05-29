<template>
    <div class="">
        <div class="col-md-12">
            <h4 class="text-center">Добавление техники</h4>
            <label>Выберите тип единицы техники</label>
            <div class="">

                <div class="form-check form-check-inline">

                    <input type="radio" name="machine_type" class="form-check-input" value="machine"
                           v-model="current_type"
                           id="radio-input-cont">
                    <label for="radio-input-cont" class="form-check-label">
                        {{$t('transbaza_proposal_search.vehicle')}}
                    </label>
                </div>
                <div class="form-check form-check-inline">

                    <input type="radio" class="form-check-input" name="machine_type" value="equipment"
                           v-model="current_type"
                           id="radio-input-cont2">
                    <label for="radio-input-cont2" class="form-check-label">
                        {{$t('transbaza_proposal_search.equipment')}}
                    </label>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <!--:class="{'required': required == 1}"-->
                <label for="type-tech" :class="{'required': required == 1 && showColumnName}">
                    {{showColumnName ? columnName : ''}}
                    <autocomplete
                            ref="autocomplete"
                            :source="currentSelected"
                            :placeholder="placeHolder"
                            @input="getDataSelect"
                            @clear="clearDataSelect"
                            :resultsDisplay="formattedDisplay"
                            :initialDisplay="initialVal ? initialVal.name : ''"
                            :initialValue="initialVal ? initialVal.name : ''"
                            clearButtonIcon="clear">
                    </autocomplete>
                    <input type="hidden" :name="colName" v-model="dataSelect">
                    <input type="hidden" :id="colName + '_hidden'" :value="nameData">
                </label>
            </div>
        </div>
        <div class="col-md-6">
            <helper-select-input :data="dataSelectBrandArr"
                                 :column-name="$t('transbaza_proposal_search.vehicle_brand')"
                                 :place-holder="$t('transbaza_proposal_search.vehicle_brand')"
                                 col-name="brand_name"
                                 :initial="''"
                                 :show-column-name="1"></helper-select-input>
        </div>

        <div class="col-md-6">
            <div class="form-item">
                <label class="required">{{$t('transbaza_proposal_search.amount')}}</label>
                <input class="promo_code"
                       v-model="count"

                       type="number" step="1" min="1">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-item">
                <label class="required">{{$t('transbaza_proposal_search.comment')}}</label>
                <textarea class="form-control" style="height: auto;" v-model="comment"></textarea>
            </div>

        </div>
        <div class="col-md-2">

            <button class="btn btn-primary" type="button" @click="pushStock" style="margin-top: 26px;"><i
                    class="fa fa-plus"></i>&nbsp;{{$t('transbaza_proposal_search.add')}}
            </button>
        </div>
        <input type="hidden" name="big_search" value="1">
        <input type="hidden" name="big_container" v-model="currentContainer">
        <div class="col-md-12" v-if="stock.length > 0">
            <h3 class="text-center">{{$t('transbaza_proposal_search.choose_positions')}}</h3>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                    <tr>
                        <th class="text-center"><i class="fa fa-cog"></i></th>
                        <th>{{$t('transbaza_proposal_search.table_name')}}</th>
                        <th>{{$t('transbaza_proposal_search.table_brand')}}</th>
                        <th>{{$t('transbaza_proposal_search.table_amount')}}</th>
                        <th>{{$t('transbaza_proposal_search.table_comment')}}</th>

                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="(machine, i) in stock">
                        <td>

                                <button class="btn btn-outline-dark" type="button"
                                        @click="delItem(i)"><i
                                        class="fa fa-minus"></i>
                                </button>

                        </td>
                        <td>{{machine.name}}</td>
                        <td>{{machine.brand.name ? machine.brand.name :
                            $t('transbaza_proposal_search.table_no_brand')}}
                        </td>
                        <td>{{machine.count}}</td>
                        <td>{{machine.comment}}</td>

                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script>
    import Autocomplete from 'vuejs-auto-complete'

  //  import {Events} from "../../../../../resources/assets/js/app";

    export default {
        data() {
            return {
                search_type: 'big',
                comment: '',
                count: 1,
                dataSelectArr: this.data,
                equipments: this.equipmentsData,
                dataSelectBrandArr: this.brandData,
                dataSelect: '',
                initialVal: this.initial,
                nameData: this.initial && this.initial.name ? this.initial.name : '',
                dataCities: null,
                selectCity: '',
                url_city: 'get-cities',
                url_region: 'get-region',
                formattedDisplay: 'name',
                current_region: 0,
                current_brand: 0,
                current_type: 'machine',
                current_city: 0,
                stock: []
            }
        },
//        dataCities
        props: [
            'data',
            'equipmentsData',
            'brandData',
            'columnName',
            'placeHolder',
            'colName',
            'initial',
            'required',
            'initialCity',
            'cityData',
            'hideCity',
            'showColumnName',
        ],
        components: {
            Autocomplete,


        },
        watch: {
            $refs: function (val) {
                console.log(val)
            }
        },
        computed: {
            currentContainer: function () {
                return JSON.stringify(this.stock)
            },
            counter() {
                let counter = parseInt(this.count) * 1;
                if (counter < 1 || isNaN(counter)) {
                    counter = 1;
                }
                return counter;
            },
            currentSelected: function () {
                this.dataSelect = '';

                if (this.$refs.autocomplete) {
                    this.$refs.autocomplete.display = ''
                }

                if (this.current_type === 'machine') return this.dataSelectArr;
                return this.equipments
            }
        },
        created() {

            Events.$on('initial-search', (data) => {
                this.checkSearch(data)
            })
            Events.$on('change-brand', (data) => {
                this.current_brand = data;
            })
            Events.$on('change-region', (data) => {
                this.current_region = data;
            })
            Events.$on('change-city', (data) => {
                this.current_city = data;
            })
            Events.$on('change-type', (data) => {
                this.current_type = data;

            })
            Events.$on('push-stock', (data) => {
                this.stock = data;

            })

            Events.$on('clear-brand', () => {
                this.current_brand = 0;

            })
            Events.$on('clear-city', (data) => {
                if ((this.colName == 'city_id' || this.colName == 'city') && this.hideCity) {

                    if (this.$refs.autocomplete) {
                        this.initialVal = '';
                        this.$refs.autocomplete.display = '';
                        this.$refs.autocomplete.value = '';
                        this.dataSelectArr = data;
                        this.dataSelect = '';
                    }
                }
            })

        },
        mounted() {
            let app = this;

        },
        methods: {
            pushStock() {
                if (this.dataSelect) {
                    console.log(this.dataSelect, this.$refs.autocomplete);
                    this.stock.push({
                        name: this.$refs.autocomplete.display,
                        id: this.dataSelect,
                        brand: this.getBrand(this.current_brand),
                        comment: this.comment,
                        count: this.counter,
                    })
                    this.dataSelect = '';
                    this.comment = '';
                    this.count = 1;
                    this.$refs.autocomplete.display = ''
                } else {
                    swal.fire('Выберите технику!')
                }

            },
            delItem(i) {
                this.stock.splice(i, 1)
            },
            getBrand(id) {
                let brands = this.dataSelectBrandArr;
                let br;
                for (br in this.dataSelectBrandArr) {
                    if (brands[br]['id'] === id) {
                        return brands[br];
                    }
                }
                return []
            },
            getDataSelect(val) {
                this.dataSelect = val;
                this.nameData = this.$refs.autocomplete.display


            },
            getDataSelectCity(val) {
                console.log(this.$refs.autocompleteCity)
                this.selectCity = val
            },
            clearDataSelect() {
                this.dataSelect = '';

            },
            clearDataSelectCity() {
                this.selectCity = '';
            },
            checkSearch(data) {
                // let fields = JSON.parse(data.fields);
                let fields = data.fields;
                switch (this.colName) {
                    case 'region':
                    case 'region_id':
                        this.$refs.autocomplete.display = '';
                        this.$refs.autocomplete.value = data.region;
                        this.$refs.autocomplete.display = data.region_name;
                        this.dataSelect = fields.region
                        break;
                    case 'type':
                    case 'type_id':
                        this.$refs.autocomplete.display = '';
                        this.$refs.autocomplete.value = fields.type;
                        this.$refs.autocomplete.display = data.type_name;
                        this.dataSelect = fields.type
                        break;
                    case 'brand':
                        this.$refs.autocomplete.display = '';
                        this.$refs.autocomplete.value = fields.brand;
                        this.$refs.autocomplete.display = data.brand_name;
                        this.dataSelect = fields.brand
                        break;
                    case 'city_id':
                    case 'city':
                        this.$refs.autocomplete.display = '';
                        this.$refs.autocomplete.value = fields.city_id;
                        this.$refs.autocomplete.display = data.city_name;
                        this.dataSelect = fields.city_id
                        break;
                }
            }
        }
    }
</script>