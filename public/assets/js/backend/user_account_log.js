define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user_account_log/index' + location.search,
                    add_url: 'user_account_log/add',
                    edit_url: 'user_account_log/edit',
                    del_url: 'user_account_log/del',
                    multi_url: 'user_account_log/multi',
                    table: 'user_account_log',
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
                        {field: 'type', title: __('Type'), searchList: {"1":__('Type 1'),"2":__('Type 2'),"3":__('Type 3'),"4":__('Type 4')}, formatter: Table.api.formatter.normal},
                        {field: 'trade_no', title: __('Trade_no')},
                        {field: 'amount', title: __('Amount')},
                        {field: 'before_balance', title: __('Before_balance')},
                        {field: 'status_text', title: __('Status')},
                        {field: 'remark', title: __('Remark')},
                        {field: 'trade_time', title: __('Trade_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
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