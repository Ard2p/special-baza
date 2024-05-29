<div class="form-group">
    <task-template name-btn="{{'Добавить сайт'}}"
                   col-name="{{'sites'}}"
                   field-name="{{'URL'}}"
                   fields="{{$lead->sites ?? ''}}"
    ></task-template>
</div>
<div class="form-group">
    <task-template name-btn="{{'Добавить ссылку соц.сети'}}"
                   col-name="{{'socials'}}"
                   field-name="{{'URL'}}"
                   fields="{{$lead->socials ?? ''}}"
    ></task-template>
</div>
@if($lead)
    <div class="form-group" id="tb_user">
        @include('admin.leads.user_in_tb')
    </div>
@endif
<script type="text/x-template" id="fields-template">
    <div class="params-helper">
        <div class="button" style="width: 200px;">
            <a href="#" class="btn btn-primary" @click.prevent="addParam" v-html="nameBtn"></a>
        </div>

        <div class="form" v-for="(param, i) in paramsList">
            <div class="col-xs-6">
                <div class="form-group">
                    <label for="" v-html="fieldName">

                    </label>
                    <input type="text" class="form-control" v-model="param.name">
                </div>
            </div>
            <div class="col-xs-6">
                <div class="form-group ">
                    <button href="#" class="btn btn-danger" style="margin-top: 20px" @click.prevent="delItem(i)"><i
                                class="fa fa-trash"></i>
                    </button>
                </div>
            </div>
            <hr>
            <div class="clearfix"></div>
        </div>
        <input type="hidden" :name="colName" v-model="result">
    </div>
</script>
