define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'learn_material/index' + location.search,
                    add_url: 'learn_material/add',
                    edit_url: 'learn_material/edit',
                    del_url: 'learn_material/del',
                    multi_url: 'learn_material/multi',
                    table: 'learn_material',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'type', title: __('Type'), searchList: {"1":__('Type 1'),"2":__('Type 2'),"3":__('Type 3')}, formatter: Table.api.formatter.normal},
                        {field: 'materialcategory.name', title: __('Material_category_id')},
                        {field: 'file', title: __('File')},
                        {field: 'title', title: __('Title')},
                        {field: 'description', title: __('Description')},
                        {field: 'status', title: __('Status'), searchList: {"1":__('Status 1'),"2":__('Status 2')}, formatter: Table.api.formatter.status},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'update_time', title: __('Update_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            $("#c-material_category_id").data("params", function (obj) {
                return {custom: {type: $("#c-type").val()}};
            });
            $('#c-type').change(function (obj) {
                $("#c-material_category_id").selectPageClear();
                Controller.materialShow();
            })
            Controller.materialShow();
            Controller.api.bindevent();
        },
        edit: function () {
            $("#c-material_category_id").data("params", function (obj) {
                return {custom: {type: $("#c-type").val()}};
            });
            Controller.materialShow();
            Controller.api.bindevent();
        },
        materialShow:function(){
            if($('#c-type').val()==3){
                $('.material-file').hide();
                $('.material-content').show();
            }else{
                $('.material-file').show();
                $('.material-content').hide();
            }
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});