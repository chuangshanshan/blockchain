define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'extra_recharge/index' + location.search,
                    add_url: 'extra_recharge/add',
                    edit_url: 'extra_recharge/edit',
                    del_url: 'extra_recharge/del',
                    multi_url: 'extra_recharge/multi',
                    table: 'extra_recharge',
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
                        {field: 'user.nickname', title: __('User.nickname')},
                        {field: 'user.mobile', title: __('User.mobile')},
                        {field: 'hash', title: __('Hash')},
                        {field: 'from', title: __('From')},
                        {field: 'to', title: __('To')},
                        {field: 'value', title: __('Value')},
                        {field: 'is_merger', title: __('Is_merger'),searchList: {"0":__('未归并'),"1":__('已归并')}, formatter: Table.api.formatter.status},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
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