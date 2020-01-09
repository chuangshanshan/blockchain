define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'punish/index' + location.search,
                    add_url: 'punish/add',
                    edit_url: 'punish/edit',
                    del_url: 'punish/del',
                    multi_url: 'punish/multi',
                    table: 'punish',
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
                        {field: 'user.mobile', title: __('用户手机')},
                        {field: 'title', title: __('Title')},
                        {field: 'violate', title: __('Violate')},
                        {field: 'amount', title: __('Amount')},
                        {field: 'status', title: __('Status'), searchList: {"1":__('Status 1'),"2":__('Status 2'),"3":__('Status 3')}, formatter: Table.api.formatter.status},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate,
                            formatter:function(value,row,index){
                                var that = $.extend({},this);
                                var table = $(that.table).clone(true);
                                if(row.status != 1){
                                    $(table).data("operate-edit",null);
                                    $(table).data("operate-del",null);
                                }
                                that.table=table;
                                return Table.api.formatter.operate.call(that,value,row,index);
                            }
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});